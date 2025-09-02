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

### Using Docker (Recommended)

1. Clone the repository:
```bash
git clone <repository-url>
cd olx-deal-hunter
```

2. Copy environment configuration:
```bash
cp .env.example .env
```

3. Start the development environment:
```bash
make dev
```

4. Run migrations:
```bash
make migrate
```

The application will be available at http://localhost

### Manual Installation

1. Install PHP dependencies:
```bash
composer install
```

2. Install Node.js dependencies:
```bash
npm install
```

3. Copy and configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=olx_deal_hunter
DB_USERNAME=postgres
DB_PASSWORD=password
```

5. Run migrations:
```bash
php artisan migrate
```

6. Build frontend assets:
```bash
npm run build
```

7. Start the development server:
```bash
php artisan serve
```

## Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# MCP Configuration
MCP_PLAYWRIGHT_ENDPOINT=http://localhost:3000
MCP_PLAYWRIGHT_TOKEN=your_token_here

# Crawler Configuration
CRAWLER_MAX_PAGES_PER_SEARCH=3
CRAWLER_REQUEST_DELAY_MS=2000
CRAWLER_MAX_LISTINGS_PER_RUN=100

# AI Classification
AI_PROVIDER=openai
AI_MODEL=gpt-3.5-turbo
AI_API_KEY=your_api_key_here
```

### Scheduled Tasks

Add to your crontab for automated crawling:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use the supervisor configuration in production Docker setup.

## Usage

### Creating Hunted Deals

1. Register/login to your account
2. Navigate to "Hunted Deals" 
3. Click "Create New"
4. Enter your search term (e.g., "iPhone 13")
5. Add optional notes
6. Save and activate

### Manual Crawling

Run a manual crawl for testing:

```bash
# Full crawl
php artisan deals:crawl

# Dry run (no database changes)
php artisan deals:crawl --dry-run

# Specific hunted deal
php artisan deals:crawl --hunted-deal=1
```

### Viewing Results

- **Dashboard**: Overview of your hunted deals and recent activity
- **Deals List**: All tracked deals with filtering options
- **Deal Details**: Complete history, price charts, and images

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
```

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

resources/
├── views/             # Blade templates
├── css/              # Stylesheets
└── js/               # JavaScript files

docker/                # Docker configuration files
```

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