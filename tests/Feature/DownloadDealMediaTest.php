<?php

namespace Tests\Feature;

use App\Jobs\DownloadDealMedia;
use App\Models\Deal;
use App\Models\DealMedia;
use App\Models\HuntedDeal;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadDealMediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_it_downloads_each_valid_image_once_and_keeps_going_after_invalid_media(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://frankfurt.apollo.olxcdn.com/good.jpg' => Http::response($this->jpegBytes(), 200, ['Content-Type' => 'image/jpeg']),
            'https://frankfurt.apollo.olxcdn.com/bad.jpg' => Http::response('not an image', 200, ['Content-Type' => 'text/html']),
        ]);
        $deal = $this->deal([
            'https://frankfurt.apollo.olxcdn.com/good.jpg',
            'https://frankfurt.apollo.olxcdn.com/bad.jpg',
            'https://example.test/not-allowed.jpg',
        ]);

        (new DownloadDealMedia($deal->id))->handle();
        (new DownloadDealMedia($deal->id))->handle();

        $media = $deal->media()->get();
        $this->assertCount(2, $media);
        Storage::disk('public')->assertExists($media->firstWhere('source_url', 'https://frankfurt.apollo.olxcdn.com/good.jpg')->path);
        $this->assertNotNull($media->firstWhere('source_url', 'https://frankfurt.apollo.olxcdn.com/bad.jpg')->failed_at);
        Http::assertSentCount(3);
    }

    public function test_it_records_malformed_image_bytes_as_permanent_failure(): void
    {
        Storage::fake('public');
        Http::fake(['https://frankfurt.apollo.olxcdn.com/corrupt.jpg' => Http::response('not a jpeg', 200, ['Content-Type' => 'image/jpeg'])]);
        $deal = $this->deal(['https://frankfurt.apollo.olxcdn.com/corrupt.jpg']);

        (new DownloadDealMedia($deal->id))->handle();

        $this->assertNotNull($deal->media()->sole()->failed_at);
        Storage::disk('public')->assertDirectoryEmpty('deal-media/'.$deal->id);
    }

    public function test_it_enforces_the_response_size_limit_before_persisting_media(): void
    {
        Storage::fake('public');
        Http::fake(['https://frankfurt.apollo.olxcdn.com/oversized.jpg' => Http::response($this->jpegBytes(), 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => (string) (21 * 1024 * 1024)])]);
        $deal = $this->deal(['https://frankfurt.apollo.olxcdn.com/oversized.jpg']);

        (new DownloadDealMedia($deal->id))->handle();

        $this->assertNotNull($deal->media()->sole()->failed_at);
        Storage::disk('public')->assertDirectoryEmpty('deal-media/'.$deal->id);
    }

    public function test_it_configures_retry_backoff_for_transient_failures(): void
    {
        $job = new DownloadDealMedia(1);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60, 300], $job->backoff);
    }

    public function test_it_preserves_url_order_when_existing_files_are_reordered(): void
    {
        Storage::fake('public');
        $deal = $this->deal(['https://frankfurt.apollo.olxcdn.com/one.jpg', 'https://frankfurt.apollo.olxcdn.com/two.jpg']);
        foreach ($deal->image_urls as $position => $url) {
            $path = "deal-media/{$deal->id}/{$position}.jpg";
            Storage::disk('public')->put($path, $this->jpegBytes());
            DealMedia::create(['deal_id' => $deal->id, 'source_url' => $url, 'source_hash' => hash('sha256', $url), 'disk' => 'public', 'path' => $path, 'position' => $position, 'downloaded_at' => now()]);
        }
        $deal->update(['image_urls' => array_reverse($deal->image_urls)]);

        (new DownloadDealMedia($deal->id))->handle();

        $this->assertSame($deal->fresh()->image_urls, $deal->fresh()->media->pluck('source_url')->all());
    }

    private function deal(array $imageUrls): Deal
    {
        $identifier = bin2hex(random_bytes(8));
        $user = User::create(['name' => 'Media Test User', 'email' => "media-{$identifier}@example.test", 'password' => 'secret-password']);
        $huntedDeal = HuntedDeal::create(['user_id' => $user->id, 'search_term' => 'laptop', 'is_active' => true]);

        return Deal::create(['hunted_deal_id' => $huntedDeal->id, 'external_id' => $identifier, 'url' => 'https://www.olx.ro/d/oferta/listing-IDabc.html', 'title' => 'Listing', 'price_currency' => 'RON', 'last_seen_at' => now(), 'image_urls' => $imageUrls]);
    }

    private function jpegBytes(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/Aaf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/Aaf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QH//Z', true);
    }
}
