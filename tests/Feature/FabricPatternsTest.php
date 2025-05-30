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

        $this->assertStringContains('Process this input: Hello World', $result);

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
            'name' => 'analyze_data',
            'title' => 'Analyze Data',
            'description' => 'Analyzes data patterns',
            'content' => 'Analyze the following data',
            'category' => 'analysis',
        ]);

        FabricPattern::create([
            'name' => 'write_essay',
            'title' => 'Write Essay',
            'description' => 'Writes academic essays',
            'content' => 'Write an essay about',
            'category' => 'writing',
        ]);

        $service = app(FabricPatternService::class);

        // Test search by keyword
        $results = $service->searchPatterns('data');
        $this->assertCount(1, $results);
        $this->assertEquals('analyze_data', $results->first()->name);

        // Test category filtering
        $analysisPatterns = $service->getPatternsByCategory('analysis');
        $this->assertCount(1, $analysisPatterns);
        $this->assertEquals('analyze_data', $analysisPatterns->first()->name);
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

        $this->assertStringContains('Analyze: test data', $result);
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
}
