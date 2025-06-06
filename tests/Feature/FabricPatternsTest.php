<?php

namespace Tests\Feature;

use App\Models\FabricPattern;
use App\Services\FabricPatternService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FabricPatternsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_fabric_patterns()
    {
        $pattern = FabricPattern::create([
            'name' => 'test_pattern',
            'title' => 'Test Pattern',
            'description' => 'A test pattern for testing',
            'content' => 'This is a test pattern content',
            'category' => 'testing',
            'metadata' => ['author' => 'test'],
            'source_url' => 'http://example.com/test.md',
            'source_hash' => hash('sha256', 'test content'),
            'synced_at' => now(),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('fabric_patterns', [
            'name' => 'test_pattern',
            'title' => 'Test Pattern',
        ]);

        $this->assertEquals('test_pattern', $pattern->name);
        $this->assertEquals('testing', $pattern->category);
        $this->assertTrue($pattern->is_active);
    }

    /** @test */
    public function it_can_execute_patterns()
    {
        $pattern = FabricPattern::create([
            'name' => 'test_pattern',
            'title' => 'Test Pattern',
            'content' => 'Process this input: {{INPUT}}',
            'category' => 'testing',
        ]);

        $service = app(FabricPatternService::class);
        $result = $service->executePattern($pattern, 'Hello World');

        $this->assertStringContainsString('Process this input: Hello World', $result);

        // Verify execution was logged
        $this->assertDatabaseHas('pattern_executions', [
            'fabric_pattern_id' => $pattern->id,
            'input_content' => 'Hello World',
        ]);
    }

    /** @test */
    public function it_can_search_patterns()
    {
        FabricPattern::create([
            'name' => 'analyze_test',
            'title' => 'Analyze Test Pattern',
            'description' => 'This pattern analyzes test data',
            'content' => 'Analyze this: {{INPUT}}',
            'category' => 'analysis',
        ]);

        FabricPattern::create([
            'name' => 'write_report',
            'title' => 'Write Report Pattern',
            'description' => 'This pattern writes reports',
            'content' => 'Write a report about: {{INPUT}}',
            'category' => 'writing',
        ]);

        $service = app(FabricPatternService::class);

        // Search by name
        $results = $service->searchPatterns('analyze');
        $this->assertCount(1, $results);
        $this->assertEquals('analyze_test', $results->first()->name);

        // Search by category
        $writingPatterns = $service->getPatternsByCategory('writing');
        $this->assertCount(1, $writingPatterns);
        $this->assertEquals('write_report', $writingPatterns->first()->name);
    }

    /** @test */
    public function it_can_get_pattern_categories()
    {
        FabricPattern::create([
            'name' => 'pattern1',
            'title' => 'Pattern 1',
            'content' => 'Content 1',
            'category' => 'analysis',
        ]);

        FabricPattern::create([
            'name' => 'pattern2',
            'title' => 'Pattern 2',
            'content' => 'Content 2',
            'category' => 'writing',
        ]);

        $service = app(FabricPatternService::class);
        $categories = $service->getCategories();

        $this->assertContains('analysis', $categories);
        $this->assertContains('writing', $categories);
        $this->assertCount(2, $categories);
    }

    /** @test */
    public function it_handles_pattern_content_placeholders()
    {
        $pattern = FabricPattern::create([
            'name' => 'test_pattern',
            'title' => 'Test Pattern',
            'content' => 'Analyze: {INPUT}',
            'category' => 'testing',
        ]);

        $service = app(FabricPatternService::class);
        $result = $service->executePattern($pattern, 'test data');

        $this->assertStringContainsString('Analyze: test data', $result);
    }

    /** @test */
    public function pattern_scopes_work_correctly()
    {
        $activePattern = FabricPattern::create([
            'name' => 'active_pattern',
            'title' => 'Active Pattern',
            'content' => 'Active content',
            'category' => 'testing',
            'is_active' => true,
        ]);

        $inactivePattern = FabricPattern::create([
            'name' => 'inactive_pattern',
            'title' => 'Inactive Pattern',
            'content' => 'Inactive content',
            'category' => 'testing',
            'is_active' => false,
        ]);

        // Test active scope
        $activePatterns = FabricPattern::active()->get();
        $this->assertCount(1, $activePatterns);
        $this->assertEquals('active_pattern', $activePatterns->first()->name);

        // Test category scope
        $testingPatterns = FabricPattern::category('testing')->get();
        $this->assertCount(2, $testingPatterns);
    }

    /** @test */
    public function it_can_extract_pattern_name_from_path()
    {
        $service = app(FabricPatternService::class);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractPatternNameFromPath');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'patterns/analyze_claims/system.md');
        $this->assertEquals('analyze_claims', $result);

        $result = $method->invoke($service, 'patterns/create_summary/system.md');
        $this->assertEquals('create_summary', $result);
    }

    /** @test */
    public function it_can_process_pattern_data()
    {
        $service = app(FabricPatternService::class);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('processPatternData');
        $method->setAccessible(true);

        $content = "---\ntitle: Test Pattern\ncategory: testing\n---\n\nThis is a test pattern content.";

        $result = $method->invoke($service, 'test_pattern', $content);

        $this->assertEquals('test_pattern', $result['name']);
        $this->assertEquals('Test Pattern', $result['title']);
        $this->assertEquals('testing', $result['category']);
        $this->assertStringContainsString('This is a test pattern content.', $result['content']);
        $this->assertEquals(['title' => 'Test Pattern', 'category' => 'testing'], $result['metadata']);
    }

    /** @test */
    public function it_can_infer_software_engineering_categories()
    {
        $service = app(FabricPatternService::class);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('inferCategory');
        $method->setAccessible(true);

        // Test software architecture patterns
        $this->assertEquals('software_architecture', $method->invoke($service, 'analyze_architecture'));
        $this->assertEquals('software_architecture', $method->invoke($service, 'create_microservice'));
        $this->assertEquals('software_architecture', $method->invoke($service, 'review_system_design'));
        $this->assertEquals('software_architecture', $method->invoke($service, 'infrastructure_planning'));

        // Test software design patterns
        $this->assertEquals('software_design', $method->invoke($service, 'design_api'));
        $this->assertEquals('software_design', $method->invoke($service, 'database_schema'));
        $this->assertEquals('software_design', $method->invoke($service, 'create_design_pattern'));

        // Test software engineering patterns
        $this->assertEquals('software_engineering', $method->invoke($service, 'software_practices'));
        $this->assertEquals('software_engineering', $method->invoke($service, 'engineering_workflow'));

        // Test that existing patterns still work
        $this->assertEquals('analysis', $method->invoke($service, 'analyze_claims'));
        $this->assertEquals('coding', $method->invoke($service, 'code_review'));
    }

    /** @test */
    public function it_includes_categories_list_tool_in_toolkit()
    {
        $toolkit = app(\App\Mcp\FabricPatternsToolkit::class);
        $tools = $toolkit->getTools();

        // Verify the categories list tool is included
        $categoriesListTool = $tools->firstWhere('name', 'fabric_list_categories');
        $this->assertNotNull($categoriesListTool, 'Categories list tool should exist');

        // Verify it has the correct description
        $this->assertEquals('fabric_list_categories', $categoriesListTool->name);
        $this->assertStringContainsString('Get a list of all available Fabric pattern categories', $categoriesListTool->description);

        // Verify all expected tools are present
        $expectedTools = [
            'fabric_execute_pattern',
            'fabric_list_patterns_by_category',
            'fabric_list_categories',
            'fabric_search_patterns',
            'fabric_get_pattern_details',
            'fabric_list_all_patterns',
        ];

        foreach ($expectedTools as $expectedTool) {
            $tool = $tools->firstWhere('name', $expectedTool);
            $this->assertNotNull($tool, "Tool '{$expectedTool}' should exist");
        }
    }
}
