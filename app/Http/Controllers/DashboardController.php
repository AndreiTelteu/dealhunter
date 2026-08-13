<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\User;
use App\Services\DealSerializer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
            ->map(fn (Deal $deal) => DealSerializer::toArray($deal, [
                'titleLimit' => 60,
                'useLatestPrice' => false,
                'withCreatedAt' => true,
                'withSearchTerm' => true,
            ]))
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
}
