# Design Document

## Overview

The OLX Deal Hunter is a Laravel-based web application that automates the monitoring of second-hand deals on OLX Romania. The system employs a scheduled crawler using Playwright MCP integration to periodically scan search results, extract listing data, and maintain comprehensive historical records. An AI classification layer provides intelligent filtering to help users identify relevant and working items.

The architecture follows Laravel conventions with clear separation of concerns: Controllers handle HTTP requests, Services encapsulate business logic, Commands manage scheduled tasks, and Models represent data relationships. The system is designed for reliability, scalability, and maintainability while respecting OLX's terms of service through configurable rate limiting.

## Architecture

### High-Level Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Web Browser   │────│   Laravel App    │────│   PostgreSQL    │
│   (Users)       │    │   (Controllers,  │    │   (Data Store)  │
└─────────────────┘    │    Services)     │    └─────────────────┘
                       └──────────────────┘
                              │
                              │
                       ┌──────────────────┐    ┌─────────────────┐
                       │   Playwright     │────│    OLX.ro       │
                       │   MCP Service    │    │   (Target Site) │
                       └──────────────────┘    └─────────────────┘
                              │
                              │
                       ┌──────────────────┐
                       │   AI Classifier  │
                       │   (Intent/Work)  │
                       └──────────────────┘
```

### Application Layers

1. **Presentation Layer**: Blade templates with Tailwind CSS for responsive UI
2. **Controller Layer**: HTTP request handling and response formatting
3. **Service Layer**: Business logic encapsulation and external integrations
4. **Data Layer**: Eloquent models and database interactions
5. **Integration Layer**: Playwright MCP and AI classification services

### Technology Stack

- **Framework**: Laravel 11 (PHP 8.2+)
- **Database**: PostgreSQL with Redis for caching/queues (optional)
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js for interactivity
- **Browser Automation**: Playwright via MCP integration
- **Authentication**: Laravel Breeze (without testing scaffolding)
- **Deployment**: Docker Compose for development, production Dockerfile

## Components and Interfaces

### Core Services

#### OlxCrawlerService
```php
class OlxCrawlerService
{
    public function crawlHuntedDeal(HuntedDeal $huntedDeal): CrawlResult
    public function extractListings(string $searchTerm, int $maxPages = 3): array
    public function parseListingData(array $rawListing): ParsedListing
    private function navigateToSearch(string $term): void
    private function extractFromResultsPage(): array
    private function handlePagination(): bool
}
```

#### DealIngestionService
```php
class DealIngestionService
{
    public function processListings(HuntedDeal $huntedDeal, array $listings): void
    public function upsertDeal(HuntedDeal $huntedDeal, ParsedListing $listing): Deal
    private function createSnapshot(Deal $deal, ParsedListing $listing): DealSnapshot
    private function hasSignificantChanges(Deal $deal, ParsedListing $listing): bool
}
```

#### IntentClassifierService
```php
class IntentClassifierService
{
    public function classifyListing(string $searchTerm, ParsedListing $listing): Classification
    private function matchesIntent(string $searchTerm, string $title, string $description): bool
    private function assessWorkingCondition(string $description): bool
    private function calculateConfidence(array $signals): float
}
```

#### PriceParserService
```php
class PriceParserService
{
    public function parsePrice(string $priceText): ParsedPrice
    public function convertToRON(float $amount, string $currency): float
    private function extractNumericValue(string $text): ?float
    private function detectCurrency(string $text): string
}
```

### Selector Management

#### OlxSelectors
```php
class OlxSelectors
{
    // Search interface selectors
    public const SEARCH_INPUT = 'input[data-testid="search-input"]';
    public const SEARCH_BUTTON = 'button[data-testid="search-submit"]';
    
    // Results page selectors
    public const LISTING_CONTAINER = '[data-testid="listing-grid"]';
    public const LISTING_ITEM = '[data-testid="l-card"]';
    public const LISTING_TITLE = 'h6[data-testid="ad-title"]';
    public const LISTING_PRICE = '[data-testid="ad-price"]';
    public const LISTING_LOCATION = '[data-testid="location-date"]';
    public const LISTING_IMAGE = 'img[data-testid="listing-image"]';
    public const PAGINATION_NEXT = '[data-testid="pagination-forward"]';
    
    // Detail page selectors (if needed)
    public const DETAIL_DESCRIPTION = '[data-testid="ad-description"]';
    public const DETAIL_SELLER = '[data-testid="seller-info"]';
}
```

### Controllers

#### HuntedDealController
- `index()`: List user's hunted deals with pagination
- `create()`: Show form for new hunted deal
- `store()`: Create new hunted deal
- `show()`: Display hunted deal details and associated deals
- `edit()`: Show edit form
- `update()`: Update hunted deal
- `destroy()`: Delete hunted deal and cascaded data

#### DealController
- `index()`: List deals with filtering and pagination
- `show()`: Display deal details with history and charts
- `filter()`: Apply filters (price drops, new items, etc.)

### Commands

#### CrawlDealsCommand
```php
class CrawlDealsCommand extends Command
{
    protected $signature = 'deals:crawl {--dry-run} {--hunted-deal=}';
    
    public function handle(): int
    {
        // Process all active hunted deals
        // Log results and errors
        // Update crawl timestamps
    }
}
```

## Data Models

### Database Schema

#### Users Table
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
```

#### Hunted Deals Table
```sql
CREATE TABLE hunted_deals (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    search_term VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    notes TEXT NULL,
    last_crawled_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_hunted_deals_user_id ON hunted_deals(user_id);
CREATE INDEX idx_hunted_deals_is_active ON hunted_deals(is_active);
CREATE INDEX idx_hunted_deals_search_term ON hunted_deals(search_term);
```

#### Deals Table
```sql
CREATE TABLE deals (
    id BIGSERIAL PRIMARY KEY,
    hunted_deal_id BIGINT NOT NULL REFERENCES hunted_deals(id) ON DELETE CASCADE,
    external_id VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    title VARCHAR(500) NOT NULL,
    price_amount DECIMAL(12,2) NULL,
    price_currency VARCHAR(8) DEFAULT 'RON',
    price_raw VARCHAR(100) NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    seller_name VARCHAR(255) NULL,
    seller_url TEXT NULL,
    posted_at TIMESTAMP NULL,
    matches_intent BOOLEAN NULL,
    likely_working BOOLEAN NULL,
    confidence DECIMAL(3,2) NULL,
    last_seen_at TIMESTAMP NOT NULL DEFAULT NOW(),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    UNIQUE(hunted_deal_id, external_id)
);

CREATE INDEX idx_deals_hunted_deal_id ON deals(hunted_deal_id);
CREATE INDEX idx_deals_external_id ON deals(external_id);
CREATE INDEX idx_deals_last_seen_at ON deals(last_seen_at);
CREATE INDEX idx_deals_price_amount ON deals(price_amount);
CREATE INDEX idx_deals_matches_intent ON deals(matches_intent);
CREATE INDEX idx_deals_likely_working ON deals(likely_working);
```

#### Deal Snapshots Table
```sql
CREATE TABLE deal_snapshots (
    id BIGSERIAL PRIMARY KEY,
    deal_id BIGINT NOT NULL REFERENCES deals(id) ON DELETE CASCADE,
    title VARCHAR(500) NOT NULL,
    price_amount DECIMAL(12,2) NULL,
    price_currency VARCHAR(8) DEFAULT 'RON',
    price_raw VARCHAR(100) NULL,
    description TEXT NULL,
    image_urls JSON NULL,
    location VARCHAR(255) NULL,
    seller_name VARCHAR(255) NULL,
    seller_url TEXT NULL,
    posted_at TIMESTAMP NULL,
    matches_intent BOOLEAN NULL,
    likely_working BOOLEAN NULL,
    confidence DECIMAL(3,2) NULL,
    captured_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_deal_snapshots_deal_id ON deal_snapshots(deal_id);
CREATE INDEX idx_deal_snapshots_captured_at ON deal_snapshots(captured_at);
CREATE INDEX idx_deal_snapshots_price_amount ON deal_snapshots(price_amount);
```

### Eloquent Relationships

```php
// User Model
public function huntedDeals(): HasMany
{
    return $this->hasMany(HuntedDeal::class);
}

// HuntedDeal Model
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function deals(): HasMany
{
    return $this->hasMany(Deal::class);
}

// Deal Model
public function huntedDeal(): BelongsTo
{
    return $this->belongsTo(HuntedDeal::class);
}

public function snapshots(): HasMany
{
    return $this->hasMany(DealSnapshot::class)->orderBy('captured_at', 'desc');
}

public function latestSnapshot(): HasOne
{
    return $this->hasOne(DealSnapshot::class)->latestOfMany('captured_at');
}

// DealSnapshot Model
public function deal(): BelongsTo
{
    return $this->belongsTo(Deal::class);
}
```

## Error Handling

### Crawler Error Handling

1. **Network Errors**: Implement exponential backoff with configurable retry limits
2. **Selector Changes**: Graceful degradation with fallback selectors and error logging
3. **Rate Limiting**: Respect 429 responses with appropriate delays
4. **Parsing Errors**: Continue processing other listings, log specific failures
5. **MCP Connection Issues**: Fail gracefully, log errors, continue with next hunted deal

### Application Error Handling

1. **Database Errors**: Transaction rollbacks for data consistency
2. **Authentication Errors**: Proper redirect handling and session management
3. **Validation Errors**: User-friendly error messages with field-specific feedback
4. **File System Errors**: Graceful handling of log file access issues

### Error Logging Strategy

```php
// Structured logging for crawler operations
Log::channel('crawler')->info('Crawl started', [
    'hunted_deal_id' => $huntedDeal->id,
    'search_term' => $huntedDeal->search_term,
    'timestamp' => now()
]);

Log::channel('crawler')->error('Selector not found', [
    'selector' => OlxSelectors::LISTING_TITLE,
    'url' => $currentUrl,
    'hunted_deal_id' => $huntedDeal->id
]);
```

## Testing Strategy

**Note: This project explicitly excludes all testing frameworks and test code as per requirements.**

### Manual Testing Approach

1. **Smoke Testing Checklist**:
   - User registration and authentication flow
   - Hunted deal CRUD operations
   - Manual crawl command execution
   - Deal listing and filtering
   - Deal detail page with history display

2. **Integration Testing**:
   - Manual verification of OLX crawling with real search terms
   - Price parsing accuracy with various currency formats
   - AI classification results review
   - Database integrity after crawl operations

3. **Performance Testing**:
   - Manual monitoring of crawl duration and resource usage
   - Database query performance with large datasets
   - Memory usage during batch processing

### Documentation for Manual Testing

- Comprehensive README with setup instructions
- Step-by-step testing procedures
- Expected behavior documentation
- Troubleshooting guide for common issues

## Configuration Management

### Environment Variables

```env
# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=olx_deal_hunter
DB_USERNAME=postgres
DB_PASSWORD=password

# MCP Configuration
MCP_PLAYWRIGHT_ENDPOINT=http://localhost:3000
MCP_PLAYWRIGHT_TOKEN=your_token_here

# Crawler Configuration
CRAWLER_MAX_PAGES_PER_SEARCH=3
CRAWLER_REQUEST_DELAY_MS=2000
CRAWLER_MAX_LISTINGS_PER_RUN=100
CRAWLER_USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"

# Currency Configuration
DEFAULT_CURRENCY=RON
EUR_TO_RON_RATE=4.95
USD_TO_RON_RATE=4.50

# AI Classification
AI_CONFIDENCE_THRESHOLD=0.7
AI_PROVIDER=openai
AI_MODEL=gpt-3.5-turbo
AI_API_KEY=your_api_key_here

# Rate Limiting
RATE_LIMIT_REQUESTS_PER_MINUTE=30
RATE_LIMIT_BURST_LIMIT=10
```

### Feature Flags

```php
// config/features.php
return [
    'ai_classification_enabled' => env('AI_CLASSIFICATION_ENABLED', true),
    'detail_page_crawling' => env('DETAIL_PAGE_CRAWLING', false),
    'image_url_extraction' => env('IMAGE_URL_EXTRACTION', true),
    'seller_info_extraction' => env('SELLER_INFO_EXTRACTION', true),
];
```

This design provides a robust foundation for the OLX Deal Hunter application, emphasizing maintainability, scalability, and compliance with the requirement to exclude all testing frameworks while ensuring reliable operation through comprehensive error handling and monitoring capabilities.