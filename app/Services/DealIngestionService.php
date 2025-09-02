<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealSnapshot;
use App\Models\HuntedDeal;
use App\Services\Crawlers\ParsedListing;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for ingesting deals and managing snapshots
 * 
 * Handles upserting deals, change detection, and snapshot creation
 * with proper transaction handling for data consistency
 */
class DealIngestionService extends BaseService
{
    protected string $logChannel = 'crawler';
    
    /**
     * Fields that trigger snapshot creation when changed
     */
    private const SIGNIFICANT_FIELDS = [
        'title',
        'price_amount',
        'price_currency',
        'price_raw',
        'description',
        'location',
        'seller_name',
        'seller_url',
        'posted_at'
    ];
    
    /**
     * Process multiple listings for a hunted deal
     * 
     * @param HuntedDeal $huntedDeal
     * @param array $listings Array of ParsedListing objects
     * @return array Processing statistics
     */
    public function processListings(HuntedDeal $huntedDeal, array $listings): array
    {
        return $this->executeWithErrorHandling(
            function () use ($huntedDeal, $listings) {
                $stats = [
                    'total_listings' => count($listings),
                    'new_deals' => 0,
                    'updated_deals' => 0,
                    'snapshots_created' => 0,
                    'errors' => 0,
                    'skipped' => 0
                ];
                
                foreach ($listings as $listing) {
                    try {
                        if (!$listing instanceof ParsedListing) {
                            $this->logWarning('Invalid listing object', [
                                'hunted_deal_id' => $huntedDeal->id,
                                'listing_type' => gettype($listing)
                            ]);
                            $stats['skipped']++;
                            continue;
                        }
                        
                        if (!$listing->isValid()) {
                            $this->logWarning('Invalid listing data', [
                                'hunted_deal_id' => $huntedDeal->id,
                                'external_id' => $listing->externalId,
                                'url' => $listing->url,
                                'title' => $listing->title
                            ]);
                            $stats['skipped']++;
                            continue;
                        }
                        
                        $result = $this->upsertDeal($huntedDeal, $listing);
                        
                        if ($result['is_new']) {
                            $stats['new_deals']++;
                        } else {
                            $stats['updated_deals']++;
                        }
                        
                        if ($result['snapshot_created']) {
                            $stats['snapshots_created']++;
                        }
                        
                    } catch (\Throwable $e) {
                        $this->logError('Failed to process listing', [
                            'hunted_deal_id' => $huntedDeal->id,
                            'external_id' => $listing->externalId ?? 'unknown',
                            'error' => $e->getMessage()
                        ], $e);
                        $stats['errors']++;
                    }
                }
                
                return $stats;
            },
            ['hunted_deal_id' => $huntedDeal->id, 'listings_count' => count($listings)],
            'process_listings'
        );
    }
    
    /**
     * Upsert a deal and manage snapshots
     * 
     * @param HuntedDeal $huntedDeal
     * @param ParsedListing $listing
     * @return array Result with is_new and snapshot_created flags
     */
    public function upsertDeal(HuntedDeal $huntedDeal, ParsedListing $listing): array
    {
        return DB::transaction(function () use ($huntedDeal, $listing) {
            $now = Carbon::now();
            
            // Find existing deal by external_id and hunted_deal_id
            $existingDeal = Deal::where('hunted_deal_id', $huntedDeal->id)
                ->where('external_id', $listing->externalId)
                ->first();
            
            $isNew = $existingDeal === null;
            $snapshotCreated = false;
            
            if ($isNew) {
                // Create new deal
                $deal = $this->createNewDeal($huntedDeal, $listing, $now);
                $snapshotCreated = true; // Always create snapshot for new deals
                
                $this->logInfo('Created new deal', [
                    'deal_id' => $deal->id,
                    'hunted_deal_id' => $huntedDeal->id,
                    'external_id' => $listing->externalId,
                    'title' => $listing->title
                ]);
            } else {
                // Update existing deal
                $hasChanges = $this->hasSignificantChanges($existingDeal, $listing);
                
                if ($hasChanges) {
                    $this->updateDeal($existingDeal, $listing, $now);
                    $this->createSnapshot($existingDeal, $listing, $now);
                    $snapshotCreated = true;
                    
                    $this->logInfo('Updated deal with changes', [
                        'deal_id' => $existingDeal->id,
                        'hunted_deal_id' => $huntedDeal->id,
                        'external_id' => $listing->externalId,
                        'title' => $listing->title
                    ]);
                } else {
                    // No significant changes, just update last_seen_at
                    $existingDeal->update(['last_seen_at' => $now]);
                    
                    $this->logDebug('Updated last_seen_at for unchanged deal', [
                        'deal_id' => $existingDeal->id,
                        'external_id' => $listing->externalId
                    ]);
                }
                
                $deal = $existingDeal;
            }
            
            return [
                'deal' => $deal,
                'is_new' => $isNew,
                'snapshot_created' => $snapshotCreated
            ];
        });
    }
    
    /**
     * Create a new deal with initial snapshot
     * 
     * @param HuntedDeal $huntedDeal
     * @param ParsedListing $listing
     * @param Carbon $timestamp
     * @return Deal
     */
    private function createNewDeal(HuntedDeal $huntedDeal, ParsedListing $listing, Carbon $timestamp): Deal
    {
        $dealData = $this->prepareDealData($huntedDeal, $listing, $timestamp);
        
        $deal = Deal::create($dealData);
        
        // Create initial snapshot
        $this->createSnapshot($deal, $listing, $timestamp);
        
        return $deal;
    }
    
    /**
     * Update existing deal with new data
     * 
     * @param Deal $deal
     * @param ParsedListing $listing
     * @param Carbon $timestamp
     * @return void
     */
    private function updateDeal(Deal $deal, ParsedListing $listing, Carbon $timestamp): void
    {
        $updateData = [
            'title' => $listing->title,
            'price_amount' => $listing->priceAmount,
            'price_currency' => $listing->priceCurrency,
            'price_raw' => $listing->priceRaw,
            'description' => $listing->description,
            'location' => $listing->location,
            'seller_name' => $listing->sellerName,
            'seller_url' => $listing->sellerUrl,
            'posted_at' => $listing->postedAt ? Carbon::parse($listing->postedAt) : null,
            'last_seen_at' => $timestamp,
        ];
        
        $deal->update($updateData);
    }
    
    /**
     * Create a snapshot for the deal
     * 
     * @param Deal $deal
     * @param ParsedListing $listing
     * @param Carbon $timestamp
     * @return DealSnapshot
     */
    private function createSnapshot(Deal $deal, ParsedListing $listing, Carbon $timestamp): DealSnapshot
    {
        $snapshotData = [
            'deal_id' => $deal->id,
            'title' => $listing->title,
            'price_amount' => $listing->priceAmount,
            'price_currency' => $listing->priceCurrency,
            'price_raw' => $listing->priceRaw,
            'description' => $listing->description,
            'image_urls' => $listing->imageUrls,
            'location' => $listing->location,
            'seller_name' => $listing->sellerName,
            'seller_url' => $listing->sellerUrl,
            'posted_at' => $listing->postedAt ? Carbon::parse($listing->postedAt) : null,
            'matches_intent' => $deal->matches_intent,
            'likely_working' => $deal->likely_working,
            'confidence' => $deal->confidence,
            'captured_at' => $timestamp,
        ];
        
        return DealSnapshot::create($snapshotData);
    }
    
    /**
     * Check if listing has significant changes compared to existing deal
     * 
     * @param Deal $deal
     * @param ParsedListing $listing
     * @return bool
     */
    private function hasSignificantChanges(Deal $deal, ParsedListing $listing): bool
    {
        $changes = [];
        
        // Check each significant field
        if ($deal->title !== $listing->title) {
            $changes[] = 'title';
        }
        
        if ($deal->price_amount != $listing->priceAmount) {
            $changes[] = 'price_amount';
        }
        
        if ($deal->price_currency !== $listing->priceCurrency) {
            $changes[] = 'price_currency';
        }
        
        if ($deal->price_raw !== $listing->priceRaw) {
            $changes[] = 'price_raw';
        }
        
        if ($deal->description !== $listing->description) {
            $changes[] = 'description';
        }
        
        if ($deal->location !== $listing->location) {
            $changes[] = 'location';
        }
        
        if ($deal->seller_name !== $listing->sellerName) {
            $changes[] = 'seller_name';
        }
        
        if ($deal->seller_url !== $listing->sellerUrl) {
            $changes[] = 'seller_url';
        }
        
        $listingPostedAt = $listing->postedAt ? Carbon::parse($listing->postedAt) : null;
        if ($deal->posted_at?->toDateTimeString() !== $listingPostedAt?->toDateTimeString()) {
            $changes[] = 'posted_at';
        }
        
        if (!empty($changes)) {
            $this->logDebug('Detected significant changes', [
                'deal_id' => $deal->id,
                'external_id' => $deal->external_id,
                'changed_fields' => $changes
            ]);
        }
        
        return !empty($changes);
    }
    
    /**
     * Prepare deal data from parsed listing
     * 
     * @param HuntedDeal $huntedDeal
     * @param ParsedListing $listing
     * @param Carbon $timestamp
     * @return array
     */
    private function prepareDealData(HuntedDeal $huntedDeal, ParsedListing $listing, Carbon $timestamp): array
    {
        return [
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => $listing->externalId,
            'url' => $listing->url,
            'title' => $listing->title,
            'price_amount' => $listing->priceAmount,
            'price_currency' => $listing->priceCurrency,
            'price_raw' => $listing->priceRaw,
            'description' => $listing->description,
            'location' => $listing->location,
            'seller_name' => $listing->sellerName,
            'seller_url' => $listing->sellerUrl,
            'posted_at' => $listing->postedAt ? Carbon::parse($listing->postedAt) : null,
            'matches_intent' => null, // Will be set by AI classification
            'likely_working' => null, // Will be set by AI classification
            'confidence' => null, // Will be set by AI classification
            'last_seen_at' => $timestamp,
        ];
    }
    
    /**
     * Get processing statistics for a hunted deal
     * 
     * @param HuntedDeal $huntedDeal
     * @return array
     */
    public function getProcessingStats(HuntedDeal $huntedDeal): array
    {
        $totalDeals = $huntedDeal->deals()->count();
        $recentDeals = $huntedDeal->deals()
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();
        
        $totalSnapshots = DealSnapshot::whereIn('deal_id', 
            $huntedDeal->deals()->pluck('id')
        )->count();
        
        $recentSnapshots = DealSnapshot::whereIn('deal_id', 
            $huntedDeal->deals()->pluck('id')
        )->where('captured_at', '>=', Carbon::now()->subDay())
        ->count();
        
        return [
            'total_deals' => $totalDeals,
            'recent_deals_24h' => $recentDeals,
            'total_snapshots' => $totalSnapshots,
            'recent_snapshots_24h' => $recentSnapshots,
            'last_crawled_at' => $huntedDeal->last_crawled_at,
        ];
    }
    
    /**
     * Clean up old snapshots beyond retention period
     * 
     * @param int $retentionDays
     * @return int Number of deleted snapshots
     */
    public function cleanupOldSnapshots(int $retentionDays = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        return $this->executeWithErrorHandling(
            function () use ($cutoffDate) {
                return DealSnapshot::where('captured_at', '<', $cutoffDate)->delete();
            },
            ['cutoff_date' => $cutoffDate->toDateTimeString(), 'retention_days' => $retentionDays],
            'cleanup_old_snapshots'
        );
    }
}