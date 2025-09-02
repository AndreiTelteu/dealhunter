# Requirements Document

## Introduction

The OLX Deal Hunter is a Laravel web application that enables users to automatically monitor and track deals on OLX Romania (olx.ro). The system uses browser automation via Playwright MCP integration to periodically crawl search results, maintain historical price and listing data, and provide intelligent classification of deals using AI agents. Users can define "hunted deals" (search terms) and receive comprehensive tracking of matching listings with full change history.

## Requirements

### Requirement 1

**User Story:** As a deal hunter, I want to authenticate and manage my account, so that I can securely access my personalized deal tracking dashboard.

#### Acceptance Criteria

1. WHEN a user visits the application THEN the system SHALL provide email/password authentication using Laravel Breeze or Fortify
2. WHEN a user successfully authenticates THEN the system SHALL redirect them to a personalized dashboard
3. WHEN a user accesses protected pages without authentication THEN the system SHALL redirect them to the login page
4. IF a user provides invalid credentials THEN the system SHALL display appropriate error messages

### Requirement 2

**User Story:** As a deal hunter, I want to create and manage hunted deals, so that I can define specific search terms to monitor on OLX Romania.

#### Acceptance Criteria

1. WHEN a user creates a hunted deal THEN the system SHALL require a search_term field and allow optional notes
2. WHEN a user creates a hunted deal THEN the system SHALL set is_active to true by default
3. WHEN a user views their hunted deals THEN the system SHALL display search_term, is_active status, last_crawled_at, and total deals found
4. WHEN a user edits a hunted deal THEN the system SHALL allow modification of search_term, is_active flag, and notes
5. WHEN a user deletes a hunted deal THEN the system SHALL remove the hunted deal and all associated deals and snapshots
6. IF a user deactivates a hunted deal THEN the system SHALL exclude it from future crawling operations

### Requirement 3

**User Story:** As a deal hunter, I want the system to automatically crawl OLX Romania hourly, so that I can stay updated on new listings and price changes for my hunted deals.

#### Acceptance Criteria

1. WHEN the hourly scheduler runs THEN the system SHALL process all active hunted deals
2. WHEN processing a hunted deal THEN the system SHALL use Playwright MCP to navigate to olx.ro and perform the search
3. WHEN crawling search results THEN the system SHALL extract external_id, title, price, description, URL, images, location, seller info, and posted_at from up to 3 pages of results
4. WHEN extracting price information THEN the system SHALL parse and normalize prices to RON currency with both numeric and raw string storage
5. WHEN finding a new listing THEN the system SHALL create a Deal record and initial DealSnapshot
6. WHEN finding an existing listing with changes THEN the system SHALL create a new DealSnapshot and update the Deal record
7. IF crawling encounters errors for one hunted deal THEN the system SHALL continue processing other hunted deals
8. WHEN crawling completes THEN the system SHALL update last_crawled_at timestamp for each processed hunted deal

### Requirement 4

**User Story:** As a deal hunter, I want AI-powered classification of listings, so that I can quickly identify relevant and working items.

#### Acceptance Criteria

1. WHEN processing each listing THEN the system SHALL classify matches_intent based on search term relevance
2. WHEN processing each listing THEN the system SHALL classify likely_working based on description analysis for defects or damage
3. WHEN processing each listing THEN the system SHALL assign a confidence score between 0.0 and 1.0
4. WHEN classifying intent THEN the system SHALL consider title/description containing search terms or strong synonyms
5. WHEN classifying working condition THEN the system SHALL detect Romanian keywords indicating broken items ("stricat", "defect", "pentru piese", "nu funcționează")
6. WHEN AI classification completes THEN the system SHALL store results in both Deal and DealSnapshot records

### Requirement 5

**User Story:** As a deal hunter, I want to view and analyze my tracked deals, so that I can identify the best opportunities and track price changes over time.

#### Acceptance Criteria

1. WHEN a user views the deals list THEN the system SHALL display paginated results with title, current price, location, and last_seen_at
2. WHEN a user applies filters THEN the system SHALL support filtering by price drops, newly found items (last 24h), matches_intent, and likely_working
3. WHEN a user views deal details THEN the system SHALL display current snapshot, price history chart, title/description change timeline, and image gallery
4. WHEN displaying price history THEN the system SHALL show price changes over time using a lightweight chart visualization
5. WHEN displaying images THEN the system SHALL show stored image URLs as a small gallery without downloading files
6. WHEN viewing deal timeline THEN the system SHALL show chronological changes to title, price, description, and other metadata

### Requirement 6

**User Story:** As a deal hunter, I want comprehensive historical tracking, so that I can analyze trends and never miss important changes to listings.

#### Acceptance Criteria

1. WHEN any listing field changes THEN the system SHALL create an immutable DealSnapshot record with captured_at timestamp
2. WHEN storing snapshots THEN the system SHALL include title, price_amount, price_currency, price_raw, description, image_urls, location, seller info, and AI classifications
3. WHEN a listing is seen again without changes THEN the system SHALL update only the last_seen_at timestamp without creating a new snapshot
4. WHEN storing image URLs THEN the system SHALL maintain them as JSON array or related DealImage records with sort order
5. IF a listing disappears from search results THEN the system SHALL retain all historical data without deletion

### Requirement 7

**User Story:** As a system administrator, I want monitoring and configuration capabilities, so that I can ensure reliable operation and compliance with OLX terms of service.

#### Acceptance Criteria

1. WHEN crawling operations run THEN the system SHALL log totals for searched terms, found listings, updated deals, created snapshots, and errors
2. WHEN accessing admin pages THEN the system SHALL display recent crawl logs and statistics
3. WHEN configuring the system THEN the system SHALL support environment variables for rate limiting, pages per search, currency conversion, and MCP settings
4. WHEN rate limiting THEN the system SHALL implement configurable throttling between page requests and hard caps on listings per run
5. IF robots.txt or terms of service restrictions exist THEN the system SHALL respect them through configurable crawling windows and delays

### Requirement 8

**User Story:** As a developer, I want well-documented selectors and integration patterns, so that I can maintain and adapt the crawling logic when OLX changes their DOM structure.

#### Acceptance Criteria

1. WHEN developing crawler logic THEN the system SHALL use Playwright MCP to inspect live olx.ro DOM structure
2. WHEN documenting selectors THEN the system SHALL maintain docs/dev/playwright-mcp-notes.md with HTML snippets and CSS/XPath selectors
3. WHEN implementing selectors THEN the system SHALL centralize them in app/Services/Crawlers/OlxSelectors.php with MCP reference comments
4. WHEN selectors change THEN the system SHALL provide fallback strategies documented in the MCP notes
5. IF DOM structure changes THEN the system SHALL fail gracefully with appropriate error logging

### Requirement 9

**User Story:** As a developer, I want a production-ready deployment setup, so that I can easily run the application locally and deploy it to production environments.

#### Acceptance Criteria

1. WHEN setting up locally THEN the system SHALL provide Docker Compose with app, PostgreSQL database, and optional Redis services
2. WHEN deploying THEN the system SHALL include production-focused Dockerfile and environment configuration
3. WHEN running migrations THEN the system SHALL create all required tables with proper indexes and relationships
4. WHEN seeding data THEN the system SHALL provide demo user and sample hunted deal for testing
5. IF the application starts THEN the system SHALL have all dependencies properly configured without any testing frameworks or test directories