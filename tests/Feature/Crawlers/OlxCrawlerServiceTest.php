<?php

namespace Tests\Feature\Crawlers;

use App\Services\Crawlers\CrawlerException;
use App\Services\Crawlers\Mcp\PlaywrightMcpClient;
use App\Services\Crawlers\OlxCrawlerService;
use App\Services\PriceParserService;
use Tests\TestCase;

class OlxCrawlerServiceTest extends TestCase
{
    public function test_it_extracts_details_and_waits_for_new_page_listings(): void
    {
        config()->set([
            'crawler.enabled' => true,
            'crawler.terms_acknowledged' => true,
            'crawler.require_terms_acknowledgement' => true,
            'crawler.allowed_windows' => '',
            'crawler.request_delay_ms' => 0,
            'crawler.burst_limit' => 100,
            'crawler.max_listings_per_run' => 100,
        ]);

        $mcp = $this->createMock(PlaywrightMcpClient::class);
        $mcp->expects($this->once())->method('ensureInitialized');
        $mcp->expects($this->exactly(3))->method('navigate');
        $mcp->expects($this->once())->method('waitForTime')->with(1);
        $mcp->expects($this->once())->method('closeSession');
        $mcp->method('evaluate')->willReturnOnConsecutiveCalls(
            true,
            [['title' => 'First', 'url' => '/d/oferta/first', 'price_raw' => '100 lei', 'location' => 'Bucuresti']],
            ['first'],
            true,
            ['first'],
            ['second'],
            [['title' => 'Second', 'url' => '/d/oferta/second', 'price_raw' => '200 lei', 'location' => 'Cluj']],
            ['second'],
            false,
            ['description' => 'First detail'],
            ['description' => 'Second detail'],
        );

        $crawler = new OlxCrawlerService(app(PriceParserService::class), $mcp);

        $listings = $crawler->extractListings('laptop', 2);

        $this->assertSame(['First', 'Second'], array_column($listings, 'title'));
        $this->assertSame(['First detail', 'Second detail'], array_column($listings, 'description'));
    }

    public function test_it_enforces_permission_before_each_network_operation(): void
    {
        config()->set([
            'crawler.enabled' => true,
            'crawler.terms_acknowledged' => true,
            'crawler.require_terms_acknowledgement' => true,
            'crawler.allowed_windows' => '',
            'crawler.request_delay_ms' => 0,
            'crawler.burst_limit' => 100,
        ]);

        $mcp = $this->createMock(PlaywrightMcpClient::class);
        $mcp->expects($this->once())->method('ensureInitialized');
        $mcp->expects($this->once())->method('navigate')->willReturnCallback(function (): array {
            config()->set('crawler.enabled', false);

            return [];
        });
        $mcp->expects($this->never())->method('evaluate');
        $mcp->expects($this->once())->method('closeSession');

        $crawler = new OlxCrawlerService(app(PriceParserService::class), $mcp);

        $this->expectException(CrawlerException::class);
        $this->expectExceptionMessage('Crawling is disabled by CRAWLER_ENABLED.');

        $crawler->extractListings('laptop');
    }
}
