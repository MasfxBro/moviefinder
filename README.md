# MovieFinder

A movie and TV discovery web application built with Laravel 13, PHP 8.5 and MySQL 8.4. Users describe a scene or title in plain language and the AI search assistant (Google Gemini) finds matching films and clips. Normal keyword search, browsing, a watchlist and a subscription plan system are also included.

**Demo mode is on by default (`LAUNCH_MODE=demo`).** The checkout is simulated and no real payments are processed until the go-live gates in `docs/GO_LIVE.md` are cleared.

---

## Setup

Follow **[docs/SETUP.md](docs/SETUP.md)** step by step. That document covers tool installation (Laragon, PHP 8.5, MySQL 8.4, Node LTS), database creation, `.env` configuration, running migrations, and the verification checklist.

---

## Environment Variables

Copy `.env.example` to `.env` and fill in the values. The table below lists every variable the application reads.

| Variable | Required | Default | Notes |
|---|---|---|---|
| `APP_KEY` | yes | — | Generate with `php artisan key:generate` |
| `APP_ENV` | yes | `production` | `local`, `staging`, or `production` |
| `APP_DEBUG` | yes | `false` | Must be `false` in production |
| `APP_URL` | yes | — | Full URL including scheme |
| `BUSINESS_TIMEZONE` | no | `Asia/Jakarta` | Timezone for AI quota resets |
| `DB_HOST` | yes | `127.0.0.1` | |
| `DB_PORT` | yes | `3306` | |
| `DB_DATABASE` | yes | — | |
| `DB_USERNAME` | yes | — | |
| `DB_PASSWORD` | yes | — | |
| `SESSION_SECURE_COOKIE` | no | `false` | Set `true` in production (HTTPS) |
| `QUEUE_CONNECTION` | no | `database` | |
| `CACHE_STORE` | no | `file` | |
| `MAIL_MAILER` | yes | `smtp` | Use Mailpit locally |
| `MAIL_HOST` | yes | `127.0.0.1` | |
| `MAIL_PORT` | yes | `1025` | |
| `MAIL_FROM_ADDRESS` | yes | — | |
| `TMDB_READ_TOKEN` | yes | — | From TMDB account (T-08) |
| `AI_DRIVER` | no | `fake` | `fake` for demo, `gemini` with real keys |
| `GEMINI_API_KEY_FREE` | no | — | Project **without** billing (Free users) |
| `GEMINI_API_KEY_PAID` | no | — | Separate billed project (Plus and Pro) |
| `AI_MODEL_STANDARD` | no | — | Model name from `ai:check` output |
| `AI_MODEL_SMART` | no | — | Model name from `ai:check` output |
| `MIDTRANS_SERVER_KEY` | no | — | Sandbox key until go-live |
| `MIDTRANS_CLIENT_KEY` | no | — | Sandbox key until go-live |
| `MIDTRANS_IS_PRODUCTION` | no | `false` | Set `true` only after merchant approval |
| `LAUNCH_MODE` | no | `demo` | Set to anything other than `demo` after all go-live gates pass |
| `ADMIN_PATH` | yes | — | Long random string; keep secret |
| `ADMIN_ALLOWED_IPS` | no | — | Comma-separated IP allow-list for admin |
| `ADMIN_EMAIL` | yes | — | Email for the admin seeder |

Never commit `.env`. Never put real API keys in `.env.example`.

---

## Artisan Commands

```bash
# Development
php artisan serve                     # start the local server
npm run dev                           # start the Vite dev server
composer run dev                      # start both together

# Database
php artisan migrate                   # run pending migrations
php artisan migrate:fresh --seed      # wipe and re-seed (demo data)
php artisan db:seed --class=AdminSeeder   # create the admin account

# Catalog
php artisan catalog:seed              # import popular titles from TMDB
php artisan catalog:refresh           # refresh stale titles

# AI
php artisan ai:check                  # check API keys and model availability
php artisan ai:evaluate               # run quality evaluation (non-production only)

# Maintenance
php artisan usage:prune               # delete old AI usage log rows
php artisan users:prune-unverified    # delete unverified accounts older than 14 days
php artisan history:prune             # delete search history older than 90 days
php artisan onboarding:reset {user}   # reset the guided tour for a user

# Quality
vendor/bin/pint                       # auto-fix code style
vendor/bin/phpstan analyse            # static analysis (level 5)
php artisan test                      # run the test suite
composer audit                        # check PHP dependencies for vulnerabilities
npm audit                             # check JS dependencies for vulnerabilities
npm run build                         # build frontend assets for production
```

---

## Architecture Overview

```
app/
  Console/Commands/   Artisan commands (catalog refresh, quota prune, etc.)
  Contracts/          Interfaces: LlmClient, ClipFinder, PlanResolver, MetadataProvider, PaymentGateway
  DTOs/               Typed data transfer objects (AiReply, CandidateData, ClipResultSet, SceneQuery)
  Enums/              Fixed value sets (Plan, Role, MediaType, AiIntent, etc.)
  Http/
    Controllers/      Thin: validate → authorize → one service call → return view/resource
    Middleware/       SecurityHeaders, EnsureVerifiedForAi, EnsureUserNotSuspended, HideAdminFromPublic
    Requests/         FormRequest classes for every write endpoint
    Resources/        Eloquent API resources (AiMessageResource, TitleSearchResource)
  Models/             Eloquent models with casts and relations; no business logic
  Policies/           Authorization policies (Watchlist, SearchHistory, TitleRequest)
  Providers/          AppServiceProvider: all service container bindings
  Services/
    Ai/               GeminiClient, ModelRouter, AiSearchService, QuotaService, etc.
    Catalog/          CatalogService, TitleImporter, TitleFilter, PosterService
    Clips/            ClipFinderChain, AiSearchClipFinder, StoredClipFinder, etc.
    Tmdb/             TmdbClient, TmdbMapper
    Admin/            Admin-specific services
    BillingPlanResolver, EntitlementService, SubscriptionService, MidtransGateway, Settings

resources/
  css/app.css         Design tokens (CSS variables) and Tailwind base
  js/                 Alpine components: aiChat.js, liveSearch.js, previewPlayer.js, etc.
  views/              Blade templates; components/ for reusable components

database/
  migrations/         One concern per migration; backward-compatible (expand/contract)
  factories/          Test and demo data factories
  seeders/            GenreSeeder, AdminSeeder, DemoSeeder

docs/                 All project documentation (ARCHITECTURE.md, FEATURES.md, SECURITY.md, etc.)
scripts/deploy.sh     Release procedure (see docs/OPERATIONS.md section 5)
```

Key design decisions:
- **Server-rendered Blade** (no SPA, no Livewire). Alpine.js for lightweight interactivity.
- **Contracts (interfaces)** decouple the AI, clip finder, payment gateway, and TMDB provider from their implementations, making each swappable and independently testable.
- **Settings service** stores runtime values (quotas, prices, feature flags) in the database with a 60-second cache, so the admin can change them without a deploy.
- **SecurityHeaders middleware** runs on every request: CSP with per-request nonce, HSTS (production only), nosniff, referrer policy, permissions policy, frame protection.
- **AI is always safe to disable**: `features.ai_enabled=false` in settings shows a friendly message; quotas are never consumed on error.

For full details see [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

---

## Tests

The test suite uses **Pest** and runs against **MySQL** (not SQLite — the catalog uses FULLTEXT and quota tests require real MySQL behavior).

```bash
# Run the full suite
php artisan test

# Run a specific file
php artisan test tests/Feature/AiPipelineTest.php

# Run tests matching a name
php artisan test --filter=quota
```

The suite covers: auth and roles, guest access and redirects, error pages, security headers, rate limits, quota races (parallel MySQL), AI pipeline with fake LLM, clip validation, subscription state machine, webhook idempotency, IDOR prevention, mass-assignment, XSS escaping, SQL injection probes, and more.

No test calls a real external service. All network calls use `Http::fake()`. The AI driver is always `fake` during tests.

---

## Demo Steps

This assumes `AI_DRIVER=fake` and `LAUNCH_MODE=demo` (the defaults).

1. **Setup**: follow `docs/SETUP.md` and run `php artisan migrate --seed` with the AdminSeeder.
2. **Landing page**: visit `/` as a guest — the landing page shows plan comparison and a poster strip.
3. **Register**: create an account, verify the email in Mailpit (`http://localhost:8025`), and complete the guided tour.
4. **Search**: use the search bar to find a title by name; a not-found search triggers a "Request" prompt.
5. **AI chat**: open the AI chat panel (top bar) and describe a scene in English or Indonesian. The fake driver returns example answers — no Gemini key needed.
6. **Title detail**: open any title; use the embedded title chat to ask about it.
7. **Clips**: in title mode, ask "find a scene where..." — the clip finder returns stored or search-link results.
8. **Watchlist**: add titles to the watchlist; test the plan cap.
9. **Pricing and demo checkout**: visit `/pricing`, choose a plan, and click Continue — the demo checkout simulates a payment without calling Midtrans.
10. **Admin**: visit `/{ADMIN_PATH}/login`, log in with the admin seeder credentials and the TOTP code, and explore the dashboard, settings, and AI monitoring.
11. **Feature toggle**: in admin Settings, set `features.ai_enabled` to `false` — the AI chat shows the maintenance message within 60 seconds.

For go-live steps see [`docs/GO_LIVE.md`](docs/GO_LIVE.md).
