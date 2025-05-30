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

    public function __construct(
        private readonly HttpClient $http,
        private readonly CommonMarkConverter $markdown
    ) {}

    /**
     * Sync all patterns from the Fabric repository
     */
    public function syncPatterns(): array
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

        // Process the pattern content with input
        $processedContent = $this->processPatternContent($pattern->content, $inputContent);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Log execution for analytics
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
        // Fetch pattern contents
        $systemUrl = self::FABRIC_RAW_URL."/{$patternInfo['name']}/system.md";
        $response = $this->http->get($systemUrl);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch pattern content for: {$patternInfo['name']}");
        }

        $content = $response->body();
        $contentHash = hash('sha256', $content);

        // Parse front matter if exists
        $document = YamlFrontMatter::parseFile($systemUrl);
        $metadata = $document->matter();
        $patternContent = $document->body();

        // Determine category (could be from metadata or directory structure)
        $category = $metadata['category'] ?? $this->inferCategory($patternInfo['name']);

        // Create or update pattern
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
     * Process pattern content with user input
     */
    private function processPatternContent(string $patternContent, string $inputContent): string
    {
        // Replace common placeholders
        $processed = str_replace(
            ['{{INPUT}}', '{INPUT}', '$INPUT'],
            $inputContent,
            $patternContent
        );

        // If no placeholders found, append input to pattern
        if ($processed === $patternContent && ! empty($inputContent)) {
            $processed = $patternContent."\n\n".$inputContent;
        }

        return $processed;
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
}
