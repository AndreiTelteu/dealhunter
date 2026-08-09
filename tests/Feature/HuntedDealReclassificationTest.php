<?php

namespace Tests\Feature;

use App\Jobs\ReclassifyHuntedDealIntent;
use App\Models\Deal;
use App\Models\DealSnapshot;
use App\Models\HuntedDeal;
use App\Models\HuntedDealPriceSnapshot;
use App\Models\User;
use App\Services\HuntedDealPriceHistoryService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HuntedDealReclassificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_search_term_edit_queues_reclassification_for_all_deals(): void
    {
        Queue::fake();
        $huntedDeal = $this->huntedDeal();

        $this->actingAs($huntedDeal->user)
            ->put(route('hunted-deals.update', $huntedDeal), $this->updatePayload($huntedDeal, [
                'search_term' => 'iphone 15',
            ]))
            ->assertRedirect(route('hunted-deals.show', $huntedDeal));

        Queue::assertPushed(ReclassifyHuntedDealIntent::class, fn (ReclassifyHuntedDealIntent $job): bool =>
            $job->huntedDealId === $huntedDeal->id && $job->scope === ReclassifyHuntedDealIntent::SCOPE_ALL
        );
    }

    public function test_exclusion_edit_queues_only_currently_matching_deals(): void
    {
        Queue::fake();
        $huntedDeal = $this->huntedDeal();

        $this->actingAs($huntedDeal->user)
            ->put(route('hunted-deals.update', $huntedDeal), $this->updatePayload($huntedDeal, [
                'excluded_phrases' => ['defect'],
            ]));

        Queue::assertPushed(ReclassifyHuntedDealIntent::class, fn (ReclassifyHuntedDealIntent $job): bool =>
            $job->scope === ReclassifyHuntedDealIntent::SCOPE_MATCHING
        );
    }

    public function test_preferred_phrase_edit_queues_only_currently_non_matching_deals(): void
    {
        Queue::fake();
        $huntedDeal = $this->huntedDeal();

        $this->actingAs($huntedDeal->user)
            ->put(route('hunted-deals.update', $huntedDeal), $this->updatePayload($huntedDeal, [
                'preferred_phrases' => ['pro max'],
            ]));

        Queue::assertPushed(ReclassifyHuntedDealIntent::class, fn (ReclassifyHuntedDealIntent $job): bool =>
            $job->scope === ReclassifyHuntedDealIntent::SCOPE_NON_MATCHING
        );
    }

    public function test_exclusion_and_preferred_phrase_edits_queue_all_deals(): void
    {
        Queue::fake();
        $huntedDeal = $this->huntedDeal();

        $this->actingAs($huntedDeal->user)
            ->put(route('hunted-deals.update', $huntedDeal), $this->updatePayload($huntedDeal, [
                'excluded_phrases' => ['defect'],
                'preferred_phrases' => ['pro max'],
            ]));

        Queue::assertPushed(ReclassifyHuntedDealIntent::class, fn (ReclassifyHuntedDealIntent $job): bool =>
            $job->scope === ReclassifyHuntedDealIntent::SCOPE_ALL
        );
    }

    public function test_price_history_is_rebuilt_from_the_historical_deal_states(): void
    {
        $huntedDeal = $this->huntedDeal();
        $deal = $this->deal($huntedDeal);
        $firstCapture = Carbon::parse('2026-08-01 10:00:00');
        $secondCapture = Carbon::parse('2026-08-01 11:00:00');

        DealSnapshot::create([
            ...$this->snapshotData($deal, 1000, true),
            'captured_at' => $firstCapture,
        ]);
        DealSnapshot::create([
            ...$this->snapshotData($deal, 1200, false),
            'captured_at' => $secondCapture,
        ]);
        HuntedDealPriceSnapshot::create([
            'hunted_deal_id' => $huntedDeal->id,
            'average_price' => 999,
            'min_price' => 999,
            'max_price' => 999,
            'deals_count' => 1,
            'price_currency' => 'RON',
            'captured_at' => $firstCapture,
        ]);
        HuntedDealPriceSnapshot::create([
            'hunted_deal_id' => $huntedDeal->id,
            'average_price' => 999,
            'min_price' => 999,
            'max_price' => 999,
            'deals_count' => 1,
            'price_currency' => 'RON',
            'captured_at' => $secondCapture,
        ]);

        app(HuntedDealPriceHistoryService::class)->rebuild($huntedDeal);

        $snapshots = $huntedDeal->fresh()->priceSnapshots()->get();
        $this->assertCount(1, $snapshots);
        $this->assertSame($firstCapture->toDateTimeString(), $snapshots->sole()->captured_at->toDateTimeString());
        $this->assertSame('1000.00', $snapshots->sole()->average_price);
        $this->assertSame(1, $snapshots->sole()->deals_count);
    }

    private function huntedDeal(): HuntedDeal
    {
        $user = User::create([
            'name' => 'Reclassification Test User',
            'email' => 'reclassification-'.uniqid().'@example.test',
            'password' => 'secret-password',
        ]);

        return HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'iphone',
            'is_active' => true,
        ]);
    }

    private function deal(HuntedDeal $huntedDeal): Deal
    {
        return Deal::create([
            'hunted_deal_id' => $huntedDeal->id,
            'external_id' => 'deal-'.uniqid(),
            'url' => 'https://example.test/deal',
            'title' => 'iPhone 14',
            'price_amount' => 1200,
            'price_currency' => 'RON',
            'matches_intent' => true,
            'intent_score' => 100,
            'likely_working' => true,
            'confidence' => .9,
        ]);
    }

    private function updatePayload(HuntedDeal $huntedDeal, array $overrides): array
    {
        return array_merge([
            'search_term' => $huntedDeal->search_term,
            'excluded_phrases' => $huntedDeal->excluded_phrases ?? [],
            'preferred_phrases' => $huntedDeal->preferred_phrases ?? [],
            'is_active' => true,
            'notes' => null,
        ], $overrides);
    }

    private function snapshotData(Deal $deal, float $price, bool $matchesIntent): array
    {
        return [
            'deal_id' => $deal->id,
            'url' => $deal->url,
            'title' => $deal->title,
            'price_amount' => $price,
            'price_currency' => 'RON',
            'matches_intent' => $matchesIntent,
            'intent_score' => $matchesIntent ? 100 : 0,
            'likely_working' => true,
            'confidence' => .9,
        ];
    }
}
