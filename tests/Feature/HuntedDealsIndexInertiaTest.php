<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\HuntedDeal;
use App\Models\HuntedDealPriceSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HuntedDealsIndexInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    private function createUser(string $email = 'hunted-deals-index@example.test', string $name = 'Hunted Deals Index User'): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
    }

    private function createHuntedDealFor(User $user, array $attributes = []): HuntedDeal
    {
        return HuntedDeal::create(array_merge([
            'user_id' => $user->id,
            'search_term' => 'iPhone 13',
            'is_active' => true,
        ], $attributes));
    }

    private function createDealFor(HuntedDeal $huntedDeal): Deal
    {
        return Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'listing-'.uniqid(),
            'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html',
            'title' => 'iPhone 13 128GB',
            'price_amount' => 2500,
            'price_currency' => 'RON',
            'matches_intent' => true,
            'intent_score' => 90,
            'likely_working' => true,
            'last_seen_at' => now(),
        ]);
    }

    public function test_guests_cannot_access_the_hunted_deals_index(): void
    {
        $this->get('/hunted-deals')->assertRedirect('/login');
    }

    public function test_hunted_deals_index_renders_the_inertia_component_with_minimal_props(): void
    {
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user, ['notes' => str_repeat('a', 300)]);
        $this->createDealFor($huntedDeal);
        $this->createDealFor($huntedDeal);

        $this->actingAs($user)
            ->get('/hunted-deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HuntedDeals/Index')
                ->has('huntedDeals.data', 1)
                ->has('huntedDeals.links')
                ->where('huntedDeals.meta.total', 1)
                ->where('huntedDeals.meta.currentPage', 1)
                ->where('huntedDeals.meta.perPage', 15)
                ->where('huntedDeals.data.0.searchTerm', 'iPhone 13')
                ->where('huntedDeals.data.0.isActive', true)
                ->where('huntedDeals.data.0.dealsCount', 2)
                ->where('huntedDeals.data.0.lastCrawledAt', null)
                ->where('huntedDeals.data.0.latestPriceSnapshot', null)
                ->has('huntedDeals.data.0.showUrl')
                ->has('huntedDeals.data.0.editUrl')
                ->has('huntedDeals.data.0.createdAt')
                ->has('huntedDeals.data.0.updatedAt')
                ->where('filters.search', null)
                ->where('filters.filter', null)
                ->where('filters.sort', 'updated_at')
                ->where('filters.direction', 'desc')
                ->where('filters.hasActiveFilters', false)
                ->has('links.index')
                ->has('links.create'));
    }

    public function test_hunted_deals_index_truncates_long_notes(): void
    {
        $user = $this->createUser('hunted-deals-notes@example.test');
        $this->createHuntedDealFor($user, ['notes' => str_repeat('x', 300)]);

        $this->actingAs($user)
            ->get('/hunted-deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.data.0.notes', fn ($notes) => strlen($notes) === 143));
    }

    public function test_hunted_deals_index_filters_by_state(): void
    {
        $user = $this->createUser('hunted-deals-filter@example.test');
        $this->createHuntedDealFor($user, ['search_term' => 'active term', 'is_active' => true]);
        $this->createHuntedDealFor($user, ['search_term' => 'paused term', 'is_active' => false]);

        $this->actingAs($user)
            ->get('/hunted-deals?filter=active')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.meta.total', 1)
                ->where('huntedDeals.data.0.searchTerm', 'active term')
                ->where('filters.filter', 'active')
                ->where('filters.hasActiveFilters', true));

        $this->actingAs($user)
            ->get('/hunted-deals?filter=inactive')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.meta.total', 1)
                ->where('huntedDeals.data.0.searchTerm', 'paused term'));
    }

    public function test_hunted_deals_index_filters_by_never_crawled_and_recently_crawled(): void
    {
        $user = $this->createUser('hunted-deals-crawl-filter@example.test');
        $this->createHuntedDealFor($user, ['search_term' => 'never crawled', 'last_crawled_at' => null]);
        $this->createHuntedDealFor($user, ['search_term' => 'crawled now', 'last_crawled_at' => now()]);
        $this->createHuntedDealFor($user, ['search_term' => 'crawled days ago', 'last_crawled_at' => now()->subDays(3)]);

        $this->actingAs($user)
            ->get('/hunted-deals?filter=never_crawled')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.meta.total', 1)
                ->where('huntedDeals.data.0.searchTerm', 'never crawled'));

        $this->actingAs($user)
            ->get('/hunted-deals?filter=recently_crawled')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.meta.total', 1)
                ->where('huntedDeals.data.0.searchTerm', 'crawled now'));
    }

    public function test_hunted_deals_index_searches_search_term_and_notes(): void
    {
        $user = $this->createUser('hunted-deals-search@example.test');
        $this->createHuntedDealFor($user, ['search_term' => 'MacBook Air', 'notes' => 'de vanzare']);
        $this->createHuntedDealFor($user, ['search_term' => 'iPhone', 'notes' => 'MacBook pe alocuri']);

        $this->actingAs($user)
            ->get('/hunted-deals?search=macbook')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.meta.total', 2)
                ->where('filters.search', 'macbook')
                ->where('filters.hasActiveFilters', true));

        $this->actingAs($user)
            ->get('/hunted-deals?search=vanzare')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.meta.total', 1)
                ->where('huntedDeals.data.0.searchTerm', 'MacBook Air'));
    }

    public function test_hunted_deals_index_serializes_the_latest_price_snapshot(): void
    {
        $user = $this->createUser('hunted-deals-snapshot@example.test');
        $huntedDeal = $this->createHuntedDealFor($user, ['last_crawled_at' => now()]);
        HuntedDealPriceSnapshot::create([
            'hunted_deal_id' => $huntedDeal->id,
            'average_price' => 2300.50,
            'min_price' => 2000,
            'max_price' => 2600,
            'deals_count' => 3,
            'price_currency' => 'RON',
            'captured_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/hunted-deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.data.0.lastCrawledAt', fn ($value) => $value !== null)
                ->where('huntedDeals.data.0.latestPriceSnapshot.minPrice', 2000)
                ->where('huntedDeals.data.0.latestPriceSnapshot.averagePrice', 2300.5)
                ->where('huntedDeals.data.0.latestPriceSnapshot.maxPrice', 2600)
                ->where('huntedDeals.data.0.latestPriceSnapshot.priceCurrency', 'RON'));
    }

    public function test_hunted_deals_index_is_scoped_to_the_authenticated_user(): void
    {
        $owner = $this->createUser('hunted-deals-owner@example.test', 'Owner');
        $intruder = $this->createUser('hunted-deals-intruder@example.test', 'Intruder');
        $this->createHuntedDealFor($owner, ['search_term' => 'owner term']);
        $this->createHuntedDealFor($intruder, ['search_term' => 'intruder term']);

        $this->actingAs($owner)
            ->get('/hunted-deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('huntedDeals.meta.total', 1)
                ->where('huntedDeals.data.0.searchTerm', 'owner term'));
    }

    public function test_hunted_deals_index_shows_empty_state_when_the_user_has_none(): void
    {
        $user = $this->createUser('hunted-deals-empty@example.test');

        $this->actingAs($user)
            ->get('/hunted-deals')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HuntedDeals/Index')
                ->where('huntedDeals.meta.total', 0)
                ->has('huntedDeals.data', 0)
                ->where('filters.hasActiveFilters', false));
    }
}
