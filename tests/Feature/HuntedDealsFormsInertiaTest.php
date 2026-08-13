<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\HuntedDeal;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HuntedDealsFormsInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    private function createUser(string $email = 'hunted-deals-forms@example.test', string $name = 'Hunted Deals Forms User'): User
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

    public function test_guests_cannot_access_the_create_or_edit_forms(): void
    {
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user);

        $this->get(route('hunted-deals.create'))->assertRedirect('/login');
        $this->get(route('hunted-deals.edit', $huntedDeal))->assertRedirect('/login');
    }

    public function test_create_renders_the_inertia_component_with_links(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('hunted-deals.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HuntedDeals/Create')
                ->where('links.store', route('hunted-deals.store'))
                ->where('links.index', route('hunted-deals.index'))
            );
    }

    public function test_store_creates_a_hunted_deal_and_redirects_to_show(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('hunted-deals.store'), [
            'search_term' => 'MacBook Pro',
            'is_active' => true,
            'notes' => 'Looking for M1',
            'preferred_phrases' => [' pro ', 'pro', 'negru'],
            'excluded_phrases' => ['pentru piese'],
        ]);

        $huntedDeal = HuntedDeal::firstOrFail();

        $response->assertRedirect(route('hunted-deals.show', $huntedDeal));

        $this->assertSame($user->id, $huntedDeal->user_id);
        $this->assertSame('MacBook Pro', $huntedDeal->search_term);
        $this->assertTrue($huntedDeal->is_active);
        $this->assertSame('Looking for M1', $huntedDeal->notes);
        $this->assertSame(['pro', 'negru'], $huntedDeal->preferred_phrases);
        $this->assertSame(['pentru piese'], $huntedDeal->excluded_phrases);
    }

    public function test_store_validates_the_search_term(): void
    {
        $this->actingAs($this->createUser())
            ->post(route('hunted-deals.store'), ['search_term' => ''])
            ->assertSessionHasErrors('search_term');
    }

    public function test_store_rejects_a_duplicate_search_term_for_the_same_user(): void
    {
        $user = $this->createUser();
        $this->createHuntedDealFor($user, ['search_term' => 'MacBook Pro']);

        $this->actingAs($user)
            ->post(route('hunted-deals.store'), ['search_term' => 'MacBook Pro'])
            ->assertSessionHasErrors('search_term');
    }

    public function test_edit_renders_the_inertia_component_with_the_serialized_hunted_deal(): void
    {
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user, [
            'notes' => 'Some notes',
            'excluded_phrases' => ['defect'],
            'preferred_phrases' => ['pro max'],
            'last_crawled_at' => now(),
        ]);
        $this->createDealFor($huntedDeal);
        $this->createDealFor($huntedDeal);

        $this->actingAs($user)
            ->get(route('hunted-deals.edit', $huntedDeal))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HuntedDeals/Edit')
                ->where('huntedDeal.id', $huntedDeal->id)
                ->where('huntedDeal.searchTerm', $huntedDeal->search_term)
                ->where('huntedDeal.isActive', true)
                ->where('huntedDeal.notes', 'Some notes')
                ->where('huntedDeal.excludedPhrases', ['defect'])
                ->where('huntedDeal.preferredPhrases', ['pro max'])
                ->where('huntedDeal.dealsCount', 2)
                ->where('huntedDeal.createdAt', $huntedDeal->created_at->format('d M Y, H:i'))
                ->where('huntedDeal.updatedAt', $huntedDeal->updated_at->format('d M Y, H:i'))
                ->where('huntedDeal.lastCrawledAt', $huntedDeal->last_crawled_at->format('d M Y, H:i'))
                ->where('huntedDeal.showUrl', route('hunted-deals.show', $huntedDeal))
                ->where('huntedDeal.editUrl', route('hunted-deals.edit', $huntedDeal))
                ->where('links.index', route('hunted-deals.index'))
                ->where('links.update', route('hunted-deals.update', $huntedDeal))
                ->where('links.destroy', route('hunted-deals.destroy', $huntedDeal))
            );
    }

    public function test_edit_returns_404_for_another_users_hunted_deal(): void
    {
        $owner = $this->createUser('owner@example.test', 'Owner');
        $intruder = $this->createUser('intruder@example.test', 'Intruder');
        $huntedDeal = $this->createHuntedDealFor($owner);

        $this->actingAs($intruder)
            ->get(route('hunted-deals.edit', $huntedDeal))
            ->assertNotFound();
    }

    public function test_update_persists_changes_and_redirects_to_show(): void
    {
        Queue::fake();
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user, ['notes' => null]);

        $this->actingAs($user)
            ->put(route('hunted-deals.update', $huntedDeal), [
                'search_term' => 'MacBook Pro',
                'is_active' => false,
                'notes' => 'Updated notes',
                'preferred_phrases' => ['m1'],
                'excluded_phrases' => [],
            ])
            ->assertRedirect(route('hunted-deals.show', $huntedDeal));

        $huntedDeal->refresh();

        $this->assertSame('MacBook Pro', $huntedDeal->search_term);
        $this->assertFalse($huntedDeal->is_active);
        $this->assertSame('Updated notes', $huntedDeal->notes);
        $this->assertSame(['m1'], $huntedDeal->preferred_phrases);
        $this->assertSame([], $huntedDeal->excluded_phrases);
    }

    public function test_update_rejects_a_search_term_owned_by_another_hunted_deal(): void
    {
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user, ['search_term' => 'iPhone 13']);
        $this->createHuntedDealFor($user, ['search_term' => 'MacBook Pro']);

        $this->actingAs($user)
            ->put(route('hunted-deals.update', $huntedDeal), [
                'search_term' => 'MacBook Pro',
                'is_active' => true,
                'notes' => null,
                'preferred_phrases' => [],
                'excluded_phrases' => [],
            ])
            ->assertSessionHasErrors('search_term');
    }

    public function test_destroy_deletes_the_hunted_deal_and_redirects_to_index(): void
    {
        $user = $this->createUser();
        $huntedDeal = $this->createHuntedDealFor($user);

        $this->actingAs($user)
            ->delete(route('hunted-deals.destroy', $huntedDeal))
            ->assertRedirect(route('hunted-deals.index'));

        $this->assertDatabaseMissing('hunted_deals', ['id' => $huntedDeal->id]);
    }
}
