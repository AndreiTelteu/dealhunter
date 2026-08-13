<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Favorite;
use App\Models\User;
use App\Services\DealSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /**
     * Display the user's favorite deals.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $favorites = $user->favorites()
            ->with(['deal.huntedDeal', 'deal.latestSnapshot', 'deal.media'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Favorites/Index', [
            'favorites' => [
                'data' => $favorites->through(fn (Favorite $favorite) => $this->serializeFavorite($favorite))->all(),
                'links' => $favorites->linkCollection()
                    ->map(fn (array $link) => [
                        'url' => $link['url'],
                        'label' => $link['label'],
                        'active' => (bool) $link['active'],
                    ])
                    ->all(),
                'meta' => [
                    'currentPage' => $favorites->currentPage(),
                    'lastPage' => $favorites->lastPage(),
                    'perPage' => $favorites->perPage(),
                    'total' => $favorites->total(),
                    'from' => $favorites->firstItem(),
                    'to' => $favorites->lastItem(),
                ],
            ],
            'links' => [
                'dealsIndex' => route('deals.index'),
            ],
        ]);
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

    /**
     * Serialize one favorite row plus its nested deal DTO.
     *
     * @return array<string, mixed>
     */
    protected function serializeFavorite(Favorite $favorite): array
    {
        return [
            'id' => $favorite->id,
            'createdAt' => $favorite->created_at->diffForHumans(),
            'deal' => DealSerializer::toArray($favorite->deal, [
                'titleLimit' => 100,
                'withIntentScore' => true,
                'withDescription' => true,
                'descriptionLimit' => 150,
                'withSearchTerm' => true,
                'withHuntedDealUrl' => true,
                'isFavorite' => true,
            ]),
        ];
    }
}
