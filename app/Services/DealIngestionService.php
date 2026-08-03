<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealSnapshot;
use App\Models\HuntedDeal;
use App\Services\Crawlers\ParsedListing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for ingesting deals and managing snapshots
 *
 * Handles upserting deals, change detection, and snapshot creation
 * with proper transaction handling for data consistency
 */
class DealIngestionService extends BaseService
{
    protected string $logChannel = 'crawler';

    private IntentClassifierService $classifier;

    public function __construct(IntentClassifierService $classifier)
    {
        parent::__construct();
        $this->classifier = $classifier;
    }

    /**
     * Process multiple listings for a hunted deal
     *
     * @param  array  $listings  Array of ParsedListing objects
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
                    'skipped' => 0,
                ];

                foreach ($listings as $listing) {
                    try {
                        if (! $listing instanceof ParsedListing) {
                            $this->logWarning('Invalid listing object', [
                                'hunted_deal_id' => $huntedDeal->id,
                                'listing_type' => gettype($listing),
                            ]);
                            $stats['skipped']++;

                            continue;
                        }

                        if (! $listing->isValid()) {
                            $this->logWarning('Invalid listing data', [
                                'hunted_deal_id' => $huntedDeal->id,
                                'external_id' => $listing->externalId,
                                'url' => $listing->url,
                                'title' => $listing->title,
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
                            'error' => $e->getMessage(),
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
                    'title' => $listing->title,
                ]);
            } else {
                // Update existing deal
                $hasChanges = $this->hasSignificantChanges($existingDeal, $listing);

                if ($hasChanges) {
                    // Re-classify if there are significant changes
                    $classification = $this->classifyListing($huntedDeal, $listing);
                    $this->updateDeal($existingDeal, $listing, $now, $classification);
                    $this->createSnapshot($existingDeal, $listing, $now);
                    $snapshotCreated = true;

                    $this->logInfo('Updated deal with changes', [
                        'deal_id' => $existingDeal->id,
                        'hunted_deal_id' => $huntedDeal->id,
                        'external_id' => $listing->externalId,
                        'title' => $listing->title,
                        'matches_intent' => $classification->matchesIntent,
                        'likely_working' => $classification->likelyWorking,
                        'confidence' => $classification->confidence,
                    ]);
                } else {
                    // No significant changes, just update last_seen_at
                    $existingDeal->update(['last_seen_at' => $now]);

                    $this->logDebug('Updated last_seen_at for unchanged deal', [
                        'deal_id' => $existingDeal->id,
                        'external_id' => $listing->externalId,
                    ]);
                }

                $deal = $existingDeal;
            }

            return [
                'deal' => $deal,
                'is_new' => $isNew,
                'snapshot_created' => $snapshotCreated,
            ];
        });
    }

    /**
     * Create a new deal with initial snapshot
     */
    private function createNewDeal(HuntedDeal $huntedDeal, ParsedListing $listing, Carbon $timestamp): Deal
    {
        $dealData = $this->prepareDealData($huntedDeal, $listing, $timestamp);

        // Apply AI classification
        $classification = $this->classifyListing($huntedDeal, $listing);
        $dealData['matches_intent'] = $classification->matchesIntent;
        $dealData['likely_working'] = $classification->likelyWorking;
        $dealData['confidence'] = $classification->confidence;

        $deal = Deal::create($dealData);

        // Create initial snapshot
        $this->createSnapshot($deal, $listing, $timestamp);

        return $deal;
    }

    /**
     * Update existing deal with new data
     */
    private function updateDeal(Deal $deal, ParsedListing $listing, Carbon $timestamp, ?Classification $classification = null): void
    {
        $updateData = [
            'url' => $listing->url,
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
            'last_seen_at' => $timestamp,
        ];

        // Add classification data if provided
        if ($classification) {
            $updateData['matches_intent'] = $classification->matchesIntent;
            $updateData['likely_working'] = $classification->likelyWorking;
            $updateData['confidence'] = $classification->confidence;
        }

        $deal->update($updateData);
    }

    /**
     * Create a snapshot for the deal
     */
    private function createSnapshot(Deal $deal, ParsedListing $listing, Carbon $timestamp): DealSnapshot
    {
        $snapshotData = [
            'deal_id' => $deal->id,
            'url' => $listing->url,
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
     */
    private function hasSignificantChanges(Deal $deal, ParsedListing $listing): bool
    {
        $incomingData = $this->prepareDealData($deal->huntedDeal, $listing, $deal->last_seen_at);
        $changes = [];

        foreach (['url', 'title', 'price_amount', 'price_currency', 'price_raw', 'description', 'image_urls', 'location', 'seller_name', 'seller_url'] as $field) {
            if ($this->normalizeComparableValue($deal->getAttribute($field)) !== $this->normalizeComparableValue($incomingData[$field])) {
                $changes[] = $field;
            }
        }

        if ($deal->posted_at?->toDateTimeString() !== ($incomingData['posted_at'])?->toDateTimeString()) {
            $changes[] = 'posted_at';
        }

        if (! empty($changes)) {
            $this->logDebug('Detected significant changes', [
                'deal_id' => $deal->id,
                'external_id' => $deal->external_id,
                'changed_fields' => $changes,
            ]);
        }

        return ! empty($changes);
    }

    /**
     * Normalize values before comparing values sourced from HTML and JSON.
     */
    private function normalizeComparableValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_values(array_map(fn (mixed $item): mixed => $this->normalizeComparableValue($item), $value));
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        return $value;
    }

    /**
     * Classify a listing using AI
     */
    private function classifyListing(HuntedDeal $huntedDeal, ParsedListing $listing): Classification
    {
        try {
            return $this->classifier->classifyListing($huntedDeal->search_term, $listing);
        } catch (\Throwable $e) {
            $this->logWarning('Classification failed, using defaults', [
                'hunted_deal_id' => $huntedDeal->id,
                'external_id' => $listing->externalId,
                'error' => $e->getMessage(),
            ]);

            // Return default classification on error
            return new Classification(
                matchesIntent: false,
                likelyWorking: null,
                confidence: 0.0,
                reasoning: 'Classification failed: '.$e->getMessage()
            );
        }
    }

    /**
     * Prepare deal data from parsed listing
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
            'image_urls' => $listing->imageUrls,
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
}
