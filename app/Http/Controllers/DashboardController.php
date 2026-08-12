<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The user's main dashboard.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $huntedDealsCount = $user->huntedDeals()->count();
        $activeHuntedDealsCount = $user->huntedDeals()->where('is_active', true)->count();
        $totalDealsCount = $this->dealsFor($user)->count();
        $newDealsCount = $this->dealsFor($user)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $huntedDeals = $user->huntedDeals()
            ->withCount('deals')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($huntedDeal) => [
                'id' => $huntedDeal->id,
                'searchTerm' => $huntedDeal->search_term,
                'isActive' => (bool) $huntedDeal->is_active,
                'notes' => $huntedDeal->notes !== null ? Str::limit($huntedDeal->notes, 100) : null,
                'dealsCount' => (int) $huntedDeal->deals_count,
                'lastCrawledAt' => $huntedDeal->last_crawled_at?->diffForHumans(),
                'showUrl' => route('hunted-deals.show', $huntedDeal),
                'editUrl' => route('hunted-deals.edit', $huntedDeal),
            ])
            ->all();

        $recentDeals = $this->dealsFor($user)
            ->with(['huntedDeal', 'latestSnapshot', 'media'])
            ->withFavoriteState($user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (Deal $deal) => $this->serializeDeal($deal))
            ->all();

        return Inertia::render('Dashboard/Index', [
            'huntedDealsCount' => $huntedDealsCount,
            'activeHuntedDealsCount' => $activeHuntedDealsCount,
            'totalDealsCount' => $totalDealsCount,
            'newDealsCount' => $newDealsCount,
            'huntedDeals' => $huntedDeals,
            'recentDeals' => $recentDeals,
            'links' => [
                'huntedDealsIndex' => route('hunted-deals.index'),
                'huntedDealsActive' => route('hunted-deals.index', ['filter' => 'active']),
                'huntedDealsCreate' => route('hunted-deals.create'),
                'dealsIndex' => route('deals.index'),
                'newDealsIndex' => route('deals.index', ['new_items' => 1]),
            ],
        ]);
    }

    /**
     * Query builder for deals owned by the user's hunted deals.
     */
    protected function dealsFor(User $user): Builder
    {
        return Deal::query()
            ->whereHas('huntedDeal', fn (Builder $query) => $query->where('user_id', $user->id));
    }

    /**
     * Serialize a deal into an explicit, frontend-safe array.
     *
     * @return array<string, mixed>
     */
    protected function serializeDeal(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'title' => Str::limit($deal->title, 60),
            'matchesIntent' => (bool) $deal->matches_intent,
            'likelyWorking' => (bool) $deal->likely_working,
            'priceAmount' => $deal->price_amount !== null ? (float) $deal->price_amount : null,
            'priceCurrency' => $deal->price_currency,
            'location' => $deal->location,
            'createdAt' => $deal->created_at->diffForHumans(),
            'searchTerm' => $deal->huntedDeal?->search_term,
            'isFavorite' => (bool) $deal->is_favorite,
            'media' => $this->serializeMedia($deal),
            'showUrl' => route('deals.show', $deal),
            'externalUrl' => $deal->url,
            'toggleFavoriteUrl' => route('deals.favorite.toggle', $deal),
        ];
    }

    /**
     * Serialize downloaded local media for a deal. Remote URLs are never
     * exposed, matching the Blade dashboard behaviour.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeMedia(Deal $deal): array
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
}
