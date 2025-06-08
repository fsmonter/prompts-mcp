<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Models\Prompt;
use App\Services\PromptService;
use Illuminate\Support\Str;
use Kirschbaum\Loop\Collections\ToolCollection;
use Kirschbaum\Loop\Contracts\Toolkit;
use Kirschbaum\Loop\Tools\CustomTool;

class PromptLibraryToolkit implements Toolkit
{
    use \Kirschbaum\Loop\Concerns\Makeable;

    public function __construct(
        private readonly PromptService $promptService
    ) {}

    public function getTools(): ToolCollection
    {
        $tools = new ToolCollection;

        $tools->push($this->createComposePromptTool());
        $tools->push($this->createListPromptsByCategory());
        $tools->push($this->createListCategoriesUtils());
        $tools->push($this->createSearchPromptsUtils());
        $tools->push($this->createGetPromptDetailsUtils());
        $tools->push($this->createListAllPromptsTool());

        return $tools;
    }

    /**
     * Create the main prompt composition tool
     */
    private function createComposePromptTool(): CustomTool
    {
        return CustomTool::make(
            name: 'compose_prompt',
            description: 'Compose a prompt by combining a template with input content. Returns the composed prompt ready for use. Works with all prompts: Fabric patterns, custom prompts, and other sources.',
        )
            ->withStringParameter(
                name: 'prompt_name',
                description: 'The name of the prompt to compose (e.g., "analyze_claims", "create_summary", or any custom prompt name)',
                required: true
            )
            ->withStringParameter(
                name: 'input_content',
                description: 'The content to process with this prompt',
                required: false
            )
            ->withStringParameter(
                name: 'additional_context',
                description: 'Any additional context or instructions',
                required: false
            )
            ->using(function (string $prompt_name, string $input_content = '', string $additional_context = '') {
                // Try to find prompt by name across all sources
                $prompt = Prompt::active()
                    ->public()
                    ->where('name', $prompt_name)
                    ->first();

                if (! $prompt) {
                    $suggestions = $this->promptService->searchPrompts($prompt_name)->take(5);
                    $suggestionsText = $suggestions->isEmpty()
                        ? ''
                        : "\n\nDid you mean one of these?\n".$suggestions->pluck('name')->implode(', ');

                    return "Prompt '{$prompt_name}' not found.{$suggestionsText}\n\nUse search_prompts to find available prompts.";
                }

                $fullInput = trim($input_content);
                if (! empty($additional_context)) {
                    $fullInput .= ! empty($fullInput) ? "\n\nAdditional context: ".$additional_context : $additional_context;
                }

                $composedPrompt = $this->promptService->composePrompt(
                    prompt: $prompt,
                    inputContent: $fullInput,
                    metadata: [
                        'client' => 'mcp',
                        'tool_name' => 'compose_prompt',
                        'prompt_name' => $prompt_name,
                        'prompt_source' => $prompt->source_type,
                        'has_additional_context' => ! empty($additional_context),
                    ]
                );

                $instruction = "EXECUTE THIS PROMPT: Use the following as your system prompt to analyze the provided content. Do not return this prompt text - execute it and return the analysis.\n\n";

                return $instruction.$composedPrompt;
            });
    }

    /**
     * Create a tool to list all prompts in a compact format
     */
    private function createListAllPromptsTool(): CustomTool
    {
        return CustomTool::make(
            name: 'list_all_prompts',
            description: 'Get a complete list of all available prompts with their names and brief descriptions. Includes Fabric patterns, custom prompts, and other sources.',
        )
            ->withStringParameter(
                name: 'format',
                description: 'Output format: "compact" (name and title only) or "detailed" (includes descriptions)',
                required: false
            )
            ->using(function (string $format = 'compact') {
                $prompts = Prompt::active()->public()->orderBy('source_type')->orderBy('category')->orderBy('title')->get();

                if ($prompts->isEmpty()) {
                    return "No prompts available. Run 'php artisan prompts:sync' to sync Fabric patterns or create custom prompts via the web interface.";
                }

                $result = "## All Available Prompts ({$prompts->count()} total)\n\n";

                $bySource = $prompts->groupBy('source_type');

                foreach ($bySource as $sourceType => $sourcePrompts) {
                    $sourceLabel = match ($sourceType) {
                        'fabric' => 'Fabric Patterns',
                        'manual' => 'Custom Prompts',
                        'github' => 'GitHub Repository',
                        default => ucfirst($sourceType)
                    };

                    $result .= "### {$sourceLabel} ({$sourcePrompts->count()} prompts)\n\n";

                    $byCategory = $sourcePrompts->groupBy('category');

                    foreach ($byCategory as $category => $categoryPrompts) {
                        $result .= "#### {$category}\n";

                        foreach ($categoryPrompts as $prompt) {
                            if ($format === 'detailed') {
                                $result .= "**{$prompt->name}** - {$prompt->title}\n";
                                if ($prompt->description) {
                                    $result .= "  {$prompt->description}\n";
                                }
                                $result .= "\n";
                            } else {
                                $result .= "- **{$prompt->name}**: {$prompt->title}\n";
                            }
                        }
                        $result .= "\n";
                    }
                }

                $result .= "\n*Use `compose_prompt` with any prompt name to compose it.*";

                return $result;
            });
    }

    /**
     * Create a tool to list prompts by category
     */
    private function createListPromptsByCategory(): CustomTool
    {
        return CustomTool::make(
            name: 'list_prompts_by_category',
            description: 'List all available prompts organized by category, or get prompts from a specific category. Includes all prompt sources.',
        )
            ->withStringParameter(
                name: 'category',
                description: 'Filter by specific category (optional). Use without category to see all categories.',
                required: false
            )
            ->using(function (string $category = '') {
                if (! empty($category)) {
                    $prompts = $this->promptService->getPromptsByCategory($category);

                    if ($prompts->isEmpty()) {
                        $availableCategories = $this->promptService->getCategories();

                        return "No prompts found in category '{$category}'.\n\nAvailable categories: ".implode(', ', $availableCategories);
                    }

                    $result = "## Prompts in '{$category}' category ({$prompts->count()} prompts):\n\n";

                    $bySource = $prompts->groupBy('source_type');

                    foreach ($bySource as $sourceType => $sourcePrompts) {
                        $sourceLabel = match ($sourceType) {
                            'fabric' => 'Fabric Patterns',
                            'manual' => 'Custom Prompts',
                            'github' => 'GitHub Repository',
                            default => ucfirst($sourceType)
                        };

                        $result .= "### {$sourceLabel}\n";

                        foreach ($sourcePrompts as $prompt) {
                            $result .= "**{$prompt->name}** - {$prompt->title}\n";
                            if ($prompt->description) {
                                $result .= "  {$prompt->description}\n";
                            }
                            $result .= "  *Use: `compose_prompt` with prompt_name `{$prompt->name}`*\n\n";
                        }
                    }
                } else {
                    $categories = $this->promptService->getCategories();
                    $result = "## All Prompt Categories:\n\n";

                    foreach ($categories as $cat) {
                        $prompts = $this->promptService->getPromptsByCategory($cat);
                        $result .= "### {$cat} ({$prompts->count()} prompts)\n";
                        foreach ($prompts->take(5) as $prompt) {
                            $sourceIcon = match ($prompt->source_type) {
                                'fabric' => '🔧',
                                'manual' => '✏️',
                                'github' => '📁',
                                default => '📄'
                            };
                            $result .= "- {$sourceIcon} **{$prompt->name}**: {$prompt->title}\n";
                        }
                        if ($prompts->count() > 5) {
                            $result .= '- ... and '.($prompts->count() - 5)." more\n";
                        }
                        $result .= "\n";
                    }

                    $result .= "*Use `list_prompts_by_category` with a specific category name to see all prompts in that category.*\n";
                }

                return $result;
            });
    }

    /**
     * Create a tool to list available prompt categories
     */
    private function createListCategoriesUtils(): CustomTool
    {
        return CustomTool::make(
            name: 'list_categories',
            description: 'Get a list of all available prompt categories with counts and descriptions. Covers all prompt sources.',
        )
            ->withStringParameter(
                name: 'format',
                description: 'Output format: "simple" (category names only), "detailed" (with counts and descriptions), or "summary" (categories with sample prompts)',
                required: false
            )
            ->using(function (string $format = 'detailed') {
                $categories = $this->promptService->getCategories();

                if (empty($categories)) {
                    return "No categories available. Run 'php artisan prompts:sync' to sync patterns or create custom prompts.";
                }

                if ($format === 'simple') {
                    return "## Available Categories:\n\n".implode(', ', $categories);
                }

                $result = '## Prompt Categories ('.count($categories)." total):\n\n";

                $categoryDescriptions = [
                    'analysis' => '🔍 Analyze content for claims, debates, papers, etc.',
                    'creation' => '🎨 Generate summaries, visualizations, documentation',
                    'extraction' => '📝 Extract insights, wisdom, ideas, and recommendations',
                    'writing' => '✍️ Generate essays, reports, and technical documentation',
                    'coding' => '💻 Code analysis, review, and project creation',
                    'improvement' => '🔧 Enhance writing, prompts, and reports',
                    'review' => '📋 Review designs and conduct assessments',
                    'explanation' => '📖 Explain documentation, math, projects, and terms',
                    'summarization' => '📑 Create different types of summaries',
                    'search' => '🔍 Find specific information or identify issues',
                    'comparison' => '⚖️ Compare and contrast items',
                    'business' => '💼 Business planning, market analysis, strategy',
                    'research' => '🔬 Academic research, scientific analysis',
                    'general' => '🌟 Versatile prompts for various tasks',
                ];

                foreach ($categories as $category) {
                    $prompts = $this->promptService->getPromptsByCategory($category);
                    $count = $prompts->count();
                    $description = $categoryDescriptions[$category] ?? '📁 General category';

                    $result .= "### {$category} ({$count} prompts)\n";
                    $result .= "{$description}\n";

                    if ($format === 'summary') {
                        $samplePrompts = $prompts->take(3);
                        if ($samplePrompts->isNotEmpty()) {
                            $result .= '**Sample prompts**: '.implode(', ', $samplePrompts->pluck('name')->toArray())."\n";
                            if ($count > 3) {
                                $result .= '... and '.($count - 3)." more\n";
                            }
                        }
                    }
                    $result .= "\n";
                }

                $result .= "*Use `list_prompts_by_category` with a category name to see all prompts in that category.*\n";
                $result .= "*Use `compose_prompt` with any prompt name to compose it.*\n";

                return $result;
            });
    }

    /**
     * Create a tool to search prompts
     */
    private function createSearchPromptsUtils(): CustomTool
    {
        return CustomTool::make(
            name: 'search_prompts',
            description: 'Search for prompts by keyword in title, description, or name. Returns prompts from all sources ready to use.',
        )
            ->withStringParameter(
                name: 'query',
                description: 'Search query to find prompts',
                required: true
            )
            ->withStringParameter(
                name: 'limit',
                description: 'Maximum number of results to return (default: 10)',
                required: false
            )
            ->using(function (string $query, string $limit = '10') {
                $prompts = $this->promptService->searchPrompts($query)->take((int) $limit);

                if ($prompts->isEmpty()) {
                    return "No prompts found matching '{$query}'.";
                }

                $result = "## Search results for '{$query}' ({$prompts->count()} found):\n\n";

                foreach ($prompts as $prompt) {
                    $sourceIcon = match ($prompt->source_type) {
                        'fabric' => '🔧 Fabric',
                        'manual' => '✏️ Custom',
                        'github' => '📁 GitHub',
                        default => '📄 '.ucfirst($prompt->source_type)
                    };

                    $result .= "### {$prompt->name}\n";
                    $result .= "**Title**: {$prompt->title}\n";
                    $result .= "**Source**: {$sourceIcon}\n";
                    $result .= "**Category**: {$prompt->category}\n";
                    if ($prompt->description) {
                        $result .= "**Description**: {$prompt->description}\n";
                    }
                    $result .= "**Usage**: `compose_prompt` with prompt_name `{$prompt->name}`\n\n";
                }

                return $result;
            });
    }

    /**
     * Create a tool to get detailed information about a prompt
     */
    private function createGetPromptDetailsUtils(): CustomTool
    {
        return CustomTool::make(
            name: 'get_prompt_details',
            description: 'Get detailed information about a specific prompt including its full content and usage statistics. Works with all prompt sources.',
        )
            ->withStringParameter(
                name: 'prompt_name',
                description: 'The name of the prompt to get details for',
                required: true
            )
            ->using(function (string $prompt_name) {
                $prompt = Prompt::active()->public()->where('name', $prompt_name)->first();

                if (! $prompt) {
                    $suggestions = $this->promptService->searchPrompts($prompt_name)->take(3);
                    $suggestionsText = $suggestions->isEmpty()
                        ? ''
                        : "\n\nDid you mean one of these?\n".$suggestions->pluck('name')->implode(', ');

                    return "Prompt '{$prompt_name}' not found.{$suggestionsText}";
                }

                $sourceLabel = match ($prompt->source_type) {
                    'fabric' => 'Fabric Pattern',
                    'manual' => 'Custom Prompt',
                    'github' => 'GitHub Repository',
                    default => ucfirst($prompt->source_type)
                };

                $result = "## Prompt Details: {$prompt->name}\n\n";
                $result .= "**Title**: {$prompt->title}\n";
                $result .= "**Source**: {$sourceLabel}\n";
                $result .= "**Category**: {$prompt->category}\n";

                if ($prompt->description) {
                    $result .= "**Description**: {$prompt->description}\n";
                }

                $result .= "**Estimated tokens**: {$prompt->estimated_tokens}\n";

                if ($prompt->synced_at) {
                    $syncedAtText = $prompt->synced_at->diffForHumans();
                    $result .= "**Last synced**: {$syncedAtText}\n";
                }

                if (! empty($prompt->tags)) {
                    $result .= '**Tags**: '.implode(', ', $prompt->tags)."\n";
                }

                $result .= "\n**How to use**: `compose_prompt` with prompt_name `{$prompt->name}`\n";

                $result .= "\n**Prompt Content Preview** (first 500 chars):\n";
                $result .= "```\n".Str::limit($prompt->content, 500)."\n```\n";

                $compositionCount = $prompt->compositions()->count();
                $recentCompositions = $prompt->compositions()->recent(7)->count();

                $result .= "\n**Usage Statistics**:\n";
                $result .= "- Total compositions: {$compositionCount}\n";
                $result .= "- Compositions in last 7 days: {$recentCompositions}\n";

                return $result;
            });
    }
}
