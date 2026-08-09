<?php

namespace App\Services;

use App\Models\DealSnapshot;
use App\Models\HuntedDeal;
use App\Models\HuntedDealPriceSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the plotted search-level average price history after the intent
 * classification of a saved search changes.
 */
class HuntedDealPriceHistoryService
{
    public function rebuild(HuntedDeal $huntedDeal): void
    {
        DB::transaction(function () use ($huntedDeal): void {
            $previousCaptureTimes = $huntedDeal->priceSnapshots()
                ->get(['captured_at'])
                ->pluck('captured_at')
                ->map(fn ($capturedAt) => $capturedAt->toDateTimeString())
                ->all();

            $huntedDeal->priceSnapshots()->delete();

            if ($previousCaptureTimes === []) {
                return;
            }

            $snapshots = DealSnapshot::query()
                ->whereIn('deal_id', $huntedDeal->deals()->select('id'))
                ->orderBy('captured_at')
                ->orderBy('id')
                ->get();

            $currentStateByDeal = [];
            $snapshotIndex = 0;
            $snapshotCount = $snapshots->count();

            foreach ($previousCaptureTimes as $capturedAt) {
                while ($snapshotIndex < $snapshotCount && $snapshots[$snapshotIndex]->captured_at->toDateTimeString() <= $capturedAt) {
                    $snapshot = $snapshots[$snapshotIndex];
                    $currentStateByDeal[$snapshot->deal_id] = $snapshot;
                    $snapshotIndex++;
                }

                $eligible = collect($currentStateByDeal)
                    ->filter(fn (DealSnapshot $snapshot): bool => $snapshot->matches_intent
                        && $snapshot->likely_working
                        && $snapshot->price_amount !== null);

                if ($eligible->isEmpty()) {
                    continue;
                }

                $prices = $eligible->map(fn (DealSnapshot $snapshot): float => (float) $snapshot->price_amount);

                $huntedDeal->priceSnapshots()->create([
                    'average_price' => round($prices->avg(), 2),
                    'min_price' => $prices->min(),
                    'max_price' => $prices->max(),
                    'deals_count' => $eligible->count(),
                    'price_currency' => $eligible->last()->price_currency ?? 'RON',
                    'captured_at' => $capturedAt,
                ]);
            }
        });
    }
}
