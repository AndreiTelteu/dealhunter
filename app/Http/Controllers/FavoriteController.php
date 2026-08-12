<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * Display the user's favorite deals.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $favorites = $user->favorites()
            ->with(['deal.huntedDeal', 'deal.latestSnapshot', 'deal.media'])
            ->latest()
            ->paginate(20);

        $favorites->getCollection()->each(function ($favorite) {
            $favorite->deal->is_favorite = true;
        });

        return view('favorites.index', compact('favorites'));
    }

    /**
     * Toggle the favorite state of a deal for the authenticated user.
     *
     * Content negotiation: JSON-expecting clients keep the exact JSON
     * contract; Inertia requests get a redirect back with flash so
     * `router.post(..., { preserveState, preserveScroll })` receives a
     * valid Inertia visit and the shared props (favoritesCount) refresh.
     */
    public function toggle(Request $request, Deal $deal): JsonResponse|RedirectResponse
    {
        $deal->loadMissing('huntedDeal');

        abort_unless($deal->huntedDeal->user_id === Auth::id(), 403);

        /** @var User $user */
        $user = Auth::user();

        $exists = $user->favorites()->where('deal_id', $deal->id)->exists();

        if ($exists) {
            $user->favorites()->where('deal_id', $deal->id)->delete();
        } else {
            $user->favorites()->create(['deal_id' => $deal->id]);
        }

        $favorited = ! $exists;
        $count = $user->favorites()->count();

        if ($request->expectsJson()) {
            return response()->json([
                'favorited' => $favorited,
                'count' => $count,
            ]);
        }

        return back()->with(
            'success',
            $favorited ? 'Adăugată la favorite.' : 'Eliminată din favorite.',
        );
    }
}
