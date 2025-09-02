<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Playwright Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Playwright MCP service endpoint and authentication
    |
    */
    'mcp_playwright_endpoint' => env('MCP_PLAYWRIGHT_ENDPOINT', 'http://localhost:3000'),
    'mcp_playwright_token' => env('MCP_PLAYWRIGHT_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Crawler Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting and request delays to respect
    | OLX terms of service and avoid being blocked
    |
    */
    'request_delay_ms' => env('CRAWLER_REQUEST_DELAY_MS', 2000),
    'max_pages_per_search' => env('CRAWLER_MAX_PAGES_PER_SEARCH', 3),
    'max_listings_per_run' => env('CRAWLER_MAX_LISTINGS_PER_RUN', 100),
    'requests_per_minute' => env('RATE_LIMIT_REQUESTS_PER_MINUTE', 30),
    'burst_limit' => env('RATE_LIMIT_BURST_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | Browser Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for browser behavior and user agent
    |
    */
    'user_agent' => env('CRAWLER_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),
    'viewport_width' => env('CRAWLER_VIEWPORT_WIDTH', 1920),
    'viewport_height' => env('CRAWLER_VIEWPORT_HEIGHT', 1080),
    'headless' => env('CRAWLER_HEADLESS', true),

    /*
    |--------------------------------------------------------------------------
    | Error Handling Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retry logic and error recovery
    |
    */
    'max_retry_attempts' => env('CRAWLER_MAX_RETRY_ATTEMPTS', 3),
    'retry_delay_ms' => env('CRAWLER_RETRY_DELAY_MS', 1000),
    'timeout_ms' => env('CRAWLER_TIMEOUT_MS', 30000),

    /*
    |--------------------------------------------------------------------------
    | Data Extraction Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for what data to extract and how to process it
    |
    */
    'extract_images' => env('CRAWLER_EXTRACT_IMAGES', true),
    'extract_seller_info' => env('CRAWLER_EXTRACT_SELLER_INFO', true),
    'extract_description' => env('CRAWLER_EXTRACT_DESCRIPTION', false), // Only from detail pages
    'max_images_per_listing' => env('CRAWLER_MAX_IMAGES_PER_LISTING', 5),

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for crawler-specific logging
    |
    */
    'log_level' => env('CRAWLER_LOG_LEVEL', 'info'),
    'log_requests' => env('CRAWLER_LOG_REQUESTS', false),
    'log_responses' => env('CRAWLER_LOG_RESPONSES', false),
];