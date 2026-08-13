<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\DealMedia;
use App\Models\DealSnapshot;
use App\Models\HuntedDeal;
use App\Models\HuntedDealPriceSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HuntedDealsShowInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    private function createUser(string $email = 'hunted-deals-show@example.test', string $name = 'Hunted Deals Show User'): User
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

    private function createDealFor(HuntedDeal $huntedDeal, array $attributes = []): Deal
    {
        return Deal::create(array_merge([
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
        ], $attributes));
    }

    public function test_guests_cannot_access_the_hunted_deal_show_page(): void
    {
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user);

        $this->get(route('hunted-deals.show', $huntedDeal))->assertRedirect('/login');
    }

    public function test_show_returns_404_for_another_users_hunted_deal(): void
    {
        $owner = $this->createUser('show-owner@example.test', 'Owner');
        $intruder = $this->createUser('show-intruder@example.test', 'Intruder');
        $huntedDeal = $this->createHuntedDealFor($owner);

        $this->actingAs($intruder)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertNotFound();
    }

    public function test_show_renders_the_inertia_component_with_minimal_props(): void
    {
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user, ['notes' => 'Some notes']);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HuntedDeals/Show')
                ->where('huntedDeal.id', $huntedDeal->id)
                ->where('huntedDeal.searchTerm', 'iPhone 13')
                ->where('huntedDeal.isActive', true)
                ->where('huntedDeal.notes', 'Some notes')
                ->where('huntedDeal.lastCrawledAt', null)
                ->where('huntedDeal.lastCrawledAtDate', null)
                ->where('huntedDeal.createdAt', $huntedDeal->created_at->format('d M Y'))
                ->where('huntedDeal.createdAtTime', $huntedDeal->created_at->format('H:i'))
                ->has('huntedDeal.showUrl')
                ->has('huntedDeal.editUrl')
                ->where('stats.totalDeals', 0)
                ->where('stats.newDeals24h', 0)
                ->where('stats.matchingIntent', 0)
                ->where('stats.likelyWorking', 0)
                ->where('stats.priceDrops', 0)
                ->where('filterCounts.total', 0)
                ->where('filterCounts.newItems', 0)
                ->where('filterCounts.matchesIntent', 0)
                ->where('filterCounts.likelyWorking', 0)
                ->where('filterCounts.priceDrops', 0)
                ->where('filters.search', null)
                ->where('filters.sort', 'last_seen_at')
                ->where('filters.direction', 'desc')
                ->where('filters.priceDrops', false)
                ->where('filters.newItems', false)
                ->where('filters.matchesIntent', true)
                ->where('filters.likelyWorking', false)
                ->where('filters.hasActiveFilters', false)
                ->where('chart.hasTrace', false)
                ->where('chart.sampleCount', 0)
                ->where('chart.currency', 'RON')
                ->has('chart.samples', 0)
                ->where('chart.latestSnapshot', null)
                ->has('deals.data', 0)
                ->where('deals.meta.total', 0)
                ->where('deals.meta.perPage', 20)
                ->where('links.index', route('hunted-deals.index'))
                ->where('links.edit', route('hunted-deals.edit', $huntedDeal))
                ->where('links.dealsIndex', route('deals.index', ['hunted_deal' => $huntedDeal->id]))
                ->where('links.reset', route('hunted-deals.show', $huntedDeal)));
    }

    public function test_show_computes_crawl_statistics(): void
    {
        $user = $this->createUser('show-stats@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);

        $this->createDealFor($huntedDeal, ['matches_intent' => true, 'likely_working' => true]);
        $this->createDealFor($huntedDeal, ['matches_intent' => false, 'likely_working' => false]);
        $oldDeal = $this->createDealFor($huntedDeal, ['matches_intent' => true, 'likely_working' => false]);
        $oldDeal->created_at = now()->subDays(2);
        $oldDeal->save();

        $this->actingAs($user)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalDeals', 3)
                ->where('stats.newDeals24h', 2)
                ->where('stats.matchingIntent', 2)
                ->where('stats.likelyWorking', 1)
                ->where('stats.priceDrops', 0)
                ->where('filterCounts.total', 3)
                ->where('filterCounts.newItems', 2)
                ->where('filterCounts.matchesIntent', 2)
                ->where('filterCounts.likelyWorking', 1));
    }

    public function test_show_counts_price_drops_from_deals_with_multiple_snapshots(): void
    {
        $user = $this->createUser('show-drops@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);
        $deal = $this->createDealFor($huntedDeal);

        DealSnapshot::create([
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'price_amount' => 3000,
            'captured_at' => now()->subDay(),
        ]);
        DealSnapshot::create([
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'price_amount' => 2500,
            'captured_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.priceDrops', 1)
                ->where('filterCounts.priceDrops', 1));
    }

    public function test_show_exposes_only_local_media_and_never_remote_urls(): void
    {
        Storage::fake('public');
        $user = $this->createUser('show-media@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);
        $deal = $this->createDealFor($huntedDeal, ['image_urls' => ['https://frankfurt.apollo.olxcdn.com/remote.jpg']]);

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
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.data.0.media', fn ($media) => count($media) === 1)
                ->where('deals.data.0.media.0.url', $localUrl))
            ->assertDontSee('https://frankfurt.apollo.olxcdn.com/remote.jpg');
    }

    public function test_show_serializes_the_associated_deal_ledger(): void
    {
        $user = $this->createUser('show-deals@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);
        $this->createDealFor($huntedDeal, [
            'title' => str_repeat('x', 100),
            'description' => str_repeat('d', 200),
            'location' => 'București',
            'price_amount' => 2500,
        ]);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.meta.total', 1)
                ->where('deals.data.0.title', fn ($title) => strlen($title) === 83)
                ->where('deals.data.0.description', fn ($description) => strlen($description) === 123)
                ->where('deals.data.0.isNew', true)
                ->where('deals.data.0.isFavorite', false)
                ->where('deals.data.0.location', 'București')
                ->where('deals.data.0.priceAmount', 2500)
                ->has('deals.data.0.createdAt')
                ->has('deals.data.0.showUrl')
                ->has('deals.data.0.externalUrl')
                ->has('deals.data.0.toggleFavoriteUrl'));
    }

    public function test_show_prefers_the_latest_snapshot_price_and_serializes_favorite_state(): void
    {
        $user = $this->createUser('show-favorite@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);
        $deal = $this->createDealFor($huntedDeal);

        DealSnapshot::create([
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'price_amount' => 999,
            'price_currency' => 'EUR',
            'captured_at' => now(),
        ]);

        $user->favorites()->create(['deal_id' => $deal->id]);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deals.data.0.priceAmount', 999)
                ->where('deals.data.0.priceCurrency', 'EUR')
                ->where('deals.data.0.isFavorite', true));
    }

    public function test_show_serializes_the_price_spectrum_trace(): void
    {
        $user = $this->createUser('show-chart@example.test');
        $huntedDeal = $this->createHuntedDealFor($user, ['last_crawled_at' => now()]);

        $first = now()->subDays(2);
        $last = now();
        HuntedDealPriceSnapshot::create([
            'hunted_deal_id' => $huntedDeal->id,
            'average_price' => 2200,
            'min_price' => 2000,
            'max_price' => 2400,
            'deals_count' => 3,
            'price_currency' => 'RON',
            'captured_at' => $first,
        ]);
        HuntedDealPriceSnapshot::create([
            'hunted_deal_id' => $huntedDeal->id,
            'average_price' => 2000,
            'min_price' => 1800,
            'max_price' => 2300,
            'deals_count' => 4,
            'price_currency' => 'RON',
            'captured_at' => $last,
        ]);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('chart.hasTrace', true)
                ->where('chart.sampleCount', 2)
                ->where('chart.currency', 'RON')
                ->where('chart.firstCaptured', $first->format('d M H:i'))
                ->where('chart.lastCaptured', $last->format('d M H:i'))
                ->has('chart.samples', 2)
                ->where('chart.samples.0.min', 2000)
                ->where('chart.samples.0.average', 2200)
                ->where('chart.samples.0.max', 2400)
                ->where('chart.samples.0.count', 3)
                ->where('chart.samples.0.timestamp', $first->timestamp)
                ->where('chart.samples.1.min', 1800)
                ->where('chart.samples.1.average', 2000)
                ->where('chart.latestSnapshot.minPrice', 1800)
                ->where('chart.latestSnapshot.averagePrice', 2000)
                ->where('chart.latestSnapshot.maxPrice', 2300)
                ->where('chart.latestSnapshot.dealsCount', 4)
                ->where('huntedDeal.lastCrawledAt', fn ($value) => $value !== null)
                ->where('huntedDeal.lastCrawledAtDate', $huntedDeal->last_crawled_at->format('d M Y'))
                ->where('huntedDeal.lastCrawledAtTime', $huntedDeal->last_crawled_at->format('H:i')));
    }

    public function test_show_serializes_a_single_snapshot_without_a_trace(): void
    {
        $user = $this->createUser('show-single@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);
        $capturedAt = now();

        HuntedDealPriceSnapshot::create([
            'hunted_deal_id' => $huntedDeal->id,
            'average_price' => 2300.5,
            'min_price' => 2000,
            'max_price' => 2600,
            'deals_count' => 5,
            'price_currency' => 'RON',
            'captured_at' => $capturedAt,
        ]);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('chart.hasTrace', false)
                ->where('chart.sampleCount', 1)
                ->has('chart.samples', 1)
                ->where('chart.latestSnapshot.minPrice', 2000)
                ->where('chart.latestSnapshot.averagePrice', 2300.5)
                ->where('chart.latestSnapshot.maxPrice', 2600)
                ->where('chart.latestSnapshot.dealsCount', 5)
                ->where('chart.latestSnapshot.priceCurrency', 'RON')
                ->where('chart.latestSnapshot.capturedAt', $capturedAt->format('d M Y, H:i')));
    }

    public function test_show_applies_and_serializes_deal_filters(): void
    {
        $user = $this->createUser('show-filters@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);
        $this->createDealFor($huntedDeal, ['title' => 'Alfa listing', 'matches_intent' => true]);
        $this->createDealFor($huntedDeal, ['title' => 'Beta listing', 'matches_intent' => false]);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', [
                $huntedDeal,
                'search' => 'alfa',
                'sort' => 'title',
                'direction' => 'asc',
                'price_drops' => 1,
                'new_items' => 1,
                'matches_intent' => 0,
                'likely_working' => 1,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'alfa')
                ->where('filters.sort', 'title')
                ->where('filters.direction', 'asc')
                ->where('filters.priceDrops', true)
                ->where('filters.newItems', true)
                ->where('filters.matchesIntent', false)
                ->where('filters.likelyWorking', true)
                ->where('filters.hasActiveFilters', true));
    }

    public function test_show_falls_back_to_default_sort_for_an_invalid_sort(): void
    {
        $user = $this->createUser('show-sort@example.test');
        $huntedDeal = $this->createHuntedDealFor($user);

        $this->actingAs($user)
            ->get(route('hunted-deals.show', [$huntedDeal, 'sort' => 'bogus', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', 'last_seen_at')
                ->where('filters.direction', 'desc'));
    }
}
