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

class DealsIndexInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    private function createUser(string $email = 'deals-index@example.test', string $name = 'Deals Index User'): User
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
            'search_term' => 'laptop',
            'is_active' => true,
        ]);

        return Deal::create(array_merge([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'listing-'.uniqid(),
            'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html',
            'title' => 'Inertia listing',
            'price_amount' => 1234.56,
            'price_currency' => 'RON',
            'matches_intent' => true,
            'intent_score' => 92,
            'likely_working' => true,
            'last_seen_at' => now(),
        ], $attributes));
    }

    public function test_guests_cannot_access_the_deals_index(): void
    {
        $this->get('/deals')->assertRedirect('/login');
    }

    public function test_deals_index_renders_the_inertia_component_with_minimal_props(): void
    {
        $user = $this->createUser();
        $deal = $this->createDealFor($user, ['description' => 'Un laptop bun']);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Deals/Index')
                ->has('deals.data', 1)
                ->has('deals.links')
                ->where('deals.meta.total', 1)
                ->where('deals.meta.perPage', 20)
                ->where('deals.data.0.title', 'Inertia listing')
                ->where('deals.data.0.priceAmount', 1234.56)
                ->where('deals.data.0.priceCurrency', 'RON')
                ->where('deals.data.0.description', 'Un laptop bun')
                ->where('deals.data.0.matchesIntent', true)
                ->where('deals.data.0.intentScore', 92)
                ->where('deals.data.0.likelyWorking', true)
                ->where('deals.data.0.isNew', true)
                ->where('deals.data.0.snapshotsCount', 0)
                ->where('deals.data.0.isFavorite', false)
                ->where('deals.data.0.searchTerm', 'laptop')
                ->has('deals.data.0.showUrl')
                ->has('deals.data.0.externalUrl')
                ->has('deals.data.0.toggleFavoriteUrl')
                ->has('deals.data.0.huntedDealUrl')
                ->has('links.index')
                ->has('links.reset')
                ->has('links.huntedDealsIndex')
                ->has('links.huntedDealsCreate'));
    }

    public function test_deals_index_exposes_only_local_media_and_never_remote_urls(): void
    {
        Storage::fake('public');
        $user = $this->createUser('deals-index-media@example.test');
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

        $localUrl = Storage::disk('public')->url($path);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Deals/Index')
                ->where('deals.data.0.media', fn ($media) => count($media) === 1)
                ->where('deals.data.0.media.0.url', $localUrl))
            ->assertDontSee('https://frankfurt.apollo.olxcdn.com/remote.jpg');
    }

    public function test_deals_index_prefers_the_latest_snapshot_price(): void
    {
        $user = $this->createUser('deals-index-price@example.test');
        $deal = $this->createDealFor($user);
        DealSnapshot::create([
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'price_amount' => 999,
            'price_currency' => 'EUR',
            'captured_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.data.0.priceAmount', 999)
                ->where('deals.data.0.priceCurrency', 'EUR'));
    }

    public function test_search_filter_restricts_results_and_is_reflected_in_props(): void
    {
        $user = $this->createUser('deals-index-search@example.test');
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'match-listing',
            'url' => 'https://www.olx.ro/d/oferta/match-listing.html',
            'title' => 'Laptop Gaming Pro',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'last_seen_at' => now(),
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'miss-listing',
            'url' => 'https://www.olx.ro/d/oferta/miss-listing.html',
            'title' => 'Telefon vechi',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/deals?search=laptop')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.meta.total', 1)
                ->where('deals.data.0.title', 'Laptop Gaming Pro')
                ->where('filters.search', 'laptop')
                ->where('filters.hasActiveFilters', true));
    }

    public function test_matches_intent_filter_is_on_by_default_and_can_be_disabled(): void
    {
        $user = $this->createUser('deals-index-intent@example.test');
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'matching-listing',
            'url' => 'https://www.olx.ro/d/oferta/matching-listing.html',
            'title' => 'Matching deal',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'last_seen_at' => now(),
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'non-matching-listing',
            'url' => 'https://www.olx.ro/d/oferta/non-matching-listing.html',
            'title' => 'Non-matching deal',
            'price_currency' => 'RON',
            'matches_intent' => false,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.meta.total', 1)
                ->where('filters.matchesIntent', true)
                ->where('filters.hasActiveFilters', false));

        $this->actingAs($user)
            ->get('/deals?matches_intent=0')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.meta.total', 2)
                ->where('filters.matchesIntent', false)
                ->where('filters.hasActiveFilters', true));
    }

    public function test_new_items_filter_uses_the_24_hour_window(): void
    {
        $user = $this->createUser('deals-index-new@example.test');
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        $fresh = Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'fresh-listing',
            'url' => 'https://www.olx.ro/d/oferta/fresh-listing.html',
            'title' => 'Fresh deal',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'last_seen_at' => now(),
        ]);
        $stale = Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'stale-listing',
            'url' => 'https://www.olx.ro/d/oferta/stale-listing.html',
            'title' => 'Stale deal',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'last_seen_at' => now(),
        ]);
        $stale->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->actingAs($user)
            ->get('/deals?new_items=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.meta.total', 1)
                ->where('deals.data.0.title', 'Fresh deal')
                ->where('deals.data.0.id', $fresh->id)
                ->where('filters.newItems', true));
    }

    public function test_sorting_and_direction_are_applied_and_serialized(): void
    {
        $user = $this->createUser('deals-index-sort@example.test');
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'alpha-listing',
            'url' => 'https://www.olx.ro/d/oferta/alpha-listing.html',
            'title' => 'Alpha deal',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'last_seen_at' => now(),
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'zulu-listing',
            'url' => 'https://www.olx.ro/d/oferta/zulu-listing.html',
            'title' => 'Zulu deal',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/deals?sort=title&direction=desc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'title')
                ->where('filters.direction', 'desc')
                ->where('deals.data.0.title', 'Zulu deal')
                ->where('deals.data.1.title', 'Alpha deal'));

        $this->actingAs($user)
            ->get('/deals?sort=title&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.direction', 'asc')
                ->where('deals.data.0.title', 'Alpha deal'));
    }

    public function test_invalid_sort_falls_back_to_last_seen_desc(): void
    {
        $user = $this->createUser('deals-index-invalid-sort@example.test');
        $this->createDealFor($user);

        $this->actingAs($user)
            ->get('/deals?sort=password&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'last_seen_at')
                ->where('filters.direction', 'desc'));
    }

    public function test_pagination_exposes_link_metadata_and_preserves_query_string(): void
    {
        $user = $this->createUser('deals-index-pagination@example.test');
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        for ($i = 0; $i < 25; $i++) {
            Deal::create([
                'hunted_deal_id' => $huntedDeal->id,
                'external_id' => 'paginated-listing-'.$i,
                'url' => 'https://www.olx.ro/d/oferta/paginated-'.$i.'.html',
                'title' => 'Paginated deal '.$i,
                'price_currency' => 'RON',
                'matches_intent' => true,
                'last_seen_at' => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($user)
            ->get('/deals?sort=title&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('deals.data', 20)
                ->where('deals.meta.total', 25)
                ->where('deals.meta.lastPage', 2)
                ->where('deals.meta.currentPage', 1)
                ->has('deals.links')
                ->where('deals.links', function ($links) {
                    $next = collect($links)->last();

                    return $next['url'] !== null
                        && str_contains($next['url'], 'page=2')
                        && str_contains($next['url'], 'sort=title')
                        && str_contains($next['url'], 'direction=asc');
                }));

        $this->actingAs($user)
            ->get('/deals?page=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('deals.data', 5)
                ->where('deals.meta.currentPage', 2));
    }

    public function test_filter_counts_are_exposed(): void
    {
        $user = $this->createUser('deals-index-counts@example.test');
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'working-listing',
            'url' => 'https://www.olx.ro/d/oferta/working-listing.html',
            'title' => 'Working deal',
            'price_currency' => 'RON',
            'matches_intent' => true,
            'likely_working' => true,
            'last_seen_at' => now(),
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'other-listing',
            'url' => 'https://www.olx.ro/d/oferta/other-listing.html',
            'title' => 'Other deal',
            'price_currency' => 'RON',
            'matches_intent' => false,
            'likely_working' => false,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filterCounts.total', 2)
                ->where('filterCounts.newItems', 2)
                ->where('filterCounts.matchesIntent', 1)
                ->where('filterCounts.likelyWorking', 1)
                ->where('filterCounts.priceDrops', 0));
    }

    public function test_hunted_deal_filter_scopes_to_owned_hunted_deal_only(): void
    {
        $user = $this->createUser('deals-index-hunted@example.test');
        $otherUser = $this->createUser('deals-index-hunted-other@example.test', 'Other User');
        $this->createDealFor($user, ['title' => 'Own deal']);
        $this->createDealFor($otherUser, ['title' => 'Foreign deal']);

        $ownHuntedDeal = $user->huntedDeals()->first();

        $this->actingAs($user)
            ->get('/deals?hunted_deal='.$ownHuntedDeal->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.meta.total', 1)
                ->where('deals.data.0.title', 'Own deal')
                ->where('filters.huntedDeal', $ownHuntedDeal->id));

        $foreignHuntedDeal = $otherUser->huntedDeals()->first();
        $this->actingAs($user)
            ->get('/deals?hunted_deal='.$foreignHuntedDeal->id)
            ->assertNotFound();
    }

    public function test_unknown_hunted_deal_filter_returns_404(): void
    {
        $user = $this->createUser('deals-index-missing@example.test');

        $this->actingAs($user)
            ->get('/deals?hunted_deal=99999')
            ->assertNotFound();
    }
}
