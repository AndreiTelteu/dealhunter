<?php

namespace App\Services\Crawlers;

use App\Models\HuntedDeal;
use App\Services\BaseService;
use App\Services\PriceParserService;
use Illuminate\Support\Facades\Http;

/**
 * OLX Romania crawler service using Playwright MCP integration
 *
 * This service handles browser automation for crawling OLX search results,
 * extracting listing data, and managing pagination with rate limiting
 */
class OlxCrawlerService extends BaseService
{
    protected string $logChannel = 'crawler';

    private string $mcpEndpoint;

    private string $mcpToken;

    private int $requestDelayMs;

    private int $maxPagesPerSearch;

    private int $maxListingsPerRun;

    private string $userAgent;

    private PriceParserService $priceParser;

    private int $requestsPerMinute;

    private int $burstLimit;

    private float $availableRequestTokens;

    private float $lastTokenRefillAt;

    public function __construct(PriceParserService $priceParser)
    {
        parent::__construct();

        $this->priceParser = $priceParser;
        $this->mcpEndpoint = config('crawler.mcp_playwright_endpoint', 'http://localhost:3000');
        $this->mcpToken = config('crawler.mcp_playwright_token', '');
        $this->requestDelayMs = max(0, (int) config('crawler.request_delay_ms', 2000));
        $this->maxPagesPerSearch = max(1, (int) config('crawler.max_pages_per_search', 3));
        $this->maxListingsPerRun = max(1, (int) config('crawler.max_listings_per_run', 100));
        $this->userAgent = config('crawler.user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $this->requestsPerMinute = max(1, (int) config('crawler.requests_per_minute', 30));
        $this->burstLimit = max(1, (int) config('crawler.burst_limit', 10));
        $this->availableRequestTokens = $this->burstLimit;
        $this->lastTokenRefillAt = microtime(true);
    }

    /**
     * Crawl a hunted deal and extract listings
     */
    public function crawlHuntedDeal(HuntedDeal $huntedDeal): CrawlResult
    {
        return $this->executeWithErrorHandling(
            operation: fn () => $this->performCrawl($huntedDeal),
            context: [
                'hunted_deal_id' => $huntedDeal->id,
                'search_term' => $huntedDeal->search_term,
                'user_id' => $huntedDeal->user_id,
            ],
            operationName: 'crawl_hunted_deal'
        );
    }

    /**
     * Extract listings for a search term
     */
    public function extractListings(string $searchTerm, int $maxPages = 3): array
    {
        $this->validateRequired(['searchTerm' => $searchTerm], ['searchTerm']);
        $this->assertCrawlingPermitted();

        $maxPages = min(max(1, $maxPages), $this->maxPagesPerSearch);
        $listings = [];
        $currentPage = 1;

        $this->logInfo('Starting listing extraction', [
            'search_term' => $searchTerm,
            'max_pages' => $maxPages,
        ]);

        try {
            // Navigate to search page
            $this->navigateToSearch($searchTerm);

            while ($currentPage <= $maxPages && count($listings) < $this->maxListingsPerRun) {
                $this->logDebug("Processing page {$currentPage}", [
                    'search_term' => $searchTerm,
                    'page' => $currentPage,
                    'listings_so_far' => count($listings),
                ]);

                // Extract listings from current page
                $pageListings = $this->extractFromResultsPage();

                if (empty($pageListings)) {
                    $this->logWarning('No listings found on page', [
                        'search_term' => $searchTerm,
                        'page' => $currentPage,
                    ]);
                    break;
                }

                $listings = array_merge($listings, array_slice($pageListings, 0, $this->maxListingsPerRun - count($listings)));

                // Check if we've hit the listing limit
                if (count($listings) >= $this->maxListingsPerRun) {
                    $this->logInfo('Reached maximum listings limit', [
                        'search_term' => $searchTerm,
                        'listings_count' => count($listings),
                        'limit' => $this->maxListingsPerRun,
                    ]);
                    break;
                }

                // Try to navigate to next page
                if (! $this->handlePagination()) {
                    $this->logInfo('No more pages available', [
                        'search_term' => $searchTerm,
                        'final_page' => $currentPage,
                    ]);
                    break;
                }

                $currentPage++;

            }

            $listings = $this->enrichListingsFromDetailPages($listings);

            $this->logInfo('Listing extraction completed', [
                'search_term' => $searchTerm,
                'pages_processed' => $currentPage,
                'total_listings' => count($listings),
            ]);

            return $listings;

        } catch (\Exception $e) {
            $this->logError('Listing extraction failed', [
                'search_term' => $searchTerm,
                'page' => $currentPage,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Parse raw listing data into ParsedListing object
     */
    public function parseListingData(array $rawListing): ParsedListing
    {
        // Extract external ID from URL
        $externalId = ParsedListing::extractExternalIdFromUrl($rawListing['url'] ?? '');
        if (! $externalId) {
            $externalId = $rawListing['external_id'] ?? uniqid('olx_');
        }

        // Parse price information
        $priceData = null;
        if (! empty($rawListing['price_raw'])) {
            $priceData = $this->priceParser->parsePrice($rawListing['price_raw']);
        }

        // Normalize URL
        $url = ParsedListing::normalizeUrl($rawListing['url'] ?? '');

        // Clean and process image URLs
        $imageUrls = $this->processImageUrls($rawListing['image_urls'] ?? []);

        return new ParsedListing(
            externalId: $externalId,
            url: $url,
            title: trim($rawListing['title'] ?? ''),
            priceRaw: $rawListing['price_raw'] ?? null,
            priceAmount: $priceData?->ronAmount,
            priceCurrency: 'RON',
            description: trim($rawListing['description'] ?? ''),
            location: trim($rawListing['location'] ?? ''),
            sellerName: trim($rawListing['seller_name'] ?? ''),
            sellerUrl: $rawListing['seller_url'] ?? null,
            postedAt: $rawListing['posted_at'] ?? null,
            imageUrls: $imageUrls,
            isPromoted: $rawListing['is_promoted'] ?? false,
            isUrgent: $rawListing['is_urgent'] ?? false,
            isNegotiable: $rawListing['is_negotiable'] ?? false,
            metadata: $rawListing['metadata'] ?? []
        );
    }

    /**
     * Perform the actual crawl operation
     */
    private function performCrawl(HuntedDeal $huntedDeal): CrawlResult
    {
        $startTime = microtime(true);
        $listings = [];
        $errors = [];
        $pagesProcessed = 0;

        try {
            $rawListings = $this->extractListings($huntedDeal->search_term, $this->maxPagesPerSearch);
            $pagesProcessed = min($this->maxPagesPerSearch, ceil(count($rawListings) / 20)); // Estimate pages

            foreach ($rawListings as $rawListing) {
                try {
                    $parsedListing = $this->parseListingData($rawListing);
                    if ($parsedListing->isValid()) {
                        $listings[] = $parsedListing;
                    } else {
                        $errors[] = 'Invalid listing data: '.json_encode($rawListing);
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Failed to parse listing: '.$e->getMessage();
                    $this->logWarning('Failed to parse individual listing', [
                        'hunted_deal_id' => $huntedDeal->id,
                        'raw_listing' => $rawListing,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $durationMs = (microtime(true) - $startTime) * 1000;

            if (empty($errors)) {
                return CrawlResult::success($listings, $pagesProcessed, $durationMs, [
                    'search_term' => $huntedDeal->search_term,
                    'hunted_deal_id' => $huntedDeal->id,
                ]);
            } else {
                return CrawlResult::failure($errors, $listings, $pagesProcessed, $durationMs, [
                    'search_term' => $huntedDeal->search_term,
                    'hunted_deal_id' => $huntedDeal->id,
                ]);
            }

        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;
            $errors[] = $e->getMessage();

            return CrawlResult::failure($errors, $listings, $pagesProcessed, $durationMs, [
                'search_term' => $huntedDeal->search_term,
                'hunted_deal_id' => $huntedDeal->id,
            ]);
        }
    }

    /**
     * Navigate to OLX search page with search term
     */
    private function navigateToSearch(string $searchTerm): void
    {
        $this->retryWithBackoff(
            operation: function () use ($searchTerm) {
                // Navigate to OLX homepage first
                $this->mcpRequest('navigate', [
                    'url' => 'https://www.olx.ro',
                ]);

                $searchInputSelector = $this->waitForAnySelector([OlxSelectors::SEARCH_INPUT, OlxSelectors::SEARCH_INPUT_FALLBACK], 10000);

                // Clear and fill search input
                $this->mcpRequest('fill', [
                    'selector' => $searchInputSelector,
                    'value' => $searchTerm,
                ]);

                $this->clickAnySelector([OlxSelectors::SEARCH_BUTTON, OlxSelectors::SEARCH_BUTTON_FALLBACK]);

                $this->waitForAnySelector([OlxSelectors::LISTING_CONTAINER, OlxSelectors::LISTING_CONTAINER_FALLBACK], 15000);

                $this->logDebug('Successfully navigated to search results', [
                    'search_term' => $searchTerm,
                ]);
            },
            maxAttempts: 3,
            baseDelayMs: 2000,
            context: ['search_term' => $searchTerm]
        );
    }

    /**
     * Extract listings from current results page
     */
    private function extractFromResultsPage(): array
    {
        return $this->retryWithBackoff(
            operation: function () {
                $listingElements = [];
                foreach (OlxSelectors::getListingItemSelectors() as $selector) {
                    $listingElements = $this->mcpRequest('queryAll', [
                        'selector' => $selector,
                    ]);

                    if (! empty($listingElements)) {
                        break;
                    }
                }

                if (empty($listingElements)) {
                    $this->logWarning('No listing elements found on page');

                    return [];
                }

                $listings = [];

                foreach ($listingElements as $index => $element) {
                    try {
                        $listing = $this->extractListingFromElement($element, $index);
                        if ($listing) {
                            $listings[] = $listing;
                        }
                    } catch (\Exception $e) {
                        $this->logWarning('Failed to extract listing from element', [
                            'element_index' => $index,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $this->logDebug('Extracted listings from page', [
                    'listings_count' => count($listings),
                    'elements_found' => count($listingElements),
                ]);

                return $listings;
            },
            maxAttempts: 2,
            baseDelayMs: 1000
        );
    }

    /**
     * Extract listing data from a single element
     */
    private function extractListingFromElement(array $element, int $index): ?array
    {
        try {
            // Extract title and URL
            $titleData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_TITLE,
                OlxSelectors::LISTING_TITLE_FALLBACK,
            ]);

            $urlData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_URL,
                OlxSelectors::LISTING_URL_FALLBACK,
            ], 'href');

            if (! $titleData || ! $urlData) {
                $this->logWarning('Missing required title or URL data', [
                    'element_index' => $index,
                    'title_found' => ! empty($titleData),
                    'url_found' => ! empty($urlData),
                ]);

                return null;
            }

            // Extract price
            $priceData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_PRICE,
                OlxSelectors::LISTING_PRICE_FALLBACK,
            ]);

            // Extract location
            $locationData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_LOCATION,
                OlxSelectors::LISTING_LOCATION_FALLBACK,
            ]);
            $postedAt = $this->normalizePostedAt($locationData);

            // Extract image URLs
            $imageUrls = $this->extractImageUrls($element);

            // Check for promotional flags
            $isPromoted = $this->hasSelector($element, OlxSelectors::LISTING_PROMOTED);
            $isUrgent = $this->hasSelector($element, OlxSelectors::LISTING_URGENT);
            $isNegotiable = $this->hasSelector($element, OlxSelectors::LISTING_NEGOTIABLE);

            return [
                'url' => $urlData,
                'title' => $titleData,
                'price_raw' => $priceData,
                'location' => $locationData,
                'posted_at' => $postedAt,
                'image_urls' => $imageUrls,
                'is_promoted' => $isPromoted,
                'is_urgent' => $isUrgent,
                'is_negotiable' => $isNegotiable,
                'metadata' => [
                    'extraction_index' => $index,
                    'extraction_timestamp' => now()->toISOString(),
                ],
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to extract listing data from element', [
                'element_index' => $index,
                'error' => $e->getMessage(),
            ], $e);

            return null;
        }
    }

    /**
     * Handle pagination to next page
     *
     * @return bool True if successfully navigated to next page
     */
    private function handlePagination(): bool
    {
        try {
            $nextSelector = null;
            foreach ([OlxSelectors::PAGINATION_NEXT, OlxSelectors::PAGINATION_NEXT_FALLBACK] as $selector) {
                $nextButton = $this->mcpRequest('query', [
                    'selector' => $selector,
                ]);

                if ($nextButton) {
                    $nextSelector = $selector;
                    break;
                }
            }

            if (! $nextSelector) {
                $this->logDebug('No next page button found');

                return false;
            }

            // Check if button is disabled
            $isDisabled = $this->mcpRequest('getAttribute', [
                'selector' => $nextSelector,
                'attribute' => 'disabled',
            ]);

            if ($isDisabled) {
                $this->logDebug('Next page button is disabled');

                return false;
            }

            // Click next page button
            $this->mcpRequest('click', [
                'selector' => $nextSelector,
            ]);

            $this->waitForAnySelector([OlxSelectors::LISTING_CONTAINER, OlxSelectors::LISTING_CONTAINER_FALLBACK], 10000);

            $this->logDebug('Successfully navigated to next page');

            return true;

        } catch (\Exception $e) {
            $this->logWarning('Failed to navigate to next page', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Extract text content with fallback selectors
     */
    private function extractWithFallback(array $element, array $selectors, string $attribute = 'textContent'): ?string
    {
        foreach ($selectors as $selector) {
            try {
                $result = $this->mcpRequest('queryInElement', [
                    'element' => $element,
                    'selector' => $selector,
                    'attribute' => $attribute,
                ]);

                if ($result && trim($result) !== '') {
                    return trim($result);
                }
            } catch (\Exception $e) {
                // Continue to next selector
                continue;
            }
        }

        return null;
    }

    /**
     * Check if element has a specific selector
     */
    private function hasSelector(array $element, string $selector): bool
    {
        try {
            $result = $this->mcpRequest('queryInElement', [
                'element' => $element,
                'selector' => $selector,
            ]);

            return ! empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract image URLs from listing element
     */
    private function extractImageUrls(array $element): array
    {
        try {
            $images = $this->mcpRequest('queryAllInElement', [
                'element' => $element,
                'selector' => OlxSelectors::LISTING_IMAGE,
            ]);

            if (empty($images)) {
                // Try fallback selector
                $images = $this->mcpRequest('queryAllInElement', [
                    'element' => $element,
                    'selector' => OlxSelectors::LISTING_IMAGE_FALLBACK,
                ]);
            }

            $imageUrls = [];
            foreach ($images as $img) {
                $src = $this->mcpRequest('getElementAttribute', [
                    'element' => $img,
                    'attribute' => 'src',
                ]);

                if ($src && ! str_contains($src, 'placeholder') && ! str_contains($src, 'default')) {
                    $imageUrls[] = ParsedListing::normalizeUrl($src);
                }
            }

            return array_unique($imageUrls);

        } catch (\Exception $e) {
            $this->logWarning('Failed to extract image URLs', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Process and clean image URLs
     */
    private function processImageUrls(array $imageUrls): array
    {
        $processed = [];

        foreach ($imageUrls as $url) {
            if (empty($url) || ! is_string($url)) {
                continue;
            }

            // Skip placeholder and default images
            if (str_contains($url, 'placeholder') ||
                str_contains($url, 'default') ||
                str_contains($url, 'no-image')) {
                continue;
            }

            // Normalize URL
            $normalizedUrl = ParsedListing::normalizeUrl($url);

            // Validate URL format
            if (filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
                $processed[] = $normalizedUrl;
            }
        }

        return array_unique($processed);
    }

    /**
     * Make MCP request to Playwright service
     *
     * @return mixed
     */
    private function mcpRequest(string $action, array $params = [])
    {
        $this->assertCrawlingPermitted();
        $this->throttleRequest();

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->mcpToken,
                'Content-Type' => 'application/json',
                'User-Agent' => $this->userAgent,
            ])
            ->post($this->mcpEndpoint.'/playwright/'.$action, $params);

        if (! $response->successful()) {
            throw new \Exception("MCP request failed: {$action} - ".$response->body());
        }

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \Exception("MCP error: {$action} - ".$data['error']);
        }

        return $data['result'] ?? $data;
    }

    private function enrichListingsFromDetailPages(array $listings): array
    {
        foreach ($listings as $index => $listing) {
            try {
                $this->mcpRequest('navigate', ['url' => ParsedListing::normalizeUrl($listing['url'])]);
                $listing['description'] = $this->extractPageAttribute([OlxSelectors::DETAIL_DESCRIPTION, OlxSelectors::DETAIL_DESCRIPTION_FALLBACK], 'textContent');
                $listing['seller_name'] = $this->extractPageAttribute([OlxSelectors::DETAIL_SELLER, OlxSelectors::DETAIL_SELLER_FALLBACK], 'textContent');
                $listing['seller_url'] = $this->extractPageAttribute([OlxSelectors::DETAIL_SELLER_URL, OlxSelectors::DETAIL_SELLER_URL_FALLBACK], 'href');
                $listing['posted_at'] = $this->normalizePostedAt($this->extractPageAttribute([OlxSelectors::DETAIL_POSTED_DATE, OlxSelectors::DETAIL_POSTED_DATE_FALLBACK], 'textContent')) ?? ($listing['posted_at'] ?? null);
                $listings[$index] = $listing;
            } catch (\Throwable $e) {
                $this->logWarning('Failed to enrich listing from detail page', [
                    'url' => $listing['url'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $listings;
    }

    private function extractPageAttribute(array $selectors, string $attribute): ?string
    {
        foreach ($selectors as $selector) {
            try {
                $value = $this->mcpRequest('query', ['selector' => $selector, 'attribute' => $attribute]);
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function waitForAnySelector(array $selectors, int $timeout): string
    {
        foreach ($selectors as $selector) {
            try {
                $this->mcpRequest('wait', ['selector' => $selector, 'timeout' => $timeout]);

                return $selector;
            } catch (\Throwable) {
                continue;
            }
        }

        throw new CrawlerException('None of the configured selectors were found.');
    }

    private function clickAnySelector(array $selectors): void
    {
        foreach ($selectors as $selector) {
            try {
                $this->mcpRequest('click', ['selector' => $selector]);

                return;
            } catch (\Throwable) {
                continue;
            }
        }

        throw new CrawlerException('None of the configured selectors could be clicked.');
    }

    private function throttleRequest(): void
    {
        $now = microtime(true);
        $this->availableRequestTokens = min($this->burstLimit, $this->availableRequestTokens + (($now - $this->lastTokenRefillAt) * ($this->requestsPerMinute / 60)));
        $this->lastTokenRefillAt = $now;

        if ($this->availableRequestTokens < 1) {
            usleep((int) ceil(((1 - $this->availableRequestTokens) / ($this->requestsPerMinute / 60)) * 1_000_000));
            $this->availableRequestTokens = 1;
            $this->lastTokenRefillAt = microtime(true);
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
        if (empty($windows)) {
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
        $timezone = (string) config('crawler.timezone', 'Europe/Bucharest');
        $now = Carbon::now($timezone);

        if (preg_match('/(?:azi|today)\s+(\d{1,2}:\d{2})/iu', $text, $matches)) {
            return $now->setTimeFromTimeString($matches[1])->toIso8601String();
        }

        if (preg_match('/(?:ieri|yesterday)\s+(\d{1,2}:\d{2})/iu', $text, $matches)) {
            return $now->subDay()->setTimeFromTimeString($matches[1])->toIso8601String();
        }

        try {
            return Carbon::parse(str_ireplace('publicat pe', '', $text), $timezone)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
