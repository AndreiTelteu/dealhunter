<?php

namespace Tests\Feature\Crawlers;

use App\Services\Crawlers\CrawlerException;
use App\Services\Crawlers\Mcp\PlaywrightMcpClient;
use App\Services\Crawlers\OlxCrawlerService;
use App\Services\PriceParserService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
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
        $visitedUrls = [];
        $mcp->expects($this->once())->method('ensureInitialized');
        $mcp->expects($this->exactly(3))->method('navigate')->willReturnCallback(function (string $url) use (&$visitedUrls): array {
            $visitedUrls[] = $url;

            return [];
        });
        $mcp->expects($this->once())->method('waitForTime')->with(1);
        $mcp->expects($this->once())->method('closeSession');
        $mcp->method('evaluate')->willReturnOnConsecutiveCalls(
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
        $this->assertSame('https://www.olx.ro/oferte/q-laptop/', $visitedUrls[0]);
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

    public function test_it_prefers_olx_schema_sku_and_removes_tracking_parameters_from_the_canonical_url(): void
    {
        $crawler = new OlxCrawlerService(app(PriceParserService::class), $this->createMock(PlaywrightMcpClient::class));

        $listing = $crawler->parseListingData([
            'external_id' => '307467347',
            'url' => 'https://www.olx.ro/d/oferta/sapphire-radeon-rx-7900-xtx-nitro-vapor-x-24gb-gddr6-384-bit-IDkO6iL.html?search_reason=search%7Corganic',
            'title' => 'Sapphire Radeon RX 7900 XTX',
        ]);

        $this->assertSame('307467347', $listing->externalId);
        $this->assertSame('https://www.olx.ro/d/oferta/sapphire-radeon-rx-7900-xtx-nitro-vapor-x-24gb-gddr6-384-bit-IDkO6iL.html', $listing->url);
    }

    public function test_it_uses_the_alphanumeric_olx_url_id_when_the_schema_sku_is_unavailable(): void
    {
        $crawler = new OlxCrawlerService(app(PriceParserService::class), $this->createMock(PlaywrightMcpClient::class));

        $listing = $crawler->parseListingData([
            'url' => 'https://www.olx.ro/d/oferta/sapphire-radeon-rx-7900-xtx-nitro-vapor-x-24gb-gddr6-384-bit-IDkO6iL.html?search_reason=search%7Corganic',
            'title' => 'Sapphire Radeon RX 7900 XTX',
        ]);

        $this->assertSame('kO6iL', $listing->externalId);
    }

    public function test_it_preserves_full_descriptions_and_all_detail_images(): void
    {
        config()->set([
            'crawler.enabled' => true,
            'crawler.terms_acknowledged' => true,
            'crawler.allowed_windows' => '',
            'crawler.request_delay_ms' => 0,
            'crawler.burst_limit' => 100,
        ]);

        $mcp = $this->createMock(PlaywrightMcpClient::class);
        $mcp->expects($this->once())->method('ensureInitialized');
        $mcp->expects($this->exactly(2))->method('navigate')->willReturn([]);
        $mcp->expects($this->once())->method('closeSession');
        $mcp->method('evaluate')->willReturnOnConsecutiveCalls(
            [['title' => 'Listing', 'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html', 'price_raw' => '100 lei', 'location' => 'Bucuresti', 'image_urls' => ['https://frankfurt.apollo.olxcdn.com/card.jpg']]],
            ['listing'],
            false,
            [
                'description' => "Descriere completă\ncu două paragrafe.",
                'image_urls' => [
                    'https://frankfurt.apollo.olxcdn.com/one.jpg',
                    'https://frankfurt.apollo.olxcdn.com/two.jpg',
                    'https://frankfurt.apollo.olxcdn.com/three.jpg',
                    'https://frankfurt.apollo.olxcdn.com/four.jpg',
                ],
            ],
        );

        $listings = (new OlxCrawlerService(app(PriceParserService::class), $mcp))->extractListings('laptop', 1);
        $listing = (new OlxCrawlerService(app(PriceParserService::class), $this->createMock(PlaywrightMcpClient::class)))->parseListingData($listings[0]);

        $this->assertSame("Descriere completă\ncu două paragrafe.", $listing->description);
        $this->assertSame([
            'https://frankfurt.apollo.olxcdn.com/one.jpg',
            'https://frankfurt.apollo.olxcdn.com/two.jpg',
            'https://frankfurt.apollo.olxcdn.com/three.jpg',
            'https://frankfurt.apollo.olxcdn.com/four.jpg',
            'https://frankfurt.apollo.olxcdn.com/card.jpg',
        ], $listing->imageUrls);
    }
}
