<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Models\FabricPattern;
use App\Services\FabricPatternService;
use Illuminate\Support\Str;
use Kirschbaum\Loop\Collections\ToolCollection;
use Kirschbaum\Loop\Contracts\Toolkit;
use Kirschbaum\Loop\Tools\CustomTool;

class FabricPatternsToolkit implements Toolkit
{
    use \Kirschbaum\Loop\Concerns\Makeable;

    public function __construct(
        private readonly FabricPatternService $patternService
    ) {}

    public function getTools(): ToolCollection
    {
        $tools = new ToolCollection;

        try {
            // Get all active patterns - handle gracefully if tables don't exist
            $patterns = FabricPattern::active()->get();

            foreach ($patterns as $pattern) {
                $tools->push($this->createPatternTool($pattern));
            }
        } catch (\Exception $e) {
            // If database tables don't exist yet, just return utility tools
            // This can happen during package discovery before migrations are run
        }

        // Add utility tools
        $tools->push($this->createListPatternsToolByCategory());
        $tools->push($this->createSearchPatternsUtils());
        $tools->push($this->createGetPatternDetailsUtils());

        return $tools;
    }

    /**
     * Create an MCP tool for a specific Fabric pattern
     */
    private function createPatternTool(FabricPattern $pattern): CustomTool
    {
        return CustomTool::make(
            name: "fabric_{$pattern->name}",
            description: $this->buildPatternDescription($pattern),
        )
            ->withStringParameter(
                name: 'input_content',
                description: 'The content to process with this pattern',
                required: false
            )
            ->withStringParameter(
                name: 'additional_context',
                description: 'Any additional context or instructions',
                required: false
            )
            ->using(function (string $input_content = '', string $additional_context = '') use ($pattern) {
                // Combine input content with additional context
                $fullInput = trim($input_content);
                if (! empty($additional_context)) {
                    $fullInput .= ! empty($fullInput) ? "\n\nAdditional context: ".$additional_context : $additional_context;
                }

                return $this->patternService->executePattern(
                    pattern: $pattern,
                    inputContent: $fullInput,
                    metadata: [
                        'client' => 'mcp',
                        'tool_name' => "fabric_{$pattern->name}",
                        'has_additional_context' => ! empty($additional_context),
                    ]
                );
            });
    }

    /**
     * Create a tool to list patterns by category
     */
    private function createListPatternsToolByCategory(): CustomTool
    {
        return CustomTool::make(
            name: 'fabric_list_patterns_by_category',
            description: 'List all available Fabric patterns organized by category',
        )
            ->withStringParameter(
                name: 'category',
                description: 'Filter by specific category (optional)',
                required: false
            )
            ->using(function (string $category = '') {
                if (! empty($category)) {
                    $patterns = $this->patternService->getPatternsByCategory($category);
                    $result = "## Fabric Patterns in '{$category}' category:\n\n";
                } else {
                    $categories = $this->patternService->getCategories();
                    $result = "## All Fabric Pattern Categories:\n\n";

                    foreach ($categories as $cat) {
                        $patterns = $this->patternService->getPatternsByCategory($cat);
                        $result .= "### {$cat} ({$patterns->count()} patterns)\n";
                        foreach ($patterns as $pattern) {
                            $result .= "- **{$pattern->name}**: {$pattern->title}\n";
                            if ($pattern->description) {
                                $result .= "  {$pattern->description}\n";
                            }
                        }
                        $result .= "\n";
                    }

                    return $result;
                }

                foreach ($patterns as $pattern) {
                    $result .= "- **{$pattern->name}**: {$pattern->title}\n";
                    if ($pattern->description) {
                        $result .= "  {$pattern->description}\n";
                    }
                    $result .= "  Estimated tokens: {$pattern->estimated_tokens}\n\n";
                }

                return $result;
            });
    }

    /**
     * Create a tool to search patterns
     */
    private function createSearchPatternsUtils(): CustomTool
    {
        return CustomTool::make(
            name: 'fabric_search_patterns',
            description: 'Search for Fabric patterns by keyword in title, description, or name',
        )
            ->withStringParameter(
                name: 'query',
                description: 'Search query to find patterns',
                required: true
            )
            ->using(function (string $query) {
                $patterns = $this->patternService->searchPatterns($query);

                if ($patterns->isEmpty()) {
                    return "No patterns found matching '{$query}'.";
                }

                $result = "## Search results for '{$query}' ({$patterns->count()} found):\n\n";

                foreach ($patterns as $pattern) {
                    $result .= "### {$pattern->name}\n";
                    $result .= "**Title**: {$pattern->title}\n";
                    $result .= "**Category**: {$pattern->category}\n";
                    if ($pattern->description) {
                        $result .= "**Description**: {$pattern->description}\n";
                    }
                    $result .= "**Estimated tokens**: {$pattern->estimated_tokens}\n";
                    $result .= "**Tool name**: `fabric_{$pattern->name}`\n\n";
                }

                return $result;
            });
    }

    /**
     * Create a tool to get detailed information about a pattern
     */
    private function createGetPatternDetailsUtils(): CustomTool
    {
        return CustomTool::make(
            name: 'fabric_get_pattern_details',
            description: 'Get detailed information about a specific Fabric pattern',
        )
            ->withStringParameter(
                name: 'pattern_name',
                description: 'The name of the pattern to get details for',
                required: true
            )
            ->using(function (string $pattern_name) {
                $pattern = FabricPattern::active()->where('name', $pattern_name)->first();

                if (! $pattern) {
                    return "Pattern '{$pattern_name}' not found.";
                }

                $result = "## Pattern Details: {$pattern->name}\n\n";
                $result .= "**Title**: {$pattern->title}\n";
                $result .= "**Category**: {$pattern->category}\n";

                if ($pattern->description) {
                    $result .= "**Description**: {$pattern->description}\n";
                }

                $result .= "**Estimated tokens**: {$pattern->estimated_tokens}\n";
                $result .= "**Tool name**: `fabric_{$pattern->name}`\n";
                $syncedAtText = $pattern->synced_at ? $pattern->synced_at->diffForHumans() : 'Never';
                $result .= "**Last synced**: {$syncedAtText}\n";

                if (! empty($pattern->tags)) {
                    $result .= '**Tags**: '.implode(', ', $pattern->tags)."\n";
                }

                $result .= "\n**Pattern Content Preview** (first 300 chars):\n";
                $result .= "```\n".Str::limit($pattern->content, 300)."\n```\n";

                // Show recent usage stats
                $executionCount = $pattern->executions()->count();
                $recentExecutions = $pattern->executions()->recent(7)->count();

                $result .= "\n**Usage Statistics**:\n";
                $result .= "- Total executions: {$executionCount}\n";
                $result .= "- Executions in last 7 days: {$recentExecutions}\n";

                return $result;
            });
    }

    /**
     * Build a comprehensive description for a pattern tool
     */
    private function buildPatternDescription(FabricPattern $pattern): string
    {
        $description = $pattern->title;

        if ($pattern->description) {
            $description .= ' - '.$pattern->description;
        }

        $description .= " (Category: {$pattern->category})";

        $tags = $pattern->tags;
        if (! empty($tags) && is_array($tags)) {
            $description .= ' [Tags: '.implode(', ', $tags).']';
        }

        return $description;
    }
}
