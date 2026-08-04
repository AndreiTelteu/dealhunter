<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
            ->with(['deal.huntedDeal', 'deal.latestSnapshot'])
            ->latest()
            ->paginate(20);

        $favorites->getCollection()->each(function ($favorite) {
            $favorite->deal->is_favorite = true;
        });

        return view('favorites.index', compact('favorites'));
    }

    /**
     * Toggle the favorite state of a deal for the authenticated user.
     */
    public function toggle(Deal $deal): JsonResponse
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

        return response()->json([
            'favorited' => ! $exists,
            'count' => $user->favorites()->count(),
        ]);
    }
}
