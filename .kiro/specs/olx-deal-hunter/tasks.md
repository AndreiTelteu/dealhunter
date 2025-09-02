# Implementation Plan

-   [x] 1. Set up project foundation and environment

    -   Configure Laravel 11 application with PHP 8.2+ requirements
    -   Set up PostgreSQL database configuration and connection
    -   Install and configure Laravel Breeze for authentication (without testing scaffolding)
    -   Create Docker Compose setup with app, PostgreSQL, and optional Redis services
    -   Configure environment variables for database, MCP, crawler, and AI settings
    -   _Requirements: 9.1, 9.2, 9.3, 1.1_

-   [x] 2. Create database schema and models

    -   Create migration for users table with authentication fields
    -   Create migration for hunted_deals table with user relationship and search configuration
    -   Create migration for deals table with external_id uniqueness and comprehensive listing data
    -   Create migration for deal_snapshots table with immutable historical records
    -   Add proper database indexes for performance optimization
    -   _Requirements: 9.4, 6.1, 6.2, 2.1_

-   [x] 3. Implement Eloquent models with relationships

    -   Create User model with hunted deals relationship
    -   Create HuntedDeal model with user and deals relationships
    -   Create Deal model with hunted deal and snapshots relationships

    -   Create DealSnapshot model with deal relationship and JSON casting for image URLs
    -   Define proper fillable fields, casts, and validation rules
    -   _Requirements: 2.1, 6.1, 6.2, 6.4_

-   [x] 4. Build core service classes for business logic

    -   Create OlxSelectors class with centralized CSS selectors and MCP documentation references
    -   Create PriceParserService for currency detection, numeric extraction, and RON conversion
    -   Create IntentClassifierService with Romanian keyword detection and AI integration
    -   Create base service structure with error handling and logging
    -   _Requirements: 8.3, 3.4, 4.1, 4.2, 4.5_

-   [x] 5. Implement Playwright MCP integration service

    -   Create OlxCrawlerService with MCP connection and browser automation
    -   Implement search navigation, results extraction, and pagination handling
    -   Add rate limiting, request delays, and error recovery mechanisms
    -   Create ParsedListing data structure for extracted listing information
    -   Implement robust selector-based data extraction with fallback strategies
    -   _Requirements: 3.1, 3.2, 3.3, 7.4, 8.1, 8.4_

-   [x] 6. Build deal ingestion and snapshot management

    -   Create DealIngestionService for upserting deals and managing snapshots
    -   Implement change detection logic comparing current vs stored listing data
    -   Create snapshot creation when significant changes are detected
    -   Add logic to update last_seen_at timestamps for unchanged listings
    -   Implement proper transaction handling for data consistency
    -   _Requirements: 3.5, 3.6, 6.1, 6.2, 6.3_

-   [ ] 7. Create scheduled crawling command

    -   Create CrawlDealsCommand with signature and options for dry-run and specific hunted deals
    -   Implement active hunted deals processing loop with error isolation
    -   Add comprehensive logging for crawl statistics and error reporting
    -   Update last_crawled_at timestamps after successful processing
    -   Register command in Laravel scheduler for hourly execution
    -   _Requirements: 3.1, 3.7, 3.8, 7.1, 7.2_

-   [ ] 8. Build authentication and user interface foundation

    -   Set up Laravel Breeze authentication without testing scaffolding
    -   Create base layout with Tailwind CSS styling and navigation
    -   Implement user dashboard with hunted deals overview and recent activity
    -   Add responsive design patterns and basic Alpine.js interactivity
    -   Create authentication middleware and route protection
    -   _Requirements: 1.1, 1.2, 1.3, 1.4_

-   [ ] 9. Implement hunted deals management interface

    -   Create HuntedDealController with full CRUD operations
    -   Build create/edit forms with search_term, is_active, and notes fields
    -   Implement hunted deals listing with pagination and status indicators
    -   Add hunted deal detail page showing associated deals and crawl statistics
    -   Implement soft delete functionality with cascading to related deals
    -   _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

-   [ ] 10. Build deals listing and filtering interface

    -   Create DealController with index method supporting pagination and filters
    -   Implement filtering by price drops, new items (24h), matches_intent, and likely_working
    -   Add search functionality within deals by title and description
    -   Create deals listing view with sortable columns and filter controls
    -   Add deal summary cards showing key information and status indicators
    -   _Requirements: 5.1, 5.2_

-   [ ] 11. Create comprehensive deal detail pages

    -   Build deal detail view showing current snapshot and listing information
    -   Implement price history chart using lightweight charting library or inline SVG
    -   Create timeline view showing chronological changes to title, price, and description
    -   Add image gallery displaying stored image URLs without downloading files
    -   Show AI classification results and confidence scores
    -   _Requirements: 5.3, 5.4, 5.5, 5.6_

-   [ ] 12. Implement AI classification integration

    -   Create AI service integration for intent matching and working condition assessment
    -   Implement Romanian keyword detection for broken items and defects
    -   Add confidence scoring based on multiple classification signals
    -   Store classification results in both Deal and DealSnapshot records
    -   Add configuration for AI provider, model selection, and API credentials
    -   _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

-   [ ] 13. Add monitoring and administration features

    -   Create admin dashboard showing recent crawl logs and statistics
    -   Implement crawl logging with structured data for analysis
    -   Add system health monitoring for MCP connection and database performance
    -   Create configuration management interface for crawler settings
    -   Add manual crawl trigger functionality for testing and debugging
    -   _Requirements: 7.1, 7.2, 7.3, 7.5_

-   [ ] 14. Create developer documentation and MCP integration notes

    -   Document Playwright MCP findings in docs/dev/playwright-mcp-notes.md
    -   Include HTML snippets, selectors, and DOM structure analysis
    -   Add fallback strategies for selector changes and anti-bot measures
    -   Create comprehensive README with setup, configuration, and usage instructions
    -   Document manual testing procedures and expected behaviors
    -   _Requirements: 8.1, 8.2, 8.3, 8.4_

-   [ ] 15. Implement production deployment configuration

    -   Create production Dockerfile with optimized PHP-FPM and Nginx setup
    -   Configure Docker Compose for production with proper security settings
    -   Add database seeding with demo user and sample hunted deal
    -   Create Makefile with common development and deployment commands
    -   Configure logging, error reporting, and performance monitoring
    -   _Requirements: 9.1, 9.2, 9.4, 9.5_

-   [ ] 16. Final integration and system testing
    -   Integrate all components and verify end-to-end functionality
    -   Test complete user workflow from registration to deal tracking
    -   Verify crawler operation with real OLX searches and data extraction
    -   Validate AI classification accuracy and price parsing correctness
    -   Confirm proper error handling and recovery mechanisms
    -   _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 8.1, 9.1_
