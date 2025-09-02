# OLX Deal Hunter

A Laravel web application that automatically monitors and tracks deals on OLX Romania (olx.ro). The system uses browser automation via Playwright MCP integration to periodically crawl search results, maintain historical price and listing data, and provide intelligent classification of deals using AI agents.

## Features

- **User Authentication**: Secure email/password authentication using Laravel Breeze
- **Hunted Deals Management**: Create and manage search terms to monitor on OLX Romania
- **Automated Crawling**: Hourly scheduled crawling of OLX search results
- **Historical Tracking**: Complete price and listing change history with snapshots
- **AI Classification**: Intelligent filtering for relevant and working items
- **Deal Analysis**: Price history charts, change timelines, and image galleries
- **Docker Support**: Complete Docker setup for development and production

## Requirements

- PHP 8.2+
- PostgreSQL 15+
- Node.js 18+
- Docker & Docker Compose (optional)
- Playwright MCP server for browser automation

## Quick Start

### Prerequisites

Before starting, ensure you have the following installed:

- **PHP 8.2+** with extensions: `pdo_pgsql`, `curl`, `json`, `mbstring`, `openssl`, `tokenizer`, `xml`
- **PostgreSQL 15+** (or Docker for containerized setup)
- **Node.js 18+** and npm
- **Composer** (PHP dependency manager)
- **Docker & Docker Compose** (recommended for development)

### Using Docker (Recommended)

This is the fastest way to get started with all dependencies managed automatically.

1. **Clone and setup:**
```bash
git clone <repository-url>
cd olx-deal-hunter
cp .env.example .env
```

2. **Configure environment variables:**
Edit `.env` file with your settings:
```env
# Application
APP_NAME="OLX Deal Hunter"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database (Docker will handle this)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=olx_deal_hunter
DB_USERNAME=postgres
DB_PASSWORD=password

# MCP Configuration (required for crawling)
MCP_PLAYWRIGHT_ENDPOINT=http://localhost:3000
MCP_PLAYWRIGHT_TOKEN=your_token_here

# AI Classification (optional)
AI_CLASSIFICATION_ENABLED=true
AI_PROVIDER=openai
AI_API_KEY=your_openai_api_key_here
```

3. **Start the development environment:**
```bash
make install  # Install dependencies
make dev      # Start Docker containers
make migrate  # Run database migrations
make seed     # Add sample data (optional)
```

4. **Access the application:**
- Web interface: http://localhost
- Database: localhost:5432 (postgres/password)
- Logs: `make logs`

### Manual Installation

For development without Docker or custom server setups.

1. **Install dependencies:**
```bash
# PHP dependencies
composer install

# Node.js dependencies
npm install

# Copy environment file
cp .env.example .env
php artisan key:generate
```

2. **Database setup:**
```bash
# Create PostgreSQL database
createdb olx_deal_hunter

# Configure database in .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=olx_deal_hunter
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

3. **Build and migrate:**
```bash
# Build frontend assets
npm run build

# Run database migrations
php artisan migrate

# Optional: Add sample data
php artisan db:seed
```

4. **Start development server:**
```bash
# Start Laravel development server
php artisan serve

# In another terminal, start queue worker (for background jobs)
php artisan queue:work

# In another terminal, start scheduler (for automated crawling)
php artisan schedule:work
```

### MCP Server Setup

The application requires a Playwright MCP server for web crawling. You have several options:

#### Option 1: Docker MCP Server (Recommended)
```bash
# Add to your docker-compose.yml or use the provided configuration
docker run -d \
  --name playwright-mcp \
  -p 3000:3000 \
  -e MCP_TOKEN=your_secure_token_here \
  playwright-mcp-server:latest
```

#### Option 2: Local MCP Server
```bash
# Install and run locally (requires Node.js)
npm install -g playwright-mcp-server
MCP_TOKEN=your_token playwright-mcp-server --port 3000
```

#### Option 3: External MCP Service
Use a hosted MCP service and configure the endpoint in your `.env` file.

### First Run Verification

After setup, verify everything is working:

```bash
# Test database connection
php artisan migrate:status

# Test MCP connection
php artisan crawler:test-connection

# Run a test crawl (dry run)
php artisan deals:crawl --dry-run

# Check application health
curl http://localhost/health
```

## Configuration

### Environment Variables

The application uses environment variables for configuration. Here's a comprehensive guide:

#### Core Application Settings
```env
# Application basics
APP_NAME="OLX Deal Hunter"
APP_ENV=production                    # local, staging, production
APP_DEBUG=false                       # true for development
APP_URL=https://your-domain.com
APP_KEY=base64:generated_key_here

# Database configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=olx_deal_hunter
DB_USERNAME=postgres
DB_PASSWORD=secure_password_here

# Redis (optional, for caching and queues)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### MCP and Crawler Configuration
```env
# MCP Server settings
MCP_PLAYWRIGHT_ENDPOINT=http://localhost:3000
MCP_PLAYWRIGHT_TOKEN=your_secure_token_here

# Browser configuration
CRAWLER_USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
CRAWLER_VIEWPORT_WIDTH=1920
CRAWLER_VIEWPORT_HEIGHT=1080
CRAWLER_HEADLESS=true

# Crawling behavior
CRAWLER_MAX_PAGES_PER_SEARCH=3        # Pages to crawl per search
CRAWLER_REQUEST_DELAY_MS=2000         # Delay between requests
CRAWLER_MAX_LISTINGS_PER_RUN=100      # Max listings per crawl session
CRAWLER_TIMEOUT_MS=30000              # Request timeout
CRAWLER_NAVIGATION_TIMEOUT_MS=60000   # Page navigation timeout

# Rate limiting and compliance
CRAWLER_ADAPTIVE_DELAYS=true          # Adjust delays based on response times
CRAWLER_RESPECT_ROBOTS_TXT=true       # Follow robots.txt rules
CRAWLER_MIN_DELAY_MS=1000            # Minimum delay between requests
```

#### AI Classification Settings
```env
# Enable/disable AI features
AI_CLASSIFICATION_ENABLED=true
AI_CONFIDENCE_THRESHOLD=0.7

# OpenAI configuration
AI_PROVIDER=openai
AI_MODEL=gpt-3.5-turbo               # gpt-3.5-turbo, gpt-4, gpt-4-turbo-preview
AI_API_KEY=sk-your_openai_key_here
AI_MAX_TOKENS=1000
AI_TEMPERATURE=0.3

# Alternative: Anthropic Claude
# AI_PROVIDER=anthropic
# AI_MODEL=claude-3-haiku-20240307    # claude-3-haiku, claude-3-sonnet, claude-3-opus
# AI_API_KEY=your_anthropic_key_here
```

#### Currency and Localization
```env
# Currency conversion rates (update regularly)
DEFAULT_CURRENCY=RON
EUR_TO_RON_RATE=4.95
USD_TO_RON_RATE=4.50
GBP_TO_RON_RATE=5.65

# Localization
APP_LOCALE=ro
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Europe/Bucharest
```

#### Performance and Monitoring
```env
# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info                       # debug, info, warning, error

# Queue configuration
QUEUE_CONNECTION=database            # database, redis, sync
QUEUE_FAILED_DRIVER=database

# Cache configuration
CACHE_DRIVER=file                    # file, redis, database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Performance monitoring
CRAWLER_ENABLE_METRICS=true
CRAWLER_METRICS_RETENTION_DAYS=30
```

### Scheduled Tasks

The application includes automated crawling and maintenance tasks.

#### Production Setup (Crontab)
Add this line to your server's crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

#### Development Setup
For local development, run the scheduler in a separate terminal:
```bash
php artisan schedule:work
```

#### Available Scheduled Tasks
The application automatically schedules these tasks:

- **Hourly crawling**: `php artisan deals:crawl` (every hour)
- **Daily cleanup**: Remove old logs and temporary files (daily at 2 AM)
- **Weekly maintenance**: Database optimization and health checks (Sundays at 3 AM)
- **Monthly reports**: Generate usage and performance reports (1st of each month)

#### Custom Schedule Configuration
You can modify the schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Crawl deals every hour
    $schedule->command('deals:crawl')
             ->hourly()
             ->withoutOverlapping()
             ->runInBackground();
    
    // Generate daily reports
    $schedule->command('crawler:daily-report')
             ->dailyAt('06:00');
    
    // Cleanup old data weekly
    $schedule->command('crawler:cleanup')
             ->weekly()
             ->sundays()
             ->at('03:00');
}
```

### Feature Flags

Control application features through configuration:

```php
// config/features.php
return [
    'ai_classification_enabled' => env('AI_CLASSIFICATION_ENABLED', true),
    'detail_page_crawling' => env('DETAIL_PAGE_CRAWLING', false),
    'image_url_extraction' => env('IMAGE_URL_EXTRACTION', true),
    'seller_info_extraction' => env('SELLER_INFO_EXTRACTION', true),
    'price_history_charts' => env('PRICE_HISTORY_CHARTS', true),
    'email_notifications' => env('EMAIL_NOTIFICATIONS', false),
    'webhook_notifications' => env('WEBHOOK_NOTIFICATIONS', false),
];
```

### Security Configuration

#### Production Security Settings
```env
# Security
APP_DEBUG=false
APP_ENV=production

# HTTPS enforcement
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true

# Database security
DB_SSL_MODE=require                  # For production databases

# API rate limiting
THROTTLE_REQUESTS_PER_MINUTE=60
THROTTLE_LOGIN_ATTEMPTS=5
```

#### CORS Configuration
If you plan to use the application with a separate frontend:

```php
// config/cors.php
'allowed_origins' => [
    'https://your-frontend-domain.com',
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization'],
```

## Usage Guide

### Getting Started

#### 1. User Registration and Authentication
```bash
# Create your first user account
php artisan make:user admin@example.com --password=secure_password

# Or register through the web interface at /register
```

#### 2. Creating Your First Hunted Deal

**Via Web Interface:**
1. Navigate to http://localhost/login and sign in
2. Go to "Hunted Deals" in the navigation
3. Click "Create New Hunted Deal"
4. Fill in the form:
   - **Search Term**: `laptop gaming` (what you want to track)
   - **Notes**: `Looking for gaming laptops under 3000 RON` (optional)
   - **Active**: ✓ (enable automatic crawling)
5. Click "Save"

**Via Command Line:**
```bash
# Create a hunted deal programmatically
php artisan hunted-deal:create \
  --user=1 \
  --search-term="laptop gaming" \
  --notes="Looking for gaming laptops under 3000 RON" \
  --active
```

#### 3. Running Your First Crawl

```bash
# Test crawl (no database changes)
php artisan deals:crawl --dry-run --verbose

# Real crawl for specific hunted deal
php artisan deals:crawl --hunted-deal=1

# Full crawl for all active hunted deals
php artisan deals:crawl
```

### Advanced Usage

#### Manual Crawling Options

```bash
# Basic crawling commands
php artisan deals:crawl                    # Crawl all active hunted deals
php artisan deals:crawl --dry-run          # Test run without saving data
php artisan deals:crawl --verbose          # Show detailed output
php artisan deals:crawl --hunted-deal=1    # Crawl specific hunted deal only

# Advanced options
php artisan deals:crawl \
  --hunted-deal=1 \
  --max-pages=5 \
  --delay=3000 \
  --timeout=60000 \
  --verbose

# Batch processing
php artisan deals:crawl \
  --batch-size=10 \
  --max-concurrent=3 \
  --memory-limit=512M
```

#### Testing and Validation

```bash
# Test MCP connection
php artisan crawler:test-connection

# Validate selectors against live OLX
php artisan crawler:validate-selectors --verbose

# Test specific search term
php artisan crawler:test "laptop dell" --pages=2 --dry-run

# Generate crawler health report
php artisan crawler:health-report --output=storage/reports/health.json
```

#### AI Classification Management

```bash
# Test AI classification
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Laptop Dell Latitude" \
  --description="Laptop functional, stare buna"

# Reclassify existing deals
php artisan deals:reclassify --limit=100 --dry-run

# Force reclassification of all deals
php artisan deals:reclassify --force --hunted-deal=1
```

### Web Interface Features

#### Dashboard Overview
- **Recent Activity**: Latest crawled deals and price changes
- **Hunted Deals Summary**: Active searches and their status
- **Quick Stats**: Total deals tracked, price drops found, etc.
- **System Health**: Crawler status and last successful run

#### Hunted Deals Management
- **Create/Edit**: Manage your search terms and preferences
- **Bulk Operations**: Enable/disable multiple hunted deals
- **Performance Metrics**: See crawl success rates and timing
- **Export Data**: Download your hunted deals configuration

#### Deals Exploration
- **Advanced Filtering**:
  ```
  - Price drops in last 24h/7d/30d
  - New listings (first time seen)
  - AI-classified as "matches intent"
  - AI-classified as "likely working"
  - Price range filters
  - Location-based filtering
  - Date range selection
  ```

- **Sorting Options**:
  ```
  - Latest price changes
  - Biggest price drops
  - Newest listings
  - Best AI confidence scores
  - Most recent activity
  ```

#### Deal Detail Views
- **Current Information**: Latest title, price, description, images
- **Price History Chart**: Interactive chart showing price changes over time
- **Change Timeline**: Chronological view of all modifications
- **Image Gallery**: All images found across different snapshots
- **AI Analysis**: Classification results and confidence scores
- **External Links**: Direct links to OLX listing

### API Usage (Optional)

If you enable API access, you can integrate with external tools:

```bash
# Get hunted deals
curl -H "Authorization: Bearer your_api_token" \
  http://localhost/api/hunted-deals

# Get deals for a specific hunted deal
curl -H "Authorization: Bearer your_api_token" \
  http://localhost/api/hunted-deals/1/deals

# Trigger manual crawl
curl -X POST -H "Authorization: Bearer your_api_token" \
  http://localhost/api/crawl/hunted-deal/1
```

### Monitoring and Maintenance

#### Log Monitoring
```bash
# Watch crawler logs in real-time
tail -f storage/logs/crawler.log

# Search for specific issues
grep -i "error\|failed\|timeout" storage/logs/crawler.log

# View structured logs
php artisan log:show --channel=crawler --level=error --last=24h
```

#### Performance Monitoring
```bash
# Generate performance report
php artisan crawler:performance-report --days=7

# Check database health
php artisan db:health-check

# Monitor memory usage
php artisan crawler:memory-report --hunted-deal=1
```

#### Maintenance Tasks
```bash
# Clean up old data
php artisan crawler:cleanup --days=90 --dry-run

# Optimize database
php artisan db:optimize

# Clear application caches
php artisan optimize:clear

# Update currency exchange rates
php artisan currency:update-rates
```

### Troubleshooting Common Issues

#### Issue: No deals found
```bash
# Check if search term returns results on OLX manually
# Verify MCP connection
php artisan crawler:test-connection

# Test with verbose output
php artisan crawler:test "your-search-term" --verbose --dry-run
```

#### Issue: Slow crawling
```bash
# Check current configuration
php artisan config:show crawler

# Optimize settings
CRAWLER_REQUEST_DELAY_MS=1500
CRAWLER_MAX_PAGES_PER_SEARCH=2
CRAWLER_TIMEOUT_MS=20000
```

#### Issue: AI classification not working
```bash
# Test AI connection
php artisan ai:test-classification --test-connection

# Check API key and credits
php artisan ai:check-quota

# Fallback to keyword-based classification
AI_CLASSIFICATION_ENABLED=false
```

## Development

### Available Make Commands

```bash
make help          # Show available commands
make install       # Install dependencies and setup
make dev           # Start development environment
make migrate       # Run database migrations
make fresh         # Fresh migration with seeding
make logs          # Show Docker logs
make shell         # Access container shell
make lint          # Run code linting
make crawl         # Run manual crawl
make crawl-dry     # Run dry-run crawl
```

### Manual Testing Procedures

Since this project excludes automated testing frameworks, comprehensive manual testing is essential. Follow these procedures to ensure system reliability:

#### 1. System Health Verification

**Prerequisites Check:**
```bash
# Verify PHP version and extensions
php -v && php -m | grep -E "(pdo_pgsql|curl|json|mbstring)"

# Check database connectivity
php artisan migrate:status

# Verify MCP server connection
php artisan crawler:test-connection

# Check file permissions
ls -la storage/ bootstrap/cache/
```

**Expected Results:**
- PHP 8.2+ with required extensions
- All migrations show "Ran" status
- MCP connection returns success
- Storage directories are writable

#### 2. Authentication Flow Testing

**User Registration:**
1. Navigate to `/register`
2. Fill form with valid email/password
3. Verify email validation works
4. Check successful registration redirects to dashboard

**User Login:**
1. Navigate to `/login`
2. Test with valid credentials → should redirect to dashboard
3. Test with invalid credentials → should show error message
4. Test "Remember Me" functionality
5. Test logout functionality

**Expected Behaviors:**
- Form validation prevents invalid inputs
- Successful auth shows personalized dashboard
- Failed auth shows clear error messages
- Session management works correctly

#### 3. Hunted Deals Management Testing

**Create Hunted Deal:**
1. Navigate to "Hunted Deals" → "Create New"
2. Test form validation:
   - Empty search term → should show error
   - Valid search term → should save successfully
3. Test with various search terms:
   - Single word: "laptop"
   - Multiple words: "laptop gaming"
   - Romanian terms: "telefon samsung"
   - Special characters: "auto BMW 320d"

**Edit Hunted Deal:**
1. Click "Edit" on existing hunted deal
2. Modify search term and notes
3. Toggle active/inactive status
4. Verify changes are saved

**Delete Hunted Deal:**
1. Click "Delete" on hunted deal
2. Confirm deletion in modal
3. Verify hunted deal and associated deals are removed

**Expected Behaviors:**
- Form validation works correctly
- All CRUD operations complete successfully
- UI updates reflect changes immediately
- Cascading deletes work properly

#### 4. Crawling System Testing

**Manual Crawl Testing:**
```bash
# Test dry run (no database changes)
php artisan deals:crawl --dry-run --verbose

# Expected output should show:
# - MCP connection established
# - Search navigation successful
# - Listings extracted (count > 0)
# - Data parsing successful
# - No database writes (dry run)
```

**Real Crawl Testing:**
```bash
# Create test hunted deal first
php artisan hunted-deal:create --user=1 --search-term="laptop test" --active

# Run real crawl
php artisan deals:crawl --hunted-deal=1 --verbose

# Verify results in database
php artisan tinker
>>> Deal::where('hunted_deal_id', 1)->count()
>>> DealSnapshot::whereHas('deal', fn($q) => $q->where('hunted_deal_id', 1))->count()
```

**Expected Results:**
- Crawl completes without errors
- New deals are created in database
- Snapshots are created for each deal
- Last crawled timestamp is updated

#### 5. Data Quality Validation

**Price Parsing Testing:**
Test various price formats found on OLX:
```bash
php artisan tinker
>>> $parser = app(\App\Services\PriceParserService::class);
>>> $parser->parsePrice("1.500 lei");        // Should return 1500.00 RON
>>> $parser->parsePrice("€250");             // Should convert to RON
>>> $parser->parsePrice("Negociabil");       // Should return null
```

**External ID Extraction:**
Test URL patterns:
```bash
php artisan tinker
>>> $crawler = app(\App\Services\Crawlers\OlxCrawlerService::class);
>>> $crawler->extractExternalId("https://www.olx.ro/d/oferta/laptop-dell-ID123456.html");
>>> // Should return "123456"
```

**Image URL Processing:**
Verify image URLs are properly extracted and validated.

#### 6. AI Classification Testing

**Connection Testing:**
```bash
php artisan ai:test-classification --test-connection
# Should return success if API key is valid
```

**Classification Accuracy:**
```bash
# Test with working item
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Laptop Dell Latitude" \
  --description="Laptop functional, stare foarte buna, testat"

# Test with broken item
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Laptop pentru piese" \
  --description="Laptop stricat, nu porneste, pentru piese"
```

**Expected Results:**
- Working item: `matches_intent=true`, `likely_working=true`, `confidence>0.7`
- Broken item: `matches_intent=true`, `likely_working=false`, `confidence>0.7`

#### 7. Web Interface Testing

**Dashboard Functionality:**
1. Verify recent activity shows latest deals
2. Check hunted deals summary displays correctly
3. Confirm quick stats are accurate
4. Test responsive design on mobile/tablet

**Deals Listing:**
1. Test pagination with large datasets
2. Verify filtering options work:
   - Price drops filter
   - New items filter (24h)
   - AI classification filters
3. Test sorting options
4. Check search functionality

**Deal Detail Pages:**
1. Verify all deal information displays correctly
2. Test price history chart functionality
3. Check change timeline shows chronological changes
4. Verify image gallery displays properly
5. Test external link to OLX listing

#### 8. Performance Testing

**Memory Usage:**
```bash
# Monitor memory during crawl
php artisan deals:crawl --verbose --hunted-deal=1
# Check memory usage in logs
```

**Response Times:**
1. Measure page load times for different sections
2. Test with large datasets (100+ deals)
3. Monitor database query performance

**Concurrent Operations:**
1. Run multiple crawls simultaneously
2. Test web interface while crawling is active
3. Verify no data corruption occurs

#### 9. Error Handling Testing

**Network Errors:**
1. Disconnect internet during crawl
2. Verify graceful error handling
3. Check error logging is comprehensive

**Invalid Data:**
1. Test with search terms that return no results
2. Test with malformed OLX pages
3. Verify system continues with partial data

**Database Errors:**
1. Test with database connection issues
2. Verify transaction rollbacks work
3. Check data consistency after errors

#### 10. Security Testing

**Authentication Security:**
1. Test password requirements
2. Verify session timeout works
3. Test CSRF protection on forms

**Input Validation:**
1. Test XSS prevention in search terms
2. Test SQL injection prevention
3. Verify file upload restrictions (if applicable)

### Expected System Behaviors

#### Normal Operation Indicators
- Crawls complete within expected timeframes (< 2 minutes per hunted deal)
- Memory usage stays below 256MB during normal operations
- Database queries execute in < 100ms for most operations
- Error rate stays below 5% for crawling operations
- AI classification accuracy > 80% for clear cases

#### Warning Signs
- Crawl duration > 5 minutes per hunted deal
- Memory usage > 512MB
- Error rate > 10%
- Database queries > 1 second
- Frequent MCP connection failures

#### Critical Issues
- Complete crawl failures
- Database corruption
- Memory leaks causing crashes
- Security vulnerabilities
- Data loss or corruption

### Testing Checklist

Before deploying or after major changes, complete this checklist:

- [ ] All authentication flows work correctly
- [ ] Hunted deals CRUD operations function properly
- [ ] Manual crawl completes successfully
- [ ] Automated crawl runs without errors
- [ ] AI classification produces reasonable results
- [ ] Web interface displays data correctly
- [ ] Price parsing handles various formats
- [ ] Image URLs are extracted and validated
- [ ] Error handling works gracefully
- [ ] Performance meets expectations
- [ ] Security measures are effective
- [ ] Logs provide sufficient debugging information

For detailed testing procedures, see [Manual Testing Guide](docs/dev/manual-testing-guide.md).

### Project Structure

```
app/
├── Http/Controllers/     # Web controllers
├── Models/              # Eloquent models
├── Services/            # Business logic services
└── Console/Commands/    # Artisan commands

config/
├── crawler.php          # Crawler configuration
├── ai.php              # AI classification config
└── features.php        # Feature flags

database/
├── migrations/         # Database migrations
└── seeders/           # Database seeders

docs/
├── ai-classification.md           # AI system documentation
└── dev/
    ├── playwright-mcp-notes.md    # MCP integration details
    └── manual-testing-guide.md    # Testing procedures

resources/
├── views/             # Blade templates
├── css/              # Stylesheets
└── js/               # JavaScript files

docker/                # Docker configuration files
```

### Developer Documentation

The project includes comprehensive developer documentation:

- **[Playwright MCP Integration Notes](docs/dev/playwright-mcp-notes.md)** - Detailed MCP integration documentation with DOM analysis, selector strategies, and anti-bot measures
- **[AI Classification Guide](docs/ai-classification.md)** - Complete AI classification system documentation
- **[Manual Testing Guide](docs/dev/manual-testing-guide.md)** - Comprehensive testing procedures and expected behaviors

These documents are essential for maintaining and extending the crawler functionality when OLX changes their DOM structure or when adapting the system for other marketplaces.

## Production Deployment

### Using Docker

1. Build production image:
```bash
make production-build
```

2. Configure production environment:
```bash
cp .env.example .env.production
# Edit .env.production with production values
```

3. Start production services:
```bash
make production-up
```

### Manual Deployment

1. Install dependencies:
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

2. Configure environment for production
3. Run migrations:
```bash
php artisan migrate --force
```

4. Configure web server (Nginx/Apache)
5. Set up supervisor for queue workers and scheduler

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).