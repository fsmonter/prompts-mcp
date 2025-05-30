<?php

namespace App\Providers;

use App\Mcp\FabricPatternsToolkit;
use App\Services\FabricPatternService;
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
        // Bind FabricPatternService
        $this->app->singleton(FabricPatternService::class, function ($app) {
            return new FabricPatternService(
                $app->make(HttpClient::class),
                new CommonMarkConverter
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Fabric patterns toolkit with Laravel Loop
        Loop::toolkit(
            new FabricPatternsToolkit(
                $this->app->make(FabricPatternService::class)
            )
        );
    }
}
