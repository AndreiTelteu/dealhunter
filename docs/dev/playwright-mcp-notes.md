# Playwright MCP Crawler Notes

`OlxCrawlerService` calls the configured REST Playwright MCP endpoint. It uses its `navigate`, `wait`, `fill`, `click`, `query`, `queryAll`, `queryInElement`, `queryAllInElement`, `getAttribute`, and `getElementAttribute` operations. The crawler sends CSS selectors to those operations; this application does not use XPath selectors.

## Runtime Controls

The crawler makes no MCP request unless `CRAWLER_ENABLED=true`. By default it also requires `CRAWLER_TERMS_ACKNOWLEDGED=true`; this is an operator acknowledgement that the target site's applicable terms and permissions were reviewed. The application does not fetch or parse `robots.txt`.

`CRAWLER_ALLOWED_WINDOWS` is an optional comma-separated list of `HH:MM-HH:MM` windows evaluated in `CRAWLER_TIMEZONE` (default `Europe/Bucharest`). An empty value permits crawling at any time. Windows are checked before every MCP request, so a crawl stops if a window closes while it is running.

Every MCP request is subject to both controls below:

- `RATE_LIMIT_REQUESTS_PER_MINUTE` is the sustained token refill rate.
- `RATE_LIMIT_BURST_LIMIT` is the maximum number of immediately available request tokens.
- `CRAWLER_REQUEST_DELAY_MS` is an additional minimum delay before each request.

`CRAWLER_MAX_PAGES_PER_SEARCH` and `CRAWLER_MAX_LISTINGS_PER_RUN` are hard caps. The listing cap is applied before detail-page enrichment.

## Navigation And Pagination

The crawler navigates to `https://www.olx.ro`, waits for the first available search-input selector, fills it, clicks the first available submit selector, then waits for a listing container. Primary selectors and their executable CSS fallback selector lists live in `app/Services/Crawlers/OlxSelectors.php`.

For each results page it queries listing cards using `[data-testid="l-card"]`, then the configured fallback list. Pagination resolves `[data-testid="pagination-forward"]` first and then its fallback list; the selected selector is also used for disabled-state inspection and clicking. No Artisan selector-validation, selector-report, DOM-capture, or benchmark commands exist in this application.

Use the existing test command for a live, explicitly enabled crawl:

```bash
php artisan crawler:test "laptop" --pages=1
php artisan deals:crawl --hunted-deal=1
```

Use `--dry-run` with either command to show intended work without requesting MCP.

## Listing Data Workflow

Result cards provide the URL, title, raw price, location/date text, images, and flags. After all capped results pages have been collected, each listing is opened independently for detail-page enrichment. The crawler attempts, with primary and fallback CSS selectors:

- description;
- seller name and seller profile URL; and
- posted date.

Failure to enrich one detail page is logged and leaves that listing's result-card data available for ingestion. The listing therefore does not prevent remaining listings or hunted deals from being processed. Posted dates are normalized to ISO 8601 where a supported Romanian relative or date format can be parsed; otherwise they remain absent rather than storing an invalid timestamp.

`PriceParserService` retains the source text in `price_raw`, converts EUR and USD using the configured `currency.rates`, and `OlxCrawlerService` persists the converted numeric amount as `price_amount` with `price_currency` fixed to `RON`.

## Updating Selectors

Inspect the target site with the configured Playwright MCP server, update primary and fallback CSS selectors together in `app/Services/Crawlers/OlxSelectors.php`, then run a one-page `crawler:test` crawl while crawling is enabled and within an allowed window. Keep fallbacks limited to CSS supported by the MCP server; do not add jQuery-only selectors such as `:contains()`.
