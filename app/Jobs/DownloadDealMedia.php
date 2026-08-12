<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Models\DealMedia;
use App\Services\DealMediaAddressResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadDealMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_BYTES = 20 * 1024 * 1024;

    private const MAX_PIXELS = 40_000_000;

    public int $timeout = 120;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $dealId) {}

    public function handle(?DealMediaAddressResolver $addressResolver = null): void
    {
        $addressResolver ??= app(DealMediaAddressResolver::class);
        $deal = Deal::find($this->dealId);
        if (! $deal) {
            return;
        }

        foreach ($this->imageUrls($deal) as $position => $sourceUrl) {
            $this->download($deal, $sourceUrl, $position, $addressResolver);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('crawler')->error('Queued deal media download failed', [
            'deal_id' => $this->dealId,
            'error' => $exception->getMessage(),
        ]);
    }

    /** @return array<int, string> */
    private function imageUrls(Deal $deal): array
    {
        return array_values(array_unique(array_filter(
            $deal->image_urls ?? [],
            fn (mixed $url): bool => is_string($url) && $this->isAllowedImageUrl($url),
        )));
    }

    private function download(Deal $deal, string $sourceUrl, int $position, DealMediaAddressResolver $addressResolver): void
    {
        $sourceHash = hash('sha256', $sourceUrl);
        Cache::lock("deal-media:{$deal->id}:{$sourceHash}", 120)->block(5, function () use ($deal, $sourceUrl, $sourceHash, $position, $addressResolver): void {
            $this->downloadLocked($deal, $sourceUrl, $sourceHash, $position, $addressResolver);
        });
    }

    private function downloadLocked(Deal $deal, string $sourceUrl, string $sourceHash, int $position, DealMediaAddressResolver $addressResolver): void
    {
        $media = $this->mediaFor($deal, $sourceUrl, $sourceHash, $position);
        $disk = Storage::disk($media->disk);

        if ($media->path && $disk->exists($media->path)) {
            $media->update(['position' => $position]);

            return;
        }

        try {
            $addresses = $this->publicDestinationAddresses($sourceUrl, $addressResolver);
            [$temporaryPath, $mimeType, $byteSize] = $this->downloadToTemporaryFile($sourceUrl, $addresses);

            try {
                $extension = $this->validateImage($temporaryPath, $mimeType, $byteSize);
                $path = "deal-media/{$deal->id}/{$sourceHash}.{$extension}";
                $disk->put($path, fopen($temporaryPath, 'r'));
            } finally {
                @unlink($temporaryPath);
            }

            $media->update([
                'path' => $path,
                'position' => $position,
                'mime_type' => $mimeType,
                'byte_size' => $byteSize,
                'downloaded_at' => now(),
                'failed_at' => null,
            ]);
        } catch (PermanentMediaException $exception) {
            $media->update(['position' => $position, 'failed_at' => now()]);
            Log::channel('crawler')->warning('Rejected deal media', ['deal_id' => $deal->id, 'source_url' => $sourceUrl, 'error' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            Log::channel('crawler')->warning('Temporary deal media download failure', ['deal_id' => $deal->id, 'source_url' => $sourceUrl, 'error' => $exception->getMessage()]);

            throw $exception;
        }
    }

    private function mediaFor(Deal $deal, string $sourceUrl, string $sourceHash, int $position): DealMedia
    {
        try {
            return DealMedia::createOrFirst(
                ['deal_id' => $deal->id, 'source_hash' => $sourceHash],
                ['source_url' => $sourceUrl, 'disk' => 'public', 'position' => $position],
            );
        } catch (UniqueConstraintViolationException) {
            return DealMedia::query()->where('deal_id', $deal->id)->where('source_hash', $sourceHash)->firstOrFail();
        }
    }

    /** @return array<int, string> */
    private function publicDestinationAddresses(string $sourceUrl, DealMediaAddressResolver $addressResolver): array
    {
        $host = (string) parse_url($sourceUrl, PHP_URL_HOST);
        $addresses = $addressResolver->publicAddresses($host);

        if ($addresses === []) {
            throw new PermanentMediaException('The image host does not resolve exclusively to public IP addresses.');
        }

        return $addresses;
    }

    /** @return array{string, string, int} */
    private function downloadToTemporaryFile(string $sourceUrl, array $addresses): array
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'deal-media-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Unable to allocate temporary media storage.');
        }

        try {
            $host = (string) parse_url($sourceUrl, PHP_URL_HOST);
            $response = Http::withoutRedirecting()
                ->timeout(30)
                ->accept('image/avif,image/webp,image/apng,image/*,*/*;q=0.8')
                ->withOptions([
                    'sink' => $temporaryPath,
                    'curl' => [CURLOPT_RESOLVE => array_map(fn (string $address): string => "{$host}:443:{$address}", $addresses)],
                    'on_progress' => function (int $total, int $downloaded): void {
                        if ($total > self::MAX_BYTES || $downloaded > self::MAX_BYTES) {
                            throw new PermanentMediaException('The image exceeds the download size limit.');
                        }
                    },
                ])
                ->get($sourceUrl);

            if ($response->serverError()) {
                throw new \RuntimeException("Image server returned {$response->status()}.");
            }

            if (! $response->successful()) {
                throw new PermanentMediaException("Image server returned {$response->status()}.");
            }

            if ((int) $response->header('Content-Length', 0) > self::MAX_BYTES) {
                throw new PermanentMediaException('The image exceeds the download size limit.');
            }

            $byteSize = filesize($temporaryPath);
            if ($byteSize === false || $byteSize === 0 || $byteSize > self::MAX_BYTES) {
                throw new PermanentMediaException('The image is empty or exceeds the download size limit.');
            }

            return [$temporaryPath, Str::lower(trim(explode(';', $response->header('Content-Type', ''))[0])), $byteSize];
        } catch (\Throwable $exception) {
            @unlink($temporaryPath);

            throw $exception;
        }
    }

    private function validateImage(string $path, string $declaredMimeType, int $byteSize): string
    {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $imageInfo = @getimagesize($path);

        if (! is_string($mimeType) || $mimeType === 'image/svg+xml' || ! is_array($imageInfo) || $imageInfo[0] < 1 || $imageInfo[1] < 1 || ($imageInfo[0] * $imageInfo[1]) > self::MAX_PIXELS) {
            throw new PermanentMediaException('The downloaded bytes are not a safe raster image.');
        }

        $extension = $this->extensionForMimeType($mimeType);
        if (! $extension || $mimeType !== $declaredMimeType || $byteSize > self::MAX_BYTES) {
            throw new PermanentMediaException('The image type is unsupported or does not match the downloaded bytes.');
        }

        return $extension;
    }

    private function isAllowedImageUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = Str::lower((string) ($parts['host'] ?? ''));

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && ($parts['scheme'] ?? null) === 'https'
            && $host !== ''
            && ! isset($parts['user'], $parts['pass'], $parts['port'])
            && (Str::endsWith($host, '.olx.ro') || $host === 'olx.ro' || Str::endsWith($host, '.olxcdn.com'));
    }

    private function extensionForMimeType(string $mimeType): ?string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/gif' => 'gif',
            default => null,
        };
    }
}

class PermanentMediaException extends \RuntimeException {}
