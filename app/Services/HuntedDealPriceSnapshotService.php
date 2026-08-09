<?php

namespace App\Services;

use App\Models\HuntedDeal;
use App\Models\HuntedDealPriceSnapshot;

/**
 * Captures one aggregate market reading for a saved search after a successful crawl.
 */
class HuntedDealPriceSnapshotService
{
    public function capture(HuntedDeal $huntedDeal): ?HuntedDealPriceSnapshot
    {
        $stats = $huntedDeal->deals()
            ->where('matches_intent', true)
            ->whereNotNull('price_amount')
            ->selectRaw('AVG(price_amount) as average_price, MIN(price_amount) as min_price, MAX(price_amount) as max_price, COUNT(*) as deals_count, MAX(price_currency) as price_currency')
            ->first();

        if (! $stats || (int) $stats->deals_count === 0) {
            return null;
        }

        return $huntedDeal->priceSnapshots()->create([
            'average_price' => round((float) $stats->average_price, 2),
            'min_price' => $stats->min_price,
            'max_price' => $stats->max_price,
            'deals_count' => $stats->deals_count,
            'price_currency' => $stats->price_currency ?? 'RON',
            'captured_at' => now(),
        ]);
    }
}
