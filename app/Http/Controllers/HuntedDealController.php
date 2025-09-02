<?php

namespace App\Http\Controllers;

use App\Models\HuntedDeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $validated['is_active'] = $request->boolean('is_active', true);
        
        $huntedDeal = HuntedDeal::create($validated);
        
        return redirect()
            ->route('hunted-deals.show', $huntedDeal)
            ->with('success', 'Hunted deal created successfully!');
    }

    /**
     * Display the specified hunted deal.
     */
    public function show(HuntedDeal $huntedDeal)
    {
        // Ensure the hunted deal belongs to the authenticated user
        if ($huntedDeal->user_id !== Auth::id()) {
            abort(404);
        }
        
        // Get deals with pagination
        $deals = $huntedDeal->deals()
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
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
        
        return view('hunted-deals.show', compact('huntedDeal', 'deals', 'stats'));
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
        
        $validated = $request->validate([
            'search_term' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('hunted_deals')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                })->ignore($huntedDeal->id)
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        
        $validated['is_active'] = $request->boolean('is_active');
        
        $huntedDeal->update($validated);
        
        return redirect()
            ->route('hunted-deals.show', $huntedDeal)
            ->with('success', 'Hunted deal updated successfully!');
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