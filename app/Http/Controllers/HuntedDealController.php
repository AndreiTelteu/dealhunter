<?php

namespace App\Http\Controllers;

use App\Jobs\ReclassifyHuntedDealIntent;
use App\Models\HuntedDeal;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HuntedDealController extends Controller
{
    /**
     * Display a listing of the user's hunted deals.
     */
    public function index(Request $request): Response
    {
        $query = Auth::user()->huntedDeals()
            ->withCount('deals')
            ->with('latestPriceSnapshot');

        // Apply filters
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'never_crawled':
                    $query->whereNull('last_crawled_at');
                    break;
                case 'recently_crawled':
                    $query->where('last_crawled_at', '>=', now()->subHours(24));
                    break;
            }
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereLike('search_term', "%{$search}%")
                    ->orWhereLike('notes', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'updated_at');
        $sortDirection = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['search_term', 'is_active', 'last_crawled_at', 'created_at', 'updated_at', 'deals_count'];
        $resolvedSort = in_array($sortBy, $allowedSorts) ? $sortBy : 'updated_at';
        $query->orderBy($resolvedSort, $sortDirection);

        $huntedDeals = $query->paginate(15)->withQueryString();

        return Inertia::render('HuntedDeals/Index', [
            'huntedDeals' => [
                'data' => $huntedDeals->through(fn (HuntedDeal $huntedDeal) => $this->serializeHuntedDeal($huntedDeal))->all(),
                'links' => $huntedDeals->linkCollection()
                    ->map(fn (array $link) => [
                        'url' => $link['url'],
                        'label' => $link['label'],
                        'active' => (bool) $link['active'],
                    ])
                    ->all(),
                'meta' => [
                    'currentPage' => $huntedDeals->currentPage(),
                    'lastPage' => $huntedDeals->lastPage(),
                    'perPage' => $huntedDeals->perPage(),
                    'total' => $huntedDeals->total(),
                    'from' => $huntedDeals->firstItem(),
                    'to' => $huntedDeals->lastItem(),
                ],
            ],
            'filters' => [
                'search' => $request->get('search'),
                'filter' => $request->get('filter'),
                'sort' => $resolvedSort,
                'direction' => $sortDirection,
                'hasActiveFilters' => $request->hasAny(['search', 'filter']),
            ],
            'links' => [
                'index' => route('hunted-deals.index'),
                'create' => route('hunted-deals.create'),
            ],
        ]);
    }

    /**
     * Serialize a hunted deal for the index list surface.
     *
     * @return array<string, mixed>
     */
    private function serializeHuntedDeal(HuntedDeal $huntedDeal): array
    {
        $snapshot = $huntedDeal->latestPriceSnapshot;

        return [
            'id' => $huntedDeal->id,
            'searchTerm' => $huntedDeal->search_term,
            'isActive' => (bool) $huntedDeal->is_active,
            'notes' => $huntedDeal->notes !== null ? Str::limit($huntedDeal->notes, 140) : null,
            'dealsCount' => (int) $huntedDeal->deals_count,
            'lastCrawledAt' => $huntedDeal->last_crawled_at?->diffForHumans(),
            'createdAt' => $huntedDeal->created_at->format('d M Y'),
            'updatedAt' => $huntedDeal->updated_at->diffForHumans(),
            'showUrl' => route('hunted-deals.show', $huntedDeal),
            'editUrl' => route('hunted-deals.edit', $huntedDeal),
            'latestPriceSnapshot' => $snapshot === null ? null : [
                'id' => $snapshot->id,
                'averagePrice' => $snapshot->average_price !== null ? (float) $snapshot->average_price : null,
                'minPrice' => $snapshot->min_price !== null ? (float) $snapshot->min_price : null,
                'maxPrice' => $snapshot->max_price !== null ? (float) $snapshot->max_price : null,
                'dealsCount' => (int) $snapshot->deals_count,
                'priceCurrency' => $snapshot->price_currency,
                'capturedAt' => $snapshot->captured_at?->toIso8601String() ?? '',
            ],
        ];
    }

    /**
     * Show the form for creating a new hunted deal.
     */
    public function create(): Response
    {
        return Inertia::render('HuntedDeals/Create', [
            'links' => [
                'store' => route('hunted-deals.store'),
                'index' => route('hunted-deals.index'),
            ],
        ]);
    }

    /**
     * Store a newly created hunted deal in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'search_term' => ['required', 'string', 'max:255'],
            'excluded_phrases' => ['nullable', 'array', 'max:20'],
            'excluded_phrases.*' => ['string', 'max:100'],
            'preferred_phrases' => ['nullable', 'array', 'max:20'],
            'preferred_phrases.*' => ['string', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Ensure user can't create duplicate search terms
        $existingHuntedDeal = Auth::user()->huntedDeals()
            ->where('search_term', $validated['search_term'])
            ->first();

        if ($existingHuntedDeal) {
            return back()->withErrors(['search_term' => 'You already have a hunted deal with this search term.']);
        }

        $validated['user_id'] = Auth::id();
        $validated['excluded_phrases'] = $this->normalizePhrases($validated['excluded_phrases'] ?? []);
        $validated['preferred_phrases'] = $this->normalizePhrases($validated['preferred_phrases'] ?? []);
        $validated['is_active'] = $request->boolean('is_active', true);

        $huntedDeal = HuntedDeal::create($validated);

        return redirect()
            ->route('hunted-deals.show', $huntedDeal)
            ->with('success', 'Hunted deal created successfully!');
    }

    /**
     * Display the specified hunted deal.
     */
    public function show(Request $request, HuntedDeal $huntedDeal)
    {
        // Ensure the hunted deal belongs to the authenticated user
        if ($huntedDeal->user_id !== Auth::id()) {
            abort(404);
        }

        $query = $huntedDeal->deals()
            ->with(['huntedDeal', 'latestSnapshot', 'media'])
            ->withCount('snapshots')
            ->withFavoriteState(Auth::id());

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // Apply price drop filter
        if ($request->boolean('price_drops')) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('deal_snapshots as ds1')
                    ->join('deal_snapshots as ds2', 'ds1.deal_id', '=', 'ds2.deal_id')
                    ->whereColumn('ds1.deal_id', 'deals.id')
                    ->where('ds1.captured_at', '>', DB::raw('ds2.captured_at'))
                    ->where('ds1.price_amount', '<', DB::raw('ds2.price_amount'))
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

        $deals = $query->paginate(20)->withQueryString();

        // Get hourly average price snapshots for the spectrum trace.
        $priceSnapshots = $huntedDeal->priceSnapshots()->get();

        // Get filter counts for display
        $filterCounts = $this->getFilterCounts($huntedDeal);

        // Get crawl statistics
        $stats = [
            'total_deals' => $huntedDeal->deals()->count(),
            'new_deals_24h' => $huntedDeal->deals()->where('created_at', '>=', now()->subDay())->count(),
            'matching_intent' => $huntedDeal->deals()->where('matches_intent', true)->count(),
            'likely_working' => $huntedDeal->deals()->where('likely_working', true)->count(),
            'price_drops' => $huntedDeal->deals()
                ->has('snapshots', '>', 1)
                ->count(),
        ];

        return view('hunted-deals.show', compact('huntedDeal', 'deals', 'stats', 'filterCounts', 'matchesIntentFilter', 'priceSnapshots'));
    }

    /**
     * Get counts for various filters to display in the UI.
     */
    private function getFilterCounts(HuntedDeal $huntedDeal): array
    {
        $base = fn (): HasMany => $huntedDeal->deals();

        return [
            'total' => $base()->count(),
            'new_items' => $base()->where('created_at', '>=', now()->subDay())->count(),
            'matches_intent' => $base()->where('matches_intent', true)->count(),
            'likely_working' => $base()->where('likely_working', true)->count(),
            'price_drops' => $base()->whereExists(function ($q) {
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

    /**
     * Show the form for editing the specified hunted deal.
     */
    public function edit(HuntedDeal $huntedDeal): Response
    {
        // Ensure the hunted deal belongs to the authenticated user
        if ($huntedDeal->user_id !== Auth::id()) {
            abort(404);
        }

        return Inertia::render('HuntedDeals/Edit', [
            'huntedDeal' => $this->serializeHuntedDealForm($huntedDeal),
            'links' => [
                'index' => route('hunted-deals.index'),
                'update' => route('hunted-deals.update', $huntedDeal),
                'destroy' => route('hunted-deals.destroy', $huntedDeal),
            ],
        ]);
    }

    /**
     * Serialize a hunted deal for the create/edit form surfaces.
     *
     * @return array<string, mixed>
     */
    private function serializeHuntedDealForm(HuntedDeal $huntedDeal): array
    {
        return [
            'id' => $huntedDeal->id,
            'searchTerm' => $huntedDeal->search_term,
            'isActive' => (bool) $huntedDeal->is_active,
            'notes' => $huntedDeal->notes,
            'excludedPhrases' => $huntedDeal->excluded_phrases ?? [],
            'preferredPhrases' => $huntedDeal->preferred_phrases ?? [],
            'dealsCount' => (int) $huntedDeal->deals()->count(),
            'createdAt' => $huntedDeal->created_at->format('d M Y, H:i'),
            'updatedAt' => $huntedDeal->updated_at->format('d M Y, H:i'),
            'lastCrawledAt' => $huntedDeal->last_crawled_at?->format('d M Y, H:i'),
            'showUrl' => route('hunted-deals.show', $huntedDeal),
            'editUrl' => route('hunted-deals.edit', $huntedDeal),
        ];
    }

    /**
     * Update the specified hunted deal in storage.
     */
    public function update(Request $request, HuntedDeal $huntedDeal): RedirectResponse
    {
        // Ensure the hunted deal belongs to the authenticated user
        if ($huntedDeal->user_id !== Auth::id()) {
            abort(404);
        }

        $previousSearchTerm = $huntedDeal->search_term;
        $previousExcludedPhrases = $huntedDeal->excluded_phrases ?? [];
        $previousPreferredPhrases = $huntedDeal->preferred_phrases ?? [];

        $validated = $request->validate([
            'search_term' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hunted_deals')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                })->ignore($huntedDeal->id),
            ],
            'excluded_phrases' => ['nullable', 'array', 'max:20'],
            'excluded_phrases.*' => ['string', 'max:100'],
            'preferred_phrases' => ['nullable', 'array', 'max:20'],
            'preferred_phrases.*' => ['string', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['excluded_phrases'] = $this->normalizePhrases($validated['excluded_phrases'] ?? []);
        $validated['preferred_phrases'] = $this->normalizePhrases($validated['preferred_phrases'] ?? []);
        $validated['is_active'] = $request->boolean('is_active');

        $huntedDeal->update($validated);

        if ($scope = $this->reclassificationScope(
            $previousSearchTerm,
            $previousExcludedPhrases,
            $previousPreferredPhrases,
            $huntedDeal
        )) {
            ReclassifyHuntedDealIntent::dispatch(
                huntedDealId: $huntedDeal->id,
                scope: $scope,
                triggeredByUserId: Auth::id(),
            );
        }

        return redirect()
            ->route('hunted-deals.show', $huntedDeal)
            ->with('success', 'Hunted deal updated successfully!');
    }

    /**
     * Select only the deals whose current state can be affected by an edit.
     */
    private function reclassificationScope(
        string $previousSearchTerm,
        array $previousExcludedPhrases,
        array $previousPreferredPhrases,
        HuntedDeal $huntedDeal
    ): ?string {
        $searchTermChanged = $previousSearchTerm !== $huntedDeal->search_term;
        $exclusionsChanged = $previousExcludedPhrases !== ($huntedDeal->excluded_phrases ?? []);
        $preferencesChanged = $previousPreferredPhrases !== ($huntedDeal->preferred_phrases ?? []);

        if ($searchTermChanged || ($exclusionsChanged && $preferencesChanged)) {
            return ReclassifyHuntedDealIntent::SCOPE_ALL;
        }

        if ($exclusionsChanged) {
            return ReclassifyHuntedDealIntent::SCOPE_MATCHING;
        }

        if ($preferencesChanged) {
            return ReclassifyHuntedDealIntent::SCOPE_NON_MATCHING;
        }

        return null;
    }

    /**
     * Normalize user-defined title phrases before persisting them.
     *
     * @param  array<int, string>  $phrases
     * @return list<string>
     */
    private function normalizePhrases(array $phrases): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (string $phrase): string => trim(preg_replace('/\s+/', ' ', $phrase) ?? ''),
            $phrases
        ))));
    }

    /**
     * Remove the specified hunted deal from storage.
     */
    public function destroy(HuntedDeal $huntedDeal): RedirectResponse
    {
        // Ensure the hunted deal belongs to the authenticated user
        if ($huntedDeal->user_id !== Auth::id()) {
            abort(404);
        }

        $searchTerm = $huntedDeal->search_term;

        // Delete the hunted deal (cascading will handle related deals and snapshots)
        $huntedDeal->delete();

        return redirect()
            ->route('hunted-deals.index')
            ->with('success', "Hunted deal '{$searchTerm}' and all associated data have been deleted.");
    }
}
