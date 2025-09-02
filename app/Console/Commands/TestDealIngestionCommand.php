<?php

namespace App\Console\Commands;

use App\Models\HuntedDeal;
use App\Models\User;
use App\Services\Crawlers\ParsedListing;
use App\Services\DealIngestionService;
use Illuminate\Console\Command;

class TestDealIngestionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:deal-ingestion {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the deal ingestion service with sample data';

    /**
     * Execute the console command.
     */
    public function handle(DealIngestionService $ingestionService): int
    {
        $this->info('Testing Deal Ingestion Service...');
        
        if ($this->option('dry-run')) {
            $this->info('Running in dry-run mode - no data will be saved');
            return 0;
        }
        
        try {
            // Find or create a test user
            $user = User::firstOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User', 'password' => bcrypt('password')]
            );
            
            // Find or create a test hunted deal
            $huntedDeal = HuntedDeal::firstOrCreate(
                ['user_id' => $user->id, 'search_term' => 'test laptop'],
                ['is_active' => true, 'notes' => 'Test hunted deal for ingestion testing']
            );
            
            $this->info("Using hunted deal: {$huntedDeal->search_term} (ID: {$huntedDeal->id})");
            
            // Create sample listings
            $listings = [
                new ParsedListing(
                    externalId: 'TEST001',
                    url: 'https://www.olx.ro/d/oferta/laptop-test-ID123456.html',
                    title: 'Laptop Dell Latitude E7470',
                    priceRaw: '1.500 lei',
                    priceAmount: 1500.00,
                    priceCurrency: 'RON',
                    description: 'Laptop in stare foarte buna, procesor Intel i5',
                    location: 'Bucuresti',
                    sellerName: 'John Doe',
                    sellerUrl: 'https://www.olx.ro/user/johndoe',
                    postedAt: '2024-01-15 10:30:00',
                    imageUrls: ['https://example.com/image1.jpg', 'https://example.com/image2.jpg']
                ),
                new ParsedListing(
                    externalId: 'TEST002',
                    url: 'https://www.olx.ro/d/oferta/laptop-hp-ID789012.html',
                    title: 'Laptop HP ProBook 450',
                    priceRaw: '2.200 lei',
                    priceAmount: 2200.00,
                    priceCurrency: 'RON',
                    description: 'Laptop business, perfect pentru birou',
                    location: 'Cluj-Napoca',
                    sellerName: 'Jane Smith',
                    sellerUrl: 'https://www.olx.ro/user/janesmith',
                    postedAt: '2024-01-15 14:20:00',
                    imageUrls: ['https://example.com/image3.jpg']
                )
            ];
            
            $this->info('Processing ' . count($listings) . ' sample listings...');
            
            // Process listings
            $stats = $ingestionService->processListings($huntedDeal, $listings);
            
            $this->info('Processing completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Listings', $stats['total_listings']],
                    ['New Deals', $stats['new_deals']],
                    ['Updated Deals', $stats['updated_deals']],
                    ['Snapshots Created', $stats['snapshots_created']],
                    ['Errors', $stats['errors']],
                    ['Skipped', $stats['skipped']],
                ]
            );
            
            // Test updating existing listing
            $this->info('Testing update with price change...');
            
            $updatedListing = new ParsedListing(
                externalId: 'TEST001',
                url: 'https://www.olx.ro/d/oferta/laptop-test-ID123456.html',
                title: 'Laptop Dell Latitude E7470',
                priceRaw: '1.300 lei', // Price changed
                priceAmount: 1300.00,  // Price changed
                priceCurrency: 'RON',
                description: 'Laptop in stare foarte buna, procesor Intel i5',
                location: 'Bucuresti',
                sellerName: 'John Doe',
                sellerUrl: 'https://www.olx.ro/user/johndoe',
                postedAt: '2024-01-15 10:30:00',
                imageUrls: ['https://example.com/image1.jpg', 'https://example.com/image2.jpg']
            );
            
            $updateStats = $ingestionService->processListings($huntedDeal, [$updatedListing]);
            
            $this->info('Update test completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Listings', $updateStats['total_listings']],
                    ['New Deals', $updateStats['new_deals']],
                    ['Updated Deals', $updateStats['updated_deals']],
                    ['Snapshots Created', $updateStats['snapshots_created']],
                    ['Errors', $updateStats['errors']],
                    ['Skipped', $updateStats['skipped']],
                ]
            );
            
            // Show processing stats
            $processingStats = $ingestionService->getProcessingStats($huntedDeal);
            $this->info('Hunted Deal Processing Stats:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Deals', $processingStats['total_deals']],
                    ['Recent Deals (24h)', $processingStats['recent_deals_24h']],
                    ['Total Snapshots', $processingStats['total_snapshots']],
                    ['Recent Snapshots (24h)', $processingStats['recent_snapshots_24h']],
                    ['Last Crawled At', $processingStats['last_crawled_at'] ?? 'Never'],
                ]
            );
            
            $this->info('✅ Deal ingestion service test completed successfully!');
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}