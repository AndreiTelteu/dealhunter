<?php

namespace App\Console\Commands;

use App\Services\Crawlers\OlxCrawlerService;
use Illuminate\Console\Command;

/**
 * Test command for the OLX crawler service
 * 
 * This command allows testing the crawler functionality without
 * requiring a full hunted deal setup
 */
class TestCrawlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:test 
                            {search_term : The search term to test}
                            {--pages=1 : Number of pages to crawl}
                            {--dry-run : Show what would be crawled without making requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the OLX crawler service with a search term';

    /**
     * Execute the console command.
     */
    public function handle(OlxCrawlerService $crawler): int
    {
        $searchTerm = $this->argument('search_term');
        $pages = (int) $this->option('pages');
        $dryRun = $this->option('dry-run');

        $this->info("Testing OLX crawler with search term: {$searchTerm}");
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No actual requests will be made');
            $this->table(['Setting', 'Value'], [
                ['Search Term', $searchTerm],
                ['Max Pages', $pages],
                ['MCP Endpoint', config('crawler.mcp_playwright_endpoint')],
                ['Request Delay', config('crawler.request_delay_ms') . 'ms'],
                ['Max Listings', config('crawler.max_listings_per_run')],
            ]);
            return 0;
        }

        try {
            $this->info('Starting crawler test...');
            
            $startTime = microtime(true);
            $listings = $crawler->extractListings($searchTerm, $pages);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("Crawl completed in {$duration}ms");
            $this->info("Found " . count($listings) . " listings");

            if (!empty($listings)) {
                $this->info('Sample listings:');
                
                $sampleListings = array_slice($listings, 0, 3);
                $tableData = [];
                
                foreach ($sampleListings as $index => $listing) {
                    $parsed = $crawler->parseListingData($listing);
                    $tableData[] = [
                        $index + 1,
                        substr($parsed->title, 0, 50) . '...',
                        $parsed->priceRaw ?? 'N/A',
                        $parsed->location ?? 'N/A',
                        $parsed->isValid() ? 'Valid' : 'Invalid'
                    ];
                }
                
                $this->table(
                    ['#', 'Title', 'Price', 'Location', 'Status'],
                    $tableData
                );
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Crawler test failed: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }
}