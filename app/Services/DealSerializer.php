<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared deal DTO serializer. All surfaces that render deal rows (dashboard,
 * deals index, favorites, hunted-deals ledger) must build their props here so
 * the local-only media guarantee and the camelCase/route() conventions live in
 * exactly one place.
 */
final class DealSerializer
{
    /**
     * Serialize downloaded local media for a deal. Remote URLs are never
     * exposed — every surface renders media through this method.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function media(Deal $deal): array
    {
        return $deal->media
            ->filter(fn (DealMedia $media) => $media->path
                && $media->downloaded_at !== null
                && Storage::disk($media->disk)->exists($media->path))
            ->values()
            ->map(fn (DealMedia $media) => [
                'url' => Storage::disk($media->disk)->url($media->path),
            ])
            ->all();
    }

    /**
     * Serialize a deal into an explicit, frontend-safe array (camelCase, URLs
     * built server-side). Surfaces opt into extra fields via options:
     *
     * - titleLimit (int, default 100)
     * - useLatestPrice (bool, default true) — false uses the deal's own price
     * - isFavorite (bool|null, default null → derived from the model)
     * - withIntentScore (bool, default false)
     * - withDescription (bool, default false) + descriptionLimit (int, 150)
     * - withIsNew (bool, default false)
     * - withCreatedAt (bool, default false)
     * - withLastSeenAt (bool, default false)
     * - withSnapshotsCount (bool, default false)
     * - withSearchTerm (bool, default false)
     * - withHuntedDealUrl (bool, default false)
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function toArray(Deal $deal, array $options = []): array
    {
        $latestSnapshot = $deal->latestSnapshot;
        $useLatestPrice = $options['useLatestPrice'] ?? true;

        $priceAmount = $useLatestPrice
            ? ($latestSnapshot?->price_amount ?? $deal->price_amount)
            : $deal->price_amount;
        $priceCurrency = $useLatestPrice
            ? ($latestSnapshot?->price_currency ?? $deal->price_currency)
            : $deal->price_currency;

        $data = [
            'id' => $deal->id,
            'title' => Str::limit($deal->title, $options['titleLimit'] ?? 100),
            'matchesIntent' => (bool) $deal->matches_intent,
            'likelyWorking' => (bool) $deal->likely_working,
            'priceAmount' => $priceAmount !== null ? (float) $priceAmount : null,
            'priceCurrency' => $priceCurrency,
            'location' => $deal->location,
            'isFavorite' => $options['isFavorite'] ?? (bool) $deal->is_favorite,
            'media' => self::media($deal),
            'showUrl' => route('deals.show', $deal),
            'externalUrl' => $deal->url,
            'toggleFavoriteUrl' => route('deals.favorite.toggle', $deal),
        ];

        if ($options['withIntentScore'] ?? false) {
            $data['intentScore'] = $deal->intent_score;
        }

        if ($options['withDescription'] ?? false) {
            $data['description'] = $deal->description !== null
                ? Str::limit($deal->description, $options['descriptionLimit'] ?? 150)
                : null;
        }

        if ($options['withIsNew'] ?? false) {
            $data['isNew'] = $deal->created_at->gte(now()->subDay());
        }

        if ($options['withCreatedAt'] ?? false) {
            $data['createdAt'] = $deal->created_at->diffForHumans();
        }

        if ($options['withLastSeenAt'] ?? false) {
            $data['lastSeenAt'] = $deal->last_seen_at?->diffForHumans();
        }

        if ($options['withSnapshotsCount'] ?? false) {
            $data['snapshotsCount'] = (int) $deal->snapshots_count;
        }

        if ($options['withSearchTerm'] ?? false) {
            $data['searchTerm'] = $deal->huntedDeal?->search_term;
        }

        if ($options['withHuntedDealUrl'] ?? false) {
            $data['huntedDealUrl'] = $deal->huntedDeal ? route('hunted-deals.show', $deal->huntedDeal) : null;
        }

        return $data;
    }
}
