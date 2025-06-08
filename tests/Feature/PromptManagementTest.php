<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Services\PromptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptManagementTest extends TestCase
{
    use RefreshDatabase;

    private PromptService $promptService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promptService = $this->app->make(PromptService::class);
    }

    /** @test */
    public function it_can_create_manual_prompts()
    {
        $promptData = [
            'title' => 'Test Marketing Analysis',
            'description' => 'Analyzes marketing copy for effectiveness',
            'content' => 'You are a marketing expert. Analyze the following content: {{INPUT}}',
            'category' => 'analysis',
            'tags' => ['marketing', 'analysis'],
            'is_public' => true,
        ];

        $prompt = $this->promptService->createManualPrompt($promptData);

        $this->assertInstanceOf(Prompt::class, $prompt);
        $this->assertEquals('manual', $prompt->source_type);
        $this->assertEquals('test-marketing-analysis', $prompt->name);
        $this->assertEquals($promptData['title'], $prompt->title);
        $this->assertEquals($promptData['category'], $prompt->category);
        $this->assertEquals($promptData['tags'], $prompt->tags);
        $this->assertTrue($prompt->is_public);
        $this->assertTrue($prompt->is_active);
    }

    /** @test */
    public function it_can_compose_prompts_with_input()
    {
        $prompt = Prompt::create([
            'name' => 'test-prompt',
            'title' => 'Test Prompt',
            'content' => 'Analyze this: {{INPUT}}',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        $inputContent = 'This is test content to analyze.';
        $composedContent = $this->promptService->composePrompt($prompt, $inputContent);

        $this->assertEquals('Analyze this: This is test content to analyze.', $composedContent);

        // Check that composition was tracked
        $this->assertDatabaseHas('compositions', [
            'prompt_id' => $prompt->id,
            'input_content' => $inputContent,
            'composed_content' => $composedContent,
        ]);
    }

    /** @test */
    public function it_can_search_prompts_across_sources()
    {
        // Create test prompts from different sources
        Prompt::create([
            'name' => 'fabric-analyze',
            'title' => 'Fabric Analysis Pattern',
            'content' => 'Fabric content',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        Prompt::create([
            'name' => 'custom-analyze',
            'title' => 'Custom Analysis Tool',
            'content' => 'Custom content',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        $results = $this->promptService->searchPrompts('analysis');

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('source_type', 'fabric'));
        $this->assertTrue($results->contains('source_type', 'manual'));
    }

    /** @test */
    public function it_can_get_prompts_by_category()
    {
        Prompt::create([
            'name' => 'test-analysis',
            'title' => 'Test Analysis',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        Prompt::create([
            'name' => 'test-writing',
            'title' => 'Test Writing',
            'content' => 'Content',
            'category' => 'writing',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        $analysisPrompts = $this->promptService->getPromptsByCategory('analysis');
        $writingPrompts = $this->promptService->getPromptsByCategory('writing');

        $this->assertCount(1, $analysisPrompts);
        $this->assertCount(1, $writingPrompts);
        $this->assertEquals('analysis', $analysisPrompts->first()->category);
        $this->assertEquals('writing', $writingPrompts->first()->category);
    }

    /** @test */
    public function web_interface_displays_prompt_library()
    {
        // Create test prompts
        Prompt::create([
            'name' => 'fabric-test',
            'title' => 'Fabric Test Pattern',
            'description' => 'A test Fabric pattern',
            'content' => 'Test content',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        Prompt::create([
            'name' => 'custom-test',
            'title' => 'Custom Test Prompt',
            'description' => 'A test custom prompt',
            'content' => 'Test content',
            'category' => 'writing',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        $response = $this->get(route('prompts.index'));

        $response->assertStatus(200);
        $response->assertSee('Fabric Test Pattern');
        $response->assertSee('Custom Test Prompt');
        $response->assertSee('🔧 Fabric');
        $response->assertSee('✏️ Custom');
    }

    /** @test */
    public function can_create_prompt_via_web_interface()
    {
        $promptData = [
            'title' => 'New Test Prompt',
            'description' => 'A test prompt created via web',
            'content' => 'You are an expert. Analyze: {{INPUT}}',
            'category' => 'analysis',
            'tags' => 'test, analysis',
            'is_public' => true,
        ];

        $response = $this->post(route('prompts.store'), $promptData);

        $response->assertRedirect();

        $this->assertDatabaseHas('prompts', [
            'title' => 'New Test Prompt',
            'source_type' => 'manual',
            'category' => 'analysis',
            'is_public' => true,
        ]);

        $prompt = Prompt::where('title', 'New Test Prompt')->first();
        $this->assertEquals(['test', 'analysis'], $prompt->tags);
    }

    /** @test */
    public function can_filter_prompts_by_source_and_category()
    {
        // Create test data
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

        // Test category filter
        $response = $this->get(route('prompts.index', ['category' => 'analysis']));
        $response->assertSee('Fabric Analysis');
        $response->assertDontSee('Manual Writing');

        // Test source filter
        $response = $this->get(route('prompts.index', ['source' => 'manual']));
        $response->assertSee('Manual Writing');
        $response->assertDontSee('Fabric Analysis');
    }

    /** @test */
    public function can_only_edit_manual_prompts()
    {
        $fabricPrompt = Prompt::create([
            'name' => 'fabric-test',
            'title' => 'Fabric Test',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'fabric',
            'is_active' => true,
            'is_public' => true,
        ]);

        $manualPrompt = Prompt::create([
            'name' => 'manual-test',
            'title' => 'Manual Test',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        // Should be able to edit manual prompt
        $response = $this->get(route('prompts.edit', $manualPrompt));
        $response->assertStatus(200);

        // Should not be able to edit Fabric prompt
        $response = $this->get(route('prompts.edit', $fabricPrompt));
        $response->assertStatus(403);
    }

    /** @test */
    public function prompt_names_are_unique_per_source()
    {
        // Create first prompt
        $prompt1 = $this->promptService->createManualPrompt([
            'title' => 'Test Prompt',
            'content' => 'Content',
            'category' => 'general',
        ]);

        // Create second prompt with same title
        $prompt2 = $this->promptService->createManualPrompt([
            'title' => 'Test Prompt',
            'content' => 'Different content',
            'category' => 'general',
        ]);

        $this->assertEquals('test-prompt', $prompt1->name);
        $this->assertEquals('test-prompt-1', $prompt2->name);
    }

    /** @test */
    public function inactive_prompts_are_hidden_from_public_views()
    {
        $activePrompt = Prompt::create([
            'name' => 'active-test',
            'title' => 'Active Test',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => true,
            'is_public' => true,
        ]);

        $inactivePrompt = Prompt::create([
            'name' => 'inactive-test',
            'title' => 'Inactive Test',
            'content' => 'Content',
            'category' => 'analysis',
            'source_type' => 'manual',
            'is_active' => false,
            'is_public' => true,
        ]);

        $response = $this->get(route('prompts.index'));
        $response->assertSee('Active Test');
        $response->assertDontSee('Inactive Test');

        $searchResults = $this->promptService->searchPrompts('test');
        $this->assertCount(1, $searchResults);
        $this->assertEquals('active-test', $searchResults->first()->name);
    }
}
