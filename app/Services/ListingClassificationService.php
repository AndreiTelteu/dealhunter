<?php

namespace App\Services;

use App\Models\HuntedDeal;
use App\Services\Crawlers\ParsedListing;

/**
 * Shared application boundary for classifying a listing under a saved search.
 *
 * New crawler listings and later reclassification jobs must use this same path
 * so user-defined search rules are interpreted consistently.
 */
class ListingClassificationService extends BaseService
{
    protected string $logChannel = 'crawler';

    public function __construct(private readonly IntentClassifierService $classifier)
    {
        parent::__construct();
    }

    public function classify(HuntedDeal $huntedDeal, ParsedListing $listing): Classification
    {
        try {
            return $this->classifier->classifyListing($huntedDeal->search_term, $listing, $huntedDeal);
        } catch (\Throwable $e) {
            $this->logWarning('Classification failed, using defaults', [
                'hunted_deal_id' => $huntedDeal->id,
                'external_id' => $listing->externalId,
                'error' => $e->getMessage(),
            ]);

            return new Classification(
                matchesIntent: false,
                likelyWorking: null,
                confidence: 0.0,
                reasoning: 'Classification failed: '.$e->getMessage(),
                intentScore: 0,
            );
        }
    }
}
