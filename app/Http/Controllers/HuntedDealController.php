<?php

namespace App\Http\Controllers;

use App\Jobs\ReclassifyHuntedDealIntent;
use App\Models\HuntedDeal;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HuntedDealController extends Controller
{
    /**
     * Display a listing of the user's hunted deals.
     */
    public function index(Request $request)
    {
        $query = Auth::user()->huntedDeals()->withCount('deals');

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
                $q->where('search_term', 'ILIKE', "%{$search}%")
                    ->orWhere('notes', 'ILIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'updated_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['search_term', 'is_active', 'last_crawled_at', 'created_at', 'updated_at', 'deals_count'];
        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'deals_count') {
                $query->orderBy('deals_count', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }
        }

        $huntedDeals = $query->paginate(15)->withQueryString();

        return view('hunted-deals.index', compact('huntedDeals'));
    }

    /**
     * Show the form for creating a new hunted deal.
     */
    public function create()
    {
        return view('hunted-deals.create');
    }

    /**
     * Store a newly created hunted deal in storage.
     */
    public function store(Request $request)
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
            return back()
                ->withInput()
                ->withErrors(['search_term' => 'You already have a hunted deal with this search term.']);
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
            ->with(['huntedDeal', 'latestSnapshot'])
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

        // Get hourly average price snapshots for the spectrum trace
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
    public function edit(HuntedDeal $huntedDeal)
    {
        // Ensure the hunted deal belongs to the authenticated user
        if ($huntedDeal->user_id !== Auth::id()) {
            abort(404);
        }

        return view('hunted-deals.edit', compact('huntedDeal'));
    }

    /**
     * Update the specified hunted deal in storage.
     */
    public function update(Request $request, HuntedDeal $huntedDeal)
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
    public function destroy(HuntedDeal $huntedDeal)
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
