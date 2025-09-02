<?php

namespace App\Services\Crawlers;

use App\Models\HuntedDeal;
use App\Services\BaseService;
use App\Services\PriceParserService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    
    public function __construct(PriceParserService $priceParser)
    {
        parent::__construct();
        
        $this->priceParser = $priceParser;
        $this->mcpEndpoint = config('crawler.mcp_playwright_endpoint', 'http://localhost:3000');
        $this->mcpToken = config('crawler.mcp_playwright_token', '');
        $this->requestDelayMs = config('crawler.request_delay_ms', 2000);
        $this->maxPagesPerSearch = config('crawler.max_pages_per_search', 3);
        $this->maxListingsPerRun = config('crawler.max_listings_per_run', 100);
        $this->userAgent = config('crawler.user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    }
    
    /**
     * Crawl a hunted deal and extract listings
     * 
     * @param HuntedDeal $huntedDeal
     * @return CrawlResult
     */
    public function crawlHuntedDeal(HuntedDeal $huntedDeal): CrawlResult
    {
        return $this->executeWithErrorHandling(
            operation: fn() => $this->performCrawl($huntedDeal),
            context: [
                'hunted_deal_id' => $huntedDeal->id,
                'search_term' => $huntedDeal->search_term,
                'user_id' => $huntedDeal->user_id
            ],
            operationName: 'crawl_hunted_deal'
        );
    }
    
    /**
     * Extract listings for a search term
     * 
     * @param string $searchTerm
     * @param int $maxPages
     * @return array
     */
    public function extractListings(string $searchTerm, int $maxPages = 3): array
    {
        $this->validateRequired(['searchTerm' => $searchTerm], ['searchTerm']);
        
        $maxPages = min($maxPages, $this->maxPagesPerSearch);
        $listings = [];
        $currentPage = 1;
        
        $this->logInfo('Starting listing extraction', [
            'search_term' => $searchTerm,
            'max_pages' => $maxPages
        ]);
        
        try {
            // Navigate to search page
            $this->navigateToSearch($searchTerm);
            
            while ($currentPage <= $maxPages && count($listings) < $this->maxListingsPerRun) {
                $this->logDebug("Processing page {$currentPage}", [
                    'search_term' => $searchTerm,
                    'page' => $currentPage,
                    'listings_so_far' => count($listings)
                ]);
                
                // Extract listings from current page
                $pageListings = $this->extractFromResultsPage();
                
                if (empty($pageListings)) {
                    $this->logWarning('No listings found on page', [
                        'search_term' => $searchTerm,
                        'page' => $currentPage
                    ]);
                    break;
                }
                
                $listings = array_merge($listings, $pageListings);
                
                // Check if we've hit the listing limit
                if (count($listings) >= $this->maxListingsPerRun) {
                    $this->logInfo('Reached maximum listings limit', [
                        'search_term' => $searchTerm,
                        'listings_count' => count($listings),
                        'limit' => $this->maxListingsPerRun
                    ]);
                    break;
                }
                
                // Try to navigate to next page
                if (!$this->handlePagination()) {
                    $this->logInfo('No more pages available', [
                        'search_term' => $searchTerm,
                        'final_page' => $currentPage
                    ]);
                    break;
                }
                
                $currentPage++;
                
                // Rate limiting delay
                if ($this->requestDelayMs > 0) {
                    usleep($this->requestDelayMs * 1000);
                }
            }
            
            $this->logInfo('Listing extraction completed', [
                'search_term' => $searchTerm,
                'pages_processed' => $currentPage,
                'total_listings' => count($listings)
            ]);
            
            return $listings;
            
        } catch (\Exception $e) {
            $this->logError('Listing extraction failed', [
                'search_term' => $searchTerm,
                'page' => $currentPage,
                'error' => $e->getMessage()
            ], $e);
            
            throw $e;
        }
    }
    
    /**
     * Parse raw listing data into ParsedListing object
     * 
     * @param array $rawListing
     * @return ParsedListing
     */
    public function parseListingData(array $rawListing): ParsedListing
    {
        // Extract external ID from URL
        $externalId = ParsedListing::extractExternalIdFromUrl($rawListing['url'] ?? '');
        if (!$externalId) {
            $externalId = $rawListing['external_id'] ?? uniqid('olx_');
        }
        
        // Parse price information
        $priceData = null;
        if (!empty($rawListing['price_raw'])) {
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
            priceAmount: $priceData?->amount,
            priceCurrency: $priceData?->currency ?? 'RON',
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
     * 
     * @param HuntedDeal $huntedDeal
     * @return CrawlResult
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
                        $errors[] = "Invalid listing data: " . json_encode($rawListing);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to parse listing: " . $e->getMessage();
                    $this->logWarning('Failed to parse individual listing', [
                        'hunted_deal_id' => $huntedDeal->id,
                        'raw_listing' => $rawListing,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            if (empty($errors)) {
                return CrawlResult::success($listings, $pagesProcessed, $durationMs, [
                    'search_term' => $huntedDeal->search_term,
                    'hunted_deal_id' => $huntedDeal->id
                ]);
            } else {
                return CrawlResult::failure($errors, $listings, $pagesProcessed, $durationMs, [
                    'search_term' => $huntedDeal->search_term,
                    'hunted_deal_id' => $huntedDeal->id
                ]);
            }
            
        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;
            $errors[] = $e->getMessage();
            
            return CrawlResult::failure($errors, $listings, $pagesProcessed, $durationMs, [
                'search_term' => $huntedDeal->search_term,
                'hunted_deal_id' => $huntedDeal->id
            ]);
        }
    } 
   
    /**
     * Navigate to OLX search page with search term
     * 
     * @param string $searchTerm
     * @return void
     */
    private function navigateToSearch(string $searchTerm): void
    {
        $this->retryWithBackoff(
            operation: function() use ($searchTerm) {
                // Navigate to OLX homepage first
                $this->mcpRequest('navigate', [
                    'url' => 'https://www.olx.ro'
                ]);
                
                // Wait for page to load
                $this->mcpRequest('wait', [
                    'selector' => OlxSelectors::SEARCH_INPUT,
                    'timeout' => 10000
                ]);
                
                // Clear and fill search input
                $this->mcpRequest('fill', [
                    'selector' => OlxSelectors::SEARCH_INPUT,
                    'value' => $searchTerm
                ]);
                
                // Submit search
                $this->mcpRequest('click', [
                    'selector' => OlxSelectors::SEARCH_BUTTON
                ]);
                
                // Wait for results to load
                $this->mcpRequest('wait', [
                    'selector' => OlxSelectors::LISTING_CONTAINER,
                    'timeout' => 15000
                ]);
                
                $this->logDebug('Successfully navigated to search results', [
                    'search_term' => $searchTerm
                ]);
            },
            maxAttempts: 3,
            baseDelayMs: 2000,
            context: ['search_term' => $searchTerm]
        );
    }
    
    /**
     * Extract listings from current results page
     * 
     * @return array
     */
    private function extractFromResultsPage(): array
    {
        return $this->retryWithBackoff(
            operation: function() {
                // Get all listing elements
                $listingElements = $this->mcpRequest('queryAll', [
                    'selector' => OlxSelectors::LISTING_ITEM
                ]);
                
                if (empty($listingElements)) {
                    // Try fallback selectors
                    $listingElements = $this->mcpRequest('queryAll', [
                        'selector' => OlxSelectors::LISTING_ITEM_FALLBACK
                    ]);
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
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $this->logDebug('Extracted listings from page', [
                    'listings_count' => count($listings),
                    'elements_found' => count($listingElements)
                ]);
                
                return $listings;
            },
            maxAttempts: 2,
            baseDelayMs: 1000
        );
    }
    
    /**
     * Extract listing data from a single element
     * 
     * @param array $element
     * @param int $index
     * @return array|null
     */
    private function extractListingFromElement(array $element, int $index): ?array
    {
        try {
            // Extract title and URL
            $titleData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_TITLE,
                OlxSelectors::LISTING_TITLE_FALLBACK
            ]);
            
            $urlData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_URL,
                OlxSelectors::LISTING_URL_FALLBACK
            ], 'href');
            
            if (!$titleData || !$urlData) {
                $this->logWarning('Missing required title or URL data', [
                    'element_index' => $index,
                    'title_found' => !empty($titleData),
                    'url_found' => !empty($urlData)
                ]);
                return null;
            }
            
            // Extract price
            $priceData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_PRICE,
                OlxSelectors::LISTING_PRICE_FALLBACK
            ]);
            
            // Extract location
            $locationData = $this->extractWithFallback($element, [
                OlxSelectors::LISTING_LOCATION,
                OlxSelectors::LISTING_LOCATION_FALLBACK
            ]);
            
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
                'image_urls' => $imageUrls,
                'is_promoted' => $isPromoted,
                'is_urgent' => $isUrgent,
                'is_negotiable' => $isNegotiable,
                'metadata' => [
                    'extraction_index' => $index,
                    'extraction_timestamp' => now()->toISOString()
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logError('Failed to extract listing data from element', [
                'element_index' => $index,
                'error' => $e->getMessage()
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
            // Check if next page button exists and is clickable
            $nextButton = $this->mcpRequest('query', [
                'selector' => OlxSelectors::PAGINATION_NEXT
            ]);
            
            if (!$nextButton) {
                // Try fallback selector
                $nextButton = $this->mcpRequest('query', [
                    'selector' => OlxSelectors::PAGINATION_NEXT_FALLBACK
                ]);
            }
            
            if (!$nextButton) {
                $this->logDebug('No next page button found');
                return false;
            }
            
            // Check if button is disabled
            $isDisabled = $this->mcpRequest('getAttribute', [
                'selector' => OlxSelectors::PAGINATION_NEXT,
                'attribute' => 'disabled'
            ]);
            
            if ($isDisabled) {
                $this->logDebug('Next page button is disabled');
                return false;
            }
            
            // Click next page button
            $this->mcpRequest('click', [
                'selector' => OlxSelectors::PAGINATION_NEXT
            ]);
            
            // Wait for new page to load
            $this->mcpRequest('wait', [
                'selector' => OlxSelectors::LISTING_CONTAINER,
                'timeout' => 10000
            ]);
            
            $this->logDebug('Successfully navigated to next page');
            return true;
            
        } catch (\Exception $e) {
            $this->logWarning('Failed to navigate to next page', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Extract text content with fallback selectors
     * 
     * @param array $element
     * @param array $selectors
     * @param string $attribute
     * @return string|null
     */
    private function extractWithFallback(array $element, array $selectors, string $attribute = 'textContent'): ?string
    {
        foreach ($selectors as $selector) {
            try {
                $result = $this->mcpRequest('queryInElement', [
                    'element' => $element,
                    'selector' => $selector,
                    'attribute' => $attribute
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
     * 
     * @param array $element
     * @param string $selector
     * @return bool
     */
    private function hasSelector(array $element, string $selector): bool
    {
        try {
            $result = $this->mcpRequest('queryInElement', [
                'element' => $element,
                'selector' => $selector
            ]);
            
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Extract image URLs from listing element
     * 
     * @param array $element
     * @return array
     */
    private function extractImageUrls(array $element): array
    {
        try {
            $images = $this->mcpRequest('queryAllInElement', [
                'element' => $element,
                'selector' => OlxSelectors::LISTING_IMAGE
            ]);
            
            if (empty($images)) {
                // Try fallback selector
                $images = $this->mcpRequest('queryAllInElement', [
                    'element' => $element,
                    'selector' => OlxSelectors::LISTING_IMAGE_FALLBACK
                ]);
            }
            
            $imageUrls = [];
            foreach ($images as $img) {
                $src = $this->mcpRequest('getElementAttribute', [
                    'element' => $img,
                    'attribute' => 'src'
                ]);
                
                if ($src && !str_contains($src, 'placeholder') && !str_contains($src, 'default')) {
                    $imageUrls[] = ParsedListing::normalizeUrl($src);
                }
            }
            
            return array_unique($imageUrls);
            
        } catch (\Exception $e) {
            $this->logWarning('Failed to extract image URLs', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Process and clean image URLs
     * 
     * @param array $imageUrls
     * @return array
     */
    private function processImageUrls(array $imageUrls): array
    {
        $processed = [];
        
        foreach ($imageUrls as $url) {
            if (empty($url) || !is_string($url)) {
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
     * @param string $action
     * @param array $params
     * @return mixed
     */
    private function mcpRequest(string $action, array $params = [])
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->mcpToken,
                'Content-Type' => 'application/json',
                'User-Agent' => $this->userAgent
            ])
            ->post($this->mcpEndpoint . '/playwright/' . $action, $params);
        
        if (!$response->successful()) {
            throw new \Exception("MCP request failed: {$action} - " . $response->body());
        }
        
        $data = $response->json();
        
        if (isset($data['error'])) {
            throw new \Exception("MCP error: {$action} - " . $data['error']);
        }
        
        return $data['result'] ?? $data;
    }
}