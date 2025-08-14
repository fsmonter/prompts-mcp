<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Composition;
use App\Models\Prompt;
use App\Models\PromptSource;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

class PromptService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly CommonMarkConverter $markdown,
        private readonly GitSyncService $gitSync
    ) {}

    /**
     * Sync all prompt sources
     */
    public function syncAllSources(): array
    {
        return $this->gitSync->syncAllSources();
    }

    /**
     * Sync Fabric patterns from repository (backward compatibility)
     */
    public function syncFabricPatterns(): array
    {
        $fabricSource = PromptSource::firstOrCreate(
            ['name' => 'fabric'],
            [
                'type' => 'fabric',
                'repository_url' => 'https://github.com/danielmiessler/fabric.git',
                'branch' => 'main',
                'path_pattern' => 'data/patterns/*/system.md',
                'file_pattern' => 'system.md',
                'is_active' => true,
                'auto_sync' => true,
                'metadata' => [
                    'description' => 'Official Fabric AI patterns',
                    'created_via' => 'migration',
                ],
            ]
        );

        return $this->gitSync->syncSource($fabricSource);
    }

    /**
     * Compose a prompt with input content
     */
    public function composePrompt(
        Prompt $prompt,
        string $inputContent,
        array $metadata = []
    ): string {
        $startTime = microtime(true);

        $composedContent = $this->processPromptContent($prompt->content, $inputContent);

        $composeTime = (microtime(true) - $startTime) * 1000;

        Composition::create([
            'prompt_id' => $prompt->id,
            'input_content' => $inputContent,
            'composed_content' => $composedContent,
            'metadata' => $metadata,
            'tokens_used' => $this->estimateTokens($composedContent),
            'compose_time_ms' => $composeTime,
            'client_info' => $metadata['client'] ?? 'unknown',
        ]);

        return $composedContent;
    }

    /**
     * Get prompts by category
     */
    public function getPromptsByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return Prompt::active()
            ->public()
            ->category($category)
            ->orderBy('title')
            ->get();
    }

    /**
     * Search prompts by keyword
     */
    public function searchPrompts(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return Prompt::active()
            ->public()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('title')
            ->get();
    }

    /**
     * Get available prompt categories
     */
    public function getCategories(): array
    {
        return Prompt::active()
            ->public()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Create a manual prompt
     */
    public function createManualPrompt(array $data, ?int $userId = null): Prompt
    {
        $baseName = Str::slug($data['title']);
        $name = $this->generateUniquePromptName($baseName, 'manual');

        return Prompt::create([
            'name' => $name,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'content' => $data['content'],
            'category' => $data['category'] ?? 'general',
            'tags' => $data['tags'] ?? [],
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => $data['is_public'] ?? true,
            'created_by' => $userId,
            'metadata' => [
                'created_via' => 'web_ui',
                'version' => 1,
            ],
        ]);
    }

    /**
     * Generate a unique prompt name for the given source type
     */
    private function generateUniquePromptName(string $baseName, string $sourceType): string
    {
        $name = $baseName;
        $counter = 0;

        while (Prompt::where('name', $name)->where('source_type', $sourceType)->exists()) {
            $counter++;
            $name = "{$baseName}-{$counter}";
        }

        return $name;
    }

    /**
     * Estimate token count for content
     */
    private function estimateTokens(string $content): int
    {
        return (int) ceil(strlen($content) / 4);
    }

    /**
     * Process prompt content with user input
     */
    private function processPromptContent(string $promptContent, string $inputContent): string
    {
        $processed = str_replace(
            ['{{INPUT}}', '{INPUT}', '$INPUT'],
            $inputContent,
            $promptContent
        );

        if ($processed === $promptContent && ! empty($inputContent)) {
            $processed = $promptContent."\n\n".$inputContent;
        }

        return $processed;
    }
}
