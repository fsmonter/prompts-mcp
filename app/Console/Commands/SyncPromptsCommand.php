<?php

namespace App\Console\Commands;

use App\Services\PromptService;
use Illuminate\Console\Command;

class SyncPromptsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prompts:sync
                            {--source=all : Source to sync (fabric, all, or specific source name)}
                            {--force : Force sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync prompts from all configured sources (Fabric patterns, GitHub repos, etc.)';

    /**
     * Execute the console command.
     */
    public function handle(PromptService $promptService): int
    {
        $source = $this->option('source');

        $this->info("🔄 Starting prompt synchronization for source: {$source}...");

        try {
            $startTime = microtime(true);

            if (! $this->option('force')) {
                $lastSync = cache('prompts_last_sync_'.$source);
                if ($lastSync && $lastSync->gt(now()->subHour())) {
                    $this->warn("⏰ {$source} prompts were synced recently. Use --force to override.");

                    return self::SUCCESS;
                }
            }

            $stats = match ($source) {
                'fabric' => $promptService->syncFabricPatterns(),
                'all' => $promptService->syncAllSources(),
                default => throw new \InvalidArgumentException("Unknown source: {$source}")
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ Synchronization completed in {$duration}ms");

            $this->displaySyncResults($stats, $source);

            $this->info('🎯 Prompts are now available via MCP!');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Synchronization failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Display synchronization results in a formatted table
     */
    private function displaySyncResults(array $stats, string $source): void
    {
        if ($source === 'all' && $this->isMultiSourceResults($stats)) {
            $this->displayMultiSourceResults($stats);
        } else {
            $this->displaySingleSourceResults($stats);
        }
    }

    /**
     * Check if results contain multiple sources
     */
    private function isMultiSourceResults(array $stats): bool
    {
        if (empty($stats)) {
            return false;
        }

        $firstKey = array_key_first($stats);

        return is_array($stats[$firstKey]) &&
               isset($stats[$firstKey]['synced']);
    }

    /**
     * Display results for multiple sources
     */
    private function displayMultiSourceResults(array $stats): void
    {
        $totalSynced = array_sum(array_column($stats, 'synced'));
        $totalUpdated = array_sum(array_column($stats, 'updated'));
        $totalFailed = array_sum(array_column($stats, 'failed'));

        $this->table(
            ['Source', 'Synced', 'Updated', 'Failed'],
            collect($stats)->map(fn ($stat, $sourceName) => [
                $sourceName,
                $stat['synced'] ?? 0,
                $stat['updated'] ?? 0,
                $stat['failed'] ?? 0,
            ])->toArray()
        );

        $this->table(
            ['Total', 'Count'],
            [
                ['Synced', $totalSynced],
                ['Updated', $totalUpdated],
                ['Failed', $totalFailed],
            ]
        );

        if ($totalFailed > 0) {
            $this->warn("⚠️  {$totalFailed} prompts failed to sync. Check logs for details.");
        }
    }

    /**
     * Display results for a single source
     */
    private function displaySingleSourceResults(array $stats): void
    {
        $synced = $stats['synced'] ?? 0;
        $updated = $stats['updated'] ?? 0;
        $failed = $stats['failed'] ?? 0;

        $this->table(
            ['Metric', 'Count'],
            [
                ['Synced', $synced],
                ['Updated', $updated],
                ['Failed', $failed],
            ]
        );

        if ($failed > 0) {
            $this->warn("⚠️  {$failed} prompts failed to sync. Check logs for details.");
        }
    }
}
