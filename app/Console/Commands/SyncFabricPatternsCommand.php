<?php

namespace App\Console\Commands;

use App\Services\FabricPatternService;
use Illuminate\Console\Command;

class SyncFabricPatternsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fabric:sync-patterns
                            {--force : Force sync even if recently synced}
                            {--pattern= : Sync only a specific pattern}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Fabric patterns from the GitHub repository';

    /**
     * Execute the console command.
     */
    public function handle(FabricPatternService $patternService): int
    {
        $this->info('🔄 Starting Fabric patterns synchronization...');

        try {
            $startTime = microtime(true);

            // Check if we need to force sync
            if (! $this->option('force')) {
                $lastSync = cache('fabric_patterns_last_sync');
                if ($lastSync && $lastSync->gt(now()->subHour())) {
                    $this->warn('⏰ Patterns were synced recently. Use --force to override.');

                    return self::SUCCESS;
                }
            }

            // Sync patterns
            $stats = $patternService->syncPatterns();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ Synchronization completed in {$duration}ms");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Synced', $stats['synced']],
                    ['Updated', $stats['updated']],
                    ['Failed', $stats['failed']],
                ]
            );

            if ($stats['failed'] > 0) {
                $this->warn("⚠️  {$stats['failed']} patterns failed to sync. Check logs for details.");
            }

            $this->info('🎯 Fabric patterns are now available as MCP tools!');

        } catch (\Exception $e) {
            $this->error("❌ Synchronization failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
