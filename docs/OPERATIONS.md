# OPERATIONS (continuity, releases, maintenance)

Goal: the people using the site never have to wait for, or notice, an update. Admin changes go live instantly. Programmer releases cause no downtime. Maintenance is announced, narrow, and rarely needs the whole site offline.

## 1. What users must never feel
- Being logged out because of a deploy.
- Losing an AI chat in progress because of a deploy.
- A broken page or missing CSS/JS in a tab that was open during a deploy.
- An error page because a third-party service is down.
- Being forced to update: nothing reloads by itself. A tab that stays open only shows a non-blocking "new version available" toast, and the user picks when to update (F-23).

## 2. Changes that need NO deploy (admin-driven)
Titles (create, edit, hide, import, delete), requests, clip links, channels, feedback handling, announcements, quotas and caps, grace days, price (demo), feature toggles, contact emails. These write to the DB and invalidate only the affected cache keys; users see them within 60 seconds, with no update notice. Anything a non-programmer owner might want to change should live in settings/DB, not in code.

## 3. Environments
| Env | Purpose | Notes |
|---|---|---|
| local | development | sandbox keys, Mailtrap |
| staging | final test of every release | production-like server, sandbox payments, separate DB, never real user data |
| production | users | real keys (when launch gates pass), `APP_DEBUG=false` |
Every release is deployed to staging first and checked with the smoke test list (section 12).

## 4. Release layout (atomic, symlink swap)
```
/var/www/moviefinder/
  releases/20261020-1530/      one folder per release (code, vendor, built assets, BUILD_ID)
  releases/...                 keep the last 5
  shared/
    .env                       one copy, symlinked into each release
    storage/                   logs, uploads (posters), framework cache files
  current -> releases/<latest> web server document root = current/public
```
Sessions, cache and queue use shared stores (database or Redis), never files inside a release.
`APP_KEY` never changes between releases (otherwise sessions and encrypted data break).

## 5. Release procedure (scripted in `scripts/deploy.sh`)
1. CI (GitHub Actions) on every push: Pint, Larastan, Pest, `npm run build`, `composer audit`, `npm audit`. Deploy only from a green main branch with a tag.
2. Back up the database (snapshot or `mysqldump`) before migrating.
3. Create the new release folder; `composer install --no-dev --optimize-autoloader`; copy the pre-built frontend assets; link `shared/.env` and `shared/storage`. Write the release id (for example `20261020-1530`) into the release's `BUILD_ID` file; `/version` and the update toast read it.
4. Run `php artisan migrate --force` (backward-compatible migrations only, section 6).
5. Warm caches for the new release: `config:cache`, `route:cache`, `view:cache`, `event:cache`.
6. Health check the new release (`/up` plus a smoke request) BEFORE switching.
7. Switch `current` to the new release (atomic symlink swap), then graceful reload of PHP-FPM (`reload`, never `restart`) so in-flight requests finish.
8. `php artisan queue:restart` (workers finish their current job and restart on the new code).
9. Post-deploy smoke test; watch logs and error rate for 15 minutes.
10. Update `CHANGELOG.md` and tag the release.
Rollback: repoint `current` to the previous release, reload PHP-FPM, `queue:restart`. Do not roll back migrations on production; fix forward (this is why migrations are backward-compatible). After a rollback the build id changes again, so open tabs see the update toast once more; that is expected.

## 6. Database changes without downtime (expand / contract)
- Release N (expand): add new nullable column/table/index; code writes both old and new when needed.
- Backfill with an Artisan command or job in small chunks (no long locks), outside peak hours.
- Release N+1 (switch): code reads the new column.
- Release N+2 (contract): remove the old column.
Rules: never drop or rename in the same release that stops using it; add indexes online (check the engine supports it); test every migration on a copy of production-size data; keep each migration small and single-purpose.

## 7. Assets, open tabs and the update notice
Vite builds hashed file names. New builds are ADDED to the shared assets folder; old files stay for 14 days so a tab opened before the deploy still finds its CSS/JS. Hashed assets are served with long immutable cache headers; HTML pages use short or no cache.
The `/version` endpoint (ARCHITECTURE.md section 3.5) lets an open tab notice a new release and show the update toast (FEATURES.md F-23). Without clicking [Update], a tab keeps working on its old assets and on endpoints that stay compatible; any normal navigation loads the new HTML. Because of that, AJAX/JSON endpoints stay compatible with the previous release's JavaScript for at least 14 days (CODING_RULES.md section 13). `/version` must be served uncached (`Cache-Control: no-store`).

## 8. Third-party failures (graceful degradation)
| Failure | Behavior |
|---|---|
| TMDB down | catalog served from local DB; search falls back to local only; lazy import/refresh paused with a notice |
| Gemini down or capped | AI shows "AI search is temporarily unavailable"; normal search keeps working; quota not consumed |
| Google grounding budget used | clip search falls back to curated/stored clips and the search link |
| YouTube oEmbed failing | stored clips kept; new candidates not embedded; link-out only |
| Midtrans down | subscribe button shows a retry message; existing paid plans stay active; reconcile job catches up |
| Email provider down | emails stay queued and retry |
| Queue worker stopped | admin health page shows backlog; web keeps working |
Each dependency has a flag in settings so the admin can switch it off cleanly during an incident.

## 9. Maintenance policy
- Prefer feature-level maintenance: toggle the single feature off (AI, payments, registration) and show a friendly message. The rest of the site stays up.
- Planned work: create an announcement (type `maintenance`) at least 24 hours ahead with start/end times.
- Full-site maintenance mode is a last resort: `php artisan down --retry=60 --refresh=15 --secret=<token>` (the secret lets the team test while users see the 503 maintenance page from DESIGN_SYSTEM.md section 11). Bring it back with `php artisan up`. Keep it as short as possible and outside peak hours.
- Unplanned incidents: post an announcement banner, fix forward or roll back, then write a short post-mortem.

## 10. Backups and recovery
- Daily automatic DB backup, 14 days retention, stored off the server; plus a backup before every migration.
- Uploads (`storage/app/public`) are included in backups.
- Restore test once before launch and every quarter. Targets: lose at most 24 h of data, restore within 2 h.
- `.env` copy kept in the owner's password manager (never in git).

## 11. Monitoring
- Uptime monitor on `/up` (free tier is enough) with email alerts.
- Error tracking optional (data scrubbed).
- Admin "System health": scheduler last run (heartbeat written by `schedule:run`), queue backlog and failed jobs, cache status, TMDB/Gemini/Midtrans reachability, DB size, disk free, app version.
- Log rotation; disk alert at 80%.
- Webhooks: Midtrans retries if we are unavailable; the endpoint stays up during releases and processing is idempotent.

## 12. Smoke test after every release
Home loads; a logged-out visit to `/browse` redirects to login and the landing page shows at `/`; search returns results; a detail page opens; login works; AI search answers (staging: with fake or low-cost call); preview modal opens; subscribe page loads; admin login page reachable only on the secret path; `/version` returns the new build id; `/up` returns OK; a made-up URL shows the branded 404; a new account receives the verification email (Mailpit) and is offered the tour once; no errors in logs.

## 13. Responsibilities
| Who | Does |
|---|---|
| Admin (owner) | content, requests, feedback, reports, announcements, settings, user support |
| Programmer | code, migrations, releases, security updates, backups check, incident response |
Both record changes: admin actions in the audit log, programmer changes in `CHANGELOG.md` and git tags.

## 14. Hosting (decision D-18) and the free-cost reality
Principle (D-32): Rp0. Facts below are from public sources as of October 2026; free tiers change often, so verify before relying on them.

What the app needs: PHP 8.5, MySQL 8.4 LTS, HTTPS, a way to run the scheduler (cron) and a queue worker (or cron `queue:work --stop-when-empty`), and ideally SSH. The default for sessions, cache and queue is the database, which works on any host.

| Option | Cost | Fits | Catches |
|---|---|---|---|
| Local (Laragon) | free | development, LSP demo | not reachable by the public |
| Oracle Cloud Always Free VPS | free | everything in this file (SSH, cron, worker, MySQL, symlink releases) | needs a debit/credit card for identity verification; sign-up rejections are common; Arm allowance cut to 2 OCPU / 12 GB in 2026; idle instances (7 days of low use) may be reclaimed; no SLA |
| GitHub Student Pack + Heroku credit | free for the credit period (about 24 months) | simple git deploys | student verification (ages 13+, school email or documents); database is PostgreSQL by default (MySQL through add-ons), so FULLTEXT search must be adapted and D-34 would need revisiting; deploys restart the app briefly; credit terms can change |
| Free shared PHP hosting | free, no card | demo only | usually no SSH, limited cron, small quotas, weak reliability; zero-downtime releases impossible |
| Free tiers of modern platforms | free or trial | quick demos | Render free sleeps after inactivity (users wait); Railway free is a short trial then a tiny monthly credit; Fly.io has no free tier for new accounts |

Always put Cloudflare (free) in front: DNS, HTTPS, basic protection. A free subdomain is acceptable; a free first-year domain is available through the Student Pack.
If the chosen host has no cron, a free external cron service may call a secret, rate-limited endpoint that runs `schedule:run` (only in that case; add it as a dedicated task and treat the token like a password).
Other costs must also stay free: TMDB (non-commercial), Gemini (free tier limits and terms), Midtrans (sandbox), email (a free tier of a transactional email service, to be chosen), GitHub Actions, uptime monitor.
If a free option cannot give zero-downtime releases, announce short maintenance windows through an announcement banner instead (section 9).

## 15. Pre-launch operational checklist
- [ ] Staging mirrors production and passed the smoke test
- [ ] `scripts/deploy.sh` and rollback tested on staging
- [ ] Backup and restore tested
- [ ] Uptime monitor and alerts active
- [ ] Admin health page green (scheduler, queue)
- [ ] Announcement banner tested (create, show, expire)
- [ ] Feature toggles tested (AI off shows a friendly message)
- [ ] Old-asset retention works (open a tab, deploy, navigate)
- [ ] Sessions and an AI chat survive a deploy
- [ ] Update toast tested (deploy on staging, an open tab shows it, [Update] warns first when a chat has messages, [Later] hides it)
- [ ] Error pages checked (404, 403, 419, 429, 500, 503) and the offline banner works
- [ ] Dependabot enabled and the monthly update routine scheduled (section 16)
- [ ] Unit economics checked with real prices and fees (section 17)
- [ ] Gemini keys configured through T-43 and `ai:check` is green; the free key's project has no billing account
- [ ] Mail provider tested (verification and reset emails reach a real inbox)

## 16. Updates and patching (D-43)
Goal: stay on supported, patched versions without breaking users.
- Cadence: one update day per month, plus immediately for a critical security advisory (PHP, Laravel, Node, MySQL, or a package).
- Routine: `composer outdated --direct` and `npm outdated`; `composer update` and `npm update` within the allowed ranges; `composer audit` and `npm audit`; full test suite, Pint and Larastan; deploy through the normal release procedure (section 5).
- Dependabot (free, GitHub) opens weekly pull requests for composer, npm and GitHub Actions; review them on the update day.
- Patch releases of PHP, Node and Laravel are applied within 14 days.
- Support dates to watch (verify at php.net, laravel.com, nodejs.org and mysql.com): PHP 8.5 security support until 31 Dec 2029; Laravel 13 security fixes until early 2028; Node 24 until 30 Apr 2028 and Node 26 (LTS from late October 2026) until 30 Apr 2029; MySQL 8.4 LTS per its lifecycle page.
- Major upgrades (the next Laravel major, a new PHP branch, a new Node LTS) are separate tasks on their own branch, started within six months of release and finished well before the old line loses security support. They are never mixed with feature work.

## 17. Unit economics (D-44, D-45, D-46)
Goal: every paying user is profitable by design, and you can see it.
Worked example with the default plan table (assumptions: about Rp16.500 per US dollar; Midtrans fees of 2.9 percent plus Rp2.000 for cards, 2 percent for GoPay, 0.7 percent for QRIS, Rp4.000 fixed for bank transfer, plus 11 percent VAT on the fee; verify every number in the Midtrans dashboard and the provider's price page):
| | Plus Rp15.000 | Pro Rp28.000 |
|---|---|---|
| Worst-case gateway fee | card about Rp2.700 (bank transfer is not offered to Plus) | bank transfer about Rp4.440 |
| Worst-case net revenue | about Rp12.300 | about Rp23.500 |
| AI budget per period (guard, D-45) | Rp6.000 | Rp11.300 |
| Margin at the worst case | about Rp6.300 | about Rp12.200 |
| Typical margin (light use, GoPay or QRIS) | about Rp12.000 or more | about Rp23.000 or more |
Tax on the sale (VAT, income tax) is not included; confirm it with an accountant before charging.
Free users cost Rp0 by construction (D-47): they use the no-billing key, so Google can only refuse requests, never charge. Their access is limited by the daily places and the global cap, and no rupiah budget applies to them.
Monthly check (on the update day, section 16):
1. Read the admin profit meter: revenue, estimated gateway fees, estimated AI cost, margin per plan.
2. Compare the estimated cost with the provider's real bill; correct the prices in `ai.cost.*` and the fee estimates in `billing.fee.*`.
3. If margin per paid user is below 40 percent: lower the AI budget or the clip allowance first (settings, no deploy), then raise prices.
4. Re-check the provider's price page for changes (one source indicates introductory Flash prices change on 1 January 2027).

## 18. Go-live: configuration versus money (D-53)
The product is complete for the LSP. Going public is configuration, accounts and money, not coding.
| Item | Kind | What to do | Cost |
|---|---|---|---|
| `LAUNCH_MODE`, `payments_live`, feature flags | configuration | switch in `.env` and admin settings after the gates pass | none |
| Gemini paid key | account + usage | create a billed project, set a budget alert, put the key in `.env` | per use, covered by paid plans (D-45) |
| Gemini free key | account | project without billing; tune `ai.free.pool_daily_limit` from `ai:check` | none |
| TMDB commercial license | license | request it before charging anyone | unknown; ask TMDB |
| Midtrans production | account | approval with business documents, activate card, GoPay, ShopeePay, QRIS, bank transfer and recurring | per transaction |
| Business registration, tax, PSE | legal | check with an advisor (COMPLIANCE.md section 8) | varies |
| Terms and Privacy review | legal | a qualified person reviews the drafts | varies |
| Email provider and own domain | account | verify the domain (SPF, DKIM), choose the provider (D-19) | domain yearly; email free tier |
| Hosting | infrastructure | choose from section 14; a paid VPS only if free options are not reliable enough | monthly if paid |
| Monthly routine | your time | sections 16 and 17 | none |
Nothing in this table needs new features: if an item blocks, a setting or an account is missing, not code.
