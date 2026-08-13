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

class DealsShowInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    private function createUser(string $email = 'deals-show@example.test', string $name = 'Deals Show User'): User
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
            'title' => 'Inertia detail listing',
            'price_amount' => 1500,
            'price_currency' => 'RON',
            'matches_intent' => true,
            'intent_score' => 88,
            'likely_working' => false,
            'confidence' => 0.91,
            'location' => 'Bucuresti',
            'seller_name' => 'Vanzatorul',
            'last_seen_at' => now(),
        ], $attributes));
    }

    public function test_guests_cannot_access_the_deal_detail(): void
    {
        $this->get('/deals/1')->assertRedirect('/login');
    }

    public function test_foreign_deal_detail_is_forbidden(): void
    {
        $owner = $this->createUser('deals-show-owner@example.test', 'Owner');
        $intruder = $this->createUser('deals-show-intruder@example.test', 'Intruder');
        $deal = $this->createDealFor($owner);

        $this->actingAs($intruder)
            ->get('/deals/'.$deal->id)
            ->assertForbidden();
    }

    public function test_deal_detail_renders_the_inertia_component_with_explicit_props(): void
    {
        $user = $this->createUser();
        $deal = $this->createDealFor($user, ['description' => 'Un laptop intretinut']);

        $this->actingAs($user)
            ->get('/deals/'.$deal->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Deals/Show')
                ->where('deal.id', $deal->id)
                ->where('deal.title', 'Inertia detail listing')
                ->where('deal.isFavorite', false)
                ->where('deal.externalUrl', $deal->url)
                ->has('deal.media')
                ->has('deal.toggleFavoriteUrl')
                ->has('deal.createdAtLabel')
                ->has('deal.lastSeenAtLabel')
                ->where('current.title', 'Inertia detail listing')
                ->where('current.priceAmount', 1500)
                ->where('current.priceCurrency', 'RON')
                ->where('current.description', 'Un laptop intretinut')
                ->where('current.matchesIntent', true)
                ->where('current.intentScore', 88)
                ->where('current.likelyWorking', false)
                ->where('current.confidence', 0.91)
                ->where('current.location', 'Bucuresti')
                ->where('current.sellerName', 'Vanzatorul')
                ->where('current.snapshotCapturedAtLabel', null)
                ->has('snapshots', 0)
                ->where('huntedDeal.searchTerm', 'laptop')
                ->where('huntedDeal.isActive', true)
                ->has('huntedDeal.showUrl')
                ->has('links.index'));
    }

    public function test_deal_detail_prefers_the_latest_snapshot_for_the_current_readout(): void
    {
        $user = $this->createUser('deals-show-snapshot@example.test');
        $deal = $this->createDealFor($user);
        DealSnapshot::create([
            'deal_id' => $deal->id,
            'title' => 'Titlu actualizat',
            'price_amount' => 1200,
            'price_currency' => 'EUR',
            'location' => 'Cluj',
            'seller_name' => 'Alt vanzator',
            'captured_at' => now()->subHour(),
        ]);
        DealSnapshot::create([
            'deal_id' => $deal->id,
            'title' => 'Titlu nou',
            'price_amount' => 1100,
            'price_currency' => 'EUR',
            'captured_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/deals/'.$deal->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('current.title', 'Titlu nou')
                ->where('current.priceAmount', 1100)
                ->where('current.priceCurrency', 'EUR')
                ->where('current.snapshotCapturedAtLabel', fn ($label) => $label !== null)
                ->has('snapshots', 2)
                ->where('snapshots.0.priceAmount', 1100)
                ->where('snapshots.1.priceAmount', 1200)
                ->where('snapshots.1.location', 'Cluj')
                ->where('snapshots.1.sellerName', 'Alt vanzator')
                ->where('snapshots.0.capturedAtLabel', fn ($label) => is_string($label)));
    }

    public function test_deal_detail_keeps_null_classification_fields_null(): void
    {
        $user = $this->createUser('deals-show-unclassified@example.test');
        $deal = $this->createDealFor($user, [
            'matches_intent' => null,
            'likely_working' => null,
            'confidence' => null,
        ]);

        $this->actingAs($user)
            ->get('/deals/'.$deal->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('current.matchesIntent', null)
                ->where('current.likelyWorking', null)
                ->where('current.confidence', null));
    }

    public function test_favorite_state_reflects_the_users_favorites(): void
    {
        $user = $this->createUser('deals-show-favorite@example.test');
        $deal = $this->createDealFor($user);
        $user->favorites()->create(['deal_id' => $deal->id]);

        $this->actingAs($user)
            ->get('/deals/'.$deal->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deal.isFavorite', true));
    }

    public function test_deal_detail_exposes_only_local_media_and_never_remote_urls(): void
    {
        Storage::fake('public');
        $user = $this->createUser('deals-show-media@example.test');
        $deal = $this->createDealFor($user, [
            'image_urls' => ['https://frankfurt.apollo.olxcdn.com/remote.jpg'],
        ]);
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
            ->get('/deals/'.$deal->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('deal.media', fn ($media) => count($media) === 1)
                ->where('deal.media.0.url', $localUrl))
            ->assertDontSee('https://frankfurt.apollo.olxcdn.com/remote.jpg');
    }
}
