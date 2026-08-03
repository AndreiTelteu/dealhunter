<?php

namespace App\Services\Crawlers;

use App\Models\HuntedDeal;
use App\Services\BaseService;
use App\Services\Crawlers\Mcp\PlaywrightMcpClient;
use App\Services\PriceParserService;
use Carbon\Carbon;

class OlxCrawlerService extends BaseService
{
    protected string $logChannel = 'crawler';

    private int $requestDelayMs;

    private int $maxPagesPerSearch;

    private int $maxListingsPerRun;

    private int $requestsPerMinute;

    private int $burstLimit;

    private float $availableRequestTokens;

    private float $lastTokenRefillAt;

    public function __construct(
        private readonly PriceParserService $priceParser,
        private readonly PlaywrightMcpClient $mcp,
    ) {
        parent::__construct();

        $this->requestDelayMs = max(0, (int) config('crawler.request_delay_ms', 2000));
        $this->maxPagesPerSearch = max(1, (int) config('crawler.max_pages_per_search', 3));
        $this->maxListingsPerRun = max(1, (int) config('crawler.max_listings_per_run', 100));
        $this->requestsPerMinute = max(1, (int) config('crawler.requests_per_minute', 30));
        $this->burstLimit = max(1, (int) config('crawler.burst_limit', 10));
        $this->availableRequestTokens = $this->burstLimit;
        $this->lastTokenRefillAt = microtime(true);
    }

    public function crawlHuntedDeal(HuntedDeal $huntedDeal): CrawlResult
    {
        return $this->executeWithErrorHandling(
            operation: fn (): CrawlResult => $this->performCrawl($huntedDeal),
            context: ['hunted_deal_id' => $huntedDeal->id, 'search_term' => $huntedDeal->search_term, 'user_id' => $huntedDeal->user_id],
            operationName: 'crawl_hunted_deal',
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function extractListings(string $searchTerm, int $maxPages = 3): array
    {
        $this->validateRequired(['searchTerm' => $searchTerm], ['searchTerm']);
        $this->assertCrawlingPermitted();
        $maxPages = min(max(1, $maxPages), $this->maxPagesPerSearch);
        $listings = [];

        try {
            $this->performMcpOperation(function (): null {
                $this->mcp->ensureInitialized();

                return null;
            });
            $this->navigateToSearch($searchTerm);

            for ($page = 1; $page <= $maxPages && count($listings) < $this->maxListingsPerRun; $page++) {
                $pageListings = $this->extractFromResultsPage();
                if ($pageListings === []) {
                    break;
                }

                $listings = array_merge($listings, array_slice($pageListings, 0, $this->maxListingsPerRun - count($listings)));
                if (count($listings) >= $this->maxListingsPerRun || ! $this->handlePagination()) {
                    break;
                }
            }

            return $this->enrichListingsFromDetailPages($listings);
        } finally {
            $this->mcp->closeSession();
        }
    }

    public function parseListingData(array $rawListing): ParsedListing
    {
        $externalId = ParsedListing::extractExternalIdFromUrl($rawListing['url'] ?? '') ?: ($rawListing['external_id'] ?? uniqid('olx_'));
        $priceData = ! empty($rawListing['price_raw']) ? $this->priceParser->parsePrice($rawListing['price_raw']) : null;

        return new ParsedListing(
            externalId: $externalId,
            url: ParsedListing::normalizeUrl($rawListing['url'] ?? ''),
            title: trim($rawListing['title'] ?? ''),
            priceRaw: $rawListing['price_raw'] ?? null,
            priceAmount: $priceData?->ronAmount,
            priceCurrency: 'RON',
            description: trim($rawListing['description'] ?? ''),
            location: trim($rawListing['location'] ?? ''),
            sellerName: trim($rawListing['seller_name'] ?? ''),
            sellerUrl: $rawListing['seller_url'] ?? null,
            postedAt: $rawListing['posted_at'] ?? null,
            imageUrls: $this->processImageUrls($rawListing['image_urls'] ?? []),
            isPromoted: $rawListing['is_promoted'] ?? false,
            isUrgent: $rawListing['is_urgent'] ?? false,
            isNegotiable: $rawListing['is_negotiable'] ?? false,
            metadata: $rawListing['metadata'] ?? [],
        );
    }

    private function performCrawl(HuntedDeal $huntedDeal): CrawlResult
    {
        $startTime = microtime(true);
        try {
            $rawListings = $this->extractListings($huntedDeal->search_term, $this->maxPagesPerSearch);
            $listings = array_values(array_filter(array_map(fn (array $listing): ParsedListing => $this->parseListingData($listing), $rawListings), fn (ParsedListing $listing): bool => $listing->isValid()));

            return CrawlResult::success($listings, min($this->maxPagesPerSearch, (int) ceil(count($rawListings) / 20)), (microtime(true) - $startTime) * 1000, ['search_term' => $huntedDeal->search_term, 'hunted_deal_id' => $huntedDeal->id]);
        } catch (\Throwable $exception) {
            return CrawlResult::failure([$exception->getMessage()], [], 0, (microtime(true) - $startTime) * 1000, ['search_term' => $huntedDeal->search_term, 'hunted_deal_id' => $huntedDeal->id]);
        }
    }

    private function navigateToSearch(string $searchTerm): void
    {
        $this->retryWithBackoff(function () use ($searchTerm): void {
            $this->performMcpOperation(fn (): array => $this->mcp->navigate('https://www.olx.ro/ro/oferta/q-'.rawurlencode($searchTerm).'/'));
            $this->waitForSelectorViaEvaluate([OlxSelectors::LISTING_CONTAINER, OlxSelectors::LISTING_CONTAINER_FALLBACK]);
        }, 3, 2000, ['search_term' => $searchTerm]);
    }

    /** @return array<int, array<string, mixed>> */
    private function extractFromResultsPage(): array
    {
        return $this->retryWithBackoff(function (): array {
            $listings = $this->performMcpOperation(fn (): mixed => $this->mcp->evaluate($this->resultsExtractorFunction()));

            if (! is_array($listings)) {
                throw new CrawlerException('MCP returned an invalid listing extraction result.');
            }

            foreach ($listings as &$listing) {
                $listing['posted_at'] = $this->normalizePostedAt($listing['location'] ?? null);
            }

            return array_values(array_filter($listings, fn (mixed $listing): bool => is_array($listing) && ! empty($listing['title']) && ! empty($listing['url'])));
        }, 2, 1000);
    }

    private function handlePagination(): bool
    {
        $selectors = json_encode([OlxSelectors::PAGINATION_NEXT, OlxSelectors::PAGINATION_NEXT_FALLBACK], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        $before = $this->listingFingerprint();
        $clicked = $this->performMcpOperation(fn (): mixed => $this->mcp->evaluate("() => { const button = {$selectors}.map(selector => document.querySelector(selector)).find(Boolean); if (!button || button.disabled || button.getAttribute('aria-disabled') === 'true') return false; button.click(); return true; }"));

        if (! $clicked) {
            return false;
        }

        return $this->waitForChangedListings($before);
    }

    private function enrichListingsFromDetailPages(array $listings): array
    {
        foreach ($listings as $index => $listing) {
            try {
                $this->performMcpOperation(fn (): array => $this->mcp->navigate(ParsedListing::normalizeUrl($listing['url'])));
                $detail = $this->performMcpOperation(fn (): mixed => $this->mcp->evaluate($this->detailExtractorFunction()));
                if (is_array($detail)) {
                    $listings[$index] = array_merge($listing, array_filter($detail, fn (mixed $value): bool => $value !== null && $value !== ''));
                    $listings[$index]['posted_at'] = $this->normalizePostedAt($detail['posted_at'] ?? null) ?? ($listing['posted_at'] ?? null);
                }
            } catch (\Throwable $exception) {
                $this->logWarning('Failed to enrich listing from detail page', ['url' => $listing['url'] ?? null, 'error' => $exception->getMessage()]);
            }
        }

        return $listings;
    }

    private function waitForSelectorViaEvaluate(array $selectors): void
    {
        $selectors = json_encode($selectors, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        if (! $this->performMcpOperation(fn (): mixed => $this->mcp->evaluate("() => {$selectors}.some(selector => document.querySelector(selector))"))) {
            throw new CrawlerException('None of the configured selectors were found.');
        }
    }

    private function waitForChangedListings(string $before): bool
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            if ($this->listingFingerprint() !== $before) {
                return true;
            }

            if ($attempt < 2) {
                $this->performMcpOperation(function (): null {
                    $this->mcp->waitForTime(1);

                    return null;
                });
            }
        }

        return false;
    }

    private function listingFingerprint(): string
    {
        $selectors = json_encode([OlxSelectors::LISTING_ITEM, OlxSelectors::LISTING_ITEM_FALLBACK], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
        $fingerprint = $this->performMcpOperation(fn (): mixed => $this->mcp->evaluate("() => {$selectors}.map(selector => [...document.querySelectorAll(selector)].map(card => card.querySelector('a')?.href || card.textContent?.trim() || '')).find(items => items.length) || []"));

        if (! is_array($fingerprint)) {
            throw new CrawlerException('MCP returned an invalid listing fingerprint.');
        }

        return json_encode($fingerprint, JSON_THROW_ON_ERROR);
    }

    private function performMcpOperation(callable $operation): mixed
    {
        $this->assertCrawlingPermitted();
        $this->throttleRequest();

        return $operation();
    }

    private function resultsExtractorFunction(): string
    {
        $selectors = json_encode($this->selectorMap(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

        return <<<JS
() => {
  const selectors = {$selectors};
  const pick = (root, names) => names.map(name => root.querySelector(selectors[name])).find(Boolean);
  const all = (root, names) => { for (const name of names) { const found = [...root.querySelectorAll(selectors[name])]; if (found.length) return found; } return []; };
  const text = element => (element?.textContent || '').trim();
  const cards = all(document, ['listing_item', 'listing_item_fallback']);
  return cards.map((card, extraction_index) => {
    const title = pick(card, ['listing_title', 'listing_title_fallback']);
    const url = pick(card, ['listing_url', 'listing_url_fallback']);
    const price = pick(card, ['listing_price', 'listing_price_fallback']);
    const location = pick(card, ['listing_location', 'listing_location_fallback']);
    return { title: text(title), url: url?.href || url?.getAttribute('href') || '', price_raw: text(price), location: text(location), image_urls: all(card, ['listing_image', 'listing_image_fallback']).map(image => image.src || image.getAttribute('src') || '').filter(Boolean), is_promoted: !!card.querySelector(selectors.listing_promoted), is_urgent: !!card.querySelector(selectors.listing_urgent), is_negotiable: !!card.querySelector(selectors.listing_negotiable), metadata: { extraction_index, extraction_timestamp: new Date().toISOString() } };
  });
}
JS;
    }

    private function detailExtractorFunction(): string
    {
        $selectors = json_encode($this->selectorMap(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

        return <<<JS
() => {
  const selectors = {$selectors};
  const pick = (names, href = false) => { for (const name of names) { const element = document.querySelector(selectors[name]); const value = href ? element?.href : element?.textContent?.trim(); if (value) return value; } return null; };
  return { description: pick(['detail_description', 'detail_description_fallback']), seller_name: pick(['detail_seller', 'detail_seller_fallback']), seller_url: pick(['detail_seller_url', 'detail_seller_url_fallback'], true), posted_at: pick(['detail_posted_date', 'detail_posted_date_fallback']) };
}
JS;
    }

    /** @return array<string, string> */
    private function selectorMap(): array
    {
        return ['listing_item' => OlxSelectors::LISTING_ITEM, 'listing_item_fallback' => OlxSelectors::LISTING_ITEM_FALLBACK, 'listing_title' => OlxSelectors::LISTING_TITLE, 'listing_title_fallback' => OlxSelectors::LISTING_TITLE_FALLBACK, 'listing_url' => OlxSelectors::LISTING_URL, 'listing_url_fallback' => OlxSelectors::LISTING_URL_FALLBACK, 'listing_price' => OlxSelectors::LISTING_PRICE, 'listing_price_fallback' => OlxSelectors::LISTING_PRICE_FALLBACK, 'listing_location' => OlxSelectors::LISTING_LOCATION, 'listing_location_fallback' => OlxSelectors::LISTING_LOCATION_FALLBACK, 'listing_image' => OlxSelectors::LISTING_IMAGE, 'listing_image_fallback' => OlxSelectors::LISTING_IMAGE_FALLBACK, 'listing_promoted' => OlxSelectors::LISTING_PROMOTED, 'listing_urgent' => OlxSelectors::LISTING_URGENT, 'listing_negotiable' => OlxSelectors::LISTING_NEGOTIABLE, 'detail_description' => OlxSelectors::DETAIL_DESCRIPTION, 'detail_description_fallback' => OlxSelectors::DETAIL_DESCRIPTION_FALLBACK, 'detail_seller' => OlxSelectors::DETAIL_SELLER, 'detail_seller_fallback' => OlxSelectors::DETAIL_SELLER_FALLBACK, 'detail_seller_url' => OlxSelectors::DETAIL_SELLER_URL, 'detail_seller_url_fallback' => OlxSelectors::DETAIL_SELLER_URL_FALLBACK, 'detail_posted_date' => OlxSelectors::DETAIL_POSTED_DATE, 'detail_posted_date_fallback' => OlxSelectors::DETAIL_POSTED_DATE_FALLBACK];
    }

    private function processImageUrls(array $imageUrls): array
    {
        return array_values(array_unique(array_filter(array_map(fn (mixed $url): ?string => is_string($url) && ! str_contains($url, 'placeholder') && ! str_contains($url, 'default') && ! str_contains($url, 'no-image') ? ParsedListing::normalizeUrl($url) : null, $imageUrls))));
    }

    private function throttleRequest(): void
    {
        $now = microtime(true);
        $this->availableRequestTokens = min($this->burstLimit, $this->availableRequestTokens + (($now - $this->lastTokenRefillAt) * ($this->requestsPerMinute / 60)));
        $this->lastTokenRefillAt = $now;
        if ($this->availableRequestTokens < 1) {
            usleep((int) ceil(((1 - $this->availableRequestTokens) / ($this->requestsPerMinute / 60)) * 1_000_000));
            $this->availableRequestTokens = 1;
        }
        $this->availableRequestTokens--;
        if ($this->requestDelayMs > 0) {
            usleep($this->requestDelayMs * 1000);
        }
    }

    private function assertCrawlingPermitted(): void
    {
        if (! config('crawler.enabled', false)) {
            throw new CrawlerException('Crawling is disabled by CRAWLER_ENABLED.');
        }
        if (config('crawler.require_terms_acknowledgement', true) && ! config('crawler.terms_acknowledged', false)) {
            throw new CrawlerException('Crawling requires an acknowledged site terms review.');
        }

        $windows = array_filter(array_map('trim', explode(',', (string) config('crawler.allowed_windows', ''))));
        if ($windows === []) {
            return;
        }

        $now = Carbon::now((string) config('crawler.timezone', 'Europe/Bucharest'));
        foreach ($windows as $window) {
            if (preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $window, $matches) !== 1) {
                continue;
            }

            $start = $now->copy()->setTimeFromTimeString($matches[1]);
            $end = $now->copy()->setTimeFromTimeString($matches[2]);
            if (($end->lessThanOrEqualTo($start) && ($now->greaterThanOrEqualTo($start) || $now->lessThan($end))) || ($end->greaterThan($start) && $now->betweenIncluded($start, $end))) {
                return;
            }
        }

        throw new CrawlerException('Crawling is outside CRAWLER_ALLOWED_WINDOWS.');
    }

    private function normalizePostedAt(?string $postedAt): ?string
    {
        if (! $postedAt) {
            return null;
        }
        $text = trim((string) preg_replace('/\s+/', ' ', $postedAt));
        $now = Carbon::now((string) config('crawler.timezone', 'Europe/Bucharest'));
        if (preg_match('/(?:azi|today)\s+(\d{1,2}:\d{2})/iu', $text, $matches)) {
            return $now->setTimeFromTimeString($matches[1])->toIso8601String();
        }
        if (preg_match('/(?:ieri|yesterday)\s+(\d{1,2}:\d{2})/iu', $text, $matches)) {
            return $now->subDay()->setTimeFromTimeString($matches[1])->toIso8601String();
        }
        try {
            return Carbon::parse(str_ireplace('publicat pe', '', $text), (string) config('crawler.timezone', 'Europe/Bucharest'))->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
