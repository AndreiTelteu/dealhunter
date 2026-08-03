<?php

namespace App\Providers;

use App\Services\Crawlers\Mcp\McpClient;
use App\Services\Crawlers\Mcp\McpSession;
use App\Services\Crawlers\Mcp\PlaywrightMcpClient;
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
        $this->app->singleton(McpSession::class);

        $this->app->singleton(McpClient::class, function ($app) {
            return new McpClient(
                endpoint: config('crawler.mcp_playwright_endpoint'),
                token: config('crawler.mcp_playwright_token', ''),
                options: [
                    'timeout_ms' => config('crawler.timeout_ms'),
                    'init_timeout_ms' => config('crawler.mcp_init_timeout_ms'),
                    'retry_attempts' => config('crawler.max_retry_attempts'),
                    'retry_delay_ms' => config('crawler.retry_delay_ms'),
                    'protocol_version' => config('crawler.mcp_protocol_version'),
                    'user_agent' => config('crawler.user_agent'),
                ],
                session: $app->make(McpSession::class),
            );
        });

        $this->app->singleton(PlaywrightMcpClient::class, fn ($app) => new PlaywrightMcpClient(
            $app->make(McpClient::class),
            $app->make(McpSession::class),
        ));

        $this->app->singleton(OlxCrawlerService::class, function ($app) {
            return new OlxCrawlerService(
                $app->make(PriceParserService::class),
                $app->make(PlaywrightMcpClient::class),
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
