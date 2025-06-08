<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mcp\PromptLibraryToolkit;
use App\Models\Prompt;
use App\Services\PromptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PromptLibraryToolkit $toolkit;

    private PromptService $promptService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promptService = $this->app->make(PromptService::class);
        $this->toolkit = new PromptLibraryToolkit($this->promptService);
    }

    /** @test */
    public function mcp_toolkit_exposes_expected_tools()
    {
        $tools = $this->toolkit->getTools();

        $toolNames = $tools->map(fn ($tool) => $tool->getName())->toArray();

        $expectedTools = [
            'fabric_execute_pattern',
            'fabric_list_patterns_by_category',
            'fabric_list_categories',
            'fabric_search_patterns',
            'fabric_get_pattern_details',
            'fabric_list_all_patterns',
        ];

        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames, "Tool '{$expectedTool}' should be available");
        }
    }

    /** @test */
    public function fabric_execute_pattern_works_with_fabric_prompts()
    {
        // Create a Fabric-style prompt
        Prompt::create([
            'name' => 'analyze_claims',
            'title' => 'Analyze Claims',
            'content' => 'Analyze these claims: {{INPUT}}',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Test via service directly (simulating MCP call)
        $prompt = Prompt::where('name', 'analyze_claims')->first();
        $result = $this->promptService->composePrompt($prompt, 'This is a test claim.');

        $this->assertStringContainsString('Analyze these claims: This is a test claim.', $result);
    }

    /** @test */
    public function fabric_execute_pattern_works_with_manual_prompts()
    {
        // Create a manual prompt
        $manualPrompt = Prompt::create([
            'name' => 'custom-analysis',
            'title' => 'Custom Analysis',
            'content' => 'Custom prompt: {{INPUT}}',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Test via service directly (simulating MCP call)
        $result = $this->promptService->composePrompt($manualPrompt, 'Test input');

        $this->assertStringContainsString('Custom prompt: Test input', $result);
    }

    /** @test */
    public function fabric_search_patterns_finds_all_prompt_types()
    {
        // Create prompts from different sources
        Prompt::create([
            'name' => 'fabric-search-test',
            'title' => 'Fabric Search Test',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        Prompt::create([
            'name' => 'manual-search-test',
            'title' => 'Manual Search Test',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Test search functionality
        $results = $this->promptService->searchPrompts('search test');

        $this->assertCount(2, $results);
        $resultTitles = $results->pluck('title')->toArray();
        $this->assertContains('Fabric Search Test', $resultTitles);
        $this->assertContains('Manual Search Test', $resultTitles);
    }

    /** @test */
    public function fabric_list_all_patterns_shows_unified_library()
    {
        // Create test prompts
        Prompt::create([
            'name' => 'fabric-test',
            'title' => 'Fabric Test',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        Prompt::create([
            'name' => 'manual-test',
            'title' => 'Manual Test',
            'content' => 'Content',
            'category' => 'writing',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Test via service
        $prompts = Prompt::active()->public()->get();

        $this->assertCount(2, $prompts);
        $fabricPrompts = $prompts->where('source_type', 'fabric');
        $manualPrompts = $prompts->where('source_type', 'manual');

        $this->assertCount(1, $fabricPrompts);
        $this->assertCount(1, $manualPrompts);
    }

    /** @test */
    public function fabric_list_categories_includes_all_sources()
    {
        // Create prompts with different categories and sources
        Prompt::create([
            'name' => 'fabric-analysis',
            'title' => 'Fabric Analysis',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        Prompt::create([
            'name' => 'manual-writing',
            'title' => 'Manual Writing',
            'content' => 'Content',
            'category' => 'writing',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Test categories retrieval
        $categories = $this->promptService->getCategories();

        $this->assertContains('analysis', $categories);
        $this->assertContains('writing', $categories);

        // Test category filtering
        $analysisPrompts = $this->promptService->getPromptsByCategory('analysis');
        $writingPrompts = $this->promptService->getPromptsByCategory('writing');

        $this->assertCount(1, $analysisPrompts);
        $this->assertCount(1, $writingPrompts);
    }

    /** @test */
    public function fabric_get_pattern_details_works_for_all_sources()
    {
        $fabricPrompt = Prompt::create([
            'name' => 'fabric-detail-test',
            'title' => 'Fabric Detail Test',
            'description' => 'A Fabric pattern for testing',
            'content' => 'Fabric content here',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        $manualPrompt = Prompt::create([
            'name' => 'manual-detail-test',
            'title' => 'Manual Detail Test',
            'description' => 'A manual prompt for testing',
            'content' => 'Manual content here',
            'category' => 'writing',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Test retrieving prompt details
        $fabricResult = Prompt::where('name', 'fabric-detail-test')->first();
        $manualResult = Prompt::where('name', 'manual-detail-test')->first();

        $this->assertNotNull($fabricResult);
        $this->assertNotNull($manualResult);

        $this->assertEquals('Fabric Detail Test', $fabricResult->title);
        $this->assertEquals('fabric', $fabricResult->source_type);

        $this->assertEquals('Manual Detail Test', $manualResult->title);
        $this->assertEquals('manual', $manualResult->source_type);
    }

    /** @test */
    public function mcp_tools_handle_missing_prompts_gracefully()
    {
        // Test search functionality for non-existent prompt
        $result = $this->promptService->searchPrompts('non-existent-prompt');

        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function mcp_tools_provide_helpful_suggestions()
    {
        // Create a prompt with similar name
        Prompt::create([
            'name' => 'analyze_claims',
            'title' => 'Analyze Claims',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Search for similar but wrong name should find the close match
        $suggestions = $this->promptService->searchPrompts('analyze_claim');

        $this->assertNotEmpty($suggestions);
        $this->assertEquals('analyze_claims', $suggestions->first()->name);
    }

    /** @test */
    public function composition_tracking_works_via_mcp()
    {
        // Create a test prompt
        $prompt = Prompt::create([
            'name' => 'test-tracking',
            'title' => 'Test Tracking',
            'content' => 'Test: {{INPUT}}',
            'category' => 'test',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Compose prompt (this should track the composition)
        $this->promptService->composePrompt($prompt, 'tracking input', [
            'client' => 'mcp',
            'tool_name' => 'fabric_execute_pattern',
        ]);

        // Verify composition was tracked
        $this->assertDatabaseHas('compositions', [
            'prompt_id' => $prompt->id,
            'input_content' => 'tracking input',
        ]);
    }

    /** @test */
    public function prompts_are_accessible_across_all_sources()
    {
        // Create prompts from different sources
        $fabricPrompt = Prompt::create([
            'name' => 'fabric-accessible',
            'title' => 'Fabric Accessible',
            'content' => 'Fabric: {{INPUT}}',
            'category' => 'test',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        $manualPrompt = Prompt::create([
            'name' => 'manual-accessible',
            'title' => 'Manual Accessible',
            'content' => 'Manual: {{INPUT}}',
            'category' => 'test',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Both should be accessible via the unified service
        $fabricResult = $this->promptService->composePrompt($fabricPrompt, 'test');
        $manualResult = $this->promptService->composePrompt($manualPrompt, 'test');

        $this->assertStringContainsString('Fabric: test', $fabricResult);
        $this->assertStringContainsString('Manual: test', $manualResult);

        // Both should appear in searches
        $allPrompts = Prompt::active()->public()->get();
        $names = $allPrompts->pluck('name')->toArray();

        $this->assertContains('fabric-accessible', $names);
        $this->assertContains('manual-accessible', $names);
    }
}
