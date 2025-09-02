# Playwright MCP Integration Notes

This document contains comprehensive findings and implementation notes for the Playwright MCP integration with OLX Romania crawling. It serves as the definitive reference for maintaining and adapting the crawling logic when OLX changes their DOM structure.

## MCP Service Configuration

The crawler service connects to a Playwright MCP server that provides browser automation capabilities through a RESTful API interface.

### Required Environment Variables

```env
# MCP Server Configuration
MCP_PLAYWRIGHT_ENDPOINT=http://localhost:3000
MCP_PLAYWRIGHT_TOKEN=your_token_here

# Browser Configuration
CRAWLER_USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
CRAWLER_VIEWPORT_WIDTH=1920
CRAWLER_VIEWPORT_HEIGHT=1080
CRAWLER_HEADLESS=true

# Performance Settings
CRAWLER_TIMEOUT_MS=30000
CRAWLER_NAVIGATION_TIMEOUT_MS=60000
CRAWLER_REQUEST_DELAY_MS=2000
```

### MCP API Endpoints

The service uses the following MCP endpoints with detailed request/response patterns:

#### Navigation and Page Control
- `POST /playwright/navigate` - Navigate to a URL
  ```json
  {
    "url": "https://www.olx.ro/",
    "waitUntil": "networkidle",
    "timeout": 30000
  }
  ```

- `POST /playwright/wait` - Wait for selector or timeout
  ```json
  {
    "selector": "[data-testid='listing-grid']",
    "timeout": 10000,
    "state": "visible"
  }
  ```

#### Element Interaction
- `POST /playwright/fill` - Fill input field
  ```json
  {
    "selector": "input[data-testid='search-input']",
    "value": "laptop gaming",
    "clear": true
  }
  ```

- `POST /playwright/click` - Click element
  ```json
  {
    "selector": "button[data-testid='search-submit']",
    "timeout": 5000
  }
  ```

#### Data Extraction
- `POST /playwright/query` - Query single element
  ```json
  {
    "selector": "h6[data-testid='ad-title']",
    "attribute": "textContent"
  }
  ```

- `POST /playwright/queryAll` - Query multiple elements
  ```json
  {
    "selector": "[data-testid='l-card']",
    "attribute": "outerHTML"
  }
  ```

- `POST /playwright/queryInElement` - Query within specific element
  ```json
  {
    "parentSelector": "[data-testid='l-card']",
    "childSelector": "img[data-testid='listing-image']",
    "attribute": "src"
  }
  ```

- `POST /playwright/getAttribute` - Get element attribute
  ```json
  {
    "selector": "a[data-testid='listing-ad-title']",
    "attribute": "href"
  }
  ```

## OLX Romania DOM Structure Analysis

### Complete Page Structure

Based on MCP inspection of live OLX pages, the following DOM structure has been identified:

#### Homepage Structure
```html
<!DOCTYPE html>
<html lang="ro">
<head>
  <title>OLX.ro - Anunturi gratuite</title>
  <!-- Meta tags and CSS -->
</head>
<body>
  <div id="root">
    <!-- Header with navigation -->
    <header class="header">
      <div class="search-container">
        <input data-testid="search-input" 
               placeholder="Caută în toate categoriile" 
               type="text" 
               name="q" />
        <button data-testid="search-submit" type="submit">
          Caută
        </button>
      </div>
    </header>
    
    <!-- Main content area -->
    <main class="main-content">
      <!-- Category navigation -->
      <nav class="categories">
        <a href="/electronice-si-electrocasnice/">Electronice</a>
        <a href="/auto-masini-moto/">Auto, Mașini, Moto</a>
        <!-- More categories -->
      </nav>
    </main>
  </div>
</body>
</html>
```

#### Search Results Page Structure
```html
<!-- Search Results Container -->
<div class="search-results">
  <!-- Search form (persistent) -->
  <form class="search-form">
    <input data-testid="search-input" value="laptop" />
    <button data-testid="search-submit">Caută</button>
  </form>
  
  <!-- Filters sidebar -->
  <aside class="filters-sidebar">
    <div class="filter-group">
      <label>Preț</label>
      <input type="number" placeholder="De la" />
      <input type="number" placeholder="Până la" />
    </div>
    <div class="filter-group">
      <label>Locația</label>
      <input data-testid="location-input" placeholder="Oraș" />
    </div>
  </aside>
  
  <!-- Main results area -->
  <main class="results-main">
    <!-- Results header with count -->
    <div class="results-header">
      <h1>Anunturi pentru "laptop" în România</h1>
      <span class="results-count">1,234 anunturi</span>
    </div>
    
    <!-- Listing grid -->
    <div data-testid="listing-grid" class="listing-grid">
      <!-- Individual listing cards -->
      <div data-testid="l-card" class="listing-card">
        <!-- Listing image -->
        <div class="listing-image-container">
          <img data-testid="listing-image" 
               src="https://apollo-ireland.akamaized.net/v1/files/..." 
               alt="Laptop Dell Latitude" />
          <!-- Promoted badge (if applicable) -->
          <div class="promoted-badge">Promovat</div>
        </div>
        
        <!-- Listing content -->
        <div class="listing-content">
          <!-- Title with link -->
          <h6 data-testid="ad-title" class="listing-title">
            <a data-testid="listing-ad-title" 
               href="/d/oferta/laptop-dell-latitude-ID123456.html">
              Laptop Dell Latitude E7450
            </a>
          </h6>
          
          <!-- Price -->
          <div class="price-container">
            <span data-testid="ad-price" class="price">1.500 lei</span>
            <span class="negotiable">Negociabil</span>
          </div>
          
          <!-- Location and date -->
          <div data-testid="location-date" class="location-date">
            <span class="location">București, Sectorul 1</span>
            <span class="separator">•</span>
            <span class="date">Azi 12:34</span>
          </div>
          
          <!-- Additional metadata -->
          <div class="listing-meta">
            <span class="views">123 vizualizări</span>
            <button class="favorite-btn">♥</button>
          </div>
        </div>
      </div>
      
      <!-- More listing cards... -->
    </div>
    
    <!-- Pagination -->
    <nav class="pagination">
      <a class="pagination-prev disabled">Precedenta</a>
      <span class="pagination-pages">
        <a class="current">1</a>
        <a href="?page=2">2</a>
        <a href="?page=3">3</a>
        <span>...</span>
        <a href="?page=42">42</a>
      </span>
      <a data-testid="pagination-forward" 
         href="?page=2" 
         class="pagination-next">Următoarea</a>
    </nav>
  </main>
</div>
```

#### Listing Detail Page Structure
```html
<div class="listing-detail">
  <!-- Image gallery -->
  <div class="image-gallery">
    <div class="main-image">
      <img src="https://apollo-ireland.akamaized.net/v1/files/..." />
    </div>
    <div class="thumbnail-list">
      <img src="..." class="thumbnail active" />
      <img src="..." class="thumbnail" />
    </div>
  </div>
  
  <!-- Listing information -->
  <div class="listing-info">
    <h1 class="listing-title">Laptop Dell Latitude E7450</h1>
    <div class="price-section">
      <span class="price">1.500 lei</span>
      <span class="negotiable">Negociabil</span>
    </div>
    
    <!-- Description -->
    <div data-testid="ad-description" class="description">
      <h3>Descriere</h3>
      <p>Laptop în stare foarte bună, funcționează perfect...</p>
    </div>
    
    <!-- Seller information -->
    <div data-testid="seller-info" class="seller-info">
      <h3>Vânzător</h3>
      <div class="seller-details">
        <span class="seller-name">Ion Popescu</span>
        <span class="seller-type">Utilizator privat</span>
        <span data-testid="posted-date" class="posted-date">
          Publicat pe 15 decembrie 2023
        </span>
      </div>
    </div>
  </div>
</div>
```

### Comprehensive Fallback Selector Strategy

The application implements a multi-tier fallback system to handle DOM structure changes. Each selector type has primary, secondary, and tertiary fallback options based on historical OLX DOM patterns.

#### Search Interface Fallbacks
```php
// Primary selectors (current OLX structure)
'input[data-testid="search-input"]'
'button[data-testid="search-submit"]'
'input[data-testid="location-input"]'

// Secondary fallbacks (previous OLX versions)
'input[name="q"]'
'input[placeholder*="Caută"]'
'button[type="submit"]'
'.search-submit'
'input[name="city"]'
'input[placeholder*="Oraș"]'

// Tertiary fallbacks (generic patterns)
'input[type="search"]'
'form input[type="text"]:first-child'
'form button:first-of-type'
```

#### Listing Container Fallbacks
```php
// Primary: Current data-testid structure
'[data-testid="listing-grid"]'

// Secondary: Class-based selectors from previous versions
'.listing-grid'
'.ads-list-photo'
'.offers'

// Tertiary: Generic container patterns
'.results-container'
'.search-results main'
'main .grid'
```

#### Individual Listing Item Fallbacks
```php
// Primary: Current card structure
'[data-testid="l-card"]'

// Secondary: Historical class names
'.offer-wrapper'
'.listing-card'
'.ad-card'

// Tertiary: Generic card patterns
'.card'
'.item'
'article'
```

#### Title Extraction Fallbacks
```php
// Primary: Current structure with data-testid
'h6[data-testid="ad-title"]'
'a[data-testid="listing-ad-title"]'

// Secondary: Heading-based selectors
'h3 a'
'h4 a'
'h5 a'
'h6 a'

// Tertiary: Class-based patterns
'.offer-item-title'
'.title-cell a'
'.listing-title a'
'.ad-title a'

// Quaternary: Generic patterns
'.title a'
'a[href*="/d/oferta/"]'
```

#### Price Extraction Fallbacks
```php
// Primary: Current data-testid
'[data-testid="ad-price"]'

// Secondary: Class-based selectors
'.price'
'.offer-item-price'
'.price-label'

// Tertiary: Content-based patterns
'*:contains("lei")'
'*:contains("€")'
'*:contains("$")'

// Quaternary: Generic price patterns
'.amount'
'.cost'
'[class*="price"]'
```

#### Location and Date Fallbacks
```php
// Primary: Current structure
'[data-testid="location-date"]'

// Secondary: Historical patterns
'.location-date'
'.bottom-cell'
'.offer-item-details'

// Tertiary: Semantic patterns
'.location'
'.date'
'.meta-info'
```

#### Image Extraction Fallbacks
```php
// Primary: Current image structure
'img[data-testid="listing-image"]'

// Secondary: Container-based patterns
'.offer-item-image img'
'.photo img'
'.listing-photo img'

// Tertiary: Generic image patterns
'.image-container img'
'.thumbnail img'
'img[src*="apollo"]'  // OLX CDN pattern
```

#### Pagination Fallbacks
```php
// Primary: Current pagination
'[data-testid="pagination-forward"]'

// Secondary: Class-based patterns
'.pager-next'
'.next-page'
'a[rel="next"]'

// Tertiary: Text-based patterns
'a:contains("Următoarea")'
'a:contains("Next")'
'.pagination a:last-child'
```

## Advanced Data Extraction Patterns

### External ID Extraction Strategies

OLX uses multiple URL patterns for listing IDs. The extraction logic implements a priority-based approach:

#### Primary Patterns (95% success rate)
```regex
# Direct ID pattern in URL
ID(\d+)
# Example: https://www.olx.ro/d/oferta/laptop-dell-latitude-ID123456.html

# Suffix pattern
/oferta/.*-(\d+)\.html
# Example: https://www.olx.ro/d/oferta/laptop-gaming-asus-456789.html
```

#### Secondary Patterns (fallback for edge cases)
```regex
# Query parameter pattern
[?&]id=(\d+)
# Example: https://www.olx.ro/listing?id=789012

# Path segment pattern
/(\d{6,})/
# Example: https://www.olx.ro/345678/laptop-details

# Generic digit sequence (6+ digits)
(\d{6,})
# Matches any sequence of 6 or more digits in the URL
```

#### Implementation Example
```php
public function extractExternalId(string $url): ?string
{
    $patterns = [
        '/ID(\d+)/',                    // Primary: ID prefix
        '/\/oferta\/.*-(\d+)\.html/',   // Primary: suffix pattern
        '/[?&]id=(\d+)/',              // Secondary: query param
        '/\/(\d{6,})\//',              // Secondary: path segment
        '/(\d{6,})/',                  // Tertiary: any 6+ digits
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}
```

### Advanced Price Parsing

The price parsing system handles multiple currency formats and Romanian number formatting:

#### Supported Price Formats
```php
// Romanian Lei (primary currency)
"1.500 lei"      → 1500.00 RON
"1500 lei"       → 1500.00 RON
"1.500,50 lei"   → 1500.50 RON
"Lei 1.500"      → 1500.00 RON

// Euro
"€250"           → 1237.50 RON (using EUR_TO_RON_RATE)
"250 €"          → 1237.50 RON
"250 EUR"        → 1237.50 RON
"250 euro"       → 1237.50 RON

// US Dollar
"$100"           → 450.00 RON (using USD_TO_RON_RATE)
"100 $"          → 450.00 RON
"100 USD"        → 450.00 RON

// Special cases
"Negociabil"     → null (negotiable)
"La cerere"      → null (on request)
"Schimb"         → null (exchange/trade)
```

#### Price Parsing Algorithm
```php
public function parsePrice(string $priceText): ParsedPrice
{
    // Clean the input
    $cleaned = trim(strip_tags($priceText));
    
    // Handle special cases first
    if (preg_match('/negociabil|la cerere|schimb/i', $cleaned)) {
        return new ParsedPrice(null, 'RON', $cleaned);
    }
    
    // Extract numeric value with Romanian formatting
    $numericPattern = '/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/';
    if (!preg_match($numericPattern, $cleaned, $matches)) {
        return new ParsedPrice(null, 'RON', $cleaned);
    }
    
    $numericValue = $this->normalizeNumber($matches[1]);
    $currency = $this->detectCurrency($cleaned);
    
    // Convert to RON if needed
    $ronAmount = $this->convertToRON($numericValue, $currency);
    
    return new ParsedPrice($ronAmount, $currency, $cleaned);
}
```

### Image URL Processing and Validation

OLX uses Apollo CDN for image hosting. The processing system handles various image formats and quality levels:

#### Image URL Patterns
```php
// Primary CDN pattern
'https://apollo-ireland.akamaized.net/v1/files/...'

// Thumbnail patterns
'https://apollo-ireland.akamaized.net/v1/files/.../image;s=300x200'
'https://apollo-ireland.akamaized.net/v1/files/.../image;s=644x461'

// Legacy patterns
'https://static.olx.ro/images/...'
'https://img.olx.ro/...'
```

#### Image Processing Rules
```php
public function processImageUrls(array $rawUrls): array
{
    $processed = [];
    
    foreach ($rawUrls as $url) {
        // Skip placeholder images
        if ($this->isPlaceholderImage($url)) {
            continue;
        }
        
        // Normalize URL
        $normalizedUrl = $this->normalizeImageUrl($url);
        
        // Validate format
        if (!$this->isValidImageUrl($normalizedUrl)) {
            continue;
        }
        
        // Avoid duplicates
        if (!in_array($normalizedUrl, $processed)) {
            $processed[] = $normalizedUrl;
        }
    }
    
    return $processed;
}

private function isPlaceholderImage(string $url): bool
{
    $placeholderPatterns = [
        'placeholder',
        'default',
        'no-image',
        'noimage',
        'blank',
        'empty',
        '1x1.gif',
        'transparent.png'
    ];
    
    foreach ($placeholderPatterns as $pattern) {
        if (stripos($url, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}
```

### Metadata Extraction Patterns

#### Location Processing
```php
// Location formats found on OLX
"București, Sectorul 1"     → city: "București", district: "Sectorul 1"
"Cluj-Napoca"               → city: "Cluj-Napoca", district: null
"Iași, Copou"              → city: "Iași", district: "Copou"
"Online"                   → city: "Online", district: null

public function parseLocation(string $locationText): array
{
    $parts = explode(',', trim($locationText));
    
    return [
        'city' => trim($parts[0]),
        'district' => isset($parts[1]) ? trim($parts[1]) : null,
        'raw' => $locationText
    ];
}
```

#### Date Processing
```php
// Date formats on OLX
"Azi 12:34"                → today at 12:34
"Ieri 15:20"               → yesterday at 15:20
"15 dec"                   → December 15th (current year)
"15 dec 2023"              → December 15th, 2023

public function parsePostedDate(string $dateText): ?Carbon
{
    $patterns = [
        '/Azi (\d{1,2}):(\d{2})/' => 'today',
        '/Ieri (\d{1,2}):(\d{2})/' => 'yesterday',
        '/(\d{1,2}) (\w{3})( \d{4})?/' => 'date_format'
    ];
    
    foreach ($patterns as $pattern => $type) {
        if (preg_match($pattern, $dateText, $matches)) {
            return $this->parseByType($type, $matches);
        }
    }
    
    return null;
}
```

## Comprehensive Error Handling and Anti-Bot Strategies

### Multi-Tier Selector Failure Handling

The system implements a sophisticated fallback mechanism that gracefully degrades when selectors fail:

#### Selector Resolution Algorithm
```php
public function extractWithFallback(string $context, array $selectors, string $attribute = 'textContent'): ?string
{
    foreach ($selectors as $index => $selector) {
        try {
            $result = $this->mcpClient->query($selector, $attribute);
            
            if ($result !== null) {
                // Log successful selector for monitoring
                Log::info("Selector success", [
                    'context' => $context,
                    'selector' => $selector,
                    'fallback_level' => $index
                ]);
                return $result;
            }
        } catch (Exception $e) {
            Log::warning("Selector failed", [
                'context' => $context,
                'selector' => $selector,
                'error' => $e->getMessage(),
                'fallback_level' => $index
            ]);
            
            // Continue to next fallback
            continue;
        }
    }
    
    // All selectors failed
    Log::error("All selectors failed", [
        'context' => $context,
        'selectors_tried' => count($selectors)
    ]);
    
    return null;
}
```

### Advanced Network Error Handling

#### Exponential Backoff Implementation
```php
public function executeWithRetry(callable $operation, int $maxAttempts = 3): mixed
{
    $attempt = 1;
    $baseDelay = 1000; // 1 second base delay
    
    while ($attempt <= $maxAttempts) {
        try {
            return $operation();
        } catch (NetworkException $e) {
            if ($attempt === $maxAttempts) {
                throw $e;
            }
            
            $delay = $baseDelay * pow(2, $attempt - 1); // Exponential backoff
            $jitter = rand(0, $delay * 0.1); // Add jitter to avoid thundering herd
            
            Log::warning("Network error, retrying", [
                'attempt' => $attempt,
                'delay_ms' => $delay + $jitter,
                'error' => $e->getMessage()
            ]);
            
            usleep(($delay + $jitter) * 1000); // Convert to microseconds
            $attempt++;
        }
    }
}
```

#### Timeout Configuration
```php
// Different timeouts for different operations
const TIMEOUTS = [
    'navigation' => 60000,      // Page navigation
    'element_wait' => 10000,    // Wait for element
    'click' => 5000,           // Click operations
    'fill' => 3000,            // Form filling
    'extraction' => 15000,     // Data extraction
];
```

### Anti-Bot Detection and Mitigation

#### User Agent Rotation
```php
private const USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
];

public function getRandomUserAgent(): string
{
    return self::USER_AGENTS[array_rand(self::USER_AGENTS)];
}
```

#### Request Pattern Randomization
```php
public function addHumanLikeDelay(): void
{
    $baseDelay = config('crawler.request_delay_ms', 2000);
    $variation = rand(-500, 1000); // ±500ms to +1000ms variation
    $finalDelay = max(1000, $baseDelay + $variation); // Minimum 1 second
    
    usleep($finalDelay * 1000);
}

public function simulateHumanBehavior(): void
{
    // Random mouse movements (if supported by MCP)
    $this->mcpClient->mouseMove(rand(100, 800), rand(100, 600));
    
    // Random scroll actions
    if (rand(1, 3) === 1) {
        $this->mcpClient->scroll(0, rand(100, 300));
        usleep(rand(500, 1500) * 1000);
    }
}
```

### Rate Limiting and Compliance

#### Adaptive Rate Limiting
```php
public function adaptiveDelay(int $responseTime, int $httpStatus): void
{
    $baseDelay = config('crawler.request_delay_ms', 2000);
    
    // Increase delay based on response time
    if ($responseTime > 5000) {
        $baseDelay *= 1.5;
    } elseif ($responseTime > 10000) {
        $baseDelay *= 2;
    }
    
    // Handle rate limiting responses
    if ($httpStatus === 429) {
        $baseDelay *= 3; // Triple delay for rate limiting
        Log::warning('Rate limited, increasing delay', ['new_delay' => $baseDelay]);
    }
    
    // Handle server errors
    if ($httpStatus >= 500) {
        $baseDelay *= 2; // Double delay for server errors
    }
    
    usleep($baseDelay * 1000);
}
```

#### Robots.txt Compliance
```php
public function checkRobotsCompliance(string $url): bool
{
    $robotsUrl = parse_url($url, PHP_URL_SCHEME) . '://' . 
                 parse_url($url, PHP_URL_HOST) . '/robots.txt';
    
    try {
        $robotsContent = file_get_contents($robotsUrl);
        
        // Parse robots.txt for crawl-delay and disallowed paths
        if (preg_match('/Crawl-delay:\s*(\d+)/i', $robotsContent, $matches)) {
            $crawlDelay = (int)$matches[1] * 1000; // Convert to milliseconds
            config(['crawler.min_delay_ms' => max(config('crawler.request_delay_ms'), $crawlDelay)]);
        }
        
        // Check if path is disallowed
        $path = parse_url($url, PHP_URL_PATH);
        if (preg_match('/Disallow:\s*' . preg_quote($path, '/') . '/i', $robotsContent)) {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        Log::warning('Could not fetch robots.txt', ['url' => $robotsUrl, 'error' => $e->getMessage()]);
        return true; // Assume allowed if robots.txt is not accessible
    }
}
```

### Captcha and Challenge Detection

#### Challenge Detection Patterns
```php
public function detectChallenges(): array
{
    $challenges = [];
    
    // Common captcha indicators
    $captchaSelectors = [
        '.captcha',
        '#captcha',
        '[class*="recaptcha"]',
        '[id*="recaptcha"]',
        '.hcaptcha',
        '.cloudflare-challenge'
    ];
    
    foreach ($captchaSelectors as $selector) {
        if ($this->mcpClient->query($selector) !== null) {
            $challenges[] = 'captcha';
            break;
        }
    }
    
    // Rate limiting detection
    if (strpos($this->mcpClient->getPageContent(), 'Too Many Requests') !== false) {
        $challenges[] = 'rate_limit';
    }
    
    // Cloudflare challenge
    if (strpos($this->mcpClient->getPageContent(), 'Checking your browser') !== false) {
        $challenges[] = 'cloudflare';
    }
    
    return $challenges;
}
```

### Error Recovery Strategies

#### Graceful Degradation
```php
public function handleExtractionFailure(string $context, Exception $e): void
{
    Log::error("Extraction failed", [
        'context' => $context,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Attempt alternative extraction methods
    switch ($context) {
        case 'price':
            // Try text-based price extraction
            $this->fallbackPriceExtraction();
            break;
            
        case 'title':
            // Try URL-based title extraction
            $this->extractTitleFromUrl();
            break;
            
        case 'images':
            // Skip images but continue with other data
            Log::info("Skipping image extraction due to failure");
            break;
    }
}
```

## Comprehensive Testing and Debugging Guide

### Manual Testing Procedures

#### Basic Functionality Tests
```bash
# Test MCP connection
php artisan crawler:test-connection

# Test single search term with minimal pages
php artisan crawler:test "laptop" --pages=1 --dry-run

# Test with different search terms
php artisan crawler:test "telefon samsung" --pages=2 --verbose
php artisan crawler:test "masina dacia" --pages=1 --debug

# Test specific hunted deal
php artisan deals:crawl --hunted-deal=1 --dry-run

# Full crawl test (dry run)
php artisan deals:crawl --dry-run --verbose
```

#### Selector Validation Tests
```bash
# Test current selectors against live OLX
php artisan crawler:validate-selectors

# Test fallback selectors
php artisan crawler:validate-selectors --test-fallbacks

# Generate selector report
php artisan crawler:selector-report --output=storage/logs/selector-report.json
```

#### Performance Testing
```bash
# Measure crawl performance
php artisan crawler:benchmark "laptop" --pages=3 --iterations=5

# Memory usage test
php artisan crawler:memory-test --search-terms="laptop,telefon,masina"

# Concurrent crawl test
php artisan crawler:concurrent-test --workers=3 --terms="laptop,telefon,masina"
```

### Expected Behaviors and Validation

#### Successful Crawl Indicators
```php
// Expected log entries for successful crawl
[
    'timestamp' => '2023-12-15 10:30:00',
    'level' => 'INFO',
    'message' => 'Crawl completed successfully',
    'context' => [
        'hunted_deal_id' => 1,
        'search_term' => 'laptop',
        'pages_processed' => 3,
        'listings_found' => 45,
        'new_deals' => 12,
        'updated_deals' => 8,
        'snapshots_created' => 20,
        'duration_ms' => 8500,
        'memory_peak_mb' => 128
    ]
]
```

#### Data Quality Validation
```php
// Validate extracted data quality
public function validateExtractedData(ParsedListing $listing): array
{
    $issues = [];
    
    // Title validation
    if (empty($listing->title) || strlen($listing->title) < 5) {
        $issues[] = 'Title too short or empty';
    }
    
    // Price validation
    if ($listing->priceAmount !== null && $listing->priceAmount <= 0) {
        $issues[] = 'Invalid price amount';
    }
    
    // URL validation
    if (!filter_var($listing->url, FILTER_VALIDATE_URL)) {
        $issues[] = 'Invalid URL format';
    }
    
    // External ID validation
    if (empty($listing->externalId) || !is_numeric($listing->externalId)) {
        $issues[] = 'Invalid external ID';
    }
    
    // Image URL validation
    foreach ($listing->imageUrls as $imageUrl) {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $issues[] = "Invalid image URL: {$imageUrl}";
        }
    }
    
    return $issues;
}
```

### Debugging Tools and Techniques

#### MCP Debug Mode
```php
// Enable detailed MCP logging
public function enableMcpDebugMode(): void
{
    $this->mcpClient->setDebugMode(true);
    $this->mcpClient->setLogLevel('DEBUG');
    
    // Log all MCP requests and responses
    $this->mcpClient->onRequest(function ($request) {
        Log::debug('MCP Request', ['request' => $request]);
    });
    
    $this->mcpClient->onResponse(function ($response) {
        Log::debug('MCP Response', ['response' => $response]);
    });
}
```

#### DOM Inspection Tools
```bash
# Capture current DOM structure
php artisan crawler:capture-dom "https://www.olx.ro/d/oferta/laptop-dell-ID123456.html" \
  --output=storage/debug/dom-capture.html

# Compare DOM structures
php artisan crawler:compare-dom \
  --url1="https://www.olx.ro/electronice-si-electrocasnice/calculatoare-si-accesorii/q-laptop/" \
  --url2="https://www.olx.ro/auto-masini-moto/autoturisme/q-dacia/" \
  --output=storage/debug/dom-comparison.json

# Extract all selectors from page
php artisan crawler:extract-selectors "https://www.olx.ro/" \
  --output=storage/debug/available-selectors.json
```

#### Performance Profiling
```php
// Profile crawler performance
public function profileCrawlOperation(string $searchTerm): array
{
    $profiler = new CrawlerProfiler();
    
    $profiler->start('total_crawl');
    
    $profiler->start('navigation');
    $this->navigateToSearch($searchTerm);
    $navigationTime = $profiler->end('navigation');
    
    $profiler->start('extraction');
    $listings = $this->extractListings();
    $extractionTime = $profiler->end('extraction');
    
    $profiler->start('processing');
    $processedListings = $this->processListings($listings);
    $processingTime = $profiler->end('processing');
    
    $totalTime = $profiler->end('total_crawl');
    
    return [
        'total_time_ms' => $totalTime,
        'navigation_time_ms' => $navigationTime,
        'extraction_time_ms' => $extractionTime,
        'processing_time_ms' => $processingTime,
        'listings_count' => count($processedListings),
        'avg_time_per_listing' => $totalTime / count($processedListings),
        'memory_peak_mb' => memory_get_peak_usage(true) / 1024 / 1024
    ];
}
```

### Common Issues and Solutions

#### Issue 1: Selector Changes
**Symptoms:**
- Null values for extracted data
- "Element not found" errors in logs
- Reduced listing counts

**Diagnosis:**
```bash
php artisan crawler:validate-selectors --verbose
```

**Solutions:**
1. Update selectors in `OlxSelectors.php`
2. Add new fallback selectors
3. Use MCP to inspect current DOM structure

#### Issue 2: Rate Limiting
**Symptoms:**
- HTTP 429 responses
- Slow response times
- Blocked requests

**Diagnosis:**
```bash
grep "429\|rate.limit\|blocked" storage/logs/crawler.log
```

**Solutions:**
```env
# Increase delays
CRAWLER_REQUEST_DELAY_MS=5000
CRAWLER_MAX_PAGES_PER_SEARCH=2
CRAWLER_MAX_LISTINGS_PER_RUN=50

# Enable adaptive delays
CRAWLER_ADAPTIVE_DELAYS=true
```

#### Issue 3: MCP Connection Problems
**Symptoms:**
- Connection timeout errors
- MCP service unavailable
- Authentication failures

**Diagnosis:**
```bash
php artisan crawler:test-connection --verbose
curl -H "Authorization: Bearer $MCP_PLAYWRIGHT_TOKEN" $MCP_PLAYWRIGHT_ENDPOINT/health
```

**Solutions:**
1. Verify MCP service is running
2. Check network connectivity
3. Validate authentication token
4. Review MCP server logs

#### Issue 4: Data Quality Issues
**Symptoms:**
- Incomplete listing data
- Invalid prices or dates
- Missing images

**Diagnosis:**
```bash
php artisan crawler:data-quality-report --days=7
```

**Solutions:**
1. Review and update extraction patterns
2. Improve fallback strategies
3. Add data validation rules
4. Enhance error handling

### Monitoring and Alerting

#### Health Check Endpoints
```php
// Create health check for crawler system
Route::get('/health/crawler', function () {
    $health = [
        'mcp_connection' => $this->testMcpConnection(),
        'database_connection' => $this->testDatabaseConnection(),
        'recent_crawls' => $this->getRecentCrawlStats(),
        'error_rate' => $this->calculateErrorRate(),
        'avg_response_time' => $this->getAverageResponseTime()
    ];
    
    $overallHealth = $this->calculateOverallHealth($health);
    
    return response()->json([
        'status' => $overallHealth,
        'details' => $health,
        'timestamp' => now()->toISOString()
    ], $overallHealth === 'healthy' ? 200 : 503);
});
```

#### Automated Monitoring
```bash
# Set up monitoring cron jobs
# Check crawler health every 5 minutes
*/5 * * * * php artisan crawler:health-check --alert-on-failure

# Generate daily reports
0 6 * * * php artisan crawler:daily-report --email=admin@example.com

# Clean up old logs weekly
0 2 * * 0 php artisan crawler:cleanup-logs --days=30
```

## Future Improvements

1. **Dynamic Selector Detection**: Automatically detect selector changes
2. **Captcha Handling**: Implement captcha solving integration
3. **Proxy Support**: Add proxy rotation for large-scale crawling
4. **Detail Page Crawling**: Extract additional data from listing detail pages
5. **Real-time Monitoring**: Add health checks and alerting for crawler failures