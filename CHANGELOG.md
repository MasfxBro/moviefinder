# Changelog

All notable changes to MovieFinder are recorded here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versions are release ids in `YYYYMMDD-HHMM` format (written to `BUILD_ID` on deploy).

---

## [Unreleased]

### Added
- T-01: UI foundation — design tokens, dark theme, layouts, base components, scene-frame motif
- T-02: Error pages (404, 403, 419, 429, 500, 503) and offline banner
- T-03: Config files, Settings service (DB over config, 60 s cache), all enums
- T-04: Auth — register (age confirmation, Terms consent), login, password rules, session security, suspended-user guard
- T-04B: Email verification — branded email, signed 60-minute links, resend throttle, re-verification on email change, `users:prune-unverified` command
- T-05: Landing page (hero, steps, plan compare, FAQ, poster strip) and home routing
- T-06: Legal pages — Terms, Privacy, Copyright, About with required attributions
- T-07: Catalog schema — titles, genres, title_genre, title_people migrations, models, factories, GenreSeeder
- T-08: TMDB client and mapper with caching, timeouts, typed exceptions
- T-09: Title importer (idempotent, throttled), CatalogService, `catalog:seed`, `catalog:refresh`
- T-10: Poster cards, title rows, logged-in home with Trending, Popular, Top Rated, Movies, TV Shows rows
- T-11: Browse page with type, genre, year, director, rating and sort filters; shareable URLs
- T-12: Search — prefix + FULLTEXT, TMDB fallback, search_misses counter, rate limit
- T-13: Live search UI — 300 ms debounce, keyboard navigation, loading and error states
- T-14: Title detail page — hero, synopsis, cast, trailer slot, where-to-watch slot
- T-15: Trailer embed (click-to-load, youtube-nocookie) and where-to-watch (JustWatch, ID region)
- T-16: LLM client (GeminiClient, FakeLlmClient), PromptBuilder, ReplyValidator, DTOs, `ai:check` command
- T-17: Quota system — QuotaService, FreePlaceService (wave-based daily pools), AiBudgetService, AiCostCalculator, PlanCatalog, `usage:prune` command
- T-18: AI search service — 10-step global pipeline, ConversationStore, AnswerCache, ModelRouter, CandidateCritic, POST `/ai/messages`, GET `/ai/status`
- T-19: Global chat panel UI — progress steps, quota meter, suggested prompts, upgrade card, exit warning, all UX strings from AI_BEHAVIOR.md
- T-20: Title chat card — mode=title in AI search service, embedded ai-chat variant on detail page
- T-21: ClipFinder foundations — PlatformUrl, YoutubeOembed, OembedValidator, ChannelPolicy, CuratedClipFinder, StoredClipFinder, SearchLinkFinder, ClipFinderChain
- T-22: AI clip search — AiSearchClipFinder (grounded search, redirect resolution, relevance judge), ClipRelevanceJudge
- T-23: Clip cards, preview modal (60-second preview, youtube-nocookie), report-link flow
- T-24: AI quality tests — hostile-content, URL/HTML rejection, golden-set framework, `ai:evaluate` command
- T-25: Watchlist — policy, optimistic toggle, /watchlist page, plan-based cap
- T-26: Search history — storage, /history page, Clear all, `history:prune` command
- T-27: Title requests — /requests/create, per-plan daily limit, duplicate block, RequestResolved notification
- T-28: Account page — name and password change, plan display, account deletion with data scrubbing
- T-29: Feedback form, announcement banner, feature-off maintenance messages, public-launch notice component
- T-30: New-version toast — `/version` endpoint, `update-toast` component, `updateNotice.js`
- T-30B: First-time guided tour — tour-prompt, tour-step, guidedTour.js, `onboarding:reset` command
- T-31: Admin foundation — secret-path guard, mandatory TOTP 2FA, recovery codes, IP allow-list, audit log, AuditLogger, AdminSeeder
- T-32: Admin dashboard and titles — CRUD, poster upload (GD WebP re-encode), import, locked fields, audit logs
- T-33: Admin requests and missed-searches queue
- T-34: Admin clip moderation — clip CRUD, verify, bulk remove, reports queue, channel allow-list CRUD
- T-35: Admin settings editor, announcements CRUD, feedback inbox
- T-36: Admin users, AI monitoring, audit log viewer, system health page
- T-37: Billing domain — EntitlementService, SubscriptionService (auto and manual modes), PaymentGateway contract, FakePaymentGateway, `billing:simulate` command
- T-38: Midtrans gateway — Snap integration, webhook handler (signature + re-fetch + idempotent), pricing page, demo checkout
- T-39: Billing jobs — `billing:reconcile`, renewal/failure/reminder emails, subscription expiry, admin billing module
- T-40: Security pass — SecurityHeaders middleware (CSP with nonce, HSTS, nosniff, referrer policy, permissions policy, frame protection), FULLTEXT injection fix, Symfony packages updated, 30 new security tests
- T-41: Operations files — README.md, CHANGELOG.md, scripts/deploy.sh, GitHub Actions CI workflow, dependabot.yml, docs/GO_LIVE.md, .env.example completeness fix

---

*Entries above cover the initial LSP build. After go-live, record every release here with its date, changed files and a one-line summary of each change.*
