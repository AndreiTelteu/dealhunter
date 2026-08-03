<?php

namespace App\Services\Crawlers;

/**
 * Centralized CSS selectors for OLX Romania crawling
 *
 * This class contains all CSS selectors used for extracting data from olx.ro
 * Selectors are documented with MCP findings and include fallback strategies
 *
 * @see docs/dev/playwright-mcp-notes.md for detailed DOM analysis
 */
class OlxSelectors
{
    // Search interface selectors
    // MCP Reference: Search input field on olx.ro homepage and search pages
    public const SEARCH_INPUT = 'input[data-testid="search-input"]';

    public const SEARCH_INPUT_FALLBACK = 'input[name="q"], input[type="search"], input[placeholder*="Caută"]';

    public const SEARCH_BUTTON = 'button[data-testid="search-submit"]';

    public const SEARCH_BUTTON_FALLBACK = 'button[type="submit"], .search-submit, form button:first-of-type';

    public const LOCATION_INPUT = 'input[data-testid="location-input"]';

    public const LOCATION_INPUT_FALLBACK = 'input[name="city"], input[placeholder*="Oraș"]';

    // Results page selectors
    // MCP Reference: Main listing grid container
    public const LISTING_CONTAINER = '[data-testid="listing-grid"]';

    public const LISTING_CONTAINER_FALLBACK = '.listing-grid, .ads-list-photo, .offers, .search-results main';

    // Individual listing card selectors
    public const LISTING_ITEM = '[data-testid="l-card"]';

    public const LISTING_ITEM_FALLBACK = '.offer-wrapper, .listing-card, .ad-card, article';

    public const LISTING_TITLE = 'a[data-testid="card-title-link"]';

    public const LISTING_TITLE_FALLBACK = 'h3 a, h4 a, h5 a, h6 a, .offer-item-title, .title-cell a, a[href*="/d/oferta/"]';

    public const LISTING_PRICE = '[data-testid="ad-price"]';

    public const LISTING_PRICE_FALLBACK = '.price, .offer-item-price, .price-label';

    public const LISTING_LOCATION = '[data-testid="location-date"]';

    public const LISTING_LOCATION_FALLBACK = '.location-date, .bottom-cell, .offer-item-details';

    public const LISTING_IMAGE = 'img[data-testid="listing-image"]';

    public const LISTING_IMAGE_FALLBACK = '.offer-item-image img, .photo img, .listing-photo img';

    public const LISTING_URL = 'a[data-testid="listing-ad-title"]';

    public const LISTING_URL_FALLBACK = 'h3 a, h4 a, h5 a, h6 a, .offer-item-title a, a[href*="/d/oferta/"]';

    // Pagination selectors
    public const PAGINATION_NEXT = '[data-testid="pagination-forward"]';

    public const PAGINATION_NEXT_FALLBACK = '.pager-next, .next-page, a[rel="next"]';

    public const PAGINATION_CURRENT = '.pagination .current, .pager .current';

    public const PAGINATION_PAGES = '.pagination a, .pager a';

    // Detail page selectors
    public const DETAIL_DESCRIPTION = '[data-testid="ad-description"]';

    public const DETAIL_DESCRIPTION_FALLBACK = '.offer-description, .description-text, .ad-description';

    public const DETAIL_SELLER = '[data-testid="seller-info"] [data-testid="seller-name"], [data-testid="seller-name"]';

    public const DETAIL_SELLER_FALLBACK = '.seller-info .seller-name, .user-info a, .contact-info a, .seller-name';

    public const DETAIL_SELLER_URL = '[data-testid="seller-info"] a[href], [data-testid="seller-name"] a[href]';

    public const DETAIL_SELLER_URL_FALLBACK = '.seller-info a[href], .user-info a[href], .contact-info a[href]';

    public const DETAIL_POSTED_DATE = '[data-testid="posted-date"]';

    public const DETAIL_POSTED_DATE_FALLBACK = '.posted-date, .offer-date, .creation-date';

    // Additional metadata selectors
    public const LISTING_PROMOTED = '.promoted, .featured, [data-promoted="true"]';

    public const LISTING_URGENT = '.urgent, [data-urgent="true"]';

    public const LISTING_NEGOTIABLE = '.negotiable, [data-negotiable="true"]';

    /**
     * Get primary selector with fallback options
     *
     * @param  string  $primary  Primary selector constant
     * @param  string  $fallback  Fallback selector constant
     * @return array Array of selectors to try in order
     */
    public static function getSelectorsWithFallback(string $primary, string $fallback): array
    {
        return array_filter([
            $primary,
            $fallback,
        ]);
    }

    /**
     * Get all listing item selectors with fallbacks
     */
    public static function getListingItemSelectors(): array
    {
        return self::getSelectorsWithFallback(
            self::LISTING_ITEM,
            self::LISTING_ITEM_FALLBACK
        );
    }

    /**
     * Get all title selectors with fallbacks
     */
    public static function getTitleSelectors(): array
    {
        return self::getSelectorsWithFallback(
            self::LISTING_TITLE,
            self::LISTING_TITLE_FALLBACK
        );
    }

    /**
     * Get all price selectors with fallbacks
     */
    public static function getPriceSelectors(): array
    {
        return self::getSelectorsWithFallback(
            self::LISTING_PRICE,
            self::LISTING_PRICE_FALLBACK
        );
    }
}
