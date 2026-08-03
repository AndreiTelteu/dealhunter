<?php

namespace Tests\Feature;

use App\Services\Crawlers\CrawlerException;
use App\Services\Crawlers\Mcp\PlaywrightMcpClient;
use App\Services\SystemHealthService;
use Tests\TestCase;

class SystemHealthServiceTest extends TestCase
{
    public function test_it_maps_an_invalid_mcp_endpoint_to_a_critical_health_check(): void
    {
        config()->set('crawler.mcp_playwright_endpoint', 'https://mcp.test/not-mcp');

        $health = $this->healthService()->checkMcpConnection();

        $this->assertSame('critical', $health->status);
        $this->assertSame('MCP endpoint not configured', $health->message);
    }

    public function test_it_maps_mcp_connection_errors_to_a_critical_health_check(): void
    {
        config()->set('crawler.mcp_playwright_endpoint', 'https://mcp.test/mcp');

        $mcp = $this->createMock(PlaywrightMcpClient::class);
        $mcp->expects($this->once())->method('healthCheck')->willThrowException(new CrawlerException('Connection refused'));
        $this->app->instance(PlaywrightMcpClient::class, $mcp);

        $health = $this->healthService()->checkMcpConnection();

        $this->assertSame('critical', $health->status);
        $this->assertSame('MCP connection failed: Connection refused', $health->message);
    }

    private function healthService(): SystemHealthService
    {
        return new class extends SystemHealthService
        {
            protected function recordHealthCheck(string $component, string $status, string $message, ?int $responseTime = null, array $details = []): \App\Models\SystemHealth
            {
                return new \App\Models\SystemHealth([
                    'component' => $component,
                    'status' => $status,
                    'message' => $message,
                    'response_time_ms' => $responseTime,
                    'details' => $details,
                ]);
            }
        };
    }
}
