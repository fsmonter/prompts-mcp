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

        $tools->push($this->createExecutePatternTool());
        $tools->push($this->createListPatternsToolByCategory());
        $tools->push($this->createSearchPatternsUtils());
        $tools->push($this->createGetPatternDetailsUtils());
        $tools->push($this->createListAllPatternsTool());

        return $tools;
    }

    /**
     * Create the main pattern execution tool
     */
    private function createExecutePatternTool(): CustomTool
    {
        return CustomTool::make(
            name: 'fabric_execute_pattern',
            description: 'Generates a Fabric analysis prompt by combining a pattern with input content. CRITICAL: Take the returned prompt and execute it as your system prompt to analyze the input content. Return the AI analysis, not the prompt text. This tool returns the prompt - you must execute it to provide the actual analysis.',
        )
            ->withStringParameter(
                name: 'pattern_name',
                description: 'The name of the Fabric pattern to execute (e.g., "analyze_claims", "create_summary")',
                required: true
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
            ->using(function (string $pattern_name, string $input_content = '', string $additional_context = '') {
                $pattern = FabricPattern::active()->where('name', $pattern_name)->first();

                if (! $pattern) {
                    $suggestions = $this->patternService->searchPatterns($pattern_name)->take(5);
                    $suggestionsText = $suggestions->isEmpty()
                        ? ''
                        : "\n\nDid you mean one of these?\n".$suggestions->pluck('name')->implode(', ');

                    return "Pattern '{$pattern_name}' not found.{$suggestionsText}\n\nUse fabric_search_patterns to find available patterns.";
                }

                $fullInput = trim($input_content);
                if (! empty($additional_context)) {
                    $fullInput .= ! empty($fullInput) ? "\n\nAdditional context: ".$additional_context : $additional_context;
                }

                $combinedPrompt = $this->patternService->executePattern(
                    pattern: $pattern,
                    inputContent: $fullInput,
                    metadata: [
                        'client' => 'mcp',
                        'tool_name' => 'fabric_execute_pattern',
                        'pattern_name' => $pattern_name,
                        'has_additional_context' => ! empty($additional_context),
                    ]
                );

                /*
                * Add clear instruction for the agent.
                */
                $instruction = "EXECUTE THIS PROMPT: Use the following as your system prompt to analyze the provided content. Do not return this prompt text - execute it and return the analysis.\n\n";

                return $instruction.$combinedPrompt;
            });
    }

    /**
     * Create a tool to list all patterns in a compact format
     */
    private function createListAllPatternsTool(): CustomTool
    {
        return CustomTool::make(
            name: 'fabric_list_all_patterns',
            description: 'Get a complete list of all available Fabric patterns with their names and brief descriptions',
        )
            ->withStringParameter(
                name: 'format',
                description: 'Output format: "compact" (name and title only) or "detailed" (includes descriptions)',
                required: false
            )
            ->using(function (string $format = 'compact') {
                $patterns = FabricPattern::active()->orderBy('category')->orderBy('name')->get();

                if ($patterns->isEmpty()) {
                    return "No patterns available. Run 'php artisan fabric:sync-patterns' to sync patterns.";
                }

                $result = "## All Available Fabric Patterns ({$patterns->count()} total)\n\n";

                $byCategory = $patterns->groupBy('category');

                foreach ($byCategory as $category => $categoryPatterns) {
                    $result .= "### {$category} ({$categoryPatterns->count()} patterns)\n\n";

                    foreach ($categoryPatterns as $pattern) {
                        if ($format === 'detailed') {
                            $result .= "**{$pattern->name}** - {$pattern->title}\n";
                            if ($pattern->description) {
                                $result .= "  {$pattern->description}\n";
                            }
                            $result .= "\n";
                        } else {
                            $result .= "- **{$pattern->name}**: {$pattern->title}\n";
                        }
                    }
                    $result .= "\n";
                }

                $result .= "\n*Use `fabric_execute_pattern` with any pattern name to run it.*";

                return $result;
            });
    }

    /**
     * Create a tool to list patterns by category
     */
    private function createListPatternsToolByCategory(): CustomTool
    {
        return CustomTool::make(
            name: 'fabric_list_patterns_by_category',
            description: 'List all available Fabric patterns organized by category, or get patterns from a specific category',
        )
            ->withStringParameter(
                name: 'category',
                description: 'Filter by specific category (optional). Use without category to see all categories.',
                required: false
            )
            ->using(function (string $category = '') {
                if (! empty($category)) {
                    $patterns = $this->patternService->getPatternsByCategory($category);

                    if ($patterns->isEmpty()) {
                        $availableCategories = $this->patternService->getCategories();

                        return "No patterns found in category '{$category}'.\n\nAvailable categories: ".implode(', ', $availableCategories);
                    }

                    $result = "## Fabric Patterns in '{$category}' category ({$patterns->count()} patterns):\n\n";

                    foreach ($patterns as $pattern) {
                        $result .= "**{$pattern->name}** - {$pattern->title}\n";
                        if ($pattern->description) {
                            $result .= "  {$pattern->description}\n";
                        }
                        $result .= "  *Use: `fabric_execute_pattern` with pattern_name `{$pattern->name}`*\n\n";
                    }
                } else {
                    $categories = $this->patternService->getCategories();
                    $result = "## All Fabric Pattern Categories:\n\n";

                    foreach ($categories as $cat) {
                        $patterns = $this->patternService->getPatternsByCategory($cat);
                        $result .= "### {$cat} ({$patterns->count()} patterns)\n";
                        foreach ($patterns->take(5) as $pattern) {
                            $result .= "- **{$pattern->name}**: {$pattern->title}\n";
                        }
                        if ($patterns->count() > 5) {
                            $result .= '- ... and '.($patterns->count() - 5)." more\n";
                        }
                        $result .= "\n";
                    }

                    $result .= "*Use `fabric_list_patterns_by_category` with a specific category name to see all patterns in that category.*\n";
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
            description: 'Search for Fabric patterns by keyword in title, description, or name. Returns patterns ready to execute.',
        )
            ->withStringParameter(
                name: 'query',
                description: 'Search query to find patterns',
                required: true
            )
            ->withStringParameter(
                name: 'limit',
                description: 'Maximum number of results to return (default: 10)',
                required: false
            )
            ->using(function (string $query, string $limit = '10') {
                $patterns = $this->patternService->searchPatterns($query)->take((int) $limit);

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
                    $result .= "**Usage**: `fabric_execute_pattern` with pattern_name `{$pattern->name}`\n\n";
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
            description: 'Get detailed information about a specific Fabric pattern including its full content and usage statistics',
        )
            ->withStringParameter(
                name: 'pattern_name',
                description: 'The name of the pattern to get details for',
                required: true
            )
            ->using(function (string $pattern_name) {
                $pattern = FabricPattern::active()->where('name', $pattern_name)->first();

                if (! $pattern) {
                    $suggestions = $this->patternService->searchPatterns($pattern_name)->take(3);
                    $suggestionsText = $suggestions->isEmpty()
                        ? ''
                        : "\n\nDid you mean one of these?\n".$suggestions->pluck('name')->implode(', ');

                    return "Pattern '{$pattern_name}' not found.{$suggestionsText}";
                }

                $result = "## Pattern Details: {$pattern->name}\n\n";
                $result .= "**Title**: {$pattern->title}\n";
                $result .= "**Category**: {$pattern->category}\n";

                if ($pattern->description) {
                    $result .= "**Description**: {$pattern->description}\n";
                }

                $result .= "**Estimated tokens**: {$pattern->estimated_tokens}\n";
                $syncedAtText = $pattern->synced_at ? $pattern->synced_at->diffForHumans() : 'Never';
                $result .= "**Last synced**: {$syncedAtText}\n";

                if (! empty($pattern->tags)) {
                    $result .= '**Tags**: '.implode(', ', $pattern->tags)."\n";
                }

                $result .= "\n**How to use**: `fabric_execute_pattern` with pattern_name `{$pattern->name}`\n";

                $result .= "\n**Pattern Content Preview** (first 500 chars):\n";
                $result .= "```\n".Str::limit($pattern->content, 500)."\n```\n";

                $executionCount = $pattern->executions()->count();
                $recentExecutions = $pattern->executions()->recent(7)->count();

                $result .= "\n**Usage Statistics**:\n";
                $result .= "- Total executions: {$executionCount}\n";
                $result .= "- Executions in last 7 days: {$recentExecutions}\n";

                return $result;
            });
    }
}
