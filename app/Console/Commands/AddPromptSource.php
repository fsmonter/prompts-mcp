<?php

namespace App\Console\Commands;

use App\Models\PromptSource;
use Illuminate\Console\Command;

class AddPromptSource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prompts:add-source
                            {name : Unique name for the source}
                            {repository : Git repository URL}
                            {--branch=main : Git branch to sync}
                            {--path= : File pattern to sync (default: *.md)}
                            {--file= : Specific file pattern (default: *.md)}
                            {--no-auto-sync : Disable automatic syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new git-based prompt source';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $repository = $this->argument('repository');
        $branch = $this->option('branch');
        $pathPattern = is_array($this->option('path')) ? implode(',', $this->option('path')) : $this->option('path');
        $filePattern = is_array($this->option('file')) ? implode(',', $this->option('file')) : $this->option('file');
        $autoSync = ! $this->option('no-auto-sync');

        // Fallback to defaults if empty
        $pathPattern = $pathPattern ?: '*.md';
        $filePattern = $filePattern ?: '*.md';

        // Check if source already exists
        if (PromptSource::where('name', $name)->exists()) {
            $this->error("❌ Source '{$name}' already exists!");

            return self::FAILURE;
        }

        // Validate repository URL
        if (! filter_var($repository, FILTER_VALIDATE_URL) && ! str_starts_with($repository, 'git@')) {
            $this->error("❌ Invalid repository URL: {$repository}");

            return self::FAILURE;
        }

        try {
            $source = PromptSource::create([
                'name' => $name,
                'type' => 'git',
                'repository_url' => $repository,
                'branch' => $branch,
                'path_pattern' => $pathPattern,
                'file_pattern' => $filePattern,
                'is_active' => true,
                'auto_sync' => $autoSync,
                'metadata' => [
                    'created_via' => 'cli',
                    'created_at' => now()->toISOString(),
                ],
            ]);

            $this->info("✅ Successfully added prompt source: {$name}");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Name', $source->name],
                    ['Type', $source->type],
                    ['Repository', $source->repository_url],
                    ['Branch', $source->branch],
                    ['Path Pattern', $source->path_pattern],
                    ['File Pattern', $source->file_pattern],
                    ['Auto Sync', $source->auto_sync ? 'Yes' : 'No'],
                ]
            );

            if ($this->confirm('Would you like to sync this source now?', true)) {
                $this->call('prompts:sync', ['--source' => 'all']);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to add source: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
