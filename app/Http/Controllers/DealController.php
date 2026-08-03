<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DealController extends Controller
{
    /**
     * Display a listing of deals with filtering and pagination.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $huntedDealId = $this->validatedHuntedDealId($request, $user);

        $query = $this->baseDealQuery($user, $huntedDealId)
            ->with(['huntedDeal', 'latestSnapshot'])
            ->withCount('snapshots');

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

        // Apply matches_intent filter
        if ($request->boolean('matches_intent')) {
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

        return view('deals.index', compact('deals', 'filterCounts'));
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
            'snapshots' => fn (HasMany $query) => $query->orderBy('captured_at'),
        ]);

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
