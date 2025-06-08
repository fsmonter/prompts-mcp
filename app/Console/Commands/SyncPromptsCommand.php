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
                'fabric' => $promptService->syncFabricPatterns(), // Backward compatibility
                'all' => $promptService->syncAllSources(),
                default => throw new \InvalidArgumentException("Unknown source: {$source}")
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("✅ Synchronization completed in {$duration}ms");

            if ($source === 'all' && is_array($stats) && isset($stats[array_key_first($stats)]['synced'])) {
                // Handle multi-source stats
                $totalSynced = array_sum(array_column($stats, 'synced'));
                $totalUpdated = array_sum(array_column($stats, 'updated'));
                $totalFailed = array_sum(array_column($stats, 'failed'));

                $this->table(
                    ['Source', 'Synced', 'Updated', 'Failed'],
                    collect($stats)->map(fn ($stat, $sourceName) => [
                        $sourceName,
                        $stat['synced'],
                        $stat['updated'] ?? 0,
                        $stat['failed'],
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
            } else {
                // Handle single source stats
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Synced', $stats['synced']],
                        ['Updated', $stats['updated'] ?? 0],
                        ['Failed', $stats['failed']],
                    ]
                );

                if ($stats['failed'] > 0) {
                    $this->warn("⚠️  {$stats['failed']} prompts failed to sync. Check logs for details.");
                }
            }

            $this->info('🎯 Prompts are now available via MCP!');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Synchronization failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
