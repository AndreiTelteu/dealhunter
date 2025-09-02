<?php

namespace App\Providers;

use App\Services\Crawlers\OlxCrawlerService;
use App\Services\PriceParserService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for crawler-related services
 */
class CrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OlxCrawlerService::class, function ($app) {
            return new OlxCrawlerService(
                $app->make(PriceParserService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}