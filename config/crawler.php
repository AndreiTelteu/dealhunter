<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Model Context Protocol integration with Playwright
    |
    */

    'mcp' => [
        'playwright_endpoint' => env('MCP_PLAYWRIGHT_ENDPOINT', 'http://localhost:3000'),
        'playwright_token' => env('MCP_PLAYWRIGHT_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the OLX crawler behavior and limits
    |
    */

    'max_pages_per_search' => env('CRAWLER_MAX_PAGES_PER_SEARCH', 3),
    'request_delay_ms' => env('CRAWLER_REQUEST_DELAY_MS', 2000),
    'max_listings_per_run' => env('CRAWLER_MAX_LISTINGS_PER_RUN', 100),
    'user_agent' => env('CRAWLER_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Default currency and conversion rates
    |
    */

    'currency' => [
        'default' => env('DEFAULT_CURRENCY', 'RON'),
        'rates' => [
            'EUR_TO_RON' => env('EUR_TO_RON_RATE', 4.95),
            'USD_TO_RON' => env('USD_TO_RON_RATE', 4.50),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for request rate limiting to respect OLX terms
    |
    */

    'rate_limit' => [
        'requests_per_minute' => env('RATE_LIMIT_REQUESTS_PER_MINUTE', 30),
        'burst_limit' => env('RATE_LIMIT_BURST_LIMIT', 10),
    ],

];