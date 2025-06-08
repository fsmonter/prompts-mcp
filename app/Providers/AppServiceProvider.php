<?php

namespace App\Providers;

use App\Mcp\PromptLibraryToolkit;
use App\Services\GitSyncService;
use App\Services\PromptService;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\ServiceProvider;
use Kirschbaum\Loop\Facades\Loop;
use League\CommonMark\CommonMarkConverter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GitSyncService::class, function ($app) {
            return new GitSyncService(
                $app->make(HttpClient::class),
                new CommonMarkConverter
            );
        });

        $this->app->singleton(PromptService::class, function ($app) {
            return new PromptService(
                $app->make(HttpClient::class),
                new CommonMarkConverter,
                $app->make(GitSyncService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Loop::toolkit(
            new PromptLibraryToolkit(
                $this->app->make(PromptService::class)
            )
        );
    }
}
