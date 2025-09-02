<?php

namespace App\Http\Controllers;

use App\Models\Deal;
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
        $user = Auth::user();
        
        // Start with deals that belong to the authenticated user's hunted deals
        $query = Deal::with(['huntedDeal', 'latestSnapshot'])
            ->withCount('snapshots')
            ->whereHas('huntedDeal', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

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
        $sortDirection = $request->get('direction', 'desc');
        
        $allowedSorts = ['title', 'price_amount', 'location', 'last_seen_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('last_seen_at', 'desc');
        }

        // Paginate results
        $deals = $query->paginate(20)->withQueryString();

        // Get filter counts for display
        $filterCounts = $this->getFilterCounts($user);

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

        $deal->load(['huntedDeal', 'snapshots' => function ($query) {
            $query->orderBy('captured_at', 'desc');
        }]);

        return view('deals.show', compact('deal'));
    }

    /**
     * Get counts for various filters to display in the UI.
     */
    private function getFilterCounts($user): array
    {
        $baseQuery = Deal::whereHas('huntedDeal', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        return [
            'total' => $baseQuery->count(),
            'new_items' => $baseQuery->where('created_at', '>=', now()->subDay())->count(),
            'matches_intent' => $baseQuery->where('matches_intent', true)->count(),
            'likely_working' => $baseQuery->where('likely_working', true)->count(),
            'price_drops' => $baseQuery->whereExists(function ($q) {
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
}