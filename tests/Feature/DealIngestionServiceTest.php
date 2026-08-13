<?php

namespace Tests\Feature;

use App\Jobs\DownloadDealMedia;
use App\Models\DealMedia;
use App\Models\HuntedDeal;
use App\Models\User;
use App\Services\Crawlers\ParsedListing;
use App\Services\DealIngestionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DealIngestionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_it_persists_complete_listing_data_and_only_queues_media_when_images_change(): void
    {
        Queue::fake();
        $huntedDeal = $this->huntedDeal();
        $listing = $this->listing(['https://frankfurt.apollo.olxcdn.com/one.jpg']);
        $service = app(DealIngestionService::class);

        $service->upsertDeal($huntedDeal, $listing);
        $deal = $huntedDeal->deals()->sole();

        $this->assertSame("Descriere completă\ncu detalii.", $deal->description);
        $this->assertSame($listing->imageUrls, $deal->image_urls);
        $this->assertSame($listing->description, $deal->snapshots()->sole()->description);
        Queue::assertPushed(DownloadDealMedia::class, fn (DownloadDealMedia $job): bool => $job->dealId === $deal->id);

        // Simulate the media download completing so an unchanged re-crawl does
        // not re-queue it.
        DealMedia::create([
            'deal_id' => $deal->id,
            'source_url' => 'https://frankfurt.apollo.olxcdn.com/one.jpg',
            'source_hash' => hash('sha256', 'https://frankfurt.apollo.olxcdn.com/one.jpg'),
            'disk' => 'public',
            'path' => 'deal-media/'.$deal->id.'/one.jpg',
            'position' => 0,
        ]);

        Queue::fake();
        $service->upsertDeal($huntedDeal, $listing);
        Queue::assertNothingPushed();

        $service->upsertDeal($huntedDeal, $this->listing(['https://frankfurt.apollo.olxcdn.com/two.jpg']));
        Queue::assertPushed(DownloadDealMedia::class, fn (DownloadDealMedia $job): bool => $job->dealId === $deal->id);
    }

    public function test_it_persists_image_only_changes_removes_stale_media_and_queues_after_commit(): void
    {
        Queue::fake();
        Storage::fake('public');
        $huntedDeal = $this->huntedDeal();
        $service = app(DealIngestionService::class);
        $service->upsertDeal($huntedDeal, $this->listing(['https://frankfurt.apollo.olxcdn.com/one.jpg', 'https://frankfurt.apollo.olxcdn.com/two.jpg']));
        $deal = $huntedDeal->deals()->sole();
        $stalePath = 'deal-media/'.$deal->id.'/stale.jpg';
        Storage::disk('public')->put($stalePath, 'stale');
        DealMedia::create([
            'deal_id' => $deal->id,
            'source_url' => 'https://frankfurt.apollo.olxcdn.com/one.jpg',
            'source_hash' => hash('sha256', 'https://frankfurt.apollo.olxcdn.com/one.jpg'),
            'disk' => 'public',
            'path' => $stalePath,
            'position' => 0,
        ]);

        Queue::fake();
        $service->upsertDeal($huntedDeal, $this->listing(['https://frankfurt.apollo.olxcdn.com/two.jpg']));

        $this->assertSame(['https://frankfurt.apollo.olxcdn.com/two.jpg'], $deal->fresh()->image_urls);
        $this->assertDatabaseMissing('deal_media', ['deal_id' => $deal->id, 'source_url' => 'https://frankfurt.apollo.olxcdn.com/one.jpg']);
        Storage::disk('public')->assertMissing($stalePath);
        Queue::assertPushed(DownloadDealMedia::class, fn (DownloadDealMedia $job): bool => $job->dealId === $deal->id);
    }

    public function test_it_backfills_missing_description_and_images_for_existing_deal(): void
    {
        Queue::fake();
        $huntedDeal = $this->huntedDeal();
        $service = app(DealIngestionService::class);

        // First crawl: no description and no images are captured yet.
        $service->upsertDeal($huntedDeal, $this->listing([], ''));
        $deal = $huntedDeal->deals()->sole();
        $this->assertSame('', $deal->description);
        $this->assertSame([], $deal->image_urls);

        // Second crawl: same listing now yields a description and images.
        Queue::fake();
        $service->upsertDeal($huntedDeal, $this->listing(['https://frankfurt.apollo.olxcdn.com/fresh.jpg']));

        $deal->refresh();
        $this->assertSame("Descriere completă\ncu detalii.", $deal->description);
        $this->assertSame(['https://frankfurt.apollo.olxcdn.com/fresh.jpg'], $deal->image_urls);
        Queue::assertPushed(DownloadDealMedia::class, fn (DownloadDealMedia $job): bool => $job->dealId === $deal->id);
    }

    public function test_it_requeues_media_download_when_images_exist_but_are_not_downloaded(): void
    {
        Queue::fake();
        $huntedDeal = $this->huntedDeal();
        $service = app(DealIngestionService::class);

        $service->upsertDeal($huntedDeal, $this->listing(['https://frankfurt.apollo.olxcdn.com/one.jpg']));
        $deal = $huntedDeal->deals()->sole();
        $this->assertSame(0, $deal->media()->count());

        // Same listing again — image_urls unchanged, media still missing.
        Queue::fake();
        $service->upsertDeal($huntedDeal, $this->listing(['https://frankfurt.apollo.olxcdn.com/one.jpg']));

        Queue::assertPushed(DownloadDealMedia::class, fn (DownloadDealMedia $job): bool => $job->dealId === $deal->id);
    }

    private function listing(array $imageUrls, string $description = "Descriere completă\ncu detalii."): ParsedListing
    {
        return new ParsedListing(
            externalId: 'listing-1',
            url: 'https://www.olx.ro/d/oferta/listing-IDabc.html',
            title: 'Listing',
            priceRaw: '100 lei',
            priceAmount: 100,
            priceCurrency: 'RON',
            description: $description,
            location: 'Bucuresti',
            sellerName: 'Seller',
            sellerUrl: null,
            postedAt: null,
            imageUrls: $imageUrls,
        );
    }

    private function huntedDeal(): HuntedDeal
    {
        $user = User::create([
            'name' => 'Media Test User',
            'email' => 'media-test@example.test',
            'password' => 'secret-password',
        ]);

        return HuntedDeal::create([
            'user_id' => $user->id,
            'search_term' => 'laptop',
            'is_active' => true,
        ]);
    }
}
