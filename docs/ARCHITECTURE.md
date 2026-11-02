# ARCHITECTURE

## 1. Stack
PHP 8.5, Laravel 13 (latest 13.x), MySQL 8.4 LTS, Blade, Tailwind CSS, Alpine.js, Vite. Node (latest LTS) is only used to build assets. Version and patching policy: D-43.
Cache: `file` in dev, `redis` or `database` in prod. Queue: `database` driver. Sessions: `database` or `file`. Mail: Mailtrap in dev.
Testing: Pest. Style: Pint. Static analysis: Larastan (level 5+).
Rendering is server-side Blade. Alpine is for small interactions only (modals, chat UI, dropdowns). No SPA.

## 2. Folder structure (app/)
```
app/
  Console/Commands/        catalog:seed, catalog:refresh, usage:prune, history:prune, users:prune-unverified, billing:reconcile, billing:simulate (non-prod), onboarding:reset, ai:check, ai:evaluate (non-prod)
  Contracts/               ClipFinder.php, MetadataProvider.php, PaymentGateway.php, LlmClient.php
  DTOs/                    TitleData, CandidateData, ClipResult, AiReply, SubscriptionEvent
  Enums/                   Role, Plan, MediaType, SubscriptionStatus, PaymentStatus, ChannelTrust, RequestStatus, AiIntent
  Http/
    Controllers/           thin; one responsibility each (HomeController, LandingController, VersionController, ...)
    Controllers/Admin/
    Middleware/            EnsureAdmin, EnsureTwoFactor, HideAdminFromPublic, SecurityHeaders, EnsureVerifiedForAi, FeatureEnabled
    Requests/              one FormRequest per write endpoint
    Resources/             JSON shaping for AJAX endpoints
  Models/
  Policies/
  Services/
    Catalog/               CatalogService, TitleImporter, TitleSearch, TitleFilter
    Tmdb/                  TmdbClient, TmdbMapper
    Ai/                    AiSearchService, PromptBuilder, ReplyValidator, ConversationStore, QuotaService, FreePlaceService, AnswerCache, AiBudgetService, AiCostCalculator, ModelRouter, CandidateCritic, GeminiClient, FakeLlmClient
    Clips/                 ClipFinderChain, CuratedClipFinder, StoredClipFinder, AiSearchClipFinder, SearchLinkFinder, PlatformUrl, YoutubeOembed, OembedValidator, ChannelPolicy, ClipRelevanceJudge
    Billing/               SubscriptionService, MidtransGateway, WebhookHandler, EntitlementService, PlanCatalog
    Onboarding/            OnboardingService (first-time tour state, replay, reset)
  Jobs/                    SendRenewalReminder, RefreshTitle
  Notifications/           RenewalReminder, PaymentFailed, PaymentSucceeded, RequestResolved, VerifyEmail (branded)
resources/views/           layouts, components (Blade components), pages, errors (404/403/419/429/500/503), admin
resources/js/              app.js, alpine components (aiChat.js, previewPlayer.js, liveSearch.js, updateNotice.js, offlineNotice.js, guidedTour.js)
config/                    app.php, features.php, ai.php, billing.php, catalog.php, clips.php, services.php
database/                  migrations, factories, seeders (ChannelSeeder, GenreSeeder, AdminSeeder)
tests/Feature, tests/Unit
docs/
```
Rule: Controllers validate (FormRequest), authorize (Policy), call one Service/Action, return a view/resource. No business logic in controllers, models, or Blade.

## 3. Key flows

### 3.1 Browse/search (logged-in users only, D-35)
Request -> `TitleSearch`/`TitleFilter` (local DB) -> if empty and `q` present -> `TmdbClient::search` (cached 24h) -> mapped to lightweight cards (not stored) -> if still empty -> not-found state.
Opening a TMDB-only result -> `TitleImporter::import(tmdbId, type)` (throttled) -> row stored -> redirect to `/titles/{slug}`.

### 3.2 AI search pipeline (details in AI_BEHAVIOR.md)
Endpoint: `POST /ai/messages` (JSON), used by both chats; there is no `/ai` page. The title chat sends only the title id; the title context is loaded server-side.
1. Auth + verified + not suspended -> `QuotaService::reserve(user, weight)` (atomic; may return "no quota") and `AiBudgetService::check(user)` (may return "allowance used"). For Free users `FreePlaceService::take(user)` runs first (atomic; may return "places full") and `AnswerCache` is checked before any model call.
2. `ConversationStore::load(conversationId)` (cache, trimmed).
3. `PromptBuilder` builds messages (system rules + title context if scoped + history + user text).
4. `ModelRouter` picks the model (Smart for Pro while Smart searches and budget remain, otherwise Standard); `GeminiClient::structured()` (Free key for Free users, paid key for Plus and Pro; `AI_DRIVER=fake` uses `FakeLlmClient`) returns JSON -> `ReplyValidator` enforces schema and length caps.
5. Candidates -> `TmdbClient::search` verify -> `TitleImporter` ensures local rows -> cards.
6. If intent = find_scene -> `ClipFinderChain::find(title, scene)`.
7. Save conversation turn to cache; `QuotaService::commit()` only if the call succeeded (else `release()`); `AiCostCalculator` prices the calls that were made and `AiBudgetService` adds the cost to the user's budget.
8. Return JSON to the UI: `{reply, titles[], clips[], fallback_links[], quota}`.

### 3.3 Billing
Two modes (D-46), both through Midtrans Snap, with plan and amount computed on the server from `PlanCatalog`.
Auto-renew (card or GoPay): `/pricing` -> `SubscriptionService::start(user, plan, 'auto')` -> Snap transaction with tokenization -> user pays -> webhook -> `WebhookHandler` verifies the signature, re-fetches status from Midtrans, stores `webhook_events` (unique) -> on first success creates the gateway subscription (monthly) -> each cycle Midtrans charges and notifies -> `EntitlementService` extends `current_period_end`.
Pay for 30 days (QRIS, ShopeePay, GoPay, card, bank transfer for Pro): `SubscriptionService::start(user, plan, 'manual')` -> one-time Snap transaction restricted to the allowed channels -> webhook (same verification) -> `current_period_end` = max(now, current end) + 30 days. No gateway subscription exists; `billing:reconcile` emails a reminder with a pay link 3 days before the end, and the plan reverts to Free after the end.
Failure (auto mode) -> status `past_due`, `grace_ends_at = now + 3 days`, email user -> if not paid by grace end -> `expired`.
Cancel (auto mode) -> gateway subscription disabled, `cancel_at_period_end = true`. Daily `billing:reconcile` re-checks subscriptions near their end date or in grace.
Plan change: upgrade starts a new period immediately (old gateway subscription disabled, no proration); downgrade sets `pending_plan`, applied at the next renewal (for auto mode the gateway subscription amount is updated before the next charge).
Each payment stores an estimated gateway fee (`payments.est_fee_idr`) for the admin profit meter.

### 3.4 Guest routing (D-35)
Routes are split into a public group (landing, `/pricing`, legal pages, auth pages, `/version`) and an `auth` group (everything else). `/` is served by `HomeController`: a guest gets the landing view (`LandingController` view model: plan values from the Settings service, up to 8 cached popular posters; no other catalog data), an authenticated user gets the catalog home. Any protected URL opened by a guest redirects to `/login`, stores the intended URL, and returns there after login. The `auth` group is the only place where catalog routes are registered, so a new route is protected by default.

### 3.5 Version check (update notice)
`GET /version` returns `{"build":"<id>"}` with `Cache-Control: no-store`, throttled (30/min per IP). The id comes from the `BUILD_ID` file that `scripts/deploy.sh` writes into the release folder ("dev" locally) and is exposed as `config('app.build')`. The layout prints it in `<meta name="app-build">`; `updateNotice.js` compares it with `/version` (F-23).

### 3.6 AI cost guard (D-45)
Per AI request: `PlanCatalog` gives the plan's limits -> `QuotaService` reserves units (1 per message, 2 for an AI clip search; Smart counter for Pro) -> `AiBudgetService::check` reads the user's window (the active subscription period, or the calendar month for Free) and applies the thresholds: below 60 percent everything allowed; from 60 percent Smart is off; from 80 percent AI web clip search is off; at 100 percent AI is paused until the window renews -> the LLM calls run -> `AiCostCalculator` converts the reported tokens and the grounded-search fee into rupiah with the prices and safety multiplier in settings -> the cost is written to `ai_usage_log.cost_idr` and added to `ai_budget.spent_idr`. Everything is server-side and uses settings, so the admin can retune prices, budgets and thresholds without a deploy.

### 3.7 Free AI places (D-47)
Free users share one Google free-tier pool, so `FreePlaceService` rations it. Places per day = `ai.free.pool_daily_limit` / `plan.free.daily_units` x `ai.free.overbook_pct`. Places are released in waves (`ai.free.waves`); the number released so far today is the total times the cumulative share of the waves already open. The first AI message of the day takes a place with one atomic `UPDATE ai_free_pool SET taken = taken + 1 WHERE used_on = ? AND taken < released`, then inserts `ai_free_places(used_on, user_id)`. A user who already holds a place today skips the take, so a user is never refused mid-conversation. With no place available, the panel tells the user before typing ("Today's free AI places are full.") with the next wave time. Plus and Pro skip all of this because they use the paid key.

### 3.8 Email verification (D-51)
Registration -> user is created unverified and logged in -> `VerifyEmail` notification (signed URL, 60 minutes) is queued through the configured mailer -> the `/verify-email` page explains and offers Resend (throttled) -> the user opens the link: same browser, the account is verified and the session continues; another browser, the user is sent to login first and verification completes afterwards -> `OnboardingService` offers the guided tour on the first logged-in home visit. Changing the email address clears `email_verified_at` and sends a new link. `users:prune-unverified` deletes stale unverified accounts.

### 3.9 First-time guided tour (D-50)
`HomeController` asks `OnboardingService::shouldOffer(user)` (verified, not admin, `onboarding_seen_at` is null, feature enabled). When true, the page includes the tour offer and the service sets `onboarding_seen_at` immediately. `guidedTour.js` (Alpine) reads the step list for the user's plan (Free, Plus or Pro) from a server-rendered JSON block, highlights the elements marked `data-tour="..."`, skips steps whose target is hidden, and posts the outcome (completed or skipped) to `POST /onboarding/outcome`. The Account link "Take the tour again" starts the same script without touching `onboarding_seen_at`; `onboarding:reset` clears it.

## 4. Scheduled jobs (`routes/console.php`)
| Command | When | Purpose |
|---|---|---|
| `catalog:refresh --limit=100` | daily 03:00 | refresh oldest TMDB-derived rows so none is older than 150 days |
| `usage:prune` | daily 03:30 | delete `ai_usage_log` older than 30 days, old `ai_usage` rows, `ai_free_*` rows older than 14 days and `ai_budget` windows older than 14 months |
| `history:prune` | daily 03:40 | delete `search_history` older than 90 days |
| `users:prune-unverified` | daily 03:50 | delete unverified accounts older than `auth.unverified_ttl_days` (14) |
| `billing:reconcile` | daily 02:00 | reconcile subscriptions near end/in grace; send reminders 3 days before renewal |
| `queue:work --stop-when-empty` | every minute (cron) | process queued mail/jobs |

## 5. Caching policy (keep the DB light)
| Data | Store | TTL |
|---|---|---|
| TMDB search results | cache | 24 h |
| Trending/popular rows | cache | 24 h |
| Landing poster strip | cache | 24 h |
| Free answer cache (non-personal movie questions) | cache | 24 h |
| Where-to-watch | cache | 24 h |
| Conversation state | cache | 2 h sliding |
| Clip resolver misses | cache | 24 h |
| Resolved clip links | DB (`clip_links`) | until reported/removed; re-verified when older than 30 days |

## 6. Config and feature flags
Feature flags live in the `settings` table (editable in the admin, cached 60 s) with defaults in `config/features.php`: `ai_enabled`, `ai_clip_search`, `payments_live` (false in demo), `registration_open`, `requests_open`, `feedback_open`, `clip_driver_youtube_api` (false).
Quotas, caps and prices are read through a `Settings` service (DB `settings` table with config defaults).
No magic numbers in code: every limit lives in config or settings. `config/app.php` also holds `build` (from the `BUILD_ID` file), `update_poll_minutes` (default 5) and `business_timezone` (from `BUSINESS_TIMEZONE`, used for the quota reset and other "today" logic).

## 7. Environment variables (names only; values in `.env`, never committed)
```
APP_NAME APP_ENV APP_KEY APP_DEBUG APP_URL APP_TIMEZONE=UTC BUSINESS_TIMEZONE=Asia/Jakarta
DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
CACHE_STORE QUEUE_CONNECTION SESSION_DRIVER SESSION_SECURE_COOKIE
MAIL_MAILER MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_FROM_ADDRESS
TMDB_READ_TOKEN TMDB_IMAGE_BASE
AI_DRIVER GEMINI_API_KEY_FREE GEMINI_API_KEY_PAID AI_MODEL_STANDARD AI_MODEL_SMART AI_GLOBAL_DAILY_CAP
MIDTRANS_SERVER_KEY MIDTRANS_CLIENT_KEY MIDTRANS_IS_PRODUCTION
LAUNCH_MODE=demo
ADMIN_PATH (long random string, e.g. ops-<24 random chars>)  ADMIN_ALLOWED_IPS (optional, comma separated)  ADMIN_EMAIL
```
Also provide `.env.example` with every name above and empty values. The build id is not an env variable; it comes from the `BUILD_ID` file (section 3.5).

## 8. Error handling
Expected failures (TMDB down, Gemini 429/timeout, no clip) return a friendly message and a fallback, never a stack trace. Unexpected exceptions are logged (without secrets) and show a generic error page. `APP_DEBUG=false` in production.
Error pages live in `resources/views/errors/` (404, 403, 419, 429, 500, 503) and use the `error-page` component. They must render without the database or the session (static layout, no queries). Every unexpected 500 gets a short request id that is shown to the user and written to the log. Admin URLs for non-admins use the exact same 404 view as any unknown URL. No error page may contain `ADMIN_PATH`, a stack trace or an internal name. Texts and behavior: DESIGN_SYSTEM.md section 11 and FEATURES.md F-24.

## 9. Performance rules
Eager load relations, paginate everything, index every filtered/sorted column, no queries inside loops, `Http::timeout()` on every outbound call (TMDB 8s, Gemini 25s, Midtrans 15s) with limited retries, lazy-load images, click-to-load all iframes.

## 10. Admin area and continuity
- Separate `admin` guard and login page at `/{ADMIN_PATH}/login`. `HideAdminFromPublic` returns 404 for every admin URL (except the login page) unless the visitor is an authenticated admin. Admin controllers live in `Http/Controllers/Admin/`, views in `resources/views/admin/`.
- Admin writes go through the same Services as the public app (no duplicate logic), then invalidate only the affected cache keys.
- Releases are atomic and stateless: code lives in a release folder; sessions, cache, queue, `.env`, logs, and uploads live in shared locations that survive releases. Details and procedure: `docs/OPERATIONS.md`.
- Cache keys carry a version prefix (`v1:`); change it only when the cached value shape changes.
