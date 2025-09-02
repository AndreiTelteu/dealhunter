<?php

namespace App\Console\Commands;

use App\Models\HuntedDeal;
use App\Services\Crawlers\OlxCrawlerService;
use App\Services\DealIngestionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled command for crawling OLX deals
 * 
 * This command processes all active hunted deals, extracts listings,
 * and updates the database with new deals and snapshots
 */
class CrawlDealsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deals:crawl 
                            {--dry-run : Show what would be crawled without making actual requests}
                            {--hunted-deal= : Process only a specific hunted deal ID}
                            {--max-deals= : Maximum number of hunted deals to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl OLX Romania for active hunted deals and update database';

    /**
     * Execute the console command.
     */
    public function handle(OlxCrawlerService $crawler, DealIngestionService $ingestion): int
    {
        $startTime = microtime(true);
        $isDryRun = $this->option('dry-run');
        $specificHuntedDealId = $this->option('hunted-deal');
        $maxDeals = $this->option('max-deals') ? (int) $this->option('max-deals') : null;
        
        $this->info('Starting OLX deals crawl...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual crawling or database updates will be performed');
        }
        
        // Initialize statistics
        $stats = [
            'hunted_deals_processed' => 0,
            'hunted_deals_failed' => 0,
            'total_listings_found' => 0,
            'new_deals_created' => 0,
            'deals_updated' => 0,
            'snapshots_created' => 0,
            'total_errors' => 0,
            'start_time' => $startTime,
            'errors' => []
        ];
        
        try {
            // Get active hunted deals to process
            $huntedDeals = $this->getHuntedDealsToProcess($specificHuntedDealId, $maxDeals);
            
            if ($huntedDeals->isEmpty()) {
                $this->info('No active hunted deals found to process');
                return 0;
            }
            
            $this->info("Found {$huntedDeals->count()} hunted deal(s) to process");
            
            if ($isDryRun) {
                $this->showDryRunInfo($huntedDeals);
                return 0;
            }
            
            // Process each hunted deal with error isolation
            foreach ($huntedDeals as $huntedDeal) {
                $dealStats = $this->processHuntedDeal($huntedDeal, $crawler, $ingestion);
                $this->mergeStats($stats, $dealStats);
                
                // Update last_crawled_at timestamp after successful processing
                if ($dealStats['success']) {
                    $huntedDeal->update(['last_crawled_at' => Carbon::now()]);
                }
            }
            
            // Log final statistics
            $this->logCrawlResults($stats);
            $this->displayResults($stats);
            
            return $stats['hunted_deals_failed'] > 0 ? 1 : 0;
            
        } catch (\Throwable $e) {
            $this->error('Critical error during crawl operation: ' . $e->getMessage());
            
            Log::channel('crawler')->error('Critical crawl failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'stats' => $stats
            ]);
            
            return 1;
        }
    }
    
    /**
     * Get hunted deals to process based on options
     * 
     * @param string|null $specificId
     * @param int|null $maxDeals
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getHuntedDealsToProcess(?string $specificId, ?int $maxDeals)
    {
        $query = HuntedDeal::with('user')
            ->where('is_active', true);
        
        if ($specificId) {
            $query->where('id', $specificId);
        }
        
        if ($maxDeals) {
            $query->limit($maxDeals);
        }
        
        // Order by last_crawled_at to prioritize least recently crawled
        $query->orderBy('last_crawled_at', 'asc')
              ->orderBy('created_at', 'asc');
        
        return $query->get();
    }
    
    /**
     * Show dry run information
     * 
     * @param \Illuminate\Database\Eloquent\Collection $huntedDeals
     * @return void
     */
    private function showDryRunInfo($huntedDeals): void
    {
        $this->info('Hunted deals that would be processed:');
        
        $tableData = [];
        foreach ($huntedDeals as $huntedDeal) {
            $tableData[] = [
                $huntedDeal->id,
                $huntedDeal->search_term,
                $huntedDeal->user->name ?? 'Unknown',
                $huntedDeal->last_crawled_at ? $huntedDeal->last_crawled_at->diffForHumans() : 'Never',
                $huntedDeal->deals()->count()
            ];
        }
        
        $this->table(
            ['ID', 'Search Term', 'User', 'Last Crawled', 'Total Deals'],
            $tableData
        );
        
        $this->info('Configuration:');
        $this->table(['Setting', 'Value'], [
            ['MCP Endpoint', config('crawler.mcp_playwright_endpoint')],
            ['Request Delay', config('crawler.request_delay_ms') . 'ms'],
            ['Max Pages per Search', config('crawler.max_pages_per_search')],
            ['Max Listings per Run', config('crawler.max_listings_per_run')],
        ]);
    }
    
    /**
     * Process a single hunted deal with error isolation
     * 
     * @param HuntedDeal $huntedDeal
     * @param OlxCrawlerService $crawler
     * @param DealIngestionService $ingestion
     * @return array
     */
    private function processHuntedDeal(
        HuntedDeal $huntedDeal, 
        OlxCrawlerService $crawler, 
        DealIngestionService $ingestion
    ): array {
        $dealStartTime = microtime(true);
        $dealStats = [
            'success' => false,
            'hunted_deal_id' => $huntedDeal->id,
            'search_term' => $huntedDeal->search_term,
            'listings_found' => 0,
            'new_deals' => 0,
            'updated_deals' => 0,
            'snapshots_created' => 0,
            'errors' => 0,
            'duration_ms' => 0,
            'error_messages' => []
        ];
        
        try {
            $this->info("Processing hunted deal #{$huntedDeal->id}: '{$huntedDeal->search_term}'");
            
            // Crawl the hunted deal
            $crawlResult = $crawler->crawlHuntedDeal($huntedDeal);
            
            $dealStats['listings_found'] = $crawlResult->validListingsExtracted;
            $dealStats['duration_ms'] = $crawlResult->durationMs;
            
            if (!$crawlResult->isSuccessful() && !$crawlResult->hasPartialSuccess()) {
                $dealStats['error_messages'] = $crawlResult->errors;
                $dealStats['errors'] = count($crawlResult->errors);
                
                $this->warn("  Failed to crawl listings: " . implode(', ', $crawlResult->errors));
                
                Log::channel('crawler')->warning('Hunted deal crawl failed', [
                    'hunted_deal_id' => $huntedDeal->id,
                    'search_term' => $huntedDeal->search_term,
                    'errors' => $crawlResult->errors,
                    'duration_ms' => $crawlResult->durationMs
                ]);
                
                return $dealStats;
            }
            
            if (!empty($crawlResult->errors)) {
                $this->warn("  Crawl completed with warnings: " . implode(', ', $crawlResult->errors));
            }
            
            // Process the listings
            if (!empty($crawlResult->getValidListings())) {
                $ingestionStats = $ingestion->processListings($huntedDeal, $crawlResult->getValidListings());
                
                $dealStats['new_deals'] = $ingestionStats['new_deals'];
                $dealStats['updated_deals'] = $ingestionStats['updated_deals'];
                $dealStats['snapshots_created'] = $ingestionStats['snapshots_created'];
                $dealStats['errors'] += $ingestionStats['errors'];
                
                $this->info("  Found {$dealStats['listings_found']} listings, " .
                           "created {$dealStats['new_deals']} new deals, " .
                           "updated {$dealStats['updated_deals']} existing deals, " .
                           "created {$dealStats['snapshots_created']} snapshots");
                
                if ($ingestionStats['errors'] > 0) {
                    $this->warn("  Encountered {$ingestionStats['errors']} ingestion errors");
                }
            } else {
                $this->info("  No valid listings found");
            }
            
            $dealStats['success'] = true;
            
            Log::channel('crawler')->info('Hunted deal processed successfully', [
                'hunted_deal_id' => $huntedDeal->id,
                'search_term' => $huntedDeal->search_term,
                'stats' => $dealStats
            ]);
            
        } catch (\Throwable $e) {
            $dealStats['error_messages'][] = $e->getMessage();
            $dealStats['errors']++;
            
            $this->error("  Error processing hunted deal: " . $e->getMessage());
            
            Log::channel('crawler')->error('Hunted deal processing failed', [
                'hunted_deal_id' => $huntedDeal->id,
                'search_term' => $huntedDeal->search_term,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        $dealStats['duration_ms'] = (microtime(true) - $dealStartTime) * 1000;
        
        return $dealStats;
    }
    
    /**
     * Merge deal statistics into overall statistics
     * 
     * @param array &$stats
     * @param array $dealStats
     * @return void
     */
    private function mergeStats(array &$stats, array $dealStats): void
    {
        $stats['hunted_deals_processed']++;
        
        if ($dealStats['success']) {
            $stats['total_listings_found'] += $dealStats['listings_found'];
            $stats['new_deals_created'] += $dealStats['new_deals'];
            $stats['deals_updated'] += $dealStats['updated_deals'];
            $stats['snapshots_created'] += $dealStats['snapshots_created'];
        } else {
            $stats['hunted_deals_failed']++;
        }
        
        $stats['total_errors'] += $dealStats['errors'];
        
        if (!empty($dealStats['error_messages'])) {
            $stats['errors'] = array_merge($stats['errors'], $dealStats['error_messages']);
        }
    }
    
    /**
     * Log comprehensive crawl results
     * 
     * @param array $stats
     * @return void
     */
    private function logCrawlResults(array $stats): void
    {
        $duration = (microtime(true) - $stats['start_time']) * 1000;
        
        $logData = [
            'crawl_completed_at' => Carbon::now()->toISOString(),
            'total_duration_ms' => round($duration, 2),
            'hunted_deals_processed' => $stats['hunted_deals_processed'],
            'hunted_deals_failed' => $stats['hunted_deals_failed'],
            'total_listings_found' => $stats['total_listings_found'],
            'new_deals_created' => $stats['new_deals_created'],
            'deals_updated' => $stats['deals_updated'],
            'snapshots_created' => $stats['snapshots_created'],
            'total_errors' => $stats['total_errors'],
            'success_rate' => $stats['hunted_deals_processed'] > 0 
                ? round((($stats['hunted_deals_processed'] - $stats['hunted_deals_failed']) / $stats['hunted_deals_processed']) * 100, 2) 
                : 0,
            'listings_per_second' => $duration > 0 
                ? round($stats['total_listings_found'] / ($duration / 1000), 2) 
                : 0
        ];
        
        if ($stats['hunted_deals_failed'] > 0 || $stats['total_errors'] > 0) {
            Log::channel('crawler')->warning('Crawl completed with errors', $logData);
        } else {
            Log::channel('crawler')->info('Crawl completed successfully', $logData);
        }
        
        // Log individual errors if any
        if (!empty($stats['errors'])) {
            Log::channel('crawler')->error('Crawl errors summary', [
                'error_count' => count($stats['errors']),
                'errors' => $stats['errors']
            ]);
        }
    }
    
    /**
     * Display final results to console
     * 
     * @param array $stats
     * @return void
     */
    private function displayResults(array $stats): void
    {
        $duration = (microtime(true) - $stats['start_time']) * 1000;
        
        $this->info('');
        $this->info('=== Crawl Results ===');
        
        $resultData = [
            ['Hunted Deals Processed', $stats['hunted_deals_processed']],
            ['Hunted Deals Failed', $stats['hunted_deals_failed']],
            ['Total Listings Found', $stats['total_listings_found']],
            ['New Deals Created', $stats['new_deals_created']],
            ['Existing Deals Updated', $stats['deals_updated']],
            ['Snapshots Created', $stats['snapshots_created']],
            ['Total Errors', $stats['total_errors']],
            ['Duration', round($duration, 2) . 'ms'],
        ];
        
        if ($stats['hunted_deals_processed'] > 0) {
            $successRate = round((($stats['hunted_deals_processed'] - $stats['hunted_deals_failed']) / $stats['hunted_deals_processed']) * 100, 2);
            $resultData[] = ['Success Rate', $successRate . '%'];
        }
        
        if ($duration > 0) {
            $listingsPerSecond = round($stats['total_listings_found'] / ($duration / 1000), 2);
            $resultData[] = ['Listings/Second', $listingsPerSecond];
        }
        
        $this->table(['Metric', 'Value'], $resultData);
        
        if ($stats['hunted_deals_failed'] > 0) {
            $this->warn("Warning: {$stats['hunted_deals_failed']} hunted deal(s) failed to process");
        }
        
        if ($stats['total_errors'] > 0) {
            $this->warn("Warning: {$stats['total_errors']} error(s) encountered during processing");
            
            if ($this->getOutput()->isVerbose() && !empty($stats['errors'])) {
                $this->error('Error details:');
                foreach (array_slice($stats['errors'], 0, 10) as $error) {
                    $this->error('  - ' . $error);
                }
                
                if (count($stats['errors']) > 10) {
                    $this->error('  ... and ' . (count($stats['errors']) - 10) . ' more errors (check logs for full details)');
                }
            }
        }
        
        if ($stats['hunted_deals_failed'] === 0 && $stats['total_errors'] === 0) {
            $this->info('✅ Crawl completed successfully!');
        } elseif ($stats['hunted_deals_processed'] > $stats['hunted_deals_failed']) {
            $this->warn('⚠️  Crawl completed with some issues');
        } else {
            $this->error('❌ Crawl failed');
        }
    }
}