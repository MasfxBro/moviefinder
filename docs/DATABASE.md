# DATABASE

MySQL 8.4 LTS, `utf8mb4`, InnoDB. Money as integer IDR. Timestamps UTC (the framework timezone is UTC, D-24). Every foreign key is indexed; use `cascadeOnDelete` only where stated.

## Size budget (the DB must stay light)
- Target: 10,000 titles ≈ 30–40 MB total. No blobs, no images, no video, no subtitles/dialogue.
- Short-lived data goes to the cache store, not tables.
- Logs are pruned (see Retention).

## Tables

### users
id, name, email (unique), email_verified_at, password, role (enum admin|user, default user), age_confirmed_at, suspended_at (null), two_factor_secret (encrypted, null), two_factor_confirmed_at (null), onboarding_seen_at (timestamp, null: the guided tour was offered), onboarding_outcome (enum skipped|completed, null), remember_token, timestamps.
Index: email unique, role.

### titles
id, tmdb_id (null for manual titles), source (enum tmdb|manual, default tmdb), media_type (enum movie|tv), slug (unique), title, original_title (null), release_year (null), poster_path (null), backdrop_path (null), overview (text, null), tagline (null), runtime_minutes (null), seasons_count (null), episodes_count (null), original_language (null), vote_average (decimal 3,1 default 0), vote_count (int default 0), popularity (float default 0), trailer_youtube_key (null), adult (bool default false), is_hidden (bool default false), synced_at (timestamp, null for manual), locked_fields (json, null: fields edited by an admin, skipped by refresh), last_viewed_at (null), created_by (null), timestamps.
Indexes: unique(tmdb_id, media_type), unique(slug), FULLTEXT(title, original_title), index(release_year), index(media_type), index(popularity), index(vote_average), index(synced_at).
Rules: `adult` must be false; rows are never created for adult titles. `poster_path` is either a TMDB path (starts with `/`) or `local:posters/<random>.webp` for admin-uploaded posters of manual titles (re-encoded WebP, max 600 px wide, at most 300 KB, stored on disk, never in the DB).

### genres
id (= TMDB genre id), name, media_scope (enum movie|tv|both).

### title_genre
title_id, genre_id. PK(title_id, genre_id). Cascade on title delete.

### title_people
id, title_id, name, role (enum director|creator|cast), position (smallint), tmdb_person_id (null).
Indexes: (title_id, role), index(role, name). Keep at most 3 directors/creators and 8 cast per title.
Used by the director filter (`role in (director, creator)`, `name LIKE %term%`).

### subscriptions
id, user_id, plan (enum plus|pro), billing_mode (enum auto|manual), payment_method (varchar 30, null), pending_plan (enum plus|pro, null: a downgrade that starts at the next renewal), status (enum pending|active|past_due|canceled|expired), source (enum midtrans|manual), gateway_subscription_id (null, unique), current_period_start, current_period_end, cancel_at_period_end (bool), canceled_at (null), grace_ends_at (null), manual_reason (null), granted_by (null), timestamps.
Indexes: user_id, status, current_period_end.
No card numbers, no raw tokens beyond what the gateway requires; if a gateway token must be kept, store it encrypted (`encrypted` cast).
Plan resolution (EntitlementService): a subscription counts when `status in (active, past_due within grace) AND (current_period_end > now OR grace_ends_at > now)`, or when it is an active manual grant; its `plan` is the user's plan. If several count, the highest plan wins. With none, the user is Free. In manual billing mode there is no gateway subscription: `current_period_end` is the paid-until date.

### payments
id, user_id, subscription_id (null), gateway_order_id (unique), plan (enum plus|pro), method (varchar 30, null), amount (unsigned int), est_fee_idr (unsigned int, null: estimated gateway fee for the profit meter), currency (char 3 default IDR), status (enum pending|paid|failed|refunded|expired), paid_at (null), failure_reason (null), timestamps.

### webhook_events
id, provider, event_key (unique) = hash of provider + order/subscription id + status + timestamp, payload (json), processed_at (null), created_at. Prune payloads after 90 days.

### ai_usage
id, user_id, used_on (date, Asia/Jakarta day), count (unsigned smallint, weighted units), smart_count (unsigned smallint, Smart searches), timestamps. Unique(user_id, used_on).
Atomic increment with a weight w (1 for a normal message, 2 for an AI clip search): `UPDATE ai_usage SET count = count + w WHERE user_id=? AND used_on=? AND count + w <= ?` inside a transaction (insert row first with count 0 using `insertOrIgnore`). `smart_count` uses the same pattern against the plan's Smart limit.

### ai_usage_log
id, user_id, mode (enum global|title), title_id (null), model, model_tier (enum standard|smart), grounded (bool), input_tokens (null), output_tokens (null), cost_idr (unsigned int, estimated), status (enum ok|error|refused), created_at. No message content. Prune after 30 days.

### ai_free_pool
used_on (date, Asia/Jakarta, PK), taken (unsigned smallint, places taken today). Atomic take: `UPDATE ai_free_pool SET taken = taken + 1 WHERE used_on=? AND taken < ?` where the limit is the places released so far today (total places per day x the share of waves already open). Insert the day's row first with `insertOrIgnore`. Keep 14 days.

### ai_free_places
id, used_on (date), user_id, taken_at (timestamp). Unique(used_on, user_id): a user holds at most one place per day, and holding it guarantees the full daily units until midnight WIB. Keep 14 days.

### ai_budget
id, user_id, window_start (date, Asia/Jakarta), window_end (date), spent_idr (unsigned int), clip_searches (unsigned smallint), timestamps. Unique(user_id, window_start).
Paid plans only: the window is the user's active subscription period. Free users have no row here; they are rationed by daily places (D-47). `AiBudgetService` adds the estimated cost of every AI call to `spent_idr` and counts AI web clip searches in `clip_searches` (D-45). Keep 14 months.

### search_history
id, user_id, type (enum normal|ai), query (varchar 200), created_at. Index(user_id, created_at). Prune after 90 days.

### watchlists
id, user_id, title_id, created_at. Unique(user_id, title_id).

### title_requests
id, user_id, media_type (movie|tv), title_text (varchar 200), release_year (null), note (varchar 500, null), status (enum pending|approved|rejected|added), admin_note (null), resolved_title_id (null), timestamps. Index(status), index(user_id).

### clip_links
id, title_id, video_platform (enum youtube|other), video_id (null), url (varchar 500), channel_name (null), channel_id (null), label (null), trust (enum official|trusted|unverified), source (enum admin|resolver), scene_key (char 40, null; sha1 of normalized scene keywords), status (enum active|reported|removed), verified_at (null), created_by (null), timestamps.
Indexes: (title_id, status), (title_id, scene_key), unique(title_id, url).

### channel_allowlist
id, platform (youtube), channel_id (null), channel_name, trust (enum official|trusted|blocked), notes (null), timestamps. Index(channel_name), unique(platform, channel_id) where not null.

### link_reports
id, clip_link_id, user_id (null), reason (enum dead|wrong_scene|copyright|inappropriate), details (varchar 500, null), status (enum open|resolved), resolved_by (null), resolved_at (null), timestamps.

### audit_logs
id, actor_id, action (varchar 80), subject_type (null), subject_id (null), meta (json, null), ip_hash (char 64, null), created_at. Index(actor_id, created_at). Keep 1 year.

### settings
key (PK), value (json), updated_by (null), updated_at. Keys: `plan.<free|plus|pro>.*` (limits and prices, see FEATURES.md F-11), `ai.cost.*` (model prices in IDR per million tokens, grounded-search fee, safety multiplier), `ai.budget.smart_off_pct`, `ai.budget.grounded_off_pct`, `ai.free.pool_daily_limit` (the Google free-tier daily request limit, from `ai:check`), `ai.free.overbook_pct` (default 100), `ai.free.waves` (default 00:00, 08:00, 16:00 WIB with shares 34, 33, 33), `auth.unverified_ttl_days` (default 14), `onboarding.enabled` (default true), `ai.global_daily_cap`, `clips.grounded_daily_budget`, `billing.grace_days`, `billing.methods.*` (allowed payment channels per plan and mode), `billing.fee.*` (gateway fee estimates), `features.ai_enabled`, `features.ai_clip_search`, `features.registration_open`, `features.requests_open`, `features.feedback_open`, `features.payments_live`, `site.contact_email`, `site.copyright_email`. Settings are cached for 60 seconds and the cache entry is invalidated on save.

### feedback
id, user_id (null, set null on user delete), type (enum bug|suggestion|content|praise|other), message (varchar 1000), rating (tinyint null, 1-5), page_path (varchar 200, null), status (enum new|read|in_progress|done|archived), admin_note (varchar 1000, null), handled_by (null), timestamps. Index(status, created_at).

### announcements
id, kind (enum info|warning|maintenance), message (varchar 300, plain text), starts_at, ends_at, is_active (bool), created_by, timestamps. Index(is_active, starts_at, ends_at). Read through a 60-second cache.

### search_misses
id, query_norm (varchar 120, unique; lowercased, trimmed), hits (unsigned int), last_seen_at, status (enum open|requested|added|ignored). No user ids (privacy). Prune rows with hits < 2 older than 90 days.

### Laravel defaults
`sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`, `password_reset_tokens`.

## Retention summary
| Data | Keep |
|---|---|
| ai_usage_log | 30 days |
| ai_budget | 14 months |
| ai_free_pool, ai_free_places | 14 days |
| unverified users | deleted after 14 days (`auth.unverified_ttl_days`) |
| ai_usage | 14 days (only today matters) |
| search_history | 90 days |
| webhook_events payload | 90 days |
| audit_logs | 1 year |
| feedback | 1 year after status `done`/`archived` |
| search_misses | aggregated; prune low-count rows after 90 days |
| titles (TMDB-derived fields) | refreshed so `synced_at` is never older than 150 days |
| payments | as required for accounting; anonymized (user_id nulled) when an account is deleted |

## TMDB data age rule
This rule applies to rows with `source=tmdb`; manual titles are our own data. A third-party summary of the TMDB terms says cached TMDB data must not be kept longer than six months. Treat this as binding until verified against the current TMDB terms. `catalog:refresh` refreshes the oldest rows daily; if TMDB returns 404 for a title, mark it `is_hidden = true` and flag it in admin.

## Seeders
GenreSeeder (TMDB genres), ChannelSeeder (placeholder rows the owner fills with real channel IDs), AdminSeeder (reads admin email from `.env`, never a hardcoded password).
