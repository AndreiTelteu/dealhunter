<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Models\HuntedDeal;
use App\Services\HuntedDealPriceHistoryService;
use App\Services\ListingClassificationService;
use App\Services\Crawlers\ParsedListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Reclassifies the persisted deals affected by a saved-search edit and then
 * rebuilds the search-level historical price trace.
 */
class ReclassifyHuntedDealIntent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SCOPE_ALL = 'all';
    public const SCOPE_MATCHING = 'matching';
    public const SCOPE_NON_MATCHING = 'non_matching';

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(
        public readonly int $huntedDealId,
        public readonly string $scope,
        public readonly ?int $triggeredByUserId = null,
    ) {
        if (! in_array($scope, [self::SCOPE_ALL, self::SCOPE_MATCHING, self::SCOPE_NON_MATCHING], true)) {
            throw new \InvalidArgumentException("Unknown reclassification scope: {$scope}");
        }
    }

    public function handle(
        ListingClassificationService $classificationService,
        HuntedDealPriceHistoryService $priceHistoryService,
    ): void {
        $huntedDeal = HuntedDeal::find($this->huntedDealId);
        if ($huntedDeal === null) {
            return;
        }

        $query = $huntedDeal->deals()->orderBy('id');
        if ($this->scope === self::SCOPE_MATCHING) {
            $query->where('matches_intent', true);
        } elseif ($this->scope === self::SCOPE_NON_MATCHING) {
            $query->where('matches_intent', false);
        }

        $processed = 0;
        $errors = 0;

        $query->cursor()->each(function (Deal $deal) use ($huntedDeal, $classificationService, &$processed, &$errors): void {
            try {
                $classification = $classificationService->classify($huntedDeal, $this->listingFromDeal($deal));
                $classificationData = [
                    'matches_intent' => $classification->matchesIntent,
                    'intent_score' => $classification->intentScore,
                    'likely_working' => $classification->likelyWorking,
                    'confidence' => $classification->confidence,
                ];

                $deal->update($classificationData);
                $deal->snapshots()->update($classificationData);
                $processed++;
            } catch (\Throwable $e) {
                $errors++;
                Log::channel('crawler')->warning('Queued deal reclassification failed', [
                    'hunted_deal_id' => $huntedDeal->id,
                    'deal_id' => $deal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        // Rebuild every existing chart point from the reclassified historical deal states.
        $priceHistoryService->rebuild($huntedDeal);

        Log::channel('crawler')->info('Queued hunted-deal reclassification completed', [
            'hunted_deal_id' => $huntedDeal->id,
            'scope' => $this->scope,
            'processed' => $processed,
            'errors' => $errors,
            'triggered_by_user_id' => $this->triggeredByUserId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('crawler')->error('Queued hunted-deal reclassification failed', [
            'hunted_deal_id' => $this->huntedDealId,
            'scope' => $this->scope,
            'triggered_by_user_id' => $this->triggeredByUserId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function listingFromDeal(Deal $deal): ParsedListing
    {
        return new ParsedListing(
            externalId: $deal->external_id,
            url: $deal->url,
            title: $deal->title,
            priceRaw: $deal->price_raw,
            priceAmount: $deal->price_amount !== null ? (float) $deal->price_amount : null,
            priceCurrency: $deal->price_currency,
            description: $deal->description,
            location: $deal->location,
            sellerName: $deal->seller_name,
            sellerUrl: $deal->seller_url,
            postedAt: $deal->posted_at?->toISOString(),
            imageUrls: $deal->image_urls ?? [],
        );
    }
}
