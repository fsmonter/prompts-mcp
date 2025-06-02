<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FabricPattern;
use App\Models\PatternExecution;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class FabricPatternService
{
    private const FABRIC_PATTERNS_URL = 'https://api.github.com/repos/danielmiessler/fabric/contents/patterns';

    private const FABRIC_RAW_URL = 'https://raw.githubusercontent.com/danielmiessler/fabric/main/patterns';

    private const FABRIC_TREE_URL = 'https://api.github.com/repos/danielmiessler/fabric/git/trees/main';

    public function __construct(
        private readonly HttpClient $http,
        private readonly CommonMarkConverter $markdown
    ) {}

    /**
     * Sync all patterns from the Fabric repository using bulk fetch
     */
    public function syncPatterns(): array
    {
        $stats = ['synced' => 0, 'updated' => 0, 'failed' => 0];

        try {
            $patterns = $this->fetchAllPatternsWithContent();

            foreach ($patterns as $patternData) {
                try {
                    $this->createOrUpdatePattern($patternData);
                    $stats['synced']++;
                } catch (\Exception $e) {
                    Log::warning("Failed to sync pattern: {$patternData['name']}", [
                        'error' => $e->getMessage(),
                        'pattern' => $patternData,
                    ]);
                    $stats['failed']++;
                }
            }

            Cache::put('fabric_patterns_last_sync', now(), now()->addHour());

        } catch (\Exception $e) {
            Log::error('Failed to sync Fabric patterns', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $stats;
    }

    /**
     * Legacy sync method - kept for backward compatibility
     */
    public function syncPatternsLegacy(): array
    {
        $stats = ['synced' => 0, 'updated' => 0, 'failed' => 0];

        try {
            $patterns = $this->fetchPatternList();

            foreach ($patterns as $patternInfo) {
                try {
                    $this->syncSinglePattern($patternInfo);
                    $stats['synced']++;
                } catch (\Exception $e) {
                    Log::warning("Failed to sync pattern: {$patternInfo['name']}", [
                        'error' => $e->getMessage(),
                        'pattern' => $patternInfo,
                    ]);
                    $stats['failed']++;
                }
            }

            Cache::put('fabric_patterns_last_sync', now(), now()->addHour());

        } catch (\Exception $e) {
            Log::error('Failed to sync Fabric patterns', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $stats;
    }

    /**
     * Execute a pattern with input content
     */
    public function executePattern(
        FabricPattern $pattern,
        string $inputContent,
        array $metadata = []
    ): string {
        $startTime = microtime(true);

        $processedContent = $this->processPatternContent($pattern->content, $inputContent);

        $executionTime = (microtime(true) - $startTime) * 1000;

        PatternExecution::create([
            'fabric_pattern_id' => $pattern->id,
            'input_content' => $inputContent,
            'output_content' => $processedContent,
            'metadata' => $metadata,
            'tokens_used' => $this->estimateTokens($processedContent),
            'execution_time_ms' => $executionTime,
            'client_info' => $metadata['client'] ?? 'unknown',
        ]);

        return $processedContent;
    }

    /**
     * Get patterns by category
     */
    public function getPatternsByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return FabricPattern::active()
            ->category($category)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search patterns by keyword
     */
    public function searchPatterns(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return FabricPattern::active()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available pattern categories
     */
    public function getCategories(): array
    {
        return FabricPattern::active()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Fetch pattern list from GitHub API
     */
    private function fetchPatternList(): array
    {
        $response = $this->http->get(self::FABRIC_PATTERNS_URL);

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch pattern list: '.$response->body());
        }

        $patterns = collect($response->json())
            ->filter(fn ($item) => $item['type'] === 'dir')
            ->map(fn ($item) => [
                'name' => $item['name'],
                'url' => $item['url'],
                'download_url' => $item['download_url'],
            ])
            ->toArray();

        return $patterns;
    }

    /**
     * Sync a single pattern
     */
    private function syncSinglePattern(array $patternInfo): void
    {
        $systemUrl = self::FABRIC_RAW_URL."/{$patternInfo['name']}/system.md";
        $response = $this->http->get($systemUrl);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch pattern content for: {$patternInfo['name']}");
        }

        $content = $response->body();
        $contentHash = hash('sha256', $content);

        $document = YamlFrontMatter::parseFile($systemUrl);
        $metadata = $document->matter();
        $patternContent = $document->body();

        $category = $metadata['category'] ?? $this->inferCategory($patternInfo['name']);

        FabricPattern::updateOrCreate(
            ['name' => $patternInfo['name']],
            [
                'title' => $metadata['title'] ?? Str::title(str_replace(['_', '-'], ' ', $patternInfo['name'])),
                'description' => $metadata['description'] ?? $this->extractDescription($patternContent),
                'content' => $patternContent,
                'metadata' => $metadata,
                'category' => $category,
                'source_url' => $systemUrl,
                'source_hash' => $contentHash,
                'synced_at' => now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * Fetch all patterns with their content in bulk using GitHub Tree API
     */
    private function fetchAllPatternsWithContent(): array
    {
        $response = $this->http->get(self::FABRIC_TREE_URL, [
            'recursive' => 'true',
        ])->throw();

        $tree = $response->json();

        $patternFiles = collect($tree['tree'])
            ->filter(function ($item) {
                return $item['type'] === 'blob'
                    && str_starts_with($item['path'], 'patterns/')
                    && str_ends_with($item['path'], '/system.md');
            })
            ->values();

        Log::info("Found {$patternFiles->count()} pattern files to process");

        $patterns = [];
        $batchSize = 10;

        foreach ($patternFiles->chunk($batchSize) as $batch) {
            $batchPatterns = $this->processBatchPatterns($batch);
            $patterns = array_merge($patterns, $batchPatterns);

            if ($patternFiles->count() > $batchSize) {
                usleep(100000); // 100ms delay
            }
        }

        return $patterns;
    }

    /**
     * Process a batch of pattern files concurrently
     */
    private function processBatchPatterns($patternFiles): array
    {
        $promises = [];
        $patterns = [];

        foreach ($patternFiles as $file) {
            $patternName = $this->extractPatternNameFromPath($file['path']);

            if (isset($file['content']) && $file['size'] <= 1048576) {
                $content = base64_decode($file['content']);
                $patterns[] = $this->processPatternData($patternName, $content, $file);
            } else {
                $rawUrl = self::FABRIC_RAW_URL."/{$patternName}/system.md";
                $promises[$patternName] = $this->http->get($rawUrl);
            }
        }

        foreach ($promises as $patternName => $response) {
            if ($response->successful()) {
                $content = $response->body();
                $patterns[] = $this->processPatternData($patternName, $content);
            } else {
                Log::warning("Failed to fetch content for pattern: {$patternName}");
            }
        }

        return $patterns;
    }

    /**
     * Extract pattern name from file path
     */
    private function extractPatternNameFromPath(string $path): string
    {
        // Extract pattern name from path like "patterns/analyze_claims/system.md"
        $parts = explode('/', $path);

        return $parts[1] ?? '';
    }

    /**
     * Process pattern content and return structured data
     */
    private function processPatternData(string $patternName, string $content, array $fileInfo = []): array
    {
        $contentHash = hash('sha256', $content);

        try {
            $tempFile = tmpfile();
            fwrite($tempFile, $content);
            $tempPath = stream_get_meta_data($tempFile)['uri'];

            $document = YamlFrontMatter::parseFile($tempPath);
            $metadata = $document->matter();
            $patternContent = $document->body();

            fclose($tempFile);
        } catch (\Exception $e) {
            Log::warning("Failed to parse YAML front matter for {$patternName}: ".$e->getMessage());
            $metadata = [];
            $patternContent = $content;
        }

        $category = $metadata['category'] ?? $this->inferCategory($patternName);

        return [
            'name' => $patternName,
            'title' => $metadata['title'] ?? Str::title(str_replace(['_', '-'], ' ', $patternName)),
            'description' => $metadata['description'] ?? $this->extractDescription($patternContent),
            'content' => $patternContent,
            'metadata' => $metadata,
            'category' => $category,
            'source_url' => self::FABRIC_RAW_URL."/{$patternName}/system.md",
            'source_hash' => $contentHash,
            'file_info' => $fileInfo,
        ];
    }

    /**
     * Create or update pattern from processed data
     */
    private function createOrUpdatePattern(array $patternData): void
    {
        FabricPattern::updateOrCreate(
            ['name' => $patternData['name']],
            [
                'title' => $patternData['title'],
                'description' => $patternData['description'],
                'content' => $patternData['content'],
                'metadata' => $patternData['metadata'],
                'category' => $patternData['category'],
                'source_url' => $patternData['source_url'],
                'source_hash' => $patternData['source_hash'],
                'synced_at' => now(),
                'is_active' => true,
            ]
        );
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
        // Try to extract first paragraph or heading
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line) && ! str_starts_with($line, '#')) {
                return Str::limit($line, 200);
            }
        }

        return null;
    }

    /**
     * Estimate token count for content
     */
    private function estimateTokens(string $content): int
    {
        return (int) ceil(strlen($content) / 4);
    }

    /**
     * Process pattern content with user input
     */
    private function processPatternContent(string $patternContent, string $inputContent): string
    {
        $processed = str_replace(
            ['{{INPUT}}', '{INPUT}', '$INPUT'],
            $inputContent,
            $patternContent
        );

        if ($processed === $patternContent && ! empty($inputContent)) {
            $processed = $patternContent."\n\n".$inputContent;
        }

        return $processed;
    }
}
