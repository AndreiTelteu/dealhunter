<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\HuntedDeal;
use App\Models\HuntedDealPriceSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HuntedDealPriceSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    private function createHuntedDeal(): HuntedDeal
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user-'.uniqid().'@example.com',
            'password' => 'secret-password',
        ]);

        return HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'iphone',
        ]);
    }

    private function createDeal(HuntedDeal $huntedDeal, ?float $price, bool $matchesIntent, bool $likelyWorking): Deal
    {
        return Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => uniqid('ext-'),
            'url' => 'https://olx.ro/anunt',
            'title' => 'Listing',
            'price_amount' => $price,
            'price_currency' => 'RON',
            'matches_intent' => $matchesIntent,
            'likely_working' => $likelyWorking,
        ]);
    }

    public function test_command_snapshots_average_of_matching_working_priced_deals(): void
    {
        $huntedDeal = $this->createHuntedDeal();

        // Included: matching, working, priced
        $this->createDeal($huntedDeal, 1000, true, true);
        $this->createDeal($huntedDeal, 2000, true, true);

        // Excluded: no price ("Schimb"), not matching, not working
        $this->createDeal($huntedDeal, null, true, true);
        $this->createDeal($huntedDeal, 500, false, true);
        $this->createDeal($huntedDeal, 500, true, false);

        Artisan::call('deals:snapshot-average-prices');

        $snapshot = HuntedDealPriceSnapshot::sole();

        $this->assertSame($huntedDeal->id, $snapshot->hunted_deal_id);
        $this->assertSame('1500.00', $snapshot->average_price);
        $this->assertSame('1000.00', $snapshot->min_price);
        $this->assertSame('2000.00', $snapshot->max_price);
        $this->assertSame(2, $snapshot->deals_count);
        $this->assertSame('RON', $snapshot->price_currency);
        $this->assertNotNull($snapshot->captured_at);
    }

    public function test_command_skips_hunted_deals_without_matching_priced_deals(): void
    {
        $huntedDeal = $this->createHuntedDeal();

        // Only excluded deals
        $this->createDeal($huntedDeal, null, true, true);
        $this->createDeal($huntedDeal, 800, false, false);

        Artisan::call('deals:snapshot-average-prices');

        $this->assertSame(0, HuntedDealPriceSnapshot::count());
    }

    public function test_hunted_deal_price_snapshots_relation_orders_by_captured_at(): void
    {
        $huntedDeal = $this->createHuntedDeal();

        $huntedDeal->priceSnapshots()->create([
            'average_price' => 1200,
            'min_price' => 1200,
            'max_price' => 1200,
            'deals_count' => 3,
            'captured_at' => now()->subHours(2),
        ]);
        $huntedDeal->priceSnapshots()->create([
            'average_price' => 1100,
            'min_price' => 1100,
            'max_price' => 1100,
            'deals_count' => 4,
            'captured_at' => now()->subHours(1),
        ]);

        $this->assertSame(
            ['1200.00', '1100.00'],
            $huntedDeal->priceSnapshots()->pluck('average_price')->all()
        );
    }

    public function test_snapshots_are_deleted_with_the_hunted_deal(): void
    {
        $huntedDeal = $this->createHuntedDeal();
        $this->createDeal($huntedDeal, 1000, true, true);

        Artisan::call('deals:snapshot-average-prices');
        $this->assertSame(1, HuntedDealPriceSnapshot::count());

        $huntedDeal->forceDelete();

        $this->assertSame(0, HuntedDealPriceSnapshot::count());
    }
}
