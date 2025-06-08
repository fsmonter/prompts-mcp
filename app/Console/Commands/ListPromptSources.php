<?php

namespace App\Console\Commands;

use App\Models\PromptSource;
use Illuminate\Console\Command;

class ListPromptSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prompts:list-sources
                            {--active : Show only active sources}
                            {--with-stats : Include prompt counts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all configured prompt sources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = PromptSource::query();

        if ($this->option('active')) {
            $query->active();
        }

        $sources = $query->orderBy('name')->get();

        if ($sources->isEmpty()) {
            $this->info('📭 No prompt sources configured.');
            $this->line('');
            $this->line('Add a source with: <comment>php artisan prompts:add-source</comment>');

            return self::SUCCESS;
        }

        $headers = ['Name', 'Type', 'Repository', 'Branch', 'Status', 'Auto Sync', 'Last Synced'];

        if ($this->option('with-stats')) {
            $headers[] = 'Prompts';
        }

        $rows = $sources->map(function (PromptSource $source) {
            $row = [
                $source->name,
                $source->type,
                $this->truncateUrl($source->repository_url),
                $source->branch,
                $this->getStatusDisplay($source),
                $source->auto_sync ? '✅' : '❌',
                $source->last_synced_at ? $source->last_synced_at->diffForHumans() : 'Never',
            ];

            if ($this->option('with-stats')) {
                $row[] = $source->prompts()->count();
            }

            return $row;
        })->toArray();

        $this->table($headers, $rows);

        $this->line('');
        $this->line('💡 <comment>Commands:</comment>');
        $this->line('   • Sync all: <info>php artisan prompts:sync --source=all</info>');
        $this->line('   • Add source: <info>php artisan prompts:add-source</info>');

        return self::SUCCESS;
    }

    /**
     * Get status display for source
     */
    private function getStatusDisplay(PromptSource $source): string
    {
        if (! $source->is_active) {
            return '🔴 Inactive';
        }

        return match ($source->sync_status) {
            'completed' => '🟢 Ready',
            'syncing' => '🟡 Syncing',
            'failed' => '🔴 Failed',
            default => '⚪ Pending'
        };
    }

    /**
     * Truncate long URLs for display
     */
    private function truncateUrl(?string $url): string
    {
        if (! $url) {
            return 'N/A';
        }

        if (strlen($url) <= 50) {
            return $url;
        }

        return substr($url, 0, 47).'...';
    }
}
