<?php

namespace App\Services\Crawlers;

/**
 * Result data structure for crawl operations
 * 
 * Contains the results of a crawling operation including
 * listings found, errors encountered, and operation metadata
 */
class CrawlResult
{
    public function __construct(
        public readonly array $listings = [],
        public readonly array $errors = [],
        public readonly int $pagesProcessed = 0,
        public readonly int $totalListingsFound = 0,
        public readonly int $validListingsExtracted = 0,
        public readonly float $durationMs = 0.0,
        public readonly array $metadata = []
    ) {}

    /**
     * Create successful result
     * 
     * @param array $listings
     * @param int $pagesProcessed
     * @param float $durationMs
     * @param array $metadata
     * @return self
     */
    public static function success(
        array $listings, 
        int $pagesProcessed = 0, 
        float $durationMs = 0.0, 
        array $metadata = []
    ): self {
        $validListings = array_filter($listings, fn($listing) => $listing instanceof ParsedListing && $listing->isValid());
        
        return new self(
            listings: $listings,
            errors: [],
            pagesProcessed: $pagesProcessed,
            totalListingsFound: count($listings),
            validListingsExtracted: count($validListings),
            durationMs: $durationMs,
            metadata: $metadata
        );
    }

    /**
     * Create failed result
     * 
     * @param array $errors
     * @param array $partialListings
     * @param int $pagesProcessed
     * @param float $durationMs
     * @param array $metadata
     * @return self
     */
    public static function failure(
        array $errors, 
        array $partialListings = [], 
        int $pagesProcessed = 0, 
        float $durationMs = 0.0, 
        array $metadata = []
    ): self {
        $validListings = array_filter($partialListings, fn($listing) => $listing instanceof ParsedListing && $listing->isValid());
        
        return new self(
            listings: $partialListings,
            errors: $errors,
            pagesProcessed: $pagesProcessed,
            totalListingsFound: count($partialListings),
            validListingsExtracted: count($validListings),
            durationMs: $durationMs,
            metadata: $metadata
        );
    }

    /**
     * Check if crawl was successful
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return empty($this->errors) && $this->validListingsExtracted > 0;
    }

    /**
     * Check if crawl had partial success
     * 
     * @return bool
     */
    public function hasPartialSuccess(): bool
    {
        return !empty($this->errors) && $this->validListingsExtracted > 0;
    }

    /**
     * Get valid listings only
     * 
     * @return array
     */
    public function getValidListings(): array
    {
        return array_filter($this->listings, fn($listing) => $listing instanceof ParsedListing && $listing->isValid());
    }

    /**
     * Get summary statistics
     * 
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccessful(),
            'partial_success' => $this->hasPartialSuccess(),
            'pages_processed' => $this->pagesProcessed,
            'total_listings_found' => $this->totalListingsFound,
            'valid_listings_extracted' => $this->validListingsExtracted,
            'errors_count' => count($this->errors),
            'duration_ms' => $this->durationMs,
            'listings_per_second' => $this->durationMs > 0 ? round($this->validListingsExtracted / ($this->durationMs / 1000), 2) : 0
        ];
    }

    /**
     * Convert to array for logging
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->getSummary(),
            'listings' => array_map(fn($listing) => $listing instanceof ParsedListing ? $listing->toArray() : $listing, $this->listings),
            'errors' => $this->errors,
            'metadata' => $this->metadata
        ];
    }
}