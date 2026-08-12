<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealMedia;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    /**
     * Display a listing of deals with filtering and pagination.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $huntedDealId = $this->validatedHuntedDealId($request, $user);

        $query = $this->baseDealQuery($user, $huntedDealId)
            ->with(['huntedDeal', 'latestSnapshot', 'media'])
            ->withCount('snapshots')
            ->withFavoriteState($user->id);

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->whereLike('title', "%{$searchTerm}%")
                    ->orWhereLike('description', "%{$searchTerm}%");
            });
        }

        // Apply price drop filter
        if ($request->boolean('price_drops')) {
            $query->whereExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('deal_snapshots as ds1')
                    ->join('deal_snapshots as ds2', 'ds1.deal_id', '=', 'ds2.deal_id')
                    ->whereColumn('ds1.deal_id', 'deals.id')
                    ->where('ds1.captured_at', '>', \DB::raw('ds2.captured_at'))
                    ->where('ds1.price_amount', '<', \DB::raw('ds2.price_amount'))
                    ->whereNotNull('ds1.price_amount')
                    ->whereNotNull('ds2.price_amount');
            });
        }

        // Apply new items filter (last 24 hours)
        if ($request->boolean('new_items')) {
            $query->where('created_at', '>=', now()->subDay());
        }

        // Apply matches_intent filter (on by default unless explicitly disabled)
        $matchesIntentFilter = ! $request->has('matches_intent') || $request->boolean('matches_intent');
        if ($matchesIntentFilter) {
            $query->where('matches_intent', true);
        }

        // Apply likely_working filter
        if ($request->boolean('likely_working')) {
            $query->where('likely_working', true);
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'last_seen_at');
        $sortDirection = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['title', 'price_amount', 'location', 'last_seen_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('last_seen_at', 'desc');
        }

        // Paginate results
        $deals = $query->paginate(20)->withQueryString();

        // Get filter counts for display
        $filterCounts = $this->getFilterCounts($user, $huntedDealId);

        $resolvedSort = in_array($sortBy, $allowedSorts) ? $sortBy : 'last_seen_at';
        $resolvedDirection = $sortBy === $resolvedSort ? $sortDirection : 'desc';

        return Inertia::render('Deals/Index', [
            'deals' => [
                'data' => $deals->through(fn (Deal $deal) => $this->serializeDeal($deal))->all(),
                'links' => $deals->linkCollection()
                    ->map(fn (array $link) => [
                        'url' => $link['url'],
                        'label' => $link['label'],
                        'active' => (bool) $link['active'],
                    ])
                    ->all(),
                'meta' => [
                    'currentPage' => $deals->currentPage(),
                    'lastPage' => $deals->lastPage(),
                    'perPage' => $deals->perPage(),
                    'total' => $deals->total(),
                    'from' => $deals->firstItem(),
                    'to' => $deals->lastItem(),
                ],
            ],
            'filters' => [
                'search' => $request->get('search'),
                'sort' => $resolvedSort,
                'direction' => $resolvedDirection,
                'priceDrops' => $request->boolean('price_drops'),
                'newItems' => $request->boolean('new_items'),
                'matchesIntent' => $matchesIntentFilter,
                'likelyWorking' => $request->boolean('likely_working'),
                'huntedDeal' => $huntedDealId,
                'hasActiveFilters' => $request->hasAny(['search', 'price_drops', 'new_items', 'matches_intent', 'likely_working']),
            ],
            'filterCounts' => [
                'total' => $filterCounts['total'],
                'newItems' => $filterCounts['new_items'],
                'matchesIntent' => $filterCounts['matches_intent'],
                'likelyWorking' => $filterCounts['likely_working'],
                'priceDrops' => $filterCounts['price_drops'],
            ],
            'links' => [
                'index' => route('deals.index'),
                'reset' => route('deals.index', $huntedDealId ? ['hunted_deal' => $huntedDealId] : []),
                'huntedDealsIndex' => route('hunted-deals.index'),
                'huntedDealsCreate' => route('hunted-deals.create'),
            ],
        ]);
    }

    /**
     * Serialize a deal for the deals index surface into an explicit,
     * frontend-safe array (camelCase, URLs built server-side).
     *
     * @return array<string, mixed>
     */
    protected function serializeDeal(Deal $deal): array
    {
        $latestSnapshot = $deal->latestSnapshot;

        return [
            'id' => $deal->id,
            'title' => Str::limit($deal->title, 100),
            'matchesIntent' => (bool) $deal->matches_intent,
            'intentScore' => $deal->intent_score,
            'likelyWorking' => (bool) $deal->likely_working,
            'description' => $deal->description !== null ? Str::limit($deal->description, 150) : null,
            'isNew' => $deal->created_at->gte(now()->subDay()),
            'priceAmount' => ($latestSnapshot?->price_amount ?? $deal->price_amount) !== null
                ? (float) ($latestSnapshot?->price_amount ?? $deal->price_amount)
                : null,
            'priceCurrency' => $latestSnapshot?->price_currency ?? $deal->price_currency,
            'location' => $deal->location,
            'lastSeenAt' => $deal->last_seen_at?->diffForHumans(),
            'snapshotsCount' => (int) $deal->snapshots_count,
            'searchTerm' => $deal->huntedDeal?->search_term,
            'huntedDealUrl' => $deal->huntedDeal ? route('hunted-deals.show', $deal->huntedDeal) : null,
            'isFavorite' => (bool) $deal->is_favorite,
            'media' => $this->serializeMedia($deal),
            'showUrl' => route('deals.show', $deal),
            'externalUrl' => $deal->url,
            'toggleFavoriteUrl' => route('deals.favorite.toggle', $deal),
        ];
    }

    /**
     * Serialize downloaded local media for a deal. Remote URLs are never
     * exposed, matching the Blade gallery behaviour.
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

    /**
     * Display the specified deal.
     */
    public function show(Deal $deal): View
    {
        // Ensure the deal belongs to the authenticated user
        if ($deal->huntedDeal->user_id !== Auth::id()) {
            abort(403);
        }

        $deal->load([
            'huntedDeal',
            'latestSnapshot',
            'media',
            'snapshots' => fn (HasMany $query) => $query->orderBy('captured_at'),
        ]);

        $deal->is_favorite = Auth::user()->favorites()->where('deal_id', $deal->id)->exists();

        return view('deals.show', compact('deal'));
    }

    /**
     * Get counts for various filters to display in the UI.
     */
    private function getFilterCounts(User $user, ?int $huntedDealId): array
    {
        return [
            'total' => $this->baseDealQuery($user, $huntedDealId)->count(),
            'new_items' => $this->baseDealQuery($user, $huntedDealId)->where('created_at', '>=', now()->subDay())->count(),
            'matches_intent' => $this->baseDealQuery($user, $huntedDealId)->where('matches_intent', true)->count(),
            'likely_working' => $this->baseDealQuery($user, $huntedDealId)->where('likely_working', true)->count(),
            'price_drops' => $this->baseDealQuery($user, $huntedDealId)->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('deal_snapshots as ds1')
                    ->join('deal_snapshots as ds2', 'ds1.deal_id', '=', 'ds2.deal_id')
                    ->whereColumn('ds1.deal_id', 'deals.id')
                    ->where('ds1.captured_at', '>', DB::raw('ds2.captured_at'))
                    ->where('ds1.price_amount', '<', DB::raw('ds2.price_amount'))
                    ->whereNotNull('ds1.price_amount')
                    ->whereNotNull('ds2.price_amount');
            })->count(),
        ];
    }

    private function baseDealQuery(User $user, ?int $huntedDealId = null): Builder
    {
        return Deal::query()
            ->whereHas('huntedDeal', function (Builder $query) use ($user, $huntedDealId) {
                $query->where('user_id', $user->id)
                    ->when($huntedDealId, fn (Builder $query) => $query->whereKey($huntedDealId));
            });
    }

    private function validatedHuntedDealId(Request $request, User $user): ?int
    {
        if (! $request->filled('hunted_deal')) {
            return null;
        }

        $huntedDealId = $request->integer('hunted_deal');

        abort_unless(
            $huntedDealId > 0 && $user->huntedDeals()->whereKey($huntedDealId)->exists(),
            404,
        );

        return $huntedDealId;
    }
}
