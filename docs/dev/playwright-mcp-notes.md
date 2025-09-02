# Playwright MCP Integration Notes

This document contains findings and implementation notes for the Playwright MCP integration with OLX Romania crawling.

## MCP Service Configuration

The crawler service connects to a Playwright MCP server that provides browser automation capabilities.

### Required Environment Variables

```env
MCP_PLAYWRIGHT_ENDPOINT=http://localhost:3000
MCP_PLAYWRIGHT_TOKEN=your_token_here
```

### MCP API Endpoints

The service uses the following MCP endpoints:

- `POST /playwright/navigate` - Navigate to a URL
- `POST /playwright/wait` - Wait for selector or timeout
- `POST /playwright/fill` - Fill input field
- `POST /playwright/click` - Click element
- `POST /playwright/query` - Query single element
- `POST /playwright/queryAll` - Query multiple elements
- `POST /playwright/queryInElement` - Query within specific element
- `POST /playwright/getAttribute` - Get element attribute

## OLX Romania DOM Structure

### Search Page Structure

The OLX search interface uses the following structure:

```html
<!-- Search Form -->
<input data-testid="search-input" placeholder="Caută în toate categoriile" />
<button data-testid="search-submit">Caută</button>

<!-- Results Container -->
<div data-testid="listing-grid">
  <!-- Individual Listings -->
  <div data-testid="l-card">
    <h6 data-testid="ad-title">
      <a href="/d/oferta/...">Listing Title</a>
    </h6>
    <span data-testid="ad-price">1.500 lei</span>
    <div data-testid="location-date">București • Azi 12:34</div>
    <img data-testid="listing-image" src="..." />
  </div>
</div>

<!-- Pagination -->
<a data-testid="pagination-forward">Următoarea</a>
```

### Fallback Selectors

When primary selectors fail, the following fallback strategies are used:

#### Search Interface
- Search Input: `input[name="q"], input[placeholder*="Caută"]`
- Search Button: `button[type="submit"], .search-submit`

#### Listing Elements
- Container: `.listing-grid, .ads-list-photo, .offers`
- Items: `.offer-wrapper, .listing-card, .ad-card`
- Title: `h3 a, h4 a, .offer-item-title, .title-cell a`
- Price: `.price, .offer-item-price, .price-label`
- Location: `.location-date, .bottom-cell, .offer-item-details`
- Images: `.offer-item-image img, .photo img, .listing-photo img`

#### Pagination
- Next Button: `.pager-next, .next-page, a[rel="next"]`

## Data Extraction Patterns

### External ID Extraction

OLX listing IDs are extracted from URLs using these patterns:

1. `ID(\d+)` - Direct ID pattern: `https://www.olx.ro/d/oferta/title-ID123456.html`
2. `/oferta/.*-(\d+)\.html` - Suffix pattern: `/oferta/something-123456.html`
3. `(\d{6,})` - Fallback: Any 6+ digit sequence

### Price Parsing

Prices are extracted and normalized:

- Raw text: "1.500 lei", "€250", "$100"
- Numeric extraction with currency detection
- Conversion to RON using configured rates

### Image URL Processing

Image URLs are processed with the following rules:

- Skip placeholder images containing "placeholder", "default", "no-image"
- Normalize relative URLs to absolute
- Validate URL format
- Remove duplicates

## Error Handling Strategies

### Selector Failures

When selectors fail:

1. Try primary selector
2. Try fallback selector(s)
3. Log warning with context
4. Continue with partial data

### Network Issues

For network problems:

1. Exponential backoff retry (3 attempts)
2. Configurable delays between requests
3. Timeout handling (30s default)
4. Graceful degradation

### Rate Limiting

To avoid being blocked:

1. Configurable request delays (2s default)
2. Maximum pages per search (3 default)
3. Maximum listings per run (100 default)
4. Respect robots.txt and terms of service

## Testing and Debugging

### Manual Testing

Use the test command to verify functionality:

```bash
php artisan crawler:test "laptop" --pages=1
php artisan crawler:test "telefon" --pages=2 --dry-run
```

### Common Issues

1. **Selector Changes**: OLX may update their DOM structure
   - Solution: Update selectors in `OlxSelectors.php`
   - Use MCP to inspect current DOM

2. **Rate Limiting**: Too many requests too quickly
   - Solution: Increase `CRAWLER_REQUEST_DELAY_MS`
   - Reduce `CRAWLER_MAX_PAGES_PER_SEARCH`

3. **MCP Connection**: Service unavailable
   - Solution: Check MCP endpoint and token
   - Verify Playwright service is running

### Logging

Crawler operations are logged to `storage/logs/crawler.log` with structured data:

```json
{
  "service": "OlxCrawlerService",
  "search_term": "laptop",
  "pages_processed": 2,
  "listings_found": 45,
  "duration_ms": 8500,
  "errors": []
}
```

## Future Improvements

1. **Dynamic Selector Detection**: Automatically detect selector changes
2. **Captcha Handling**: Implement captcha solving integration
3. **Proxy Support**: Add proxy rotation for large-scale crawling
4. **Detail Page Crawling**: Extract additional data from listing detail pages
5. **Real-time Monitoring**: Add health checks and alerting for crawler failures