<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealMedia;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'deal' => $this->serializeDeal($favorite->deal),
        ];
    }

    /**
     * Serialize a favorited deal for the favorites index. Mirrors
     * DealController::serializeDeal (title/description limits, latest
     * snapshot price, local-only media) without the snapshots count the
     * Blade favorites surface never showed.
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
            'priceAmount' => ($latestSnapshot?->price_amount ?? $deal->price_amount) !== null
                ? (float) ($latestSnapshot?->price_amount ?? $deal->price_amount)
                : null,
            'priceCurrency' => $latestSnapshot?->price_currency ?? $deal->price_currency,
            'location' => $deal->location,
            'searchTerm' => $deal->huntedDeal?->search_term,
            'huntedDealUrl' => $deal->huntedDeal ? route('hunted-deals.show', $deal->huntedDeal) : null,
            'isFavorite' => true,
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
}
