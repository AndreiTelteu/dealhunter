<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealSnapshot;
use App\Models\User;
use App\Services\DealSerializer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                'data' => $deals->through(fn (Deal $deal) => DealSerializer::toArray($deal, [
                    'titleLimit' => 100,
                    'withIntentScore' => true,
                    'withDescription' => true,
                    'descriptionLimit' => 150,
                    'withIsNew' => true,
                    'withLastSeenAt' => true,
                    'withSnapshotsCount' => true,
                    'withSearchTerm' => true,
                    'withHuntedDealUrl' => true,
                ]))->all(),
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
     * Display the specified deal.
     */
    public function show(Deal $deal): Response
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

        return Inertia::render('Deals/Show', [
            'deal' => $this->serializeDealDetail($deal),
            'current' => $this->serializeCurrentReadout($deal),
            'snapshots' => $deal->snapshots
                ->map(fn (DealSnapshot $snapshot) => $this->serializeSnapshot($snapshot))
                ->all(),
            'huntedDeal' => [
                'searchTerm' => $deal->huntedDeal->search_term,
                'isActive' => (bool) $deal->huntedDeal->is_active,
                'showUrl' => route('hunted-deals.show', $deal->huntedDeal),
            ],
            'links' => [
                'index' => route('deals.index'),
            ],
        ]);
    }

    /**
     * Serialize the deal fields shown on the deal detail surface into an
     * explicit, frontend-safe array (camelCase, URLs built server-side).
     *
     * @return array<string, mixed>
     */
    protected function serializeDealDetail(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'title' => $deal->title,
            'createdAtLabel' => $deal->created_at->format('d M Y, H:i'),
            'lastSeenAtLabel' => $deal->last_seen_at?->format('d M Y, H:i'),
            'externalUrl' => $deal->url,
            'isFavorite' => (bool) $deal->is_favorite,
            'media' => DealSerializer::media($deal),
            'toggleFavoriteUrl' => route('deals.favorite.toggle', $deal),
        ];
    }

    /**
     * Serialize the "current reading" of the deal detail surface: the
     * latest snapshot when one exists, otherwise the deal itself — the
     * exact merge the Blade page performed. Classification fields stay
     * nullable so the "Fără clasificare" state survives.
     *
     * @return array<string, mixed>
     */
    protected function serializeCurrentReadout(Deal $deal): array
    {
        $current = $deal->latestSnapshot ?? $deal;

        return [
            'title' => $current->title,
            'priceAmount' => $current->price_amount !== null ? (float) $current->price_amount : null,
            'priceCurrency' => $current->price_currency,
            'priceRaw' => $current->price_raw,
            'description' => $current->description,
            'matchesIntent' => $current->matches_intent === null ? null : (bool) $current->matches_intent,
            'intentScore' => $current->intent_score,
            'likelyWorking' => $current->likely_working === null ? null : (bool) $current->likely_working,
            'confidence' => $current->confidence !== null ? (float) $current->confidence : null,
            'snapshotCapturedAtLabel' => $deal->latestSnapshot?->captured_at?->format('d M Y, H:i'),
            'postedAtLabel' => $current->posted_at?->format('d M Y, H:i'),
            'location' => $current->location,
            'sellerName' => $current->seller_name,
            'sellerUrl' => $current->seller_url,
        ];
    }

    /**
     * Serialize one price/ledger snapshot for the deal detail history.
     *
     * @return array<string, mixed>
     */
    protected function serializeSnapshot(DealSnapshot $snapshot): array
    {
        return [
            'id' => $snapshot->id,
            'title' => $snapshot->title,
            'priceAmount' => $snapshot->price_amount !== null ? (float) $snapshot->price_amount : null,
            'priceCurrency' => $snapshot->price_currency,
            'location' => $snapshot->location,
            'sellerName' => $snapshot->seller_name,
            'capturedAt' => $snapshot->captured_at->toIso8601String(),
            'capturedAtLabel' => $snapshot->captured_at->format('d M Y, H:i'),
        ];
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
