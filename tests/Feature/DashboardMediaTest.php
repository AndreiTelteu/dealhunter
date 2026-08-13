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

class DashboardMediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_dashboard_renders_the_recent_deal_local_media_gallery_without_remote_url_fallback(): void
    {
        Storage::fake('public');
        $user = User::create(['name' => 'Dashboard User', 'email' => 'dashboard@example.test', 'password' => 'secret-password', 'email_verified_at' => now()]);
        $huntedDeal = HuntedDeal::create(['user_id' => $user->id, 'search_term' => 'laptop', 'is_active' => true]);
        $deal = Deal::create(['hunted_deal_id' => $huntedDeal->id, 'external_id' => 'dashboard-listing', 'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html', 'title' => 'Dashboard listing', 'price_currency' => 'RON', 'last_seen_at' => now(), 'image_urls' => ['https://frankfurt.apollo.olxcdn.com/remote.jpg']]);
        $path = 'deal-media/'.$deal->id.'/local.jpg';
        Storage::disk('public')->put($path, 'local-image');
        DealMedia::create(['deal_id' => $deal->id, 'source_url' => 'https://frankfurt.apollo.olxcdn.com/remote.jpg', 'source_hash' => hash('sha256', 'https://frankfurt.apollo.olxcdn.com/remote.jpg'), 'disk' => 'public', 'path' => $path, 'position' => 0, 'downloaded_at' => now()]);

        $localUrl = Storage::disk('public')->url($path);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('recentDeals.0.media.0.url', $localUrl))
            ->assertDontSee('https://frankfurt.apollo.olxcdn.com/remote.jpg');
    }
}
