# OLX Deal Hunter

OLX Deal Hunter is a Laravel 12 application for tracking OLX Romania listings, their prices, snapshots, and optional AI classifications.

## Requirements

- PHP 8.2+; the production image uses PHP 8.2.
- Composer.
- Node.js 18+ and npm.
- PostgreSQL 15+.
- Docker Engine and Docker Compose for the supplied containers.

## Local Setup

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
npm run build
php artisan migrate
```

Start the local Laravel development server, queue worker, and scheduler in separate terminals as needed:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
```

## Docker Development

The Makefile wraps the existing development Docker Compose configuration:

```bash
make install
make dev
make logs
make shell
```

`make dev` starts the services, runs migrations, and runs the default database seeder. Use `make down` to stop the development containers.

## Frontend (Inertia + React)

The UI is a single-page React application rendered server-side through Inertia. The stack is:

- [Inertia.js](https://inertiajs.com/) v3 (`inertiajs/inertia-laravel` + `@inertiajs/react`) as the adapter between Laravel controllers and React.
- React 19 with TypeScript (strict mode).
- Vite 7 with `@vitejs/plugin-react` and `laravel-vite-plugin`.
- Tailwind CSS 3 and `@tailwindcss/forms`.
- `@fancyapps/ui` (Fancybox) for the deal media gallery.

### How it works

- The root template is `resources/views/app.blade.php`, which renders `@inertia` and loads the Vite entrypoints (`resources/css/app.css` and `resources/js/app.tsx`).
- `resources/js/app.tsx` calls `createInertiaApp` and resolves pages automatically via `import.meta.glob('./pages/**/*.tsx')`. A page named `HuntedDeals/Index` maps to `resources/js/pages/HuntedDeals/Index.tsx`.
- Controllers return `Inertia::render('Page/Name', [...])`; server-side data is shared through props (see `HandleInertiaRequests` and the `app/Http/Middleware` middleware stack).
- `resources/js/routes.ts` holds a static path map (no Ziggy). Parameterized URLs are built server-side with `route()` and passed through props.

### Running the frontend

During development, run Vite in a separate terminal:

```bash
npm run dev
```

The Vite dev server hot-reloads pages, components, and Tailwind classes. Build production assets with:

```bash
npm run build
```

`composer dev` starts the Laravel server, queue worker, and Vite dev server together in one command.

### Conventions

- Pages live in `resources/js/pages/`, components in `resources/js/components/`, layouts in `resources/js/layouts/`, and shared types in `resources/js/types/`.
- Use the `usePage()` / `useForm()` / `Link` helpers from `@inertiajs/react` for page data, forms, and navigation.
- Keep route paths centralized in `resources/js/routes.ts`; pass dynamic URLs from controllers instead of interpolating them client-side.
- Tailwind is configured in `tailwind.config.js`; global styles live in `resources/css/app.css`.

## Crawler Configuration

Configure the MCP endpoint and token in `.env`:

```env
MCP_PLAYWRIGHT_ENDPOINT=http://localhost:3000
MCP_PLAYWRIGHT_TOKEN=your_token_here
CRAWLER_ENABLED=false
CRAWLER_TERMS_ACKNOWLEDGED=false
CRAWLER_ALLOWED_WINDOWS=08:00-20:00
CRAWLER_TIMEZONE=Europe/Bucharest
```

Before live crawling, set `CRAWLER_ENABLED=true`, review the target site's applicable terms and permissions, set `CRAWLER_TERMS_ACKNOWLEDGED=true`, and ensure the current time is inside an allowed window. `CRAWLER_ALLOWED_WINDOWS` is optional; an empty value allows any time.

Preview crawler work without MCP requests or database changes:

```bash
php artisan crawler:test "laptop" --pages=1 --dry-run
php artisan deals:crawl --hunted-deal=1 --dry-run
```

Run a live crawl only after enabling it explicitly:

```bash
php artisan crawler:test "laptop" --pages=1
php artisan deals:crawl --hunted-deal=1
```

`deals:crawl` supports `--hunted-deal` and `--max-deals`; `crawler:test` supports `--pages` and `--dry-run`.

## AI Classification

Set `AI_CLASSIFICATION_ENABLED=true`, `AI_PROVIDER`, `AI_MODEL`, and `AI_API_KEY` to enable classification. Verify configuration with:

```bash
php artisan ai:test-classification --test-connection
php artisan ai:test-classification --search-term="laptop" --title="Laptop Dell Latitude" --description="Laptop functional, stare buna"
php artisan deals:reclassify --hunted-deal=1 --limit=10 --dry-run
```

## Production Deployment

1. Create the production environment file:

```bash
cp .env.production.example .env.production
```

2. Set at least `APP_KEY`, `APP_URL`, `DB_PASSWORD`, and `REDIS_PASSWORD` in `.env.production`.
3. Build and start the production stack:

```bash
make production-build
make production-up
```

`make production-up` starts the stack, runs migrations and `ProductionSeeder`, then caches configuration, routes, and views. For a full rebuild and restart, use `make production-deploy`.

The supplied production container exposes HTTP on port 80. Terminate TLS at a reverse proxy or load balancer and set `APP_URL` to the public HTTPS URL. This repository does not include a self-signed certificate generator.

Useful production commands:

```bash
make production-logs
make production-shell
make health-check-production
make backup-production
make logs-clear-production
```

For detailed deployment notes, see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). For manual verification steps, see [docs/dev/manual-testing-guide.md](docs/dev/manual-testing-guide.md).
