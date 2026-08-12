<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\DealMedia;
use App\Models\HuntedDeal;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_dashboard_renders_the_inertia_component_with_statistics(): void
    {
        $user = User::create([
            'name' => 'Inertia User',
            'email' => 'inertia@example.test',
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'inertia-listing',
            'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html',
            'title' => 'Inertia listing',
            'price_amount' => 1234.56,
            'price_currency' => 'RON',
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('huntedDealsCount', 1)
                ->where('activeHuntedDealsCount', 1)
                ->where('totalDealsCount', 1)
                ->where('newDealsCount', 1)
                ->where('huntedDeals.0.searchTerm', 'laptop')
                ->where('huntedDeals.0.isActive', true)
                ->where('huntedDeals.0.dealsCount', 1)
                ->where('recentDeals.0.title', 'Inertia listing')
                ->where('recentDeals.0.priceAmount', 1234.56)
                ->where('recentDeals.0.priceCurrency', 'RON')
                ->where('recentDeals.0.isFavorite', false)
                ->has('links.huntedDealsIndex')
                ->has('links.dealsIndex'));
    }

    public function test_dashboard_exposes_only_local_media_and_never_remote_urls(): void
    {
        Storage::fake('public');
        $user = User::create([
            'name' => 'Inertia Media User',
            'email' => 'inertia-media@example.test',
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
        $huntedDeal = HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
        $deal = Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'inertia-media-listing',
            'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html',
            'title' => 'Inertia media listing',
            'price_currency' => 'RON',
            'last_seen_at' => now(),
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
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('recentDeals.0.media.0.url', $localUrl)
                ->where('recentDeals.0.media', fn ($media) => count($media) === 1))
            ->assertDontSee('https://frankfurt.apollo.olxcdn.com/remote.jpg');
    }

    public function test_dashboard_shares_auth_user_with_admin_flag(): void
    {
        $admin = User::create([
            'name' => 'Inertia Admin',
            'email' => 'admin@example.test',
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('appName', config('app.name'))
                ->where('auth.user.id', $admin->id)
                ->where('auth.user.name', 'Inertia Admin')
                ->where('auth.user.email', 'admin@example.test')
                ->where('auth.user.isAdmin', true));
    }

    public function test_dashboard_shares_flash_messages(): void
    {
        $user = User::create([
            'name' => 'Flash User',
            'email' => 'flash@example.test',
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['success' => 'Salvat cu succes'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Salvat cu succes')
                ->where('flash.error', null));
    }
}
