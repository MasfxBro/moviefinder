# TASKS

Ordered, ready-to-paste prompts for Copilot Chat. Do `docs/SETUP.md` first (Phase 0): every task assumes the project folder, packages, database and docs are already in place.

## How to run a task
1. Start a NEW chat for each task (history is resent on every message and burns quota).
2. Pick the combo listed on the task. Use agent mode.
3. Paste the prompt block exactly. Do not attach the whole project; the prompt names the docs to read.
4. The agent restates the task and the files, implements, runs Pint, Larastan, tests and the build, then replies with changed files, verification steps, assumptions and a commit message.
5. You check the result by hand (the "Done when" lines), then commit with the suggested message on branch `task/<number>-<name>`. The agent never commits.
6. If the agent stops to ask a question, that is the interaction-mode rule working. Answer it; do not push it to guess.
7. If a task needs a decision that is not in the docs, the agent must ask. Decide, update the docs first, then rerun the task.

## Combos (from your 9Router setup)
| Combo | Use for |
|---|---|
| `webplan` | splitting work, discussing doc changes |
| `webcoder` | hard logic: auth, AI pipeline, quota, billing, security-sensitive code |
| `webui` | pages and components from the design (can read images) |
| `webfast` | daily work: CRUD, small pages, fixes |
| `webtest` | tests, factories, seeders |
| `webreview` | confusing bugs, code review at checkpoints |
| `websec` | security and compliance audit at checkpoints |
| `webdocs` | README, legal page drafts, comments |
| `webquick` | short questions, commit messages |
| `webrescue` | only when everything else is out of quota; never for auth or payments |

## Priority and cut line (LSP window 2-5 November 2026)
There are 46 tasks. Must exist for the demo: Phases 1, 2 and 3 plus T-31, T-32, T-33, T-35, T-37, T-40, T-43 and T-44.
If time runs short, cut in this order (PROJECT_BRIEF.md section 6): T-38 and T-39 (real Midtrans), then T-34 and T-36, then T-30B (guided tour), then the polish of T-42. Never cut search, the AI pipeline, code quality or the security baseline.
Do the hard tasks (`webcoder`) early while every quota pool is full. Do T-43 (the real Gemini keys) when everything else is finished and at the latest 2 to 3 days before the LSP: the quality of the AI answers can only be measured with real keys, and weak answers need time to fix.

---

# Phase 1 - Foundation, catalog and browsing

### T-01 UI foundation (design tokens, layouts, base components)
Combo `webui` | Branch `task/01-ui-foundation` | Docs: DESIGN_SYSTEM.md sections 1-9 and 12, CODING_RULES.md section 4
```
Task T-01: UI foundation. Read DESIGN_SYSTEM.md (sections 1-9, 12) and CODING_RULES.md section 4 first.
First run `npm ls tailwindcss`. If the major version is 4, STOP and propose the DESIGN_SYSTEM.md section 9 edit before coding.
Otherwise: define the color tokens as CSS variables in resources/css/app.css and map them in tailwind.config.js; load Inter (400/500/600/700) from @fontsource/inter with font-display swap; make the theme dark only (remove Breeze's light/dark classes); create the layouts (app and guest) and the components navbar (logo, Home, Movies, TV Shows, slots for search, AI button and avatar; guest variant with Log in and Create account), footer, button, input, select, modal (focus trap, Esc), toast, skeleton, empty-state, plan-badge (PLUS and PRO), admin-badge. Add the "scene frame" corner-bracket motif as one reusable component or class.
No feature logic, no lorem ipsum, no hard-coded hex outside the token file. All user-facing text is formal, standard English without contractions (DESIGN_SYSTEM.md section 15). Add the CopyStyle test described in CODING_RULES.md section 10.
```
Done when: pages show at 360 and 1440 px with no horizontal scroll; focus ring visible; `npm run build` passes.

### T-02 Error pages and offline banner
Combo `webfast` | `task/02-error-pages` | Docs: FEATURES.md F-24, DESIGN_SYSTEM.md section 11, ARCHITECTURE.md section 8
```
Task T-02: error pages and offline handling (F-24). Read FEATURES.md F-24, DESIGN_SYSTEM.md section 11 and ARCHITECTURE.md section 8.
Create the error-page component and views 404, 403, 419, 429, 500, 503 in resources/views/errors/ using the exact titles, messages and actions in the section 11 table. The views must not run queries or read the session. A 500 shows a short request id that is also written to the log. Add the offline-banner component with offlineNotice.js (navigator.onLine plus online/offline events, "Back online" toast). Add a reusable inline "request failed" message with a Retry button for AJAX components.
Write Pest tests: each status returns its own page and HTTP code; no stack trace; the text "ADMIN_PATH" and the real admin path never appear.
```
Done when: a made-up URL shows the branded 404; the offline banner appears when you switch the browser offline.

### T-03 Config, settings service and enums
Combo `webcoder` | `task/03-config-settings` | Docs: ARCHITECTURE.md sections 5-7, DATABASE.md `settings`, CODING_RULES.md sections 2 and 13
```
Task T-03: configuration and settings. Read ARCHITECTURE.md sections 5-7, DATABASE.md (settings table) and CODING_RULES.md sections 2 and 13.
Create config/features.php, ai.php, billing.php, catalog.php, clips.php and plans.php (the plan table from FEATURES.md F-11) with the keys and defaults named in the docs, including ai.cost.*, ai.budget.*, billing.methods.* and billing.fee.* (no magic numbers anywhere else; fill the model and fee prices from the provider and Midtrans price pages on the day you run this task); add `build` (from a BUILD_ID file, "dev" if missing), `update_poll_minutes` (5) and `business_timezone` (from BUSINESS_TIMEZONE, Asia/Jakarta) to config/app.php; add ai.title_reply_max (600). Create the `settings` table migration and a Settings service (DB value over config default, cached 60 seconds, targeted invalidation on save). Create the enums listed in ARCHITECTURE.md section 2 (Role, Plan, MediaType, SubscriptionStatus, PaymentStatus, ChannelTrust, RequestStatus, AiIntent).
Tests: Settings read order, cache expiry and invalidation.
```
Done when: tests pass; no config key is unused.

### T-04 Auth, roles and the guest/auth route split
Combo `webcoder` | `task/04-auth` | Docs: FEATURES.md F-05 F-06, SECURITY.md sections 1-2 and 6, DECISIONS.md D-17 D-35
```
Task T-04: authentication and access. Read FEATURES.md F-05, F-06 and the Guest rule, SECURITY.md sections 1, 2 and 6, DECISIONS.md D-17 and D-35.
Adapt the Breeze Blade auth: register with name, email, password, a required "I am 18 or older" checkbox (store age_confirmed_at) and Terms/Privacy consent; password min 10 chars with Password::uncompromised(); email verification (basic; the branded email flow is T-04B); throttles from SECURITY.md section 6; session regeneration; logout. Add users.role (Role enum, default user) and users.suspended_at with middleware that blocks suspended users on every request. Add User::isAdmin() and User::plan() (returns Plan::Free for now; T-17 and T-37 add the limits and the entitlements). Split routes into a public group (landing, pricing, legal, auth, /version) and an `auth` group for everything else; guests are redirected to /login and returned afterwards. The public /login rejects admin accounts with the generic invalid-credentials message.
Tests: policies per role, suspended user blocked, unverified user cannot reach verified-only routes, guests redirected from protected URLs, throttling.
```
Done when: you can register, verify (log mailer), log in, and a logged-out visit to /browse redirects to login.

### T-04B Email verification flow and account hygiene
Combo `webcoder` | `task/04b-email-verification` | Docs: FEATURES.md F-05, DECISIONS.md D-51, INTEGRATIONS.md section 5, SECURITY.md sections 1 and 6, ARCHITECTURE.md section 3.8, SETUP.md (Mailpit)
```
Task T-04B: email verification and account hygiene. Read FEATURES.md F-05, DECISIONS.md D-51, INTEGRATIONS.md section 5, SECURITY.md sections 1 and 6, ARCHITECTURE.md section 3.8.
Build the full flow on top of T-04: a branded VerifyEmail notification (subject, body, button and footer exactly as in INTEGRATIONS.md section 5; signed URL valid 60 minutes; queued); the /verify-email page ("Please verify your email address. We have sent a link to {email}.") with a Resend button throttled to 3 per hour per user and 5 per hour per IP; a clear message for an expired or invalid link, with Resend; the rule that the link verifies the account and keeps the session in the same browser but asks for login first in another browser (never an automatic login); re-verification when the email address is changed (clear email_verified_at and send a new link); the setting auth.unverified_ttl_days (14) and the command `users:prune-unverified` with its schedule (delete only unverified accounts older than the limit). Style the reset-password email the same way. Use Mailpit (MAIL_MAILER=smtp) for manual checks. All text in formal English without contractions.
Tests: verification in the same browser, a link opened in a second browser requires login, expired link, resend throttling, email change requires re-verification, pruning removes only old unverified accounts, no tracking pixel in any email.
```
Done when: you register, receive the email in Mailpit at http://localhost:8025, click the button and arrive verified.

### T-05 Landing page and home routing
Combo `webui` | `task/05-landing` | Docs: FEATURES.md F-22 and F-01, ARCHITECTURE.md section 3.4, DESIGN_SYSTEM.md sections 5 and 12
```
Task T-05: landing page and `/` routing. Read FEATURES.md F-22 and F-01, ARCHITECTURE.md section 3.4, DESIGN_SYSTEM.md sections 5 and 12.
HomeController serves the landing page to guests and the (for now empty-state) catalog home to logged-in users. Build the landing sections in the order given in F-22 with components landing-hero, step-card, plan-compare (three columns Free, Plus, Pro; values from Settings; payment-method labels), a short FAQ, the decorative poster strip (hidden while the catalog is empty, cached, not linked) and the footer. Write real product copy in English, in the voice described in DESIGN_SYSTEM.md section 12. Every call to action goes to /register or /login.
Tests: guest sees the landing, logged-in user does not; strip hidden with empty catalog; no catalog data in the guest HTML besides the strip.
```
Done when: the landing page is understandable in 5 seconds and passes the 360-1440 px check.

### T-06 Legal and attribution pages
Combo `webdocs` | `task/06-legal-pages` | Docs: FEATURES.md F-18, COMPLIANCE.md sections 2-7, INTEGRATIONS.md sections 1 and 6
```
Task T-06: legal and attribution pages. Read FEATURES.md F-18, COMPLIANCE.md sections 2-7 and INTEGRATIONS.md sections 1 and 6.
Create /terms, /privacy, /copyright and /about as Blade pages with plain-language English drafts that cover every required point in COMPLIANCE.md sections 4, 5 and 7 (including the YouTube clause and the AI/Gemini notice), the exact TMDB sentence and logo placement rules, the JustWatch line, and the footer links (including YouTube Terms and Google Privacy Policy). Contact and copyright emails come from Settings. Add a note at the top of each draft in a code comment that a qualified person must review it before any commercial launch.
Tests: pages exist and are public; the exact attribution sentences are present.
```
Done when: footer links work on every page, including the landing page.

### T-07 Catalog tables and models
Combo `webcoder` | `task/07-catalog-tables` | Docs: DATABASE.md (titles, genres, title_genre, title_people), CODING_RULES.md section 5
```
Task T-07: catalog schema. Read DATABASE.md (titles, genres, title_genre, title_people and the size budget) and CODING_RULES.md section 5.
Create the four migrations exactly as specified (indexes, FULLTEXT on title and original_title, unique constraints), models with casts and relations, factories, and GenreSeeder (TMDB genre ids and names, media_scope). Slug generation must be unique and stable. Adult rows can never be created (guard at model level).
Tests: factory sanity, unique slug, adult guard, FULLTEXT search returns a seeded title.
```
Done when: `php artisan migrate:fresh --seed` works on MySQL.

### T-08 TMDB client and mapper
Combo `webcoder` | `task/08-tmdb-client` | Docs: INTEGRATIONS.md section 1, ARCHITECTURE.md sections 5 and 9, CODING_RULES.md section 6
```
Task T-08: TMDB integration. Read INTEGRATIONS.md section 1, ARCHITECTURE.md sections 5 and 9, CODING_RULES.md section 6. (TMDB_READ_TOKEN is in .env; never open or print .env.)
Create the MetadataProvider contract, TmdbClient (search movie/tv with include_adult=false, details with credits and videos, trending/popular/top_rated, genres, watch providers for region ID), TmdbMapper to TitleData (max 3 directors/creators, 8 cast, drop adult), the 10 req/s limiter, the 8 s timeout with limited retry, and the cache TTLs from ARCHITECTURE.md section 5. Failures map to typed exceptions, never raw errors.
Tests with Http::fake only: mapping, adult dropped, cache hit, timeout handled.
```
Done when: tests pass with no real network call.

### T-09 Title importer and catalog commands
Combo `webcoder` | `task/09-importer` | Docs: FEATURES.md F-02 (import), ARCHITECTURE.md sections 3.1 and 4, DATABASE.md "TMDB data age rule"
```
Task T-09: import and refresh. Read ARCHITECTURE.md sections 3.1 and 4 and DATABASE.md "TMDB data age rule".
Create TitleImporter::import(tmdbId, type) (idempotent, throttled 30/hour per IP and 200/hour global, stores people and genres, sets synced_at) and CatalogService. Add commands catalog:seed (popular titles), catalog:refresh --limit=100 (oldest synced_at first, skip locked_fields, mark is_hidden when TMDB returns 404) and register the schedule entries from ARCHITECTURE.md section 4.
Tests: import twice creates one row, adult refused, refresh respects locked_fields, 404 hides the title.
```
Done when: `php artisan catalog:seed` fills the catalog with a TMDB token.

### T-10 Poster cards, rows and logged-in home
Combo `webui` | `task/10-home-rows` | Docs: FEATURES.md F-01, DESIGN_SYSTEM.md sections 3, 5, 6 and 10
```
Task T-10: home rows. Read FEATURES.md F-01 and DESIGN_SYSTEM.md sections 3, 5, 6, 10.
Build poster-card (2:3 poster from the TMDB image CDN by path, lazy loading, title clamped to 2 lines, watchlist quick-action slot), title-row (horizontal scroll with arrow buttons on desktop and swipe on touch) and title-grid. Fill the logged-in home with rows Trending (cached daily from TMDB), Popular, Top Rated and per type from the local catalog. Add skeletons and the empty state. Genre chips show genre names only.
Tests: renders with an empty catalog; no N+1 (assert query count).
```
Done when: home looks like the reference mockup's grid, without its flaws (DESIGN_SYSTEM.md section 13).

### T-11 Browse and filters
Combo `webfast` | `task/11-browse-filters` | Docs: FEATURES.md F-04, ARCHITECTURE.md section 3.1, DESIGN_SYSTEM.md section 10
```
Task T-11: browse page and filters. Read FEATURES.md F-04 and DESIGN_SYSTEM.md section 10.
Create TitleFilter (type, multi-genre, year range, director text min 3 chars over title_people, minimum rating, sort) with a FormRequest, a paginated grid at /browse (navbar Movies and TV Shows link here with the type preset), removable filter chips, a mobile bottom-sheet drawer, and URLs that stay shareable. Empty result shows the not-found state.
Tests: filters combine, invalid values rejected, pagination keeps filters.
```
Done when: filters work together on mobile and desktop.

### T-12 Search backend
Combo `webcoder` | `task/12-search-backend` | Docs: FEATURES.md F-03 and F-15 (search_misses), SECURITY.md sections 3 and 6, DATABASE.md `search_misses`
```
Task T-12: search backend. Read FEATURES.md F-03 and F-15, SECURITY.md sections 3 and 6, DATABASE.md (search_misses).
Create TitleSearch (prefix plus FULLTEXT over title and original_title, LIKE terms escaped), TMDB fallback when local results are empty (cached 24 h, results marked "not in our catalog yet", opening one triggers the importer), the 60/min per IP limiter, and the aggregated search_misses counter (migration included, no user id) when nothing is found anywhere. JSON resource for the live search.
Tests: local hit, TMDB fallback, adult excluded, escaped LIKE, miss counter increments, rate limit.
```
Done when: tests pass; a search for a made-up title increments one miss row.

### T-13 Live search UI
Combo `webui` | `task/13-live-search` | Docs: FEATURES.md F-03, DESIGN_SYSTEM.md section 10
```
Task T-13: live search. Read FEATURES.md F-03 and DESIGN_SYSTEM.md section 10.
Add liveSearch.js (Alpine): 300 ms debounce, min 2 chars, dropdown with highlighted matches (server-escaped), arrow/Enter/Esc keyboard navigation, "/" focuses the search, recent searches slot for later, loading and failed-request states with retry. Wire it into the navbar and /search.
Tests: the /search page renders results and the not-found state with the request button placeholder.
```
Done when: keyboard-only search works.

### T-14 Title detail page
Combo `webui` | `task/14-title-detail` | Docs: FEATURES.md F-02, DESIGN_SYSTEM.md sections 5 and 10
```
Task T-14: title detail. Read FEATURES.md F-02 and DESIGN_SYSTEM.md sections 5 and 10.
Create /titles/{slug} with detail-hero, meta-chips, cast-list, expandable synopsis, sticky action bar on mobile, and slots reserved for the trailer, where-to-watch, watchlist button and the title chat card. Missing fields are hidden, never shown as "null". Adult or hidden titles return the 404 page. Unknown TMDB-only results import once, then redirect.
Tests: adult 404, hidden 404, missing fields hidden, guest redirected to login.
```
Done when: detail pages look right from 360 to 1440 px.

### T-15 Trailer and where to watch
Combo `webfast` | `task/15-trailer-watch` | Docs: FEATURES.md F-02, INTEGRATIONS.md sections 1 and 6, COMPLIANCE.md section 2
```
Task T-15: trailer and where to watch. Read FEATURES.md F-02, INTEGRATIONS.md sections 1 and 6, COMPLIANCE.md section 2.
Add trailer-embed (click-to-load, youtube-nocookie domain, "Playing loads content from YouTube" notice, no autoplay) and where-to-watch (ID region, cached 24 h, provider names as text, "Where-to-watch data provided by JustWatch." line). No provider logos (D-39).
Tests: no iframe in the initial HTML; attribution present when providers are shown.
```
Done when: the trailer loads only after a click.

## Checkpoint 1 (after T-15)
Manual: register, browse, filter, search, open a detail page, trigger the error pages. Review: run `webreview` on the auth and importer code, `websec` on T-04 (prompt: "Audit the code from T-04 against SECURITY.md sections 1-2 and 6; list findings, change nothing"). Fix findings as small tasks.

---

# Phase 2 - AI search and clips

### T-16 LLM client, drivers, prompt builder and reply validator
Combo `webcoder` | `task/16-ai-foundations` | Docs: AI_BEHAVIOR.md sections 3-6 and 9, INTEGRATIONS.md section 2, ARCHITECTURE.md section 3.2, DECISIONS.md D-47 D-52
```
Task T-16: AI foundations. Read AI_BEHAVIOR.md sections 3-6 and 9, INTEGRATIONS.md section 2, ARCHITECTURE.md section 3.2, DECISIONS.md D-47 and D-52. There are no real keys yet: build and test everything against the fake driver, and never open or print .env.
Create the LlmClient contract; GeminiClient (structured JSON, 25 s timeout, one retry on 429/5xx, model names from config only, the key chosen by plan: GEMINI_API_KEY_FREE for Free users and GEMINI_API_KEY_PAID for Plus and Pro); FakeLlmClient with canned, plausible answers for every intent (find_title, find_scene, clarify, refuse, answer), selected when AI_DRIVER=fake; a demo banner component ("AI demo mode: the answers are examples only.") shown while the fake driver is active; DTOs (CandidateData, AiReply); PromptBuilder (global prompt and the title-mode variant exactly as in AI_BEHAVIOR.md; history and user text as delimited data); ReplyValidator (schema, caps including ai.title_reply_max in title mode, unknown fields dropped, one repair retry); and the command `php artisan ai:check`. With the real driver, ai:check prints which keys are set, the HTTP status of a tiny test call per key, the model names available, the rate limits it can see and whether a grounding request is accepted, WITHOUT ever printing a key; with the fake driver it says that the real check runs in task T-43.
Tests: invalid JSON repaired once then failed, caps enforced, hostile content in history does not change the system rules, no secrets or user emails in the prompt, the Free key is never used for Plus or Pro and the paid key never for Free, the fake driver never calls the network.
```
Done when: tests pass using only fakes and the chat flow can be demonstrated with the fake driver.

### T-17 Quota, plan limits, Free places and the AI budget guard
Combo `webcoder` | `task/17-quota-budget` | Docs: FEATURES.md F-11, AI_BEHAVIOR.md section 7, DATABASE.md (ai_usage, ai_usage_log, ai_budget, ai_free_pool, ai_free_places), DECISIONS.md D-44 D-45 D-47, ARCHITECTURE.md sections 3.6 and 3.7, SECURITY.md section 7
```
Task T-17: AI quota, plan limits, Free places and the AI budget guard. Read FEATURES.md F-11, AI_BEHAVIOR.md section 7, DATABASE.md (ai_usage, ai_usage_log, ai_budget, ai_free_pool, ai_free_places), DECISIONS.md D-44, D-45 and D-47, ARCHITECTURE.md sections 3.6 and 3.7, SECURITY.md section 7.
Create the migrations (ai_usage with smart_count; ai_usage_log with model_tier, grounded and cost_idr; ai_budget; ai_free_pool; ai_free_places) and the services:
- PlanCatalog: reads plan.<free|plus|pro>.* from Settings with the defaults from config/plans.php.
- QuotaService: atomic reserve, commit and release with weights (1 per message, 2 for an AI clip search), the Smart counter for Pro, the Asia/Jakarta day from config('app.business_timezone'), admin exempt from metering, the global daily cap.
- FreePlaceService (Free users only): places per day = ai.free.pool_daily_limit / plan.free.daily_units x ai.free.overbook_pct; released in waves from ai.free.waves (default 00:00, 08:00 and 16:00 WIB, shares 34, 33, 33); an atomic take (UPDATE ... WHERE taken < released); a user who already holds a place today never loses it and is never refused mid-conversation; when no place is available it returns the next wave time.
- AiCostCalculator: tokens x model prices x safety multiplier from settings ai.cost.*, plus the grounded-search fee.
- AiBudgetService (paid plans only): window = the active subscription period; adds cost; thresholds from settings (Smart off at 60 percent, AI web clip search off at 80 percent, AI paused at 100 percent); the per-period clip-search allowance.
- AiAccessState: one method that returns the user's current AI state for the screen (units left, Smart left, place held, places full with the next wave time, daily limit used with the reset time, allowance used with the renewal date), used by GET /ai/status in T-18.
Until T-37 exists, resolve every user as Free through a small PlanResolver interface that T-37 replaces. Add the usage:prune command and its schedule.
Tests: 20 parallel reservations with 5 left allow exactly 5 (MySQL); places never exceed the released number under parallel requests; a place holder is never refused; waves open at the right times; weighted clip search; errors release the reservation and record no cost; midnight WIB reset; budget thresholds change behavior exactly at 60, 80 and 100 percent; a price change in settings changes the cost; global cap handling.
```
Done when: the parallel tests are green on MySQL and no test can spend more than the budget or hand out more places than released.

### T-18 AI search service (global chat backend)
Combo `webcoder` | `task/18-ai-service` | Docs: AI_BEHAVIOR.md sections 1-4, 6-9 and 11, ARCHITECTURE.md sections 3.2, 3.6 and 3.7, FEATURES.md F-07 and F-11
```
Task T-18: AI search pipeline. Read AI_BEHAVIOR.md sections 1-4, 6-9 and 11, ARCHITECTURE.md sections 3.2, 3.6 and 3.7, FEATURES.md F-07 and F-11.
Create ConversationStore (cache key ai:conv:{userId}:{conversationId}, 2 h sliding, ownership check, turns kept by plan); AnswerCache (Free users only: normalized non-personal movie questions, 24 hours, never for text containing an email address, phone number or other personal data, no user id); ModelRouter (Pro uses AI_MODEL_SMART while Smart searches remain and the budget guard allows it; everyone else uses AI_MODEL_STANDARD; Free uses the cheapest Standard model and the Free key, Plus and Pro the paid key); CandidateCritic (Pro Smart searches only: compares each verified candidate's TMDB overview, year and cast with the user's clues, reorders the cards and writes what matches and what does not); AiSearchService implementing the 10-step pipeline for mode=global (verify every candidate through TmdbClient and import missing ones, drop unverifiable ones, "closest matches" when confidence is low, results capped by the plan); the wiring to FreePlaceService, QuotaService and AiBudgetService (take or check access first, reserve before, commit and record cost after); the EnsureVerifiedForAi middleware; POST /ai/messages (FormRequest, rate limit by plan 6/8/12 per minute, JSON resource that also returns the limits left); and GET /ai/status (the AiAccessState without consuming anything). Mode=title is wired in T-20.
Tests: unverified candidates dropped, quota not consumed on error, refusal path, ownership check, guests redirected, rate limit per plan, routing and key by plan, critic only on Pro Smart searches, a Free user cannot reach the paid key, answer cache never stores personal data, places full and allowance used responses, the fake driver never calls Google.
```
Done when: a fake-driver request returns verified title cards for each plan and GET /ai/status reflects every state.

### T-19 Global chat panel UI
Combo `webui` | `task/19-chat-panel` | Docs: AI_BEHAVIOR.md sections 8, 9 and 12, FEATURES.md F-07, F-10 and F-11, DESIGN_SYSTEM.md sections 5, 10, 12, 14 and 15
```
Task T-19: global chat UI. Read AI_BEHAVIOR.md sections 8, 9 and 12, FEATURES.md F-07, F-10 and F-11, DESIGN_SYSTEM.md sections 5, 10, 12, 14 and 15.
Build ai-nav-button (speech bubble with scene-frame corners, "AI Chat" label on desktop, aria-label on mobile), ai-chat-panel (popover on 1024px+, full-height sheet on mobile) hosting the ai-chat component, and aiChat.js: progress steps, typing indicator, cancel button, 30 s timeout message, quota meter ("{n} of {limit} AI searches left today", and for Pro the Smart searches left), suggested prompts, data notice, ai-title-card results, failed-request retry, exit-warning modal and beforeunload only when hasMessages. The conversation id lives in JS memory only. When the panel opens, call GET /ai/status and show the ai-status line BEFORE the user types: places full with the next wave time, daily limit used with the reset time, allowance used with the renewal date, or provider unavailable, each with the exact copy in AI_BEHAVIOR.md section 9. Build the upgrade-card component (soft accent style, "See plans" and "Maybe later", no countdown, never danger colors) and show it for Free and Plus in those states and as the clip teaser. Never show Free users the number of places left. Show the demo banner while AI_DRIVER=fake. All text in formal English without contractions.
Tests: panel markup for logged-in users only; every string matches AI_BEHAVIOR.md section 9; the upgrade card never uses danger styling; Free users never see the places count.
```
Done when: you can chat end to end with the fake driver and each unavailable state shows the right message and return time.

### T-20 Title chat card (detail page)
Combo `webcoder` | `task/20-title-chat` | Docs: FEATURES.md F-08, DECISIONS.md D-38 D-41, AI_BEHAVIOR.md sections 1, 5 and 6
```
Task T-20: title chat. Read FEATURES.md F-08, DECISIONS.md D-38 and D-41, AI_BEHAVIOR.md sections 1, 5, 6.
Add mode=title to AiSearchService: the client sends only the title id; title, year and overview are loaded from the DB; any question is answered (intent=answer) while all safety refusals stay; scene questions run ClipFinder (stub until T-21/T-22); same shared quota. Add the embedded variant of ai-chat as a card inside the detail layout (never full screen, own scroll, also on mobile) with the default prompt "What would you like to know about {title}?" (UI text, no AI call, no quota, not counted as a message for the exit warning). It shows the same ai-status and upgrade-card states as the global chat.
Tests: DB context used, client-supplied context ignored, off-topic answered, explicit/piracy/harm refused, title mode ignores candidates.
```
Done when: the card fits the detail layout at 360, 768 and 1440 px.

### T-21 ClipFinder base (no AI search yet)
Combo `webcoder` | `task/21-clipfinder-base` | Docs: INTEGRATIONS.md section 3, DATABASE.md (clip_links, channel_allowlist), SECURITY.md section 8
```
Task T-21: clip foundations. Read INTEGRATIONS.md section 3, DATABASE.md (clip_links, channel_allowlist) and SECURITY.md section 8.
Create the migrations and models, the ClipFinder contract, PlatformUrl (strict host allow-list, 11-char YouTube id regex, canonical rebuild), YoutubeOembed::check (fixed endpoint, 5 s timeout, non-200 is invalid), OembedValidator for the other platforms, ChannelPolicy, CuratedClipFinder, StoredClipFinder, SearchLinkFinder, ClipFinderChain, ChannelSeeder (placeholder rows only) and negative-result caching (24 h). No URL provided by a user or the model is ever fetched directly.
Tests with Http::fake: host allow-list, SSRF attempts rejected, oEmbed failure handling, channel policy outcomes, fallback link is never labeled verified.
```
Done when: the chain returns curated, stored or fallback results.

### T-22 AI clip search and relevance judge
Combo `webcoder` | `task/22-ai-clip-search` | Docs: INTEGRATIONS.md sections 2 and 3, AI_BEHAVIOR.md section 6
```
Task T-22: AI clip search. Read INTEGRATIONS.md sections 2 and 3 and AI_BEHAVIOR.md section 6.
Create AiSearchClipFinder (never for Free users; for Plus and Pro only when features.ai_clip_search, the grounded daily budget, the user's per-period clip-search allowance and the AI budget guard allow; candidates only from grounding metadata; redirects resolved at most once and only to allowed hosts) and ClipRelevanceJudge (keep score >= 0.6, judge 3 / 5 / 8 candidates for Free / Plus / Pro, store accepted links as source=resolver). Return the data needed to render Google's Search Suggestions chip exactly as provided. A URL the model typed from memory is dropped. Return at most the plan's number of clips to show. When the paid key is not configured yet, return the launch notice "AI clip search opens with the public launch." together with saved clips and the search link, never an error.
Tests with fakes: model-typed URL dropped, budget exhausted falls back, redirect to a disallowed host rejected, relevance threshold respected.
```
Done when: no unvalidated link can reach a response.

### T-23 Clip cards, preview player and report link
Combo `webui` | `task/23-clip-ui` | Docs: FEATURES.md F-09 and F-16, INTEGRATIONS.md section 3 (Preview player), DESIGN_SYSTEM.md section 10
```
Task T-23: clip UI. Read FEATURES.md F-09 and F-16, INTEGRATIONS.md section 3 "Preview player" and DESIGN_SYSTEM.md section 10.
Build clip-card (thumbnail, source label Official / Trusted / Unverified / External link, Preview and Watch full video buttons), preview-modal and previewPlayer.js exactly as specified (iframe only after click, youtube-nocookie, start/end = start+60, destroy() at the limit with the "Preview ended - continue on YouTube" overlay, getDuration handling, no autoplay, Esc closes and focus returns). Add the report-link flow (FormRequest, 10/day per user, link status becomes reported). Show the "No clip found" state with the Search on YouTube link and Where to watch.
Tests: reported links are not embedded; embeds only for official/trusted; report limit.
```
Done when: a preview stops at 60 seconds in a real browser test.

### T-24 AI quality and hostile-content tests
Combo `webtest` | `task/24-ai-tests` | Docs: AI_BEHAVIOR.md sections 10-11, SECURITY.md section 9
```
Task T-24: AI tests. Read AI_BEHAVIOR.md sections 10 and 11 and SECURITY.md section 9.
Add the missing tests from the AI_BEHAVIOR.md section 10 list, hostile-content tests with a fake LLM (prompt injection in user text and in history, attempts to output URLs or HTML), and tests/Fixtures/ai_golden.json with about 30 Indonesian, English and mixed queries plus acceptable titles. Add the non-CI command `php artisan ai:evaluate` (non-production only) that reports the top-3 hit rate.
```
Done when: the suite is green; `ai:evaluate` runs with real keys on your machine.

## Checkpoint 2 (after T-24)
Manual: ask the global chat for a title from a vague description, ask for a scene, open a detail page and use the title chat, trigger quota exhaustion, switch the AI flag off. Review: `websec` on T-16 to T-23 ("Audit against SECURITY.md sections 3, 7, 8, 9 and AI_BEHAVIOR.md section 6; list findings, change nothing"), `webreview` on the pipeline.

---

# Phase 3 - User features

### T-25 Watchlist
Combo `webfast` | `task/25-watchlist` | Docs: FEATURES.md F-13, DESIGN_SYSTEM.md section 10, SECURITY.md section 2
```
Task T-25: watchlist. Read FEATURES.md F-13, DESIGN_SYSTEM.md section 10 and SECURITY.md section 2.
Migration (unique user/title), Policy, optimistic toggle with toast and undo on cards and the detail page, /watchlist page, plan-based cap (Free 100, Plus 300, Pro 500), verified users only.
Tests: IDOR (user A cannot touch user B), cap, uniqueness.
```
Done when: toggling works with rollback on failure.

### T-26 Search history
Combo `webfast` | `task/26-history` | Docs: FEATURES.md F-14, DECISIONS.md D-23
```
Task T-26: search history. Read FEATURES.md F-14 and DECISIONS.md D-23.
Store normal searches and the first AI prompt of each chat (truncated to 200 chars), /history page with Clear all, recent searches in the live search dropdown, history:prune (90 days) and its schedule.
Tests: only the owner can read, prune works, AI conversation content is never stored.
```
Done when: history shows and clears.

### T-27 Not found and title requests
Combo `webfast` | `task/27-requests` | Docs: FEATURES.md F-15, DATABASE.md `title_requests`, SECURITY.md section 6
```
Task T-27: not found and requests. Read FEATURES.md F-15, DATABASE.md (title_requests) and SECURITY.md section 6.
Show "We could not find that title yet" with the Request button (prefilled), /requests/create and /requests, FormRequest, per-plan daily limit (Free 2, Plus 5, Pro 10), duplicate block by title+year, status display, RequestResolved notification (log mailer). Guests are redirected to login and back.
Tests: limit, duplicates, IDOR, prefill.
```
Done when: the full request loop works up to the admin queue (built in T-33).

### T-28 Account page
Combo `webcoder` | `task/28-account` | Docs: FEATURES.md F-19 and F-06, COMPLIANCE.md section 5
```
Task T-28: account. Read FEATURES.md F-19 and F-06 and COMPLIANCE.md section 5.
/account with name and password change (logout everywhere on password change), plan and quota meter, PRO badge, and account deletion with password confirmation that removes personal data and keeps anonymized payment records (user_id nulled). The subscription block shows the plan, the billing mode, the renewal or paid-until date, the Smart searches left and the AI allowance used (percent), with a placeholder for upgrade, downgrade and cancel until billing exists.
Tests: deletion removes personal data, password confirmation required, sessions invalidated.
```
Done when: deleting a test account leaves no personal data.

### T-29 Feedback, announcements and feature-off messages
Combo `webfast` | `task/29-feedback-announce` | Docs: FEATURES.md F-20 and F-21, DATABASE.md (feedback, announcements)
```
Task T-29: feedback and announcements. Read FEATURES.md F-20 and F-21 and DATABASE.md (feedback, announcements).
Create the migrations, /feedback form (type, message 10-1000, rating, same-origin page path, honeypot, 3/day, verified users), the announcement banner component (60 s cache, time window), and the "under maintenance" replacement shown when a feature flag is off (AI, requests, feedback, registration), plus a reusable "available at public launch" notice component for features that need paid resources and are not live yet (D-49).
Tests: rate limit, honeypot, plain text escaped, banner window, flag off shows the friendly message.
```
Done when: a feature toggle takes effect within 60 seconds.

### T-30 New-version toast
Combo `webfast` | `task/30-update-toast` | Docs: FEATURES.md F-23, ARCHITECTURE.md section 3.5, OPERATIONS.md section 7
```
Task T-30: new-version notice. Read FEATURES.md F-23, ARCHITECTURE.md section 3.5, OPERATIONS.md section 7.
GET /version (JSON build id, no-store, 30/min per IP), the build meta tag in the layout, update-toast component and updateNotice.js (check on visibilitychange and every update_poll_minutes; [Update] reloads, with the exit-warning modal first if the chat has messages; [Later] hides for an hour or until the id changes again).
Tests: endpoint output and headers, throttle, meta tag present.
```
Done when: changing the BUILD_ID file makes an open tab show the toast.

### T-30B First-time guided tour
Combo `webui` | `task/30b-guided-tour` | Docs: FEATURES.md F-25, DECISIONS.md D-50, ARCHITECTURE.md section 3.9, DESIGN_SYSTEM.md sections 5 and 15, DATABASE.md (users)
```
Task T-30B: first-time guided tour. Read FEATURES.md F-25, DECISIONS.md D-50, ARCHITECTURE.md section 3.9, DESIGN_SYSTEM.md sections 5 and 15 and DATABASE.md (users).
Add the users columns onboarding_seen_at and onboarding_outcome (migration); OnboardingService (shouldOffer for verified, non-admin users whose onboarding_seen_at is null and when onboarding.enabled is true; set onboarding_seen_at the moment the offer is shown; record the outcome through POST /onboarding/outcome); the tour-prompt and tour-step components; and guidedTour.js (Alpine only, no library): a spotlight on elements marked data-tour, the step lists for Free, Plus and Pro exactly as in F-25 read from a server-rendered JSON block, Back, Next, a "3 of 6" marker, Skip and Esc, keyboard focus kept inside the tour, a bottom card on small screens, steps whose target is hidden are skipped, prefers-reduced-motion respected. Mark the navbar, search, navigation links, AI button, a poster card and the avatar menu with data-tour attributes. Add the "Take the tour again" link in /account (user-initiated only), the command `onboarding:reset {user}`, and the reset action that the admin users module (T-36) will call. All text in formal English without contractions.
Tests: the offer appears once after verification and never again after any choice (start, skip, reload); it is not offered to unverified users or admins; each plan variant has its steps; the replay link does not change onboarding_seen_at; reset works.
```
Done when: a fresh verified account sees the offer once, a second login does not show it, and the replay link works.

## Checkpoint 3 (after T-30): the product core exists
Manual: full user journey as guest, free user and (via tinker or the seeder) users with a manual Plus grant and a manual Pro grant (until billing exists in T-37, use the seeder or tinker with the PlanResolver). Run `php artisan test`, Pint, Larastan and the build. Review: `webreview` on T-25 to T-30 diffs.

---

# Phase 4 - Admin

### T-31 Admin foundation
Combo `webcoder` | `task/31-admin-foundation` | Docs: SECURITY.md section 1, FEATURES.md F-17 (access), ARCHITECTURE.md section 10, DECISIONS.md D-25
```
Task T-31: admin foundation. Read SECURITY.md section 1 (admin bullet), FEATURES.md F-17 access rules, ARCHITECTURE.md section 10, DECISIONS.md D-25.
Create the admin guard and login at /{ADMIN_PATH}/login (throttled 5/min, X-Robots-Tag noindex), HideAdminFromPublic (404 identical to the normal 404 for everyone except an authenticated admin), mandatory TOTP 2FA with encrypted secret and 8 hashed recovery codes (pragmarx/google2fa-laravel, bacon/qr-code), optional ADMIN_ALLOWED_IPS, new-IP login email, the audit_logs table and an AuditLogger service, and AdminSeeder (reads ADMIN_EMAIL, generates a random password shown once in the console, never hardcoded). Admin layout with the admin-badge.
Tests: guest/user/admin-without-2FA see 404, path never appears in HTML, public /login rejects admin accounts, audit rows written.
```
Done when: the admin area is reachable only through the secret path with 2FA.

### T-32 Admin dashboard and titles
Combo `webfast` | `task/32-admin-titles` | Docs: FEATURES.md F-17 modules 1-2, DATABASE.md (titles), SECURITY.md section 3 (uploads)
```
Task T-32: admin dashboard and titles. Read FEATURES.md F-17 modules 1 and 2, DATABASE.md (titles) and SECURITY.md section 3 (uploads).
Dashboard cards from existing data, and full titles CRUD: filter (source, hidden, stale), create manual title with poster upload (MIME by content, GD re-encode to WebP max 600 px and 300 KB, random name, original discarded), import from TMDB, edit with locked_fields, hide/unhide, delete with confirmation and dependency summary, refresh, bulk import by ids. Writes go through the same Services as the public app, invalidate only affected cache keys and write audit logs.
Tests: upload validation, locked_fields respected by refresh, audit rows, changes visible within 60 s.
```
Done when: you can add a manual title with a poster and see it on the site.

### T-33 Admin requests and missed searches
Combo `webfast` | `task/33-admin-requests` | Docs: FEATURES.md F-17 modules 3-4 and F-15
```
Task T-33: admin requests and missed searches. Read FEATURES.md F-17 modules 3 and 4 and F-15.
Requests queue with status filters (approve and import, create manually, reject with note, merge duplicates, user notified), and missed searches list with counts, one-click create request or import, mark ignored.
Tests: each action writes an audit log and notifies the user.
```
Done when: the request loop from T-27 closes end to end.

### T-34 Admin clips, reports and channels
Combo `webfast` | `task/34-admin-clips` | Docs: FEATURES.md F-17 modules 5-7 and F-16
```
Task T-34: admin clip moderation. Read FEATURES.md F-17 modules 5, 6, 7 and F-16.
Clip links CRUD with "verify now" and bulk remove by channel, reports queue (restore, remove, block channel, resolve with note), channel allow-list CRUD.
Tests: blocked channel never appears, restored link embeddable again, audit logs.
```
Done when: reporting a link and handling it works end to end. (Cut-line task.)

### T-35 Admin settings, announcements and feedback
Combo `webfast` | `task/35-admin-settings` | Docs: FEATURES.md F-17 modules 8, 12, 13, DATABASE.md `settings`
```
Task T-35: admin settings, announcements, feedback inbox. Read FEATURES.md F-17 modules 8, 12 and 13 and DATABASE.md (settings).
Settings editor for quotas, caps, grace days, price, feature toggles and contact emails (validated, audited, cache invalidated on save), announcements CRUD, feedback inbox with filters, internal note, status and optional email reply.
Tests: invalid values rejected, saves invalidate cache, feedback status changes audited.
```
Done when: changing a quota in the admin changes the chat limit within 60 seconds.

### T-36 Admin users, AI monitoring, audit log and system health
Combo `webfast` | `task/36-admin-ops` | Docs: FEATURES.md F-17 modules 9, 11, 14, 15, OPERATIONS.md section 11
```
Task T-36: admin users and monitoring. Read FEATURES.md F-17 modules 9, 11, 14, 15 and OPERATIONS.md section 11.
Users (search, summary, grant/revoke a plan with reason and expiry, suspend, force password reset, reset the guided tour (calls the T-30B reset), delete; no individual watchlist or history), AI monitoring (usage, errors, refusals, tokens, estimated cost against budget and revenue per plan, cap), read-only searchable audit log, and system health (scheduler heartbeat, AI key status (driver, which keys are configured, Free pool use against ai.free.pool_daily_limit), queue backlog and failed jobs, cache, dependency reachability, DB size, app version).
Tests: admin cannot read another user's watchlist or history; a manual plan grant requires reason and expiry.
```
Done when: the health page shows a live scheduler heartbeat. (Cut-line task.)

---

# Phase 5 - Subscription

### T-37 Entitlements, plans and billing simulation
Combo `webcoder` | `task/37-entitlements` | Docs: FEATURES.md F-12 and F-06, DATABASE.md (subscriptions, payments, webhook_events), ARCHITECTURE.md section 3.3, DECISIONS.md D-44 D-46
```
Task T-37: billing domain. Read FEATURES.md F-12 and F-06, DATABASE.md (subscriptions, payments, webhook_events), ARCHITECTURE.md section 3.3, DECISIONS.md D-44 and D-46.
Create the migrations (subscriptions with plan, billing_mode, payment_method, pending_plan; payments with plan, method, est_fee_idr; webhook_events), EntitlementService (plan resolution exactly as in DATABASE.md: highest plan wins, manual grants with reason and expiry), the real PlanResolver replacing T-17's, SubscriptionService with the state machine for both modes (auto: pending, active, past_due with 3-day grace, canceled, expired; manual: active until current_period_end, extended by 30 days per payment, then expired), the upgrade and downgrade rules (upgrade immediate with the old subscription stopped and no proration; downgrade through pending_plan at the next renewal), the PaymentGateway contract with a fake gateway, `billing:simulate {user} {renew|fail|cancel|expire} --mode --plan` (non-production only), and wire User::plan() and User::limits(). Add the plan-badge (PLUS, PRO) wherever badges appear.
Tests: every transition in both modes, upgrade, downgrade at renewal, manual extension stacking, grace expiry, highest plan wins, simulate blocked in production.
```
Done when: you can demo Plus and Pro with the simulate command and no Midtrans account.

### T-38 Midtrans gateway, webhook, pricing page and checkout
Combo `webcoder` | `task/38-midtrans` | Docs: INTEGRATIONS.md section 4, FEATURES.md F-12, SECURITY.md sections 6 and 7, COMPLIANCE.md section 6, DESIGN_SYSTEM.md section 14, DECISIONS.md D-46
```
Task T-38: Midtrans. Read INTEGRATIONS.md section 4, FEATURES.md F-12, SECURITY.md sections 6 and 7, COMPLIANCE.md section 6, DESIGN_SYSTEM.md section 14, DECISIONS.md D-46. (Midtrans keys are in .env; never open or print .env.)
Implement MidtransGateway for both modes (Snap with enabled_payments from billing.methods: auto-renew = card and GoPay tokenization plus the Subscription API; pay-for-30-days = one-time payment with the plan's allowed channels), WebhookHandler at POST /billing/webhook (signature check, ALSO re-fetch the status, unique event key, respond 200 fast, queue the work, no session or CSRF), POST /billing/checkout (server computes plan and amount from settings, rate limit 10/hour per user, upgrade warning shown before payment), /pricing with the three plan columns, the two payment modes (auto-renew promoted first, with the explicit consent box), accepted-method labels (with an "available at public launch" label for OVO, DANA, ShopeePay and LinkAja auto-debit), renewal and plan-change terms and the refund link, cancel and resume in /account, the demo banner while LAUNCH_MODE=demo, and the estimated fee recorded per payment from billing.fee.* settings.
Tests with Http::fake: replayed, tampered and duplicate webhooks, stale events ignored, the client cannot choose a price or plan, methods offered per plan (no bank transfer for Plus), upgrade stops the old subscription.
```
Done when: a sandbox payment activates the chosen plan in both modes. (Cut-line task.)

### T-39 Reminders, reconcile, receipts and billing admin
Combo `webcoder` | `task/39-billing-jobs` | Docs: ARCHITECTURE.md sections 3.3 and 4, FEATURES.md F-12 and F-17 (modules 1, 10, 11), INTEGRATIONS.md section 5, OPERATIONS.md section 17
```
Task T-39: billing jobs and admin. Read ARCHITECTURE.md sections 3.3 and 4, FEATURES.md F-12 and F-17 (modules 1, 10, 11), INTEGRATIONS.md section 5 and OPERATIONS.md section 17.
Add billing:reconcile (daily), the emails PaymentSucceeded (receipt), PaymentFailed (with the grace end date) and RenewalReminder (auto mode: before the charge; manual mode: before the end, with a pay link), queued with retry; reversion to Free after expiry; the admin subscriptions and payments module (list, filter, timeline, cancel on behalf, manual grants, CSV export); and the profit meter on the dashboard and in AI monitoring (revenue, estimated fees, estimated AI cost, margin per plan).
Tests: reminder timing in both modes, reconcile catches a missed webhook, expiry reverts the plan, CSV export content, profit meter arithmetic.
```
Done when: simulated renewal, failure and expiry send the right emails and the profit meter shows a margin. (Cut-line task.)

---

# Phase 6 - Hardening and demo

### T-40 Security pass
Combo `websec` | `task/40-security-pass` | Docs: SECURITY.md (all), COMPLIANCE.md section 8
```
Task T-40: security pass. Read SECURITY.md fully and COMPLIANCE.md section 8.
Implement the SecurityHeaders middleware (HSTS in production only, nosniff, referrer policy, permissions policy, frame protection, CSP with per-request script nonce and the allowed hosts, report-only in local), then walk the SECURITY.md section 13 checklist item by item, fix each failure with the smallest change, and add the missing [T] tests. Report the checklist with pass or fail per line.
```
Done when: every checklist line passes or has a written reason and a follow-up task.

### T-41 README, changelog, deploy script and CI
Combo `webdocs` | `task/41-ops-docs` | Docs: OPERATIONS.md sections 4-5 and 12, CODING_RULES.md section 7
```
Task T-41: operations files. Read OPERATIONS.md sections 4, 5, 12 and CODING_RULES.md section 7.
Write README.md (setup pointer to docs/SETUP.md, env, commands, architecture overview, tests, demo steps), CHANGELOG.md, scripts/deploy.sh following the release procedure (including writing BUILD_ID), a GitHub Actions workflow on PHP 8.5 with MySQL 8.4 and the Node LTS from SETUP.md (Pint, Larastan, Pest, build, composer audit, npm audit), .github/dependabot.yml (weekly: composer, npm, github-actions), an .env.example completeness check, and docs/GO_LIVE.md: a short runbook that turns the OPERATIONS.md section 18 table into exact steps for each item.
```
Done when: the CI workflow passes on a pushed branch.

### T-42 Responsive, accessibility and performance pass
Combo `webui` | `task/42-polish` | Docs: DESIGN_SYSTEM.md sections 7, 8, 10, 12 and 13, FEATURES.md cross-cutting
```
Task T-42: polish. Read DESIGN_SYSTEM.md sections 7, 8, 10, 12, 13 and the FEATURES.md cross-cutting requirements.
Audit every page at 360, 390, 768, 1024 and 1440 px, fix overflow, tap targets (44 px), text size (16 px inputs), focus and keyboard paths, contrast, layout shift and image dimensions; remove anything that looks generic against section 12. Run and fix the CopyStyle test (no contractions in user-facing text) and read every screen once for formal, clear English (DESIGN_SYSTEM.md section 15). Report Lighthouse mobile scores for home, browse and detail.
```
Done when: Lighthouse mobile is 85+ performance and 95+ accessibility on those pages. (Cut-line task.)

### T-43 Connect the real Gemini keys (guided)
Combo `webplan` (a guide; no code unless needed) | Branch `task/43-gemini-keys` (only if code changes) | Docs: INTEGRATIONS.md sections 2 and 8, DECISIONS.md D-47 D-52, SECURITY.md section 5, OPERATIONS.md sections 17-18
REMINDER: do this when every other task is finished and at the latest 2 to 3 days before the LSP. The AI answer quality can only be measured with real keys, and weak answers need time to fix.
```
Task T-43: connect the real Gemini keys, guided. Read INTEGRATIONS.md sections 2 and 8, DECISIONS.md D-47 and D-52, SECURITY.md section 5 and OPERATIONS.md sections 17 and 18. NEVER open, print or edit .env: I will make every .env change myself.
Guide me step by step in Indonesian, in short numbered steps that are easy to follow, complete and clear, and wait until I say that a step is done before you give the next one. Cover: (1) creating the FREE key in Google AI Studio inside a NEW project without billing, and checking on the API keys page that its billing tier is free and that the project has no billing account (I must never press "Set up billing" there); (2) putting the key in .env as GEMINI_API_KEY_FREE; (3) running `php artisan ai:check` with AI_DRIVER=gemini and explaining every line of its output to me, including the HTTP codes (200, 400, 403, 404, 429), the model names, the rate limits and whether grounding was accepted; (4) filling ai.free.pool_daily_limit in the admin settings and AI_MODEL_STANDARD and AI_MODEL_SMART with me, from the check results; (5) testing the Free chat end to end, including what happens when the places run out; (6) the PAID key: explain that it belongs in a separate project with billing and is created only after the first paying customer, what Google asks when billing is enabled (it may require prepaid credit; verify), how to set a budget alert, and how I put it in .env as GEMINI_API_KEY_PAID; until then AI web clip search shows the launch notice; (7) running `php artisan ai:evaluate` and reading the results with me; (8) switching AI_DRIVER to gemini permanently and confirming the demo banner is gone; (9) the final safety checks of SECURITY.md section 5. If Gemini turns out to be unworkable, stop and follow INTEGRATIONS.md section 8 instead of improvising.
```
Done when: ai:check is green for the free key, the Free chat works end to end, ai:evaluate has run and the admin System health shows the key status.

### T-44 Demo data and LSP rehearsal
Combo `webdocs` | `task/44-demo` | Docs: COMPLIANCE.md section 9, CODING_RULES.md section 9, PROJECT_BRIEF.md
```
Task T-44: demo preparation. Read COMPLIANCE.md section 9, CODING_RULES.md section 9 and PROJECT_BRIEF.md.
Create a DemoSeeder (fake users for each role and plan: admin, Free, Plus and Pro; a handful of requests and feedback; fresh verified and unverified users to demonstrate the email flow in Mailpit and the guided tour with `onboarding:reset`; no real personal data), a one-page demo script (steps and expected screens), and a "where to find it in the code" map for the certification units in CODING_RULES.md section 9 so the author can explain structure, naming, functions and libraries.
```
Done when: you can run the full demo from a fresh `migrate:fresh --seed` without surprises. (Cut-line task.)

## Final checkpoint
REMINDER: the real Gemini keys are not configured until T-43. If T-43 is not done yet, do it now (see its reminder).
Run `php artisan test`, Pint, Larastan, `npm run build`, `composer audit` and `npm audit`; walk OPERATIONS.md section 12 (smoke test) locally; confirm `LAUNCH_MODE=demo`, that the demo banner shows, and that `AI_DRIVER` is `gemini` with the real keys (or `fake` with its banner if T-43 is deliberately postponed). Read the go-live table in OPERATIONS.md section 18 so you know what is configuration and what is money.

## Coverage matrix (every feature and every decision maps to a task)
Rule: no decision without a task or a stated reason, and no feature without acceptance criteria. When a docs change adds a feature or a decision, add its row here in the same change.
### Features
| Feature | Tasks |
|---|---|
| F-01 | T-10 |
| F-02 | T-09, T-14, T-15 |
| F-03 | T-12, T-13 |
| F-04 | T-11 |
| F-05 | T-04, T-04B |
| F-06 | T-04, T-37 |
| F-07 | T-18, T-19 |
| F-08 | T-20 |
| F-09 | T-21, T-22, T-23 |
| F-10 | T-19, T-20 |
| F-11 | T-17, T-18, T-19 |
| F-12 | T-37, T-38, T-39 |
| F-13 | T-25 |
| F-14 | T-26 |
| F-15 | T-12, T-27, T-33 |
| F-16 | T-23, T-34 |
| F-17 | T-31, T-32, T-33, T-34, T-35, T-36, T-39 |
| F-18 | T-06 |
| F-19 | T-28, T-30B |
| F-20 | T-29, T-35 |
| F-21 | T-29, T-35 |
| F-22 | T-05 |
| F-23 | T-30 |
| F-24 | T-02 |
| F-25 | T-30B |
### Decisions
| Decision | Tasks or reason |
|---|---|
| D-01 | principle; enforced in T-21, T-23 and audited in T-40 |
| D-02 | Phase 0 (SETUP.md) |
| D-03 | T-04, T-37 |
| D-04 | T-17 |
| D-05 | T-07, T-09 |
| D-06 | principle; T-07 and T-09 |
| D-07 | T-21, T-22 |
| D-08 | T-23 |
| D-09 | T-21, T-34 |
| D-10 | T-18, T-19 |
| D-11 | T-12, T-27 |
| D-12 | T-37, T-38, T-39 |
| D-13 | launch gate (OPERATIONS.md section 18), no task |
| D-14 | T-01, T-42 |
| D-15 | T-01 |
| D-16 | T-31, T-40 |
| D-17 | T-04 |
| D-18 | open; hosting chosen after the LSP (OPERATIONS.md section 14) |
| D-19 | open; Mailpit now (SETUP.md), provider after the LSP |
| D-21 | open; name read from config (T-03) |
| D-22 | T-16, T-43 |
| D-23 | T-26, T-18 |
| D-24 | T-03, T-17 |
| D-25 | T-31 |
| D-26 | T-31 to T-36 |
| D-27 | T-32 |
| D-28 | T-29, T-35 |
| D-29 | T-30, T-41 |
| D-30 | every UI task, T-42 |
| D-31 | T-12, T-33 |
| D-32 | principle; Phase 0 and T-43 |
| D-33 | principle; T-23 |
| D-34 | Phase 0, T-07 |
| D-35 | T-04, T-05 |
| D-36 | T-30 |
| D-37 | T-02 |
| D-38 | T-19, T-20 |
| D-39 | not built (open item) |
| D-40 | T-01 (default identity) |
| D-41 | T-20 |
| D-42 | Phase 0 |
| D-43 | Phase 0, T-41 |
| D-44 | T-03, T-17 |
| D-45 | T-17, T-18 |
| D-46 | T-37, T-38, T-39 |
| D-47 | T-16, T-17, T-18, T-19, T-43 |
| D-48 | T-01, T-42 |
| D-49 | T-19, T-29, T-38 |
| D-50 | T-30B |
| D-51 | T-04B |
| D-52 | T-16, T-43 |
| D-53 | T-41 |

## Backlog after the LSP (not part of the 46 tasks; do not build without an approved change in DECISIONS.md)
- Xendit as a second gateway for auto-debit from OVO, DANA, ShopeePay and LinkAja (needs activation by Xendit and provider approval); a new driver behind `PaymentGateway`.
- Contextual hints on the title detail page (a second, separate tour).
- Deep Search mode (a button that uses the Smart model, more candidates and the critic step for 3 units).
- Email alerts when a title in the watchlist becomes available on a streaming provider in Indonesia.
- Personalized recommendations from the watchlist (needs a privacy notice).
- Custom named watchlists; annual plans with a discount.
- Provider logos in "Where to watch" and "More like this" from TMDB recommendations (D-39).
- A funded Free pool (a paid key with a fixed monthly budget for Free users) or another provider, if Free demand outgrows the free pool (INTEGRATIONS.md section 8).
- Convenience-store payments (Indomaret, Alfamart).
- Rejected, do not build: bringing your own key (BYOK, D-47).
