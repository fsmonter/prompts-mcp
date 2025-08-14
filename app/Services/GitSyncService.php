<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prompt;
use App\Models\PromptSource;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Finder\Finder;

class GitSyncService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly CommonMarkConverter $markdown
    ) {}

    /**
     * Sync all active prompt sources
     */
    public function syncAllSources(): array
    {
        $results = [];
        $sources = PromptSource::active()->get();

        foreach ($sources as $source) {
            $results[$source->name] = $this->syncSource($source);
        }

        return $results;
    }

    /**
     * Sync a specific prompt source
     */
    public function syncSource(PromptSource $source): array
    {
        $source->markSyncStarted();

        try {
            $stats = match ($source->type) {
                'fabric' => $this->syncFabricSource($source),
                'git' => $this->syncGitSource($source),
                default => throw new \InvalidArgumentException("Unsupported source type: {$source->type}")
            };

            $source->markSyncCompleted();

            return $stats;

        } catch (\Exception $e) {
            $source->markSyncFailed($e->getMessage());
            Log::error("Failed to sync source: {$source->name}", [
                'error' => $e->getMessage(),
                'source' => $source->toArray(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync Fabric patterns using GitHub API
     */
    private function syncFabricSource(PromptSource $source): array
    {
        $stats = ['synced' => 0, 'updated' => 0, 'failed' => 0];

        $response = $this->http->get('https://api.github.com/repos/danielmiessler/fabric/git/trees/main', [
            'recursive' => 'true',
        ])->throw();

        $tree = $response->json();

        $patternFiles = collect($tree['tree'])
            ->filter(function ($item) use ($source) {
                return $item['type'] === 'blob'
                    && str_starts_with($item['path'], 'data/patterns/')
                    && str_ends_with($item['path'], $source->file_pattern);
            })
            ->values();

        Log::info("Found {$patternFiles->count()} {$source->name} pattern files to process");

        $batchSize = 10;
        foreach ($patternFiles->chunk($batchSize) as $batch) {
            $patterns = $this->processFabricBatch($batch, $source);

            foreach ($patterns as $patternData) {
                try {
                    $this->createOrUpdatePrompt($patternData, $source->name, 'fabric');
                    $stats['synced']++;
                } catch (\Exception $e) {
                    Log::warning("Failed to sync pattern: {$patternData['name']}", [
                        'source' => $source->name,
                        'error' => $e->getMessage(),
                    ]);
                    $stats['failed']++;
                }
            }

            if ($patternFiles->count() > $batchSize) {
                usleep(100000); // 100ms delay
            }
        }

        return $stats;
    }

    /**
     * Sync generic git repository by cloning locally
     */
    private function syncGitSource(PromptSource $source): array
    {
        $stats = ['synced' => 0, 'updated' => 0, 'failed' => 0];
        $tempDir = sys_get_temp_dir().'/prompt-sync-'.uniqid();

        try {
            // Clone repository
            $process = Process::run([
                'git', 'clone',
                '--depth', '1',
                '--branch', $source->branch,
                $source->repository_url,
                $tempDir,
            ]);

            if (! $process->successful()) {
                throw new \RuntimeException('Git clone failed: '.$process->errorOutput());
            }

            // Find prompt files
            $finder = new Finder;
            $finder->files()
                ->in($tempDir)
                ->name($source->path_pattern)
                ->ignoreVCS(true);

            foreach ($finder as $file) {
                try {
                    $content = $file->getContents();
                    $relativePath = str_replace($tempDir.'/', '', $file->getRealPath());
                    $name = $this->extractNameFromPath($relativePath);

                    $patternData = $this->processPatternContent($name, $content, [
                        'source_url' => $this->buildSourceUrl($source, $relativePath),
                        'file_path' => $relativePath,
                    ]);

                    $this->createOrUpdatePrompt($patternData, $source->name, 'github');
                    $stats['synced']++;

                } catch (\Exception $e) {
                    Log::warning("Failed to process file: {$file->getFilename()}", [
                        'source' => $source->name,
                        'error' => $e->getMessage(),
                    ]);
                    $stats['failed']++;
                }
            }

        } finally {
            // Cleanup temp directory
            if (is_dir($tempDir)) {
                Process::run(['rm', '-rf', $tempDir]);
            }
        }

        return $stats;
    }

    /**
     * Process Fabric batch using existing logic
     */
    private function processFabricBatch($patternFiles, PromptSource $source): array
    {
        $promises = [];
        $patterns = [];
        $baseUrl = 'https://raw.githubusercontent.com/danielmiessler/fabric/main';

        foreach ($patternFiles as $file) {
            $patternName = $this->extractPatternNameFromPath($file['path']);

            if (isset($file['content']) && $file['size'] <= 1048576) {
                $content = base64_decode($file['content']);
                $patterns[] = $this->processPatternContent($patternName, $content, [
                    'source_url' => "{$baseUrl}/{$file['path']}",
                    'file_path' => $file['path'],
                ]);
            } else {
                $rawUrl = "{$baseUrl}/{$file['path']}";
                $promises[$patternName] = $this->http->get($rawUrl);
            }
        }

        foreach ($promises as $patternName => $response) {
            if ($response->successful()) {
                $content = $response->body();
                $patterns[] = $this->processPatternContent($patternName, $content, [
                    'source_url' => $response->effectiveUri(),
                ]);
            } else {
                Log::warning("Failed to fetch content for pattern: {$patternName}");
            }
        }

        return $patterns;
    }

    /**
     * Extract pattern name from Fabric path
     */
    private function extractPatternNameFromPath(string $path): string
    {
        $parts = explode('/', $path);

        return $parts[2] ?? '';
    }

    /**
     * Extract name from generic git path
     */
    private function extractNameFromPath(string $path): string
    {
        $basename = pathinfo($path, PATHINFO_FILENAME);

        return Str::slug($basename);
    }

    /**
     * Process pattern content into structured data
     */
    private function processPatternContent(string $name, string $content, array $additionalData = []): array
    {
        try {
            $tempFile = tmpfile();
            fwrite($tempFile, $content);
            $tempPath = stream_get_meta_data($tempFile)['uri'];

            $document = YamlFrontMatter::parseFile($tempPath);
            $metadata = $document->matter();
            $patternContent = $document->body();

            fclose($tempFile);
        } catch (\Exception $e) {
            Log::warning("Failed to parse YAML front matter for {$name}: ".$e->getMessage());
            $metadata = [];
            $patternContent = $content;
        }

        $category = $metadata['category'] ?? $this->inferCategory($name);

        return [
            'name' => $name,
            'title' => $metadata['title'] ?? Str::title(str_replace(['_', '-'], ' ', $name)),
            'description' => $metadata['description'] ?? $this->extractDescription($patternContent),
            'content' => $patternContent,
            'metadata' => array_merge($metadata, $additionalData),
            'category' => $category,
            'source_url' => $additionalData['source_url'] ?? null,
            'checksum' => hash('sha256', $content),
        ];
    }

    /**
     * Create or update prompt from processed data
     */
    private function createOrUpdatePrompt(array $promptData, string $sourceIdentifier, string $sourceType = 'github'): void
    {
        Prompt::updateOrCreate(
            ['source_type' => $sourceType, 'source_identifier' => $sourceIdentifier, 'name' => $promptData['name']],
            [
                'title' => $promptData['title'],
                'description' => $promptData['description'],
                'content' => $promptData['content'],
                'metadata' => $promptData['metadata'],
                'category' => $promptData['category'],
                'source_url' => $promptData['source_url'],
                'is_active' => true,
                'is_public' => true,
                'checksum' => $promptData['checksum'],
            ]
        );
    }

    /**
     * Build source URL for git repositories
     */
    private function buildSourceUrl(PromptSource $source, string $filePath): ?string
    {
        if (str_contains($source->repository_url, 'github.com')) {
            $repoPath = str_replace(['git@github.com:', '.git'], ['', ''], $source->repository_url);

            return "https://github.com/{$repoPath}/blob/{$source->branch}/{$filePath}";
        }

        return null; // Could add support for other git providers
    }

    /**
     * Infer category from pattern name
     */
    private function inferCategory(string $name): string
    {
        $categoryMappings = [
            'analyze' => 'analysis',
            'extract' => 'extraction',
            'create' => 'creation',
            'write' => 'writing',
            'improve' => 'improvement',
            'review' => 'review',
            'summarize' => 'summarization',
            'code' => 'coding',
            'explain' => 'explanation',
            'find' => 'search',
            'compare' => 'comparison',
        ];

        foreach ($categoryMappings as $keyword => $category) {
            if (str_contains(strtolower($name), $keyword)) {
                return $category;
            }
        }

        return 'general';
    }

    /**
     * Extract description from pattern content
     */
    private function extractDescription(string $content): ?string
    {
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line) && ! str_starts_with($line, '#')) {
                return Str::limit($line, 200);
            }
        }

        return null;
    }
}
