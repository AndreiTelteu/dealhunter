<?php

namespace App\Services\Crawlers;

/**
 * Data structure for extracted listing information from OLX
 * 
 * This class represents a parsed listing with all extracted data
 * from the OLX search results or detail pages
 */
class ParsedListing
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $url,
        public readonly string $title,
        public readonly ?string $priceRaw = null,
        public readonly ?float $priceAmount = null,
        public readonly string $priceCurrency = 'RON',
        public readonly ?string $description = null,
        public readonly ?string $location = null,
        public readonly ?string $sellerName = null,
        public readonly ?string $sellerUrl = null,
        public readonly ?string $postedAt = null,
        public readonly array $imageUrls = [],
        public readonly bool $isPromoted = false,
        public readonly bool $isUrgent = false,
        public readonly bool $isNegotiable = false,
        public readonly array $metadata = []
    ) {}

    /**
     * Create ParsedListing from array data
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['external_id'] ?? '',
            url: $data['url'] ?? '',
            title: $data['title'] ?? '',
            priceRaw: $data['price_raw'] ?? null,
            priceAmount: $data['price_amount'] ?? null,
            priceCurrency: $data['price_currency'] ?? 'RON',
            description: $data['description'] ?? null,
            location: $data['location'] ?? null,
            sellerName: $data['seller_name'] ?? null,
            sellerUrl: $data['seller_url'] ?? null,
            postedAt: $data['posted_at'] ?? null,
            imageUrls: $data['image_urls'] ?? [],
            isPromoted: $data['is_promoted'] ?? false,
            isUrgent: $data['is_urgent'] ?? false,
            isNegotiable: $data['is_negotiable'] ?? false,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'url' => $this->url,
            'title' => $this->title,
            'price_raw' => $this->priceRaw,
            'price_amount' => $this->priceAmount,
            'price_currency' => $this->priceCurrency,
            'description' => $this->description,
            'location' => $this->location,
            'seller_name' => $this->sellerName,
            'seller_url' => $this->sellerUrl,
            'posted_at' => $this->postedAt,
            'image_urls' => $this->imageUrls,
            'is_promoted' => $this->isPromoted,
            'is_urgent' => $this->isUrgent,
            'is_negotiable' => $this->isNegotiable,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Check if listing has valid required data
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->externalId) && 
               !empty($this->url) && 
               !empty($this->title);
    }

    /**
     * Get external ID from URL if not provided
     * 
     * @param string $url
     * @return string|null
     */
    public static function extractExternalIdFromUrl(string $url): ?string
    {
        // OLX URLs typically contain ID like: https://www.olx.ro/d/oferta/title-ID123456.html
        if (preg_match('/ID(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Alternative pattern: /oferta/something-123456.html
        if (preg_match('/\/oferta\/.*-(\d+)\.html/', $url, $matches)) {
            return $matches[1];
        }
        
        // Fallback: extract any number sequence from URL
        if (preg_match('/(\d{6,})/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Normalize URL to absolute format
     * 
     * @param string $url
     * @param string $baseUrl
     * @return string
     */
    public static function normalizeUrl(string $url, string $baseUrl = 'https://www.olx.ro'): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        
        if (str_starts_with($url, '/')) {
            return rtrim($baseUrl, '/') . $url;
        }
        
        return $baseUrl . '/' . ltrim($url, '/');
    }
}