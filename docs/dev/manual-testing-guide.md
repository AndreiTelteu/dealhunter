# Manual Verification Guide

This project does not include a testing framework or a `tests/` directory. Use these manual checks after deploying or changing the application.

## Prepare A Safe Environment

1. Use a non-production database for destructive checks.
2. Configure the application and run migrations:

```bash
php artisan migrate
```

3. Create a user and hunted deal through the web interface.
4. Confirm the configured components can be inspected:

```bash
php artisan system:health-check
php artisan about
```

## Crawler Checks

`crawler:test` and `deals:crawl` accept `--dry-run` to preview work without MCP requests or database changes:

```bash
php artisan crawler:test "laptop" --pages=1 --dry-run
php artisan deals:crawl --hunted-deal=1 --dry-run
```

Before making a live crawler request, set `CRAWLER_ENABLED=true`, acknowledge the applicable target-site terms with `CRAWLER_TERMS_ACKNOWLEDGED=true`, and ensure the current time is in `CRAWLER_ALLOWED_WINDOWS`. Then run one limited crawl:

```bash
php artisan crawler:test "laptop" --pages=1
php artisan deals:crawl --hunted-deal=1
```

Verify that the hunted deal's listings, prices, detail data, and snapshots appear in the web interface. Review `storage/logs/` if a crawl reports an error.

## AI Classification Checks

With valid AI provider credentials, verify the provider and sample classification:

```bash
php artisan ai:test-classification --test-connection
php artisan ai:test-classification --search-term="laptop" --title="Laptop Dell Latitude" --description="Laptop functional, stare buna"
```

Preview or apply reclassification only for records you intend to process:

```bash
php artisan deals:reclassify --hunted-deal=1 --limit=10 --dry-run
php artisan deals:reclassify --hunted-deal=1 --limit=10
```

## Web Checks

- Register and sign in through the browser; verify validation, logout, and session behavior.
- Create, edit, deactivate, and delete a hunted deal; verify the list updates correctly.
- Check the deals list, filters, pagination, detail page, snapshots, price history, and external listing links.
- Repeat the essential flows at mobile and desktop viewport widths.

## Production Checks

After `make production-up` or `make production-deploy`, verify the running containers and the HTTP health endpoint:

```bash
make health-check-production
curl http://localhost/health
```

Use `make production-logs` to inspect service output. Deploy TLS through a reverse proxy or load balancer; the supplied production container exposes HTTP on port 80.
