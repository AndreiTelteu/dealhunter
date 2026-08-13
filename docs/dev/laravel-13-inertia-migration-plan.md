# Laravel 13 + Inertia migration plan — AI agent execution prompt

**Branch:** `laravel-13-inertia`
**Status:** execution plan. This document is the single source of truth and the working prompt for a sequence of AI sub-agents. No agent may deviate from it without recording the deviation in the Agent handoff log.

---

## How agents must work (read this first, every agent, every time)

You are one agent in a relay. Each agent executes exactly **one phase** (or one clearly bounded task group inside a phase), then stops. Rules:

1. **Read this entire file before doing anything.** Then read the Agent handoff log at the bottom for context left by previous agents.
2. **Work only on the `laravel-13-inertia` branch.** Confirm with `git branch --show-current` and confirm a clean worktree (`git status`) before starting. If the worktree is dirty with changes you did not make, stop and record the problem in the handoff log instead of guessing.
3. **Claim your work:** find the first unchecked phase in the Progress tracker. That is your phase. Do not skip ahead. Do not start a phase whose predecessor has unchecked verification boxes.
4. **Verify before you mark done.** A checkbox may only be ticked when the described work is implemented **and** the phase verification gate (defined per phase) is green. Never tick a box based on intent.
5. **Update this file as you go:** tick your checkboxes (`- [ ]` → `- [x]`), and append an entry to the Agent handoff log with: date, phase, what you did, exact resolved versions where relevant, anything the next agent must know, and any deviation + reason.
6. **Commit your phase as one or more green commits.** Every commit must pass the verification gate. Commit message prefix: `L13/Inertia Phase N: ...`. Include this plan file's checkbox updates in the same commit.
7. **Never do destructive database operations.** No `migrate:fresh`, no `db:wipe`, no dropping columns. Tests use the project's isolated in-memory SQLite guard. The app runs under **Laravel Herd** (`https://dealhunter.test`) — do not start your own HTTP server for it.
8. **If blocked**, do not force a broken state. Leave the box unchecked, write exactly what is blocking in the handoff log, and stop.
9. **Do not change dependencies beyond what this plan authorizes** for your phase.

### Standard verification gate (run after every meaningful change, and before every commit)

```bash
vendor/bin/pint --dirty
vendor/bin/phpunit
npm run build
php artisan route:list > /dev/null
php artisan migrate:status --no-interaction
```

All five must succeed. Phases add extra gate items on top of this.

---

## Progress tracker

Tick a box only when done **and verified**. One agent per phase unless the handoff log says a phase was split.

### Phase 0 — safety baseline and audit
- [x] 0.1 Pre-upgrade evidence captured (versions, test run, build, route list) and recorded in handoff log
- [x] 0.2 Laravel 13 upgrade guide re-read at execution time; deltas vs. this plan noted in handoff log
- [x] 0.3 Repository audited for every Laravel 13 breaking-change API (checklist below); findings recorded
- [x] 0.4 Inventory of every view-returning code path recorded (`view(...)`, `Route::view`, Blade templates)
- [x] 0.5 Session/cache/Redis prefix compatibility decision made and recorded

### Phase 1 — Laravel 13 upgrade only (no Inertia)
- [x] 1.1 Composer constraints updated to Laravel 13 targets; dependencies resolved; lock diff reviewed
- [x] 1.2 All applicable upgrade-guide code/config/middleware changes applied
- [x] 1.3 Breeze/dev-tooling compatibility resolved (kept, upgraded, or removed — decision logged)
- [x] 1.4 Full verification gate green on Laravel 13, PHP suite passing with zero skips introduced
- [x] 1.5 Isolated green commit: framework upgrade only

### Phase 2 — install and wire Inertia React
- [x] 2.1 Backend: `inertiajs/inertia-laravel` installed; `HandleInertiaRequests` middleware created and registered in `bootstrap/app.php`; root view `resources/views/app.blade.php` created
- [x] 2.2 Frontend: `@inertiajs/react`, `react`, `react-dom`, `@vitejs/plugin-react`, TypeScript toolchain installed; exact versions logged
- [x] 2.3 Tailwind toolchain normalized (single major version — see Phase 2 details); build green
- [x] 2.4 `resources/js/app.tsx` with `createInertiaApp`, page resolution, progress indicator; `vite.config.js` updated; `tsconfig.json` added
- [x] 2.5 Route-name strategy implemented (see Phase 2 decision D2)
- [x] 2.6 Shared props (auth user + admin flag, flash, app name) implemented and tested
- [x] 2.7 Dashboard converted end-to-end as the proof page (`DashboardController` created, closure removed from `routes/web.php`); Inertia feature test asserting component + props passes
- [x] 2.8 Verification gate green; commit

### Phase 3 — shared frontend primitives
- [x] 3.1 `Layouts/AppLayout`, `Layouts/GuestLayout`, navigation + responsive/mobile navigation
- [x] 3.2 Flash/status message component wired to shared props
- [x] 3.3 Form primitives: input, label, error, primary/secondary/danger buttons, submit-state, modal/confirm dialog, empty state
- [x] 3.4 `Components/Pagination` consuming Laravel paginator link metadata via Inertia visits
- [x] 3.5 `Components/Deals/DealMediaGallery` — local media only, Fancybox bind on mount / destroy on unmount, first-image listing mode
- [x] 3.6 `Components/Deals/FavoriteButton` — Inertia POST, preserve-state
- [x] 3.7 Deal card/list-row/metadata primitives; stat card; spectrum line; phrase-tag input; admin navigation (authorization-aware)
- [x] 3.8 Shared TypeScript types for page props and domain DTOs (`resources/js/types/`)
- [x] 3.9 Verification gate green; commit

### Phase 4 — migrate authenticated product surfaces
- [x] 4.1 Dashboard finalized on shared primitives (recent deals + local media gallery)
- [x] 4.2 Deals index: filters, sorting, pagination, gallery listing mode + feature tests
- [x] 4.3 Deals show: deal, snapshots, local media gallery + feature tests
- [x] 4.4 Favorites index + toggle flow + feature tests
- [x] 4.5 Hunted deals index (filters, statistics) + feature tests
- [x] 4.6 Hunted deals create + edit forms (validation error bags) + feature tests
- [x] 4.7 Hunted deals show (charts, price history, actions) + feature tests
- [ ] 4.8 AI classification page (test/connection flows) + feature tests
- [ ] 4.9 Profile edit (profile/password/delete forms, named error bags) + feature tests
- [ ] 4.10 Replaced Blade templates for the above deleted after reference search; verification gate green; commit(s)

### Phase 5 — migrate admin and auth surfaces
- [ ] 5.1 Admin dashboard + feature tests
- [ ] 5.2 Admin crawl logs list + detail + feature tests
- [ ] 5.3 Admin system health + admin actions (POST redirects preserved) + feature tests
- [ ] 5.4 Admin configuration forms (no secret leakage in props — test it) + feature tests
- [ ] 5.5 Auth: login, register + feature tests
- [ ] 5.6 Auth: forgot/reset password (signed URLs, throttle preserved) + feature tests
- [ ] 5.7 Auth: confirm password, verify email (signed routes, resend throttle) + feature tests
- [ ] 5.8 Public welcome page + guest/auth redirect behavior tests
- [ ] 5.9 Access-matrix tests pass: guest / auth / verified / admin against protected URLs
- [ ] 5.10 Replaced Blade templates deleted; verification gate green; commit(s)

### Phase 6 — remove Blade UI, cleanup, stabilize
- [ ] 6.1 Repository-wide scan proves no user-facing `view(...)` / `Route::view` remains (only Inertia root + mail/error templates if any)
- [ ] 6.2 Obsolete Blade layouts/components/pages/vendor pagination views deleted
- [ ] 6.3 Alpine removed; Axios/`bootstrap.js` reviewed — removed or its retention justified in handoff log
- [ ] 6.4 `package.json` / lock files clean; no unused frontend deps; `npm run build` green
- [ ] 6.5 Full verification gate + full PHPUnit green; commit

### Phase 7 — browser verification and cutover readiness
- [ ] 7.1 Every page verified in a real browser via Herd at desktop + mobile widths (checklist in Phase 7); results logged
- [ ] 7.2 Browser back/forward, direct URL loads, filters, pagination, flash, redirects verified
- [ ] 7.3 Fancybox gallery verified: same-deal local images only, no duplicate bindings after navigation
- [ ] 7.4 No console errors, hydration warnings, or failed asset requests on any page
- [ ] 7.5 Queue worker restart requirement + deploy steps documented in handoff log
- [ ] 7.6 Completion criteria (bottom of file) all confirmed; final summary written in handoff log

---

## Objective

Upgrade DealHunter from Laravel 12 to Laravel 13, then replace the complete Blade-rendered web UI with an **Inertia.js v3 + React 19 + TypeScript** single-page frontend while preserving the existing routes, authorization, behavior, the polished "instrument panel" visual language, server-side domain logic, queue processing, crawler behavior, and local-only deal-media galleries.

This is a delivery-model rewrite, **not a visual redesign**. After cutover, no user-facing Blade page remains except the Inertia root document.

## Verified baseline (at plan time — re-verify in Phase 0)

- Branch created from `main` at `c73c6c4`.
- Laravel `12.26.4`, PHP CLI `8.5.0` (Composer constraint `^8.2`).
- Frontend: Blade + Vite 7 + Tailwind **3** (v3 `@tailwind` directives in `resources/css/app.css`) + Alpine 3 + Fancybox 6. Note: `@tailwindcss/vite ^4.0.0` is present in `package.json` but **unused** by `vite.config.js` — this inconsistency is resolved in Phase 2.3.
- No Inertia/React packages installed.
- Dashboard is currently a route closure in `routes/web.php` (moved to a controller in Phase 2.7).
- User-facing screens: welcome; dashboard; deal index/detail; favorites; hunted-deal index/create/edit/detail; AI classification; profile edit (profile/password/delete partial forms); auth (login, register, forgot/reset password, confirm password, verify email); admin (dashboard, crawl logs list/detail, system health, configuration).
- Non-UI routes stay server responses: `/health`, `/ping`.

## Version targets and authoritative sources

### Laravel 13

Official upgrade guide: <https://laravel.com/docs/13.x/upgrade>. **Phase 0 must re-read it at execution time**; the checklist below reflects the guide as of 2026-08-12.

| Package | Current constraint | Laravel 13 target |
|---|---:|---:|
| `laravel/framework` | `^12.0` | `^13.0` |
| `laravel/boost` | `^1.0` | `^2.0` |
| `laravel/tinker` | `^2.10.1` | `^3.0` |
| `phpunit/phpunit` | `^11.5` | `^12.0` |

Remaining Composer packages (`prism-php/prism`, `laravel/breeze`, Pint, Sail, Pail, IDE helper) must be resolved through Composer's solver — do not hand-pin unverified versions. Special case: **`laravel/breeze`** exists in this project only as auth scaffolding already published into the codebase; if it blocks Laravel 13 resolution and no compatible release exists, remove it from `require-dev` (the published controllers/routes stay) and log the decision.

Laravel 13 breaking-change audit checklist (Phase 0.3 — check each against actual app code):

1. `PreventRequestForgery` replaces direct uses of `VerifyCsrfToken` / `ValidateCsrfToken` (check `bootstrap/app.php`, tests).
2. Default cache/Redis/session prefixes change; per decision 0.5, explicitly set `CACHE_PREFIX`, `REDIS_PREFIX`, `SESSION_COOKIE` if production continuity is required.
3. Cache `serializable_classes` hardening: ensure cached values are arrays/scalars or allow-list intentional classes.
4. Session serialization may move to JSON in the Laravel 13 skeleton. Decide: keep PHP serialization (seamless sessions) or JSON (invalidates sessions). Record in 0.5.
5. Every `upsert()` call must have a non-empty `uniqueBy`.
6. Renamed queue event fields (`JobAttempted::$exception`, `QueueBusy::$connectionName`) in listeners/monitoring code.
7. CSRF exceptions/middleware usage in tests and routes.
8. Custom cache stores, queue drivers, dispatcher/response contracts, model boot logic, custom manager `extend()` callbacks, Bootstrap-3 pagination view references.
9. After dependency resolution, fix actual incompatibilities — do not blindly copy skeleton config files over customized ones.

### Inertia / React (verified against npm + Packagist 2026-08-12 — re-verify immediately before install in Phase 2)

| Package | Target |
|---|---:|
| `inertiajs/inertia-laravel` | `^3.3` (latest `3.3.1`) — verify Laravel 13 support in its composer constraints before install |
| `@inertiajs/react` | `^3.6` (latest `3.6.1`) |
| `react` / `react-dom` | `^19` (peer requirement of the adapter) |
| `@vitejs/plugin-react` | latest compatible with Vite 7 — **required** for JSX transform and fast refresh |
| `typescript`, `@types/react`, `@types/react-dom` | latest stable |

Never mix Inertia adapter/server major versions.

## Architecture after migration

### Backend responsibility

Laravel remains the system of record; all domain behavior stays server-side: routes and named routes; auth, email verification, password flows, CSRF, policies, middleware; validation (extract Form Requests where inline validation exists, following sibling conventions); crawler dispatch, AI classification, queue work, health checks, configuration writes, favorite toggles, local media persistence; filtering, sorting, pagination, eager loading, page props; flash messages and validation error responses.

Controllers change only their HTML response boundary from `view(...)` to `Inertia::render(...)`. Mutations continue to return redirects/validation responses so Inertia preserves Laravel semantics.

### Frontend responsibility

- `resources/js/Pages/**` — React pages (TypeScript, `.tsx`).
- `resources/js/Components/**` — reusable components.
- `resources/js/Layouts/**` — layouts.
- `resources/js/types/**` — shared prop/DTO types.
- Styling stays in `resources/css/app.css` + existing Tailwind tokens/utilities. Do not redesign.
- `resources/views/app.blade.php` is the single Inertia root document (`@inertia`, `@inertiaHead`, `@vite`).

Shared props via `HandleInertiaRequests`: authenticated user identity + capabilities (admin flag), flash messages, app metadata needed by navigation. Keep shared props minimal.

Use Inertia's React APIs (`Link`, `router`, `useForm`, `usePage`, partial reloads, preserve-state/scroll). Do not duplicate API endpoints; do not make Axios the default navigation/mutation mechanism. Keep Axios only where a real non-navigation async request is justified (review each use in Phase 6.3).

### Decisions the implementing agents must honor

- **D1 (Phase 0.5):** session/cache prefix continuity — decide and record before Phase 1.
- **D2 (Phase 2.5):** route names in JS. Default decision: **do not add Ziggy**; controllers pass fully-built URLs (via `route()`) in props, and navigation targets are defined in a single typed `resources/js/routes.ts` map of literal paths for static routes. If an agent finds this materially insufficient (many parameterized client-side URL constructions), it may propose Ziggy in the handoff log and the next agent may install it — log the change.
- **D3 (Phase 2.3):** Tailwind. The project mixes Tailwind 3 (installed, used, v3 directives) with an unused `@tailwindcss/vite` v4 package. Resolution: **stay on Tailwind 3 for this migration** — remove `@tailwindcss/vite` from `package.json`, keep the existing postcss pipeline. Migrating to Tailwind 4 is explicitly out of scope (a third concurrent migration multiplies risk).

### URL and page data rules

- Keep current web URLs and route names stable.
- Preserve server pagination as Laravel paginator props; the React `Pagination` component consumes paginator `links` metadata and issues Inertia visits.
- Serialize only what each page needs — explicit controller mapping, `->through()` on paginators, or API Resources. Never pass raw models blindly.
- Deal media comes exclusively from validated, locally persisted `DealMedia` rows. Gallery props must never fall back to remote OLX URLs. Add a feature test asserting this.
- Never expose secrets, crawler credentials, unredacted configuration, stack traces, or raw service exceptions in Inertia props. Add a feature test for the admin configuration page props.

## Page migration map

| Existing Blade surface | Target Inertia page/component | Backend change |
|---|---|---|
| `welcome.blade.php` | `Pages/Welcome` | root route returns `Inertia::render` for guests; authenticated redirect stays |
| `dashboard.blade.php` | `Pages/Dashboard` | new `DashboardController` (closure removed from `routes/web.php`); retain counts, recent deals, eager-loaded media |
| `deals/index.blade.php` | `Pages/Deals/Index` | `DealController@index` returns paginator/filter props |
| `deals/show.blade.php` | `Pages/Deals/Show` | `DealController@show` returns deal, snapshots, local media props |
| `favorites/index.blade.php` | `Pages/Favorites/Index` | `FavoriteController@index` Inertia page; toggle stays POST redirect |
| `hunted-deals/*` | `Pages/HuntedDeals/{Index,Create,Edit,Show}` | resource controller conversion; preserve filters/statistics/chart data/mutations |
| `ai-classification/index` | `Pages/AiClassification/Index` | keep server routes; Inertia forms for test actions |
| `profile/edit` + partials | `Pages/Profile/Edit` + form components | preserve PATCH/DELETE actions and **named error bags** (`updatePassword`, `userDeletion`) |
| auth screens | `Pages/Auth/{Login,Register,ForgotPassword,ResetPassword,ConfirmPassword,VerifyEmail}` | convert Breeze controllers to `Inertia::render`; preserve guest/auth middleware, signed URLs, throttles, intended redirects |
| admin screens | `Pages/Admin/{Dashboard,CrawlLogs,CrawlLogDetail,SystemHealth,Configuration}` | convert `AdminController` GET actions; operations stay protected POST redirects |
| `layouts/app`, `layouts/guest`, `layouts/navigation` | `Layouts/AppLayout`, `Layouts/GuestLayout`, `Components/Navigation` | shared-prop-driven nav/auth state |
| `components/deal-media-gallery` | `Components/Deals/DealMediaGallery` | local `media` prop only; Fancybox bound in React lifecycle, destroyed on unmount |
| `components/favorite-button` | `Components/Deals/FavoriteButton` | Inertia POST, preserve-state/partial reload |
| remaining Blade components (buttons, inputs, modal, dropdown, stat-card, spectrum-line, phrase-tag-input, nav links, logo, auth-session-status) | equivalents under `Components/**` | Phase 3 |
| `resources/views/vendor/pagination/*` | `Components/Pagination` | Laravel paginator link metadata |

Phase 0.4's inventory is authoritative: every `view(...)`, `Route::view(...)`, Blade include/component and user-facing template found there must be accounted for before Phase 6 can complete.

## Per-page conversion procedure (Phases 4–5, repeat for every page)

1. Convert the controller GET response to `Inertia::render('Namespace/Page', $props)` with explicit prop mapping (eager-load `huntedDeal`, `latestSnapshot`, `media` where deal collections are involved — no N+1).
2. Define/extend the TypeScript prop types.
3. Implement the React page using Phase 3 primitives; replace Blade interactions with `Link` / `useForm` / `router`.
4. Write/update feature tests using `AssertableInertia` (`$response->assertInertia(fn (Assert $page) => $page->component('Deals/Index')->has('deals.data', ...))`): component name, key props, auth boundaries, validation error bags, redirects/flash.
5. Run the verification gate.
6. Delete the replaced Blade template(s) only after a repository-wide reference search proves they are unused.
7. Tick the checkbox; commit.

## Controller and route conversion rules

- Keep form mutations as Laravel actions with validation + redirects; use flash messages and Inertia error bags — never client-trusted mutation logic.
- Preserve named routes, HTTP methods, middleware, route model binding, soft-delete behavior, signed URLs, throttles, authorization checks.
- Use explicit DTO/resource mapping for sensitive or large props; adopt lazy/deferred props only after baseline parity is established.
- Never move crawler/AI/queue behavior into the browser.

## Test and verification plan

### Automated (the standard gate, plus)

- Feature tests must cover: Inertia component names + minimal props for every page; guest/auth/verified/admin access matrix; validation error bags and redirect/flash behavior; deal filter + paginator state; favorite toggling; hunted-deal CRUD + price snapshot behavior; admin protected actions; local-only media props (no remote OLX fallback); no-secret-leak on admin configuration props; every Laravel 13 breaking-change path found in Phase 0.3.
- Tests run on the project's isolated in-memory SQLite guard. Never run destructive commands against the Herd/live database. Only additive migrations, applied to production only with explicit approval.

### Browser verification (Phase 7, via Herd at `https://dealhunter.test`)

For each migrated page, at desktop and mobile widths:

- no server exception, hydration failure, console error, or failed asset request;
- navigation works through both Inertia visits and direct URL loads;
- forms show server validation state and preserve expected input;
- Fancybox opens a same-deal local gallery, thumbnails/nav work, unmount/navigation leaves no duplicate bindings;
- responsive navigation and the instrument-panel visual design remain intact (compare against pre-migration screenshots captured in Phase 0.1);
- browser back/forward, filters, pagination, flash messages, redirects behave correctly.

### Production cutover checks (Phase 7.5–7.6, document — do not deploy without approval)

1. Test the migration path on a production-shaped backup/staging database.
2. Build production assets.
3. Apply only approved additive migrations.
4. Clear/rebuild config, route, view caches per deployment procedure.
5. Restart/reload queue workers so they run Laravel 13 code.
6. Smoke-test: health endpoints, login, dashboard, deal gallery, an admin action, a queued media/crawler flow.
7. Monitor logs for request-forgery, session, queue serialization, and asset-manifest failures.

## Risks and mitigations

| Risk | Mitigation |
|---|---|
| Framework and frontend migrations obscure each other's regressions | Phases 1 and 2+ are independently green commits |
| Laravel 13 session/cache default changes disrupt active users/jobs | Decision D1 made in Phase 0.5 before any upgrade |
| React migration unintentionally redesigns the UI | Carry existing tokens/CSS; build shared primitives first; compare against Phase 0 screenshots |
| Loss of local-media guarantees | Backend media validation untouched; feature test asserts no remote URLs in props |
| Heavy payloads / N+1 | Explicit props, eager loading, feature tests, query inspection |
| Auth flows subtly broken | Dedicated Phase 5 with guest/auth/signed/throttle coverage |
| Fancybox vs. React lifecycle | Single gallery component; bind on mount, destroy on unmount |
| Tailwind 3/4 mix | Decision D3: stay on Tailwind 3, remove unused v4 vite plugin |
| Package versions drift before implementation | Re-verify npm/Packagist/upgrade guide immediately before each install |
| Breeze incompatible with Laravel 13 | Scaffolding already published; drop the dev dependency if unresolvable (log it) |

## Completion criteria

The migration is complete only when all of the following are true:

1. Composer resolves and runs Laravel 13 with all upgrade-guide impacts addressed.
2. Inertia Laravel + React adapter are on compatible Inertia v3 versions verified at install time.
3. Every user-facing page is an Inertia React page; no user-facing Blade page/layout/component remains except the Inertia root document.
4. Every existing route, authorization boundary, form flow, filter, pagination behavior, admin operation, and local deal-media guarantee has parity or an explicitly logged, approved improvement.
5. Full PHPUnit, Pint, production Vite build, route checks, and browser verification pass.
6. The deployment path is additive and verified without destructive database commands.
7. The handoff log documents exact resolved versions, Laravel 13 compatibility decisions, removed Blade files, and test/browser evidence.
8. Every checkbox in the Progress tracker is ticked.

---

## Agent handoff log

Append entries below. Format:

```
### YYYY-MM-DD — Phase N — <agent/task name>
- Done: ...
- Versions resolved: ...
- Decisions/deviations: ...
- Next agent must know: ...
```

### 2026-08-12 — Phase 0 — baseline/audit agent (pi)
- Done: Full Phase 0 safety baseline and audit. Branch confirmed `laravel-13-inertia` at `c73c6c4`; worktree clean except the expected untracked plan file (claimed in the Phase 0 commit).
- Baseline evidence (0.1):
  - Versions: Laravel Framework 12.26.4, PHP CLI 8.5.0 (constraint `^8.2`), Composer 2.8.12, Node v24.14.1, npm 11.9.0, Vite 7.1.4, Tailwind 3 (`@tailwind` directives confirmed in `resources/css/app.css`; `@tailwindcss/vite ^4.0.0` present but NOT referenced by `vite.config.js` — matches plan), Alpine 3 (`resources/js/app.js`), Fancybox `@fancyapps/ui ^6.1.14`. Key packages: boost 1.0.20, breeze 2.3.8, tinker 2.10.1, phpunit 11.5.56, prism-php/prism 0.87.0, laravel/mcp 0.1.1, serializable-closure 2.0.4, carbon 3.10.2, sail 1.45.0, pint 1.24.0.
  - Tests: `vendor/bin/phpunit` → OK, 47 tests / 114 assertions, 0 failures, 0 errors, 0 skips, 2 deprecations (pre-existing baseline). All tests use in-memory SQLite via `phpunit.xml`.
  - Build: `npm run build` green (92 modules; app.js 196.84 kB, CSS 33.30 + 51.04 kB). Browserslist data 12 months old — cosmetic warning only; optionally `npx update-browserslist-db@latest` in a later phase.
  - `vendor/bin/pint --dirty` PASS; `php artisan route:list` green — 46 routes (all names captured; dashboard is a closure as planned; `/health`, `/ping`, `/up` non-UI).
  - Known baseline noise: PHP 8.5 deprecation `PDO::MYSQL_ATTR_SSL_CA` from Laravel 12 `config/database.php` in vendor — expected to disappear on Laravel 13.
- Deviations (0.1/0.2 environment limits, recorded per rule 8):
  - `php artisan migrate:status` cannot run against the app DB here: `.env` `DB_HOST=lerd-mysql` (and `REDIS_HOST=lerd-redis`) do not resolve in this CLI environment (Docker-only hostnames). A read-only fallback `migrate:status` against a scratch SQLite file was attempted (no destructive ops) and the command runs correctly; the authoritative migration proof for this branch is that all 47 tests apply every migration against in-memory SQLite and pass. Herd hosts the app (`https://dealhunter.test`) but that host is also not resolvable from this CLI environment.
  - Screenshots were NOT feasible: `dealhunter.test` is unreachable from this environment and no reachable browser target exists for the app. Pre-migration screenshots must be captured by a later agent (before Phase 4 starts converting pages) from an environment where Herd resolves, or accepted as deferred evidence — Phase 7 agent should treat this as an open item.
- Upgrade guide re-read (0.2): live fetch of `https://laravel.com/docs/13.x/upgrade` on 2026-08-12. Full section list and deltas vs. this plan's checklist:
  - Covered by plan checklist and confirmed: PreventRequestForgery rename (High); cache/Redis prefix + session cookie fallback change (Low); cache `serializable_classes` (Medium); session `serialization` = json in new skeleton (Low); upsert non-empty `uniqueBy` on MySQL/MariaDB (Medium); JobAttempted `$exceptionOccurred` → `$exception` and QueueBusy `$connection` → `$connectionName` (Low); Manager `extend()` callback binding (Low); model boot nested instantiation (Very Low); Bootstrap-3 pagination view names (Low); custom cache Store `touch()` / Dispatcher `dispatchAfterResponse` / ResponseFactory `eventStream` / MustVerifyEmail `markEmailAsUnverified` / Queue contract size methods (Very Low).
  - NOT in plan checklist (new deltas Phase 1 must handle): (a) `Container::call` now honors nullable class defaults → null; (b) polymorphic pivot table names now pluralized; (c) eager-loaded relations restored after collection serialization (queued jobs); (d) HTTP client `throw`/`throwIf` signatures; (e) default password-reset mail subject → "Reset your password"; (f) queued notifications respect `#[DeleteWhenMissingModels]`; (g) MySQL `DELETE ... JOIN` now compiles `ORDER BY`/`LIMIT`; (h) domain route registration precedence; (i) `withScheduling` registration deferred; (j) Str factories reset between tests; (k) `Js::from` now `JSON_UNESCAPED_UNICODE`; (l) Symfony php85 polyfill defines `array_first`/`array_last` globals on PHP < 8.5 (we run 8.5, but avoid legacy helper conflicts); (m) `pestphp/pest ^4.0` target listed — NOT applicable (project uses PHPUnit); (n) Laravel installer update — informational only.
  - Packagist spot-check: `laravel/framework` v13 line exists and is active (latest observed `v13.25.0`). Phase 1 must re-verify at install time per plan.
- Audit results (0.3), item by item against code at `c73c6c4`:
  1. CSRF: zero references to `VerifyCsrfToken`/`ValidateCsrfToken`/`PreventRequestForgery` in `app/`, `routes/`, `tests/`, `bootstrap/app.php` (only generated `_ide_helper.php`/`.phpstorm.meta.php`). No `withoutMiddleware` in tests. No impact.
  2. Prefixes/cookie: `config/cache.php` and `config/database.php` already define hyphenated defaults and `config/session.php` cookie already uses `Str::slug(APP_NAME).'-session'` — app-level values, so the framework-fallback change does not apply. Runtime values captured via tinker: cache prefix `olx-deal-hunter-cache-`, Redis prefix `olx-deal-hunter-database-`, session cookie `olx-deal-hunter-session` (`.env` sets none of `CACHE_PREFIX`/`REDIS_PREFIX`/`SESSION_COOKIE`). See D1 below.
  3. Cache payloads: `HealthController` caches string `'ok'`; `AiService::classifyIntent`/`assessWorkingCondition` `Cache::remember` return strings (raw AI responses). No objects cached → `serializable_classes => false` (13 skeleton default) is safe. Phase 1: after upgrade, confirm the app config's behavior when the key is absent and add explicit `'serializable_classes' => false` if the config file is touched.
  4. Session serialization: `config/session.php` has NO `serialization` key; framework default is `php` → current sessions use PHP serialization seamlessly. See D1.
  5. `upsert()`: no query-builder `->upsert(` calls anywhere in `app/` (the `DealIngestionService::upsertDeal()` is a hand-rolled `DB::transaction` find-or-create, unaffected). No impact.
  6. Queue events: no `app/Events` or `app/Listeners` dirs, no `Event::listen` for `JobAttempted`/`QueueBusy`. Jobs exist (`RunDealCrawl`, `DownloadDealMedia`, `ReclassifyHuntedDealIntent`) — Phase 1 should confirm they pass IDs/scalars, not model collections, re: delta (c). No listeners → no impact.
  7/8. Custom cache stores/queue drivers: none. Custom middleware: only `PerformanceMonitoring`, `SecurityMonitoring` (web appends in `bootstrap/app.php`) — CSRF untouched. `Paginator::useTailwind()` set in `AppServiceProvider` → Bootstrap-3 rename irrelevant. Published `resources/views/vendor/pagination/{bootstrap-4,bootstrap-5,semantic-ui,simple-bootstrap-4,simple-bootstrap-5,tailwind}.blade.php` are explicitly-named published views (cleanup in Phase 6.2). No custom Manager `extend()` callbacks. No custom pivot/morph-pivot classes. No `->domain(` routes. No `Container::call` nullable-default dependencies found. No legacy `array_first`/`array_last` helpers. No tests assert the old password-reset mail subject. `Js::from` used in `deals/show.blade.php` and `hunted-deals/show.blade.php` for chart data — both pages are replaced by React in Phase 4, and no test compares escaped Unicode; no impact.
  9. PHP 8.5 + Laravel 12 vendor emits `PDO::MYSQL_ATTR_SSL_CA` deprecations; expected fix path is the framework upgrade itself.
  Net: the Laravel 13 upgrade for this codebase is low-risk; the main Phase 1 work is dependency constraints, config hardening keys, and re-running the gate.
- View inventory (0.4), complete at `c73c6c4`:
  - `view(...)` returns (21 call sites): root closure `view('welcome')` and dashboard closure `view('dashboard')` in `routes/web.php`; `DealController` (`deals.index`, `deals.show`); `AdminController` (`admin.dashboard`, `admin.crawl-logs`, `admin.system-health`, `admin.configuration`, `admin.crawl-log-detail`); `HuntedDealController` (`hunted-deals.index`, `hunted-deals.create`, `hunted-deals.show`, `hunted-deals.edit`); `ProfileController` (`profile.edit`); `AiClassificationController` (`ai-classification.index`); `FavoriteController` (`favorites.index`); Auth: `AuthenticatedSessionController` (`auth.login`), `RegisteredUserController` (`auth.register`), `PasswordResetLinkController` (`auth.forgot-password`), `NewPasswordController` (`auth.reset-password`), `ConfirmablePasswordController` (`auth.confirm-password`), `EmailVerificationPromptController` (`auth.verify-email`).
  - `Route::view(...)`: none.
  - Blade files (54): 21 page templates matching the call sites above; 3 layouts (`layouts/app`, `layouts/guest`, `layouts/navigation`); 3 profile partials (`update-profile-information-form`, `update-password-form`, `delete-user-form` — named error bags `updatePassword`/`userDeletion`); 19 components incl. `deal-media-gallery`, `favorite-button`, `stat-card`, `spectrum-line`, `phrase-tag-input`, `modal`, `dropdown`, `text-input`, buttons, `input-error`, `input-label`, `nav-link`, `responsive-nav-link`, `auth-session-status`, `application-logo`, `admin/partials/status`; 6 vendor pagination views; `welcome.blade.php`.
  - Non-Blade responses preserved: `/health`, `/ping` (`HealthController`), `/up` route health.
  This inventory is the authoritative Phase 6.1 checklist.
- Decisions (0.5 / D1):
  - Prefix continuity: pin `CACHE_PREFIX=olx-deal-hunter-cache-`, `REDIS_PREFIX=olx-deal-hunter-database-`, `SESSION_COOKIE=olx-deal-hunter-session` explicitly in `.env` (and production env) in Phase 1. Values equal today's computed values, so zero cache/session disruption. `.env` is gitignored — Phase 1 agent applies locally and must ensure the same values land in the production environment config.
  - Session serialization: keep PHP serialization (seamless sessions, no forced re-login). Phase 1: explicitly add `'serialization' => 'php'` to `config/session.php` (or `env('SESSION_SERIALIZATION', 'php')`) so a future skeleton sync cannot silently flip to `json`. The app stores only scalars/arrays in sessions, but continuity is chosen over the json hardening for this migration window.
  - No deviation from plan decisions D2/D3 (they bind Phase 2).
- Next agent must know (Phase 1):
  - Worktree at this commit contains only code + plan file; `.env` is untracked. DB/Redis hostnames (`lerd-mysql`, `lerd-redis`) and `dealhunter.test` do not resolve in the CLI environment — the verification gate's `migrate:status` item cannot be satisfied against MySQL here; use the documented SQLite fallback (read-only) or justify via the green test suite, and record it as you found it.
  - Targets: framework `^13.0`, boost `^2.0`, tinker `^3.0`, phpunit `^12.0`; let Composer solve `prism-php/prism`, breeze, pint, sail, pail, ide-helper; remove `laravel/breeze` from `require-dev` only if it blocks resolution (scaffolding already published).
  - Phase 1 config tasks: add `serializable_classes` handling check and explicit `serialization => php` in `config/session.php`; pin the three prefix/cookie env values.
  - PHPUnit 12 may change deprecation/skipping output — re-baseline the "0 skips introduced" rule against the 47-test green run.
  - Pre-migration screenshots still owed (see deviation); capture before Phase 4 if the environment allows.

### 2026-08-12 — Phase 1 — Laravel 13 upgrade agent (pi)
- Done: Complete Phase 1 framework-only upgrade on `laravel-13-inertia` (started from `a36edc6`, clean tree confirmed). Composer constraints updated, resolved, lock diff reviewed; all applicable upgrade-guide changes applied; dev-tooling compatibility resolved; full gate green; isolated commit.
- Versions resolved (exact):
  - `laravel/framework` v13.25.0 (PHP constraint now `^8.3`; CLI is 8.5.0 — fine; `composer.json` root PHP constraint left at `^8.2` since framework itself enforces `^8.3`)
  - `laravel/boost` v2.5.3, `laravel/tinker` v3.0.2, `phpunit/phpunit` 12.5.33 (target `^12.0` per plan/guide; PHPUnit 13.x exists but plan pins `^12.0`)
  - `prism-php/prism` v0.100.1 — the ONLY Prism release allowing `laravel/framework ^13.0`; constraint raised from `^0.87.0` to `^0.100.1` (logged deviation, see below)
  - `laravel/breeze` v2.4.2 (kept — supports `^13.0`, scaffolding stays), `laravel/pint` v1.30.5, `laravel/sail` v1.66.0, `laravel/pail` v1.2.7, `barryvdh/laravel-ide-helper` v3.7.0, `nesbot/carbon` 3.13.2, `laravel/serializable-closure` 2.0.15, `laravel/mcp` v0.9.3 (auto-resolved), `laravel/roster` v1.0.0 (new, pulled by boost 2)
  - Symfony line 7.3 → 8.1 (mailer/routing/translation/var-dumper etc.); new `symfony/polyfill-php85` v1.41.0 + `php86`; `polyfill-php83` dropped. Guzzle 7.15.x, monolog 3.10.x. No security advisories (`composer audit` output).
- Upgrade-guide changes applied (all deltas from Phase 0 re-read, re-checked at execution time):
  1. CSRF: no direct `VerifyCsrfToken`/`ValidateCsrfToken` references anywhere (app, routes, tests, `bootstrap/app.php`) — framework's renamed `PreventRequestForgery` applies by default; nothing to rewrite.
  2. Prefixes/cookie (D1): `.env` now pins `CACHE_PREFIX=olx-deal-hunter-cache-`, `REDIS_PREFIX=olx-deal-hunter-database-`, `SESSION_COOKIE=olx-deal-hunter-session` (values identical to pre-upgrade computed values — zero disruption). Same keys documented in tracked `.env.example` with comments. Runtime verification via tinker: all four values + `serialization=php` + `serializable_classes=false` resolve exactly as intended. **Production env must carry the same four values — the deploy agent must verify this; `.env` itself is gitignored.**
  3. Cache hardening: added `'serializable_classes' => false` to `config/cache.php` (app caches only scalars/strings: `HealthController` `'ok'`, `AiService` AI responses — verified). Hardcoded `false` like the skeleton, not `env()` (env strings would be truthy — deliberate choice).
  4. Session: added `'serialization' => env('SESSION_SERIALIZATION', 'php')` to `config/session.php` per D1 (keeps PHP serialization, seamless sessions, blocks a future skeleton sync from silently flipping to `json`). `.env.example` documents the key.
  5. `upsert()`: none in app code (confirmed in Phase 0) — no impact.
  6. Queue events: no listeners; jobs (`RunDealCrawl`, `DownloadDealMedia`, `ReclassifyHuntedDealIntent`) pass scalar/int IDs only — delta (c) eager-load restoration irrelevant.
  7–9. No custom cache stores, queue drivers, dispatcher/ResponseFactory implementations, MustVerifyEmail implementations, Manager `extend()` callbacks, domain routes, custom pivots, model-boot nesting, or Bootstrap-3 pagination references. `Paginator::useTailwind()` unaffected. No `Str` factories in tests. No password-reset-subject assertions. `Js::from` unicode change: only used in two Blade pages slated for Phase 4 replacement, no test comparisons.
  - Pre-existing Phase 0 noise (`PDO::MYSQL_ATTR_SSL_CA` deprecation from Laravel 12 vendor config) is GONE on Laravel 13 — confirmed.
- Deviation (logged per rules): `prism-php/prism` constraint changed `^0.87.0` → `^0.100.1`. The plan said to resolve remaining packages through Composer's solver and named only Breeze as a possible removal candidate, but `^0.87` hard-blocks Laravel 13 resolution (requires framework `^11|^12`; solver confirmed the conflict) and no 0.87/0.88/0.89 release supports 13 — `v0.100.1` is the first (and only) L13-compatible Prism. Risk assessment: Prism has ZERO direct usage in app code (`AiService` calls the OpenAI-compatible API via `Http` directly; no `use Prism` anywhere in `app/`, `config/`, `routes/`, `tests/`), so the jump is low-risk. Removing Prism instead was the other option, but the package is installed/configured at the platform level (MCP `PrismServer` provider registration) and keeping it honors "don't change dependencies beyond what the plan authorizes" more conservatively than removal. If the platform team later needs Prism APIs, re-audit the 0.87 → 0.100 changelog then.
- Breeze decision (1.3): KEPT at `^2.3` (resolved v2.4.2). It supports Laravel 13 and the published auth scaffolding remains the codebase's own code — no blocker, no removal needed.
- Verification gate (Phase 1.4), all green:
  1. `vendor/bin/pint --dirty` → passed.
  2. `vendor/bin/phpunit` → **OK, 47 tests / 114 assertions, 0 failures, 0 errors, 0 skips, 0 notices, 0 deprecations** (PHPUnit 12.5.33). Baseline comparison: same 47 tests/114 assertions as Phase 0; the 2 pre-existing PHP deprecations are gone; zero skips introduced. One PHPUnit 12 behavioral change fixed: 3 `OlxCrawlerServiceTest` tests triggered "No expectations were configured for the mock object" notices (new in PHPUnit 12) — resolved with class-level `#[AllowMockObjectsWithoutExpectations]` attribute (PHPUnit 12 API; no test logic changed).
  3. `npm run build` → green, 92 modules. Note: second CSS chunk is 51.98 kB vs 51.04 kB in Phase 0 with NO frontend source changes — cause: `tailwind.config.js` scans `vendor/laravel/framework/.../Pagination/resources/views/*.blade.php` and Laravel 13's shipped pagination views contain slightly more utility classes. Cosmetic; no app view changed. If the Phase 2/3 agent wants, the pagination content glob could be narrowed to `resources/views` only.
  4. `php artisan route:list` → green, 46 routes + `/up` (47 listed lines), same route surface as Phase 0.
  5. `php artisan migrate:status`: MySQL `lerd-mysql` unresolvable from this CLI (unchanged since Phase 0) — established safe evidence used instead: (a) fresh scratch SQLite `/tmp/dealhunter_l13_phase1.sqlite`: `php artisan migrate` + `migrate:status` → all 18 migrations ran cleanly under Laravel 13; (b) all 47 tests apply every migration against in-memory SQLite. No destructive operation against the app DB.
- Files changed in this commit: `composer.json`, `composer.lock`, `config/session.php` (+serialization), `config/cache.php` (+serializable_classes), `.env.example` (D1 pins documented), `tests/Feature/Crawlers/OlxCrawlerServiceTest.php` (PHPUnit 12 attribute), this plan file. `.env` changes are local/gitignored (per D1 the deploy agent must ensure production carries the same values).
- Next agent must know (Phase 2):
  - Laravel 13.25.0 is live on this branch; framework `^13.0` in `composer.json`.
  - Re-verify `inertiajs/inertia-laravel` ^3.x Laravel-13 support in its composer constraints at install time (plan already mandates this). Current `laravel/framework` in the app is 13.25.0.
  - Tailwind is still v3; `@tailwindcss/vite` v4 still in `package.json` unused (Phase 2.3 removal per D3). Second CSS chunk size bump above is the vendor-pagination scan, not a source change.
  - D1 values are pinned in `.env` and `.env.example`; production env must match (deploy checklist item).
  - Pre-migration screenshots still owed (Phase 0 deviation) — capture before Phase 4 if the environment allows.
  - PHPUnit is now 12.x; new tests may use PHPUnit 12 attributes/APIs. Zero notices is the new clean baseline.

### 2026-08-14 — Phase 2 — Inertia React wiring agent (pi)
- Done: Full Phase 2 (2.1–2.8) executed.
  - 2.1: `inertiajs/inertia-laravel` v3.3.1 installed (`^3.3`). `HandleInertiaRequests` middleware created and registered in `bootstrap/app.php` web middleware group. Root view `resources/views/app.blade.php` created (dark theme, fonts, `@vite` for css+tsx, `@inertiaHead`, `@inertia`).
  - 2.2: Frontend stack installed — `@inertiajs/react@3.6.1`, `react@19.2.8`, `react-dom@19.2.8`, `@vitejs/plugin-react@5.2.0`, `typescript@7.0.2`, `@types/react@19.x`, `@types/react-dom@19.x`. Inertia v3 uses `<script type="application/json">` for props (verified in `Directive.php`), so legacy raw `assertSee` on the dashboard had to be migrated to `AssertableInertia` (see 2.7 note).
  - 2.3: Tailwind stays on v3 per D3; `@tailwindcss/vite` v4 was already absent from `package.json` (removed/never added in an earlier session), single major confirmed — `tailwindcss@3.4.17` only. `npm run build` green.
  - 2.4: `resources/js/app.tsx` with `createInertiaApp` (eager glob resolution, progress bar color `#59e3ff`), `vite.config.js` updated (added `resources/js/app.tsx` alongside existing `app.js` so non-Inertia Blade pages keep working during the migration), `tsconfig.json` added (strict, react-jsx). `npx tsc --noEmit` clean.
  - 2.5: D2 honored — no Ziggy. Controllers pass fully-built URLs via `route()` in props; static paths live in typed `resources/js/routes.ts`.
  - 2.6: Shared props implemented in `HandleInertiaRequests::share()` — `appName`, `auth.user` (id/name/email/isAdmin), `flash` (success/error/info/warning, lazy closures). Covered by `DashboardInertiaTest`.
  - 2.7: Dashboard converted end-to-end. `DashboardController@index` created (mirrors the old closure semantics: stats, 5 hunted deals with `last_crawled_at`/deals_count/notes, 10 recent deals with favorite state, local-only media); closure removed from `routes/web.php`. New `tests/Feature/DashboardInertiaTest.php` (4 tests: component+stats+props, local-media-only, shared auth/admin, flash). Existing `DashboardMediaTest` rewritten to assert via `AssertableInertia` because Inertia props are JSON-encoded (slashes escaped), so raw `assertSee` on a media URL no longer matches. Minimal `resources/js/pages/Dashboard/Index.tsx` stub created — Phase 3/4 will build the real page.
  - 2.8: Verification gate green.
- Versions resolved: inertiajs/inertia-laravel v3.3.1, @inertiajs/react 3.6.1, react 19.2.8, react-dom 19.2.8, @vitejs/plugin-react 5.2.0, typescript 7.0.2.
- Gate results: `vendor/bin/phpunit` OK 51 tests / 196 assertions; `vendor/bin/pint --dirty` passed; `npx tsc --noEmit` clean; `npm run build` green (two CSS/JS chunks — `app.js` Alpine bundle for remaining Blade pages + `app.tsx` Inertia bundle).
- Deviations (logged per rule 8):
  - `vite.config.js` temporarily keeps BOTH `app.js` (Alpine) and `app.tsx` (Inertia) as build inputs. This is intentional and transient — the old Blade layout (`layouts/app.blade.php`) is still used by un-migrated pages and `FavoriteTest`; removing `app.js` before Phase 4 cutover would break those pages (ViteException). The next agent must remove `app.js` + `resources/js/app.js` + Alpine/Fancybox npm deps only at the final cutover (Phase 4), not before.
  - Removed a stray `app/Models/none.php` created by an erroneous earlier `make:model none` invocation (never committed, no references).
- Next agent must know:
  - Phase 3 starts from a working Inertia shell: root view + middleware + shared props + one stub page (`Dashboard/Index`). No React layouts/primitives exist yet.
  - `resources/js/routes.ts` is the only place for static route paths (D2). Parameterized URLs must come from controller props (pattern already used in `DashboardController`: `links` object + per-item `showUrl`/`editUrl`/`toggleFavoriteUrl`).
  - The dashboard Blade page (`resources/views/dashboard.blade.php`) still exists and is now orphaned (route serves Inertia). Do NOT delete Blade pages until the Phase 4 cutover audit confirms zero references.
  - Tests asserting media URLs must use `AssertableInertia` `->where('recentDeals.0.media.0.url', ...)`; raw `assertSee` on Inertia-rendered props fails due to JSON slash escaping.

### 2026-08-16 — Phase 3 — shared-primitives audit/close agent (pi)
- Done: Audited the Phase 3 dirty batches (all legit, no foreign work) and mapped every artifact to its checkbox, then fixed the two genuine deviations found:
  - 3.1: `layouts/AppLayout`, `layouts/GuestLayout` (Head, header slot, flash row, footer placard); `components/Navigation` (desktop rail + hamburger + mobile panel, aria-expanded/controls, Escape handling), `NavLink`/`ResponsiveNavLink` (POST logout), `AccountDropdown` (focus trap-ish open, Escape + outside click, `aria-haspopup`/`aria-expanded`, `as="button"` logout), `FavoritesBadge` (listens for `favorites:updated`, parity with Alpine version). Admin link renders only on shared `auth.user.isAdmin` — authorization-aware; the route itself stays server-protected (admin middleware untouched).
  - 3.2: `ui/Flash` — `FlashMessages` reads shared `flash` via `usePage<SharedPageProps>()`, 4 variants (success/error/info/warning) as spectral lines, `role="alert"` + `aria-live`.
  - 3.3: `ui/TextInput` (class-for-class port of x-text-input), `InputLabel` (value prop), `InputError` (bag-aware, role="alert"), `PrimaryButton`/`SecondaryButton`/`DangerButton` with `processing` submit-state + `Spinner`, `Modal` (focus trap, Escape, backdrop close, scroll lock, role="dialog" aria-modal), `ConfirmDialog`, `EmptyState`.
  - 3.4: `components/Pagination` consumes `links[]` (url/label/active from `linkCollection()`) + `meta` subset (`Paginated<T>` type), plain Inertia `Link` visits with `preserveScroll`, HTML-entity decode via DOMParser, mobile + desktop variants; hidden when lastPage <= 1.
  - 3.5: `deals/DealMediaGallery` — local-only by contract (prop documented as pre-filtered server-side; no remote fallback), Fancybox scoped `bind(container, groupSelector)` on mount / `unbind` on cleanup (checked against `@fancyapps/ui` .d.ts overload signatures — the container-scoped overload is the correct one to avoid document-level leak), unique per-instance group id (`useId`), `thumbnail` mode = first image + sr-only anchors in the same Fancybox group, `gallery` mode = full grid; `onError` drops broken images.
  - 3.6: `deals/FavoriteButton` — `router.post(toggleUrl, {}, { preserveState, preserveScroll })` verified; optimistic flip with `lastConfirmed` rollback on `onError`; re-dispatches `favorites:updated` from the shared `favoritesCount` prop on `onSuccess`. Backend support for it (part of this batch): `FavoriteController::toggle` content-negotiated — `expectsJson()` keeps the exact JSON contract (`{favorited, count}`, covered by the 3 pre-existing postJson tests) and Inertia/browser requests get `back()->with('success', ...)`; shared `favoritesCount` lazy prop added to `HandleInertiaRequests`; 3 new FavoriteTest cases cover redirect+flash both directions and the refreshed count via `AssertableInertia`.
  - 3.7: `deals/DealCard`, `deals/DealListRow` (dashboard/ledger variants with injectable media/favorite slots for 3.5/3.6), `deals/DealMetadata` (intent/working/new verdicts), `StatCard`, `deals/SpectrumLine`, `deals/PhraseTagInput`, `ApplicationLogo`, `lib/format` + `lib/navigation` + `lib/csrf`, `routes.ts` extended with the 4 static guest/auth paths it now needs.
  - 3.8: `types/index.ts` (SharedPageProps incl. `favoritesCount`), `types/domain.ts` (Deal/DealMedia/DealSnapshot/HuntedDeal/Favorite/CrawlLog/SystemHealth/AiClassification DTOs, camelCase to match the explicit controller-serialization convention), `types/pagination.ts` (Paginated/PaginationLink/PaginationMeta).
- Fixes applied (genuine deviations only): (1) Renamed `resources/js/Layouts` -> `resources/js/layouts` — case-convention deviation: Phase 2 established lowercase `resources/js/pages/` (page resolution is `./pages/**`), and `components/` is lowercase; only the plan prose capitalizes dirs. No imports referenced the dir path (imports go `../components/...`), verified by re-running tsc + build after the rename. (2) No other omissions found: FavoriteButton preserve-state contract, gallery lifecycle, nav auth/admin/mobile, form a11y, pagination wire format, and JSON backward-compat were all already correct in the batch; left untouched.
- NOT done (by scope): Dashboard page and all Phase 4 pages — `pages/Dashboard/Index.tsx` is still the Phase 2 stub, intentionally. Blade pages remain.
- Gate (all green): `npx tsc --noEmit` clean; `vendor/bin/pint --dirty` passed; `vendor/bin/phpunit` OK **54 tests / 214 assertions** (51 from Phase 2 + 3 new FavoriteTest cases), 0 failures/errors/skips/notices/deprecations; `npm run build` green (658 modules; CSS chunk 33.30 + 53.37 kB, JS 210.00 + 316.80 kB); `php artisan route:list` green — 47 routes, unchanged surface; `migrate:status` vs. app MySQL still unreachable from this CLI (`lerd-mysql`, known since Phase 0) — safe evidence: scratch SQLite `migrate` + `migrate:status` = 18/18 Ran + every test applies all migrations in-memory.
- Next agent must know (Phase 4): import paths — pages resolve from `resources/js/pages/**/*.tsx` (lowercase); layouts live at `resources/js/layouts/{AppLayout,GuestLayout}.tsx`; primitives are default exports except `ui/*` (named: TextInput, InputLabel, InputError, PrimaryButton, SecondaryButton, DangerButton, Modal, ConfirmDialog, EmptyState, Spinner, FlashMessages/FlashLine) and Pagination/NavLink/ResponsiveNavLink/FavoritesBadge/ApplicationLogo/StatCard/DealMediaGallery/DealCard/DealListRow/DealMetadata/FavoriteButton/PhraseTagInput/SpectrumLine (default). Parameterized URLs must come from controller props (D2); `tailwind.config.js` now scans `resources/js/**`. The `favorites:updated` CustomEvent contract `{detail:{count}}` is preserved for any component that mutates favorites. Dashboard Blade page still orphaned — delete only in Phase 4 after the reference search.

### 2026-08-16 — Phase 4.1 — dashboard finalize agent (pi)
- Done: Replaced the Phase 2 stub `resources/js/pages/Dashboard/Index.tsx` with the full instrument-panel dashboard, preserving the Blade page's design, content, and data semantics one-for-one:
  - `AppLayout` with the Blade `$header` slot content: placard "Panou · <name>", headline, auto-refresh toggle (beamkey, armed state, 300s interval now via `router.reload()` instead of `window.location.reload()` — SPA-correct equivalent), manual refresh button (spinner + disabled state, `router.reload` with `onFinish`), and the armed "+ Căutare nouă" key — all navigation via controller-provided `links` URLs (D2).
  - Spectral readouts: four `StatCard`s with the exact Blade colors/hrefs (`huntedDealsIndex`, `huntedDealsActive`, `dealsIndex`, `newDealsIndex`; amber/dim conditional on `newDealsCount > 0`).
  - Hunted-searches ledger: status dots (Activă/În pauză), notes (server already limits to 100 chars), deals-count + `ultima verificare`/`neverificată încă` line, Detalii/Editează rail-links; parked-beam `EmptyState` with the "iPhone 13" copy when empty.
  - Recent listings ledger: `DealListRow variant="dashboard"` with `DealMediaGallery mode="thumbnail"` (local-only, Fancybox lifecycle-bound) as `leading` and `FavoriteButton` as `leadingActions`; default dashboard meta (price · location · human-diff · Căutare) matches the Blade row; Detalii + external OLX link actions; empty-state panel identical to Blade.
- Controller: **zero changes needed** — `DashboardController` from Phase 2 already serializes every prop the page consumes (`serializeDeal`/`serializeMedia`, camelCase DTOs, `route()`-built `showUrl`/`editUrl`/`toggleFavoriteUrl`/`externalUrl` + `links` map), matching the `types/domain.ts` `Deal`/`HuntedDeal` DTOs. No type changes needed either.
- Tests: strengthened `DashboardInertiaTest::test_dashboard_renders_the_inertia_component_with_statistics` with D2-focused assertions — `recentDeals.0.searchTerm` plus `has()` for all server-provided URLs (`huntedDeals.0.showUrl`/`editUrl`, `recentDeals.0.showUrl`/`externalUrl`/`toggleFavoriteUrl`) and all five `links.*` keys. Local-only media guarantee test unchanged and still green.
- Gate (all green): `npx tsc --noEmit` clean; `npm run build` green (fancybox code-split chunk now emitted — expected from Phase 3 gallery import); `vendor/bin/pint --dirty` passed; `vendor/bin/phpunit` OK **54 tests / 224 assertions**, 0 failures/errors/skips/notices/deprecations; `php artisan route:list` green; `migrate:status` vs. app MySQL still unreachable from this CLI (known since Phase 0) — scratch SQLite `migrate` + `migrate:status` = all migrations Ran.
- Deviations: none.
- Next agent must know (Phase 4.2): dashboard Blade (`resources/views/dashboard.blade.php`) is still orphaned on disk — do NOT delete until 4.10. Auto-refresh is now an Inertia reload, not a full page reload; if Phase 7 browser checks want strict parity, a full `window.location.reload()` variant is a one-line swap. `DealListRow` dashboard variant defaults (`ledgerActions` right column, default meta) are verified to match the Blade dashboard row — reuse them for deals index/favorites (ledger variant) in 4.2+.

### 2026-08-19 — Phase 4.3 — deal detail migration (pi)
- Done: Converted `DealController@show` to explicit `Inertia::render('Deals/Show')` props and added the typed React detail surface with current reading, full local-media gallery, favorite action, snapshot/price history, classification metadata, and safe OLX/seller links. Authorization and route model binding are preserved; media uses persisted `DealMedia` rows only and never `image_urls`.
- Tests/gate: Added `DealsShowInertiaTest` coverage for guest/foreign access, explicit props, latest-snapshot readout, null classification, favorite state, snapshot history, and local-only media. Focused suite: 7 tests / 114 assertions; Pint, TypeScript, and Vite build green.
- Decisions/deviations: No chart dependency added; historical visualization uses native React/CSS. The old Blade template remains until Phase 4.10 cleanup.
- Next agent must know: Phase 4.4 is favorites index; reuse the existing Deal DTO/gallery/favorite primitives and preserve JSON toggle compatibility.

### 2026-08-13 — Phase 4.2 — deals-index migration agent (pi)
- Done: Deals index migrated end-to-end to Inertia React.
  - Backend: `DealController@index` now returns `Inertia::render('Deals/Index')` with explicit camelCase props — `deals` (Paginated<Deal>: `data` via `serializeDeal` + `links` from `linkCollection()` + camelCase `meta` subset), `filters` (resolved sort/direction with fallback, all booleans, `huntedDeal`, `hasActiveFilters`), `filterCounts`, `links` (index/reset/huntedDealsIndex/huntedDealsCreate). Query/filter/sort/pagination/eager-load/favorite-state logic unchanged; all URLs built server-side via `route()`; local-only media serialization mirrors DashboardController. Blade `deals/index.blade.php` kept (deletion in 4.10).
  - Frontend: `resources/js/pages/Deals/Index.tsx` — faithful instrument-panel port: filter form (search/sort/direction + 4 filter checkboxes with counts, default-on matches_intent with explicit 0/1 submit parity via `router.get`), ledger rows via `DealListRow variant="ledger"` + `DealMediaGallery mode="thumbnail"` + `FavoriteButton`, snapshot-price readout, both empty states (filtered vs. registry-empty), `Pagination` from paginator link metadata. No Axios/Ziggy.
  - Tests: `tests/Feature/DealsIndexInertiaTest.php` — 13 tests: guest redirect, component + minimal props, local-only media (assertDontSee remote URL), latest-snapshot price preference, search filter, matches_intent default-on/off, new_items 24h window, sort+direction applied/serialized, invalid-sort fallback, paginator links carry `sort=title&direction=asc&page=2` query string, filter counts, hunted_deal scoping (own = scoped, foreign = 404, unknown = 404).
- Deviation (logged per rules): deal search switched from raw `'ILIKE'` operator to Laravel 13's cross-grammar `whereLike()`/`orWhereLike()` (case-insensitive default). Raw ILIKE is PostgreSQL-only and 500s on the SQLite test guard; `whereLike` compiles correctly on MySQL (production) and SQLite (tests) with identical case-insensitive semantics. Same change NOT applied to `HuntedDealController` (two more ILIKE sites) — out of scope for 4.2, flagged for 4.5/4.6/4.7 agent.
- Gate (all green): `vendor/bin/pint --dirty` passed; `vendor/bin/phpunit` OK **67 tests / 449 assertions**, 0 failures/errors/skips/notices/deprecations; `npx tsc --noEmit` clean; `npm run build` green; `php artisan route:list` green; `migrate:status` vs. app MySQL still unreachable from this CLI (known since Phase 0) — scratch SQLite `migrate` + `migrate:status` = all 18 migrations Ran.
- Next agent must know (Phase 4.3): `resources/views/deals/index.blade.php` is now orphaned — do not delete until 4.10. `serializeDeal`/`serializeMedia` on `DealController` are ready to extend for the show surface (snapshots list, full deal fields); the `Deal` DTO already carries optional show-surface fields (`intentScore`, `description`, `lastSeenAt`, `snapshotsCount`, `huntedDealUrl`). Remember the ILIKE deviation — `HuntedDealController` still has raw ILIKE at two sites and will hit the same SQLite test problem in 4.5–4.7; convert with `whereLike` then.


### 2026-08-13 — Phase 4.4 — favorites index migration (pi)
- Done: Favorites page migrated to Inertia React.
  - Backend: `FavoriteController@index` returns `Inertia::render('Favorites/Index')` with explicit camelCase props — `favorites` (Paginated<Favorite>: `data` via `serializeFavorite` + `links` from `linkCollection()` + camelCase `meta` subset, `withQueryString()`), `links.dealsIndex`. `toggle()` content negotiation unchanged (JSON contract preserved for Inertia `router.post` + legacy Alpine `X-Requested-With` path).
  - Serialization: `serializeFavorite` emits favorite-level `id` + `createdAt` (`diffForHumans`); `serializeDeal` mirrors `DealController::serializeDeal` (title `Str::limit` 100, description 150 or null, latest-snapshot price preference, `matchesIntent`/`intentScore`/`likelyWorking`, `searchTerm`, `huntedDealUrl`, `isFavorite: true`, local-only media) but omits `snapshotsCount`/`isNew`/`lastSeenAt`/`createdAt` (Blade favorites surface never showed them).
  - Frontend: `resources/js/pages/Favorites/Index.tsx` — instrument-panel ledger (`DealListRow variant="ledger"` + `DealMediaGallery thumbnail` + `FavoriteButton`), custom meta „adăugat la favorite {createdAt} · căutare: {searchTerm}”, empty state „Nicio favorită încă”, `Pagination`. `Deal.createdAt` made optional on the TS DTO (dashboard still serializes it; deals/favorites omit it).
  - Tests: `FavoriteTest` index assertions converted to `AssertableInertia`; new `tests/Feature/FavoritesIndexInertiaTest.php` — 8 tests: guest redirect, component + minimal props, empty state, user isolation, local-only media (assertDontSee remote), latest-snapshot price, title/description limits, newest-first ordering, 21-row pagination meta/links.
- Gate (all green except known MySQL host): `vendor/bin/pint --dirty` passed; `vendor/bin/phpunit` OK **83 tests / 729 assertions**, 0 failures; `npx tsc --noEmit` clean; `npm run build` green; `php artisan route:list` green; `php artisan migrate:status` on production `.env` still unreachable (lerd-mysql does not resolve from this CLI — known since Phase 0); scratch SQLite `migrate` + `migrate:status` = all 18 migrations Ran.
- Next agent must know (Phase 4.5): `resources/views/favorites/index.blade.php` is now orphaned — do not delete until 4.10. The `serializeDeal`/`serializeMedia` duplication now exists in `DealController` AND `FavoriteController`; extract a shared serializer during 4.10 cleanup. `HuntedDealController` still has raw ILIKE at two sites and will hit the SQLite test guard in 4.5–4.7; convert with `whereLike` then.


### 2026-08-13 — Phase 4.7 — hunted deals show migration (pi)
- Done: Hunted deals show migrated end-to-end to Inertia React.
  - Backend: `HuntedDealController@show` now returns `Inertia::render('HuntedDeals/Show')` with explicit camelCase props — `huntedDeal` (show summary via new `serializeHuntedDealShow`: id/searchTerm/isActive/notes/full, `lastCrawledAt` diffForHumans + split `lastCrawledAtDate`/`lastCrawledAtTime`, split `createdAt`/`createdAtTime` + `updatedAt`/`updatedAtTime`, showUrl/editUrl), `deals` (Paginated<Deal> via new `serializeDeal` — title limit 80, description 120, `createdAt` diffForHumans, `isNew`, latest-snapshot price preference, local-only media, favorite state — plus `serializeMedia`), `stats`, `filterCounts`, `filters` (search/sort/direction/4 booleans/hasActiveFilters), `chart` (via `serializeChart`: hasTrace/currency/sampleCount/firstCaptured/lastCaptured + `samples` min/average/max/currency/count/captured/timestamp + `latestSnapshot`), `links` (index/edit/dealsIndex/reset). Query/filter/sort/pagination/authorization unchanged; `matches_intent` hidden-input "0"/"1" parity preserved.
  - Deviation (logged per rules): show() search switched from raw `'ILIKE'` to `whereLike()`/`orWhereLike()` — the last remaining ILIKE site flagged in 4.5's handoff. Cross-grammar, case-insensitive, correct on MySQL + the SQLite test guard.
  - Frontend: `pages/HuntedDeals/Show.tsx` — header (status dot + last-crawled + Editează), 5 `StatCard` spectral readouts, notes block, `SpectrumSection` (native React/CSS price-spectrum trace with min/average/max metric tabs + hover popup + summary readout, single-snapshot and parked-beam states, "Lecturi" emission lines sidebar), associated-deals ledger (`DealListRow variant="dashboard"` + thumbnail gallery + `FavoriteButton` + custom meta + `showIntentScore`), filter form (`router.get`, hidden-input matches_intent parity), `Pagination`, both empty states (filtered vs parked, activation CTA when paused), metadata block. No chart dependency added — same approach as Phase 4.3. `DealListRow` gained an optional `showIntentScore` prop (defaults to the ledger variant's previous behavior).
  - Types: added `HuntedDealShowSummary`, `HuntedDealChart`, `HuntedDealChartSample` to `types/domain.ts`.
  - Tests: new `tests/Feature/HuntedDealsShowInertiaTest.php` — 12 tests: guest redirect, foreign-user 404, minimal props (stats/filterCounts/filters/chart/links all zeroed/defaulted), crawl statistics (incl. 24h window via direct `created_at` set), price-drop count from multi-snapshot deals, local-only media (assertDontSee remote OLX URL), associated-deal serialization (title 80/desc 120 truncation, isNew, favorite state, latest-snapshot price preference), full chart trace serialization, single-snapshot state, deal filter apply + serialize, invalid-sort fallback.
- Gate (all green except known MySQL host): `vendor/bin/pint --dirty` passed; `vendor/bin/phpunit` OK **114 tests / 1257 assertions**, 0 failures/errors/skips/notices/deprecations; `npx tsc --noEmit` clean; `npm run build` green (693 modules); `php artisan route:list` green; `php artisan migrate:status` vs. app MySQL still unreachable (`lerd-mysql`, known since Phase 0) — scratch SQLite `migrate` + `migrate:status` = all 18 migrations Ran.
- Next agent must know (Phase 4.8): `resources/views/hunted-deals/show.blade.php` is now orphaned — do not delete until 4.10. `HuntedDealController` now carries `serializeDeal`/`serializeMedia` duplicating `DealController`/`FavoriteController` — extract a shared serializer during 4.10 cleanup. `serializeHuntedDeal` (index) and `serializeHuntedDealForm` (form) and `serializeHuntedDealShow` (show) are three distinct, surface-specific serializers — leave them separate. No `HuntedDealPriceSnapshot` factory exists yet; the tests construct snapshots via `::create` (fine for now). Phase 4.8 is the AI classification page (`AiClassificationController`).

### 2026-08-13 — Phase 4.6 — hunted deals create/edit forms (pi)
- Done: Hunted deals create + edit forms migrated to Inertia React (commit `0cc11fd`). This tracker entry closes the bookkeeping for that commit — the code, tests and gate were completed by the previous agent, but the plan-file checkbox and handoff entry were missed.
- Backend: `HuntedDealController@create` returns `Inertia::render('HuntedDeals/Create')` (props: `links.store`, `links.index`); `edit` returns `Inertia::render('HuntedDeals/Edit')` (props: `huntedDeal` via new `serializeHuntedDealForm` — id/searchTerm/isActive/notes/excludedPhrases/preferredPhrases/dealsCount/createdAt/updatedAt/lastCrawledAt/showUrl/editUrl — plus `links.index/update/destroy`). `store`/`update`/`destroy` unchanged (validation, duplicate guard, `Rule::unique`, phrase normalization, reclassification dispatch, cascade delete).
- Frontend: `pages/HuntedDeals/Create.tsx` + `Edit.tsx` (shared form layout: search term, active checkbox, preferred/excluded phrase tag inputs, notes; Edit adds the "Informații căutare" readout + `ConfirmDialog` delete). `PhraseTagInput` tightened for the form surfaces (Enter/comma delimiters, duplicate filtering, error display).
- Tests: `tests/Feature/HuntedDealsFormsInertiaTest.php` — 8 tests: guest redirect, create/edit component + links, store persists + redirects, store validation + duplicate rejection, edit serialization, foreign-user 404, update persists + reclassification queue, update duplicate rejection, destroy + redirect.
- Gate (all green except known MySQL host): `vendor/bin/phpunit` OK **102 tests / 976 assertions**; `npx tsc --noEmit` clean; `vendor/bin/pint --dirty` passed; `npm run build` green; `php artisan route:list` green; `migrate:status` vs. app MySQL still unreachable (`lerd-mysql`, known since Phase 0) — scratch SQLite evidence as before.
- Next agent must know (Phase 4.7): `resources/views/hunted-deals/{create,edit}.blade.php` are now orphaned — do not delete until 4.10. `serializeHuntedDealForm` is form-specific (combined `d M Y, H:i` timestamps); the show page needs its own split date/time serializer. `HuntedDealController@show` still returns Blade `view('hunted-deals.show')` and still has ONE raw `ILIKE` site — both are the 4.7 targets.

### 2026-08-13 — Phase 4.5 — hunted deals index migration (pi)
- Done: Hunted deals index migrated to Inertia React.
  - Backend: `HuntedDealController@index` now returns `Inertia::render('HuntedDeals/Index')` with explicit camelCase props — `huntedDeals` (Paginated<HuntedDeal>: `data` via `serializeHuntedDeal` + `links` from `linkCollection()` + camelCase `meta` subset, `withQueryString()`), `filters` (`search`, `filter`, `sort`, `direction`, `hasActiveFilters`), `links` (`index`, `create`). Pagination kept at `paginate(15)`.
  - Serialization: `serializeHuntedDeal` emits `id`, `searchTerm`, `isActive`, `notes` (`Str::limit` 140), `dealsCount` (withCount), `lastCrawledAt` (`diffForHumans` or null), `createdAt` (`d M Y`), `updatedAt` (`diffForHumans`), `showUrl`/`editUrl`, and `latestPriceSnapshot` (id/averagePrice/minPrice/maxPrice/dealsCount/priceCurrency/capturedAt) — matches the existing `HuntedDeal`/`HuntedDealPriceSnapshot` DTOs.
  - Frontend: `resources/js/pages/HuntedDeals/Index.tsx` — header (placard + count + "+ Căutare nouă"), filter form (search / stare / sort / direcție, `router.get` submit + "Reinițializează" reset), instrument-panel ledger rows (search-term link, Activă/În pauză status dot, deals-count, notes, `Creată · Actualizată · Verificată/Neverificată` meta line, Min/Medie price readout from latest snapshot, Detalii/Editează rail-links), `Pagination`, and both parked-beam empty states (filtered vs registry-empty).
  - Tests: new `tests/Feature/HuntedDealsIndexInertiaTest.php` — 9 tests: guest redirect, component + minimal props (perPage 15, dealsCount, null snapshot/lastCrawledAt, filters defaults, links), notes truncation (140 + ellipsis = 143), state filter (active/inactive), crawl filters (never_crawled/recently_crawled), search across `search_term`+`notes`, latest-price-snapshot serialization, authenticated-user scoping, empty state.
- Deviations (logged per rules): (1) index search switched from raw `'ILIKE'` to cross-grammar `whereLike()`/`orWhereLike()` — same Phase 4.2 deviation, now applied to the index site (the show site stays until 4.7). (2) `direction` now normalized to `asc`/`desc` (was passed raw into `orderBy`); invalid direction previously risked a SQL error. (3) invalid `sort` now falls back to `updated_at` (was: no ORDER BY at all) so the serialized `filters.sort` always reflects the effective ordering.
- Gate (all green except known MySQL host): `vendor/bin/pint --dirty` passed; `vendor/bin/phpunit` OK **92 tests / 897 assertions**, 0 failures; `npx tsc --noEmit` clean; `npm run build` green (680 modules); `php artisan route:list` green; `php artisan migrate:status` on production `.env` still unreachable (lerd-mysql does not resolve from this CLI — known since Phase 0); scratch SQLite `migrate` + `migrate:status` = all 18 migrations Ran.
- Next agent must know (Phase 4.6): `resources/views/hunted-deals/index.blade.php` is now orphaned — do not delete until 4.10. `HuntedDealController` still has ONE raw ILIKE site left in `show()` (title/description) — convert with `whereLike` in 4.7. `create`/`edit`/`show` still return Blade `view(...)` and are the 4.6/4.7 targets; `serializeHuntedDeal` + `serializePriceSnapshot` are available on `HuntedDealController` and reusable/extendable for the show surface. No `HuntedDealPriceSnapshot` factory exists yet — the test constructs snapshots via `::create`; a factory is optional for 4.7.
