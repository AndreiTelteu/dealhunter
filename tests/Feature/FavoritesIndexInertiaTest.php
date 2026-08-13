<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\DealMedia;
use App\Models\DealSnapshot;
use App\Models\HuntedDeal;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FavoritesIndexInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    private function createUser(string $email = 'favorites-index@example.test', string $name = 'Favorites Index User'): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
    }

    private function createDealFor(User $user, array $attributes = []): Deal
    {
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => $attributes['search_term'] ?? 'laptop',
            'is_active' => true,
        ]);

        unset($attributes['search_term']);

        return Deal::create(array_merge([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'listing-'.uniqid(),
            'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html',
            'title' => 'Inertia favorite listing',
            'price_amount' => 1234.56,
            'price_currency' => 'RON',
            'matches_intent' => true,
            'intent_score' => 92,
            'likely_working' => true,
            'location' => 'București',
            'description' => 'Un laptop bun',
            'last_seen_at' => now(),
        ], $attributes));
    }

    public function test_guests_cannot_access_the_favorites_index(): void
    {
        $this->get('/favorites')->assertRedirect('/login');
    }

    public function test_favorites_index_renders_the_inertia_component_with_minimal_props(): void
    {
        $user = $this->createUser();
        $deal = $this->createDealFor($user);
        $user->favorites()->create(['deal_id' => $deal->id]);

        $this->actingAs($user)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Favorites/Index')
                ->where('favorites.meta.total', 1)
                ->where('favorites.meta.currentPage', 1)
                ->where('favorites.meta.perPage', 20)
                ->where('favorites.data.0.deal.title', 'Inertia favorite listing')
                ->where('favorites.data.0.deal.description', 'Un laptop bun')
                ->where('favorites.data.0.deal.matchesIntent', true)
                ->where('favorites.data.0.deal.intentScore', 92)
                ->where('favorites.data.0.deal.likelyWorking', true)
                ->where('favorites.data.0.deal.isFavorite', true)
                ->where('favorites.data.0.deal.searchTerm', 'laptop')
                ->where('favorites.data.0.deal.location', 'București')
                ->where('favorites.data.0.deal.priceAmount', 1234.56)
                ->where('favorites.data.0.deal.priceCurrency', 'RON')
                ->has('favorites.data.0.createdAt')
                ->has('favorites.data.0.deal.showUrl')
                ->has('favorites.data.0.deal.externalUrl')
                ->has('favorites.data.0.deal.toggleFavoriteUrl')
                ->has('favorites.data.0.deal.huntedDealUrl')
                ->has('links.dealsIndex'));
    }

    public function test_favorites_index_empty_state_has_zero_rows(): void
    {
        $user = $this->createUser('favorites-empty@example.test');

        $this->actingAs($user)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Favorites/Index')
                ->where('favorites.meta.total', 0)
                ->where('favorites.data', [])
                ->where('favorites.meta.from', null)
                ->where('favorites.meta.to', null)
                ->has('links.dealsIndex'));
    }

    public function test_favorites_index_does_not_include_another_users_favorites(): void
    {
        $owner = $this->createUser('favorites-owner@example.test', 'Owner');
        $intruder = $this->createUser('favorites-intruder@example.test', 'Intruder');
        $ownedDeal = $this->createDealFor($owner, ['title' => 'Owner favorite']);
        $foreignDeal = $this->createDealFor($intruder, ['title' => 'Intruder favorite']);
        $owner->favorites()->create(['deal_id' => $ownedDeal->id]);
        $intruder->favorites()->create(['deal_id' => $foreignDeal->id]);

        $this->actingAs($owner)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorites.meta.total', 1)
                ->where('favorites.data.0.deal.title', 'Owner favorite')
                ->where('favorites.data.0.deal.id', $ownedDeal->id));
    }

    public function test_favorites_index_exposes_only_local_media_and_never_remote_urls(): void
    {
        Storage::fake('public');
        $user = $this->createUser('favorites-media@example.test');
        $deal = $this->createDealFor($user, ['image_urls' => ['https://frankfurt.apollo.olxcdn.com/remote.jpg']]);
        $path = 'deal-media/'.$deal->id.'/local.jpg';
        Storage::disk('public')->put($path, 'local-image');
        DealMedia::create([
            'deal_id' => $deal->id,
            'source_url' => 'https://frankfurt.apollo.olxcdn.com/remote.jpg',
            'source_hash' => hash('sha256', 'https://frankfurt.apollo.olxcdn.com/remote.jpg'),
            'disk' => 'public',
            'path' => $path,
            'position' => 0,
            'downloaded_at' => now(),
        ]);
        $user->favorites()->create(['deal_id' => $deal->id]);

        $localUrl = Storage::disk('public')->url($path);

        $this->actingAs($user)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Favorites/Index')
                ->where('favorites.data.0.deal.media', fn ($media) => count($media) === 1)
                ->where('favorites.data.0.deal.media.0.url', $localUrl))
            ->assertDontSee('https://frankfurt.apollo.olxcdn.com/remote.jpg');
    }

    public function test_favorites_index_prefers_the_latest_snapshot_price(): void
    {
        $user = $this->createUser('favorites-price@example.test');
        $deal = $this->createDealFor($user);
        DealSnapshot::create([
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'price_amount' => 999,
            'price_currency' => 'EUR',
            'captured_at' => now(),
        ]);
        $user->favorites()->create(['deal_id' => $deal->id]);

        $this->actingAs($user)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorites.data.0.deal.priceAmount', 999)
                ->where('favorites.data.0.deal.priceCurrency', 'EUR'));
    }

    public function test_favorites_index_limits_title_and_description(): void
    {
        $user = $this->createUser('favorites-limits@example.test');
        $deal = $this->createDealFor($user, [
            'title' => str_repeat('T', 140),
            'description' => str_repeat('D', 200),
        ]);
        $user->favorites()->create(['deal_id' => $deal->id]);

        $this->actingAs($user)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorites.data.0.deal.title', str_repeat('T', 100).'...')
                ->where('favorites.data.0.deal.description', str_repeat('D', 150).'...'));
    }

    public function test_favorites_index_orders_newest_favorites_first(): void
    {
        $user = $this->createUser('favorites-order@example.test');
        $olderDeal = $this->createDealFor($user, ['title' => 'Older favorite']);
        $newerDeal = $this->createDealFor($user, ['title' => 'Newer favorite']);
        $older = $user->favorites()->create(['deal_id' => $olderDeal->id]);
        $user->favorites()->create(['deal_id' => $newerDeal->id]);
        $older->forceFill(['created_at' => now()->subHour()])->save();

        $this->actingAs($user)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorites.meta.total', 2)
                ->where('favorites.data.0.deal.title', 'Newer favorite')
                ->where('favorites.data.1.deal.title', 'Older favorite'));
    }

    public function test_favorites_index_paginates_twenty_per_page(): void
    {
        $user = $this->createUser('favorites-pages@example.test');
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);

        foreach (range(1, 21) as $index) {
            $deal = Deal::create([
                'hunted_deal_id' => $huntedDeal->id,
                'external_id' => 'listing-'.$index,
                'url' => 'https://www.olx.ro/d/oferta/listing-ID'.$index.'.html',
                'title' => 'Favorite listing '.$index,
                'price_currency' => 'RON',
                'matches_intent' => true,
                'last_seen_at' => now(),
            ]);
            $user->favorites()->create(['deal_id' => $deal->id]);
        }

        $this->actingAs($user)
            ->get('/favorites')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorites.meta.total', 21)
                ->where('favorites.meta.perPage', 20)
                ->where('favorites.meta.lastPage', 2)
                ->where('favorites.meta.currentPage', 1)
                ->where('favorites.data', fn ($data) => count($data) === 20)
                ->where('favorites.links', fn ($links) => collect($links)->contains(
                    fn ($link) => is_string($link['url'] ?? null) && str_contains($link['url'], 'page=2')
                )));
    }
}
