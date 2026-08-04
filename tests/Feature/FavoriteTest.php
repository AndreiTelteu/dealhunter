<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\HuntedDeal;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'secret-password',
        ]);
    }

    private function createDealForUser(User $user): Deal
    {
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'iphone',
        ]);

        return Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'external-1',
            'url' => 'https://olx.ro/anunt',
            'title' => 'iPhone 12 128GB',
        ]);
    }

    public function test_user_can_toggle_favorite_on_own_deal(): void
    {
        $user = $this->createUser('toggle@example.com');
        $deal = $this->createDealForUser($user);

        $response = $this->actingAs($user)->postJson(route('deals.favorite.toggle', $deal));

        $response->assertOk()->assertJson(['favorited' => true, 'count' => 1]);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'deal_id' => $deal->id]);
    }

    public function test_user_can_unfavorite_a_deal(): void
    {
        $user = $this->createUser('unfavorite@example.com');
        $deal = $this->createDealForUser($user);
        $user->favorites()->create(['deal_id' => $deal->id]);

        $response = $this->actingAs($user)->postJson(route('deals.favorite.toggle', $deal));

        $response->assertOk()->assertJson(['favorited' => false, 'count' => 0]);
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'deal_id' => $deal->id]);
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $user = $this->createUser('guest@example.com');
        $deal = $this->createDealForUser($user);

        $this->postJson(route('deals.favorite.toggle', $deal))->assertUnauthorized();
    }

    public function test_user_cannot_favorite_another_users_deal(): void
    {
        $owner = $this->createUser('owner@example.com');
        $intruder = $this->createUser('intruder@example.com');
        $deal = $this->createDealForUser($owner);

        $this->actingAs($intruder)->postJson(route('deals.favorite.toggle', $deal))->assertForbidden();
    }

    public function test_favorites_index_lists_user_favorites(): void
    {
        $user = $this->createUser('list@example.com');
        $deal = $this->createDealForUser($user);
        $user->favorites()->create(['deal_id' => $deal->id]);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk()->assertSee($deal->title);
    }

    public function test_favorites_index_is_empty_without_favorites(): void
    {
        $user = $this->createUser('empty@example.com');

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk()->assertSee('Nicio favorită încă');
    }
}
