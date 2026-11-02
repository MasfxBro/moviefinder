# SECURITY

Goal: hard to break, safe by default. No system is 100% secure; this file defines the baseline, how to test it, and what to do when something goes wrong.
Every item below is a requirement. Each task that touches an area must satisfy the matching items and add tests where marked [T].

## 1. Authentication and sessions
- Laravel Breeze (Blade) for register/login/reset/verify. Passwords hashed (bcrypt or argon2id, Laravel default). Never log passwords.
- Password rules: min 10 chars, mixed, and `Password::uncompromised()` check. [T]
- Email verification required before AI, watchlist, requests. [T]
- Verification links are signed and expire after 60 minutes; opening one in a different browser never logs that browser in by itself (it asks for login first); resend is throttled (3 per hour per user, 5 per hour per IP); unverified accounts older than 14 days are deleted. [T]
- Login throttle: 5 attempts/min per email+IP; progressive delay. Registration: 5/hour/IP. Reset: 5/hour. [T]
- Session: `SESSION_SECURE_COOKIE=true` in prod, HttpOnly, SameSite=Lax, regenerate id on login, invalidate on logout, short lifetime (e.g. 120 min idle), logout everywhere on password change.
- Suspended users (`suspended_at`) are blocked at middleware level on every request. [T]
- Admin area: separate login on a secret path (`ADMIN_PATH` in `.env`, long random string), separate `admin` guard and login form. The public `/login` rejects admin accounts with the generic invalid-credentials message. TOTP 2FA is mandatory (secret encrypted, 8 hashed single-use recovery codes). Optional IP allow-list (`ADMIN_ALLOWED_IPS`). Every admin URL except the login page returns 404 (not 403) unless the visitor is an authenticated admin. The login page is throttled (5/min per IP) and sends `X-Robots-Tag: noindex`. The path never appears in HTML, JS, robots.txt, sitemaps, emails, or error pages. The secret path only reduces noise; the real controls are the guard, 2FA, throttling and audit logs. Admin accounts are created by seeder/CLI only. Email the owner when an admin logs in from a new IP. [T]

## 2. Authorization
- Policies for every model a user can touch (Watchlist, SearchHistory, TitleRequest, Subscription, LinkReport). Controllers call `authorize()`. [T] (IDOR tests: user A cannot read/change user B's data.)
- Role checks in middleware/gates, never only in the UI.
- Plan checks (Free, Plus, Pro) and limits go through `EntitlementService` and `PlanCatalog`, never ad-hoc `if`s.
- Guest access (D-35): only the landing page, `/pricing`, the legal pages, the auth pages, `/version` and the signature-verified Midtrans webhook are public. Every other route is registered inside the `auth` group, so a new route is protected by default and guests are redirected to login. [T]

## 3. Input, output, injection
- FormRequest validation for every write and every query parameter that reaches the DB (types, lengths, enums).
- Eloquent/query builder with bindings only. No string-built SQL. `LIKE` terms are escaped.
- Mass assignment: explicit `$fillable`; never `$guarded = []`; never `->update($request->all())`.
- Blade `{{ }}` only for dynamic data. No `{!! !!}` with user-derived data. Highlighting in search is done server-side with escaped output.
- No inline event handlers. Alpine components live in JS files; Blade passes data via `data-*` or `@json` into safe attributes.
- CSRF on all state-changing web routes. AJAX sends the token header.
- Text from feedback, requests, reports and announcements is plain text, length-capped, and escaped on output.
- Uploads: only admins, only poster images for manual titles. Validate the MIME type by content (not extension), max 2 MB input, decode and re-encode with GD to WebP (max 600 px wide, at most 300 KB), random file names, served from `storage/app/public`, never keep the original, never execute. Any other upload requires a new security review. [T]

## 4. HTTP security headers (middleware `SecurityHeaders`)
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` (prod only)
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`: disable camera, microphone, geolocation, payment unless needed
- `X-Frame-Options: DENY` (or CSP `frame-ancestors 'none'`)
- Content-Security-Policy with per-request nonce for scripts. Allowed: self; `image.tmdb.org` and `i.ytimg.com` for images; `www.youtube-nocookie.com` and `www.youtube.com` for frames and the IFrame API script (`https://www.youtube.com/iframe_api` loaded only after the first preview click); Midtrans Snap script/frame hosts only on the checkout page. Everything else denied. Start in report-only during development, enforce before launch. [T] (header presence)
- CORS: no wildcard; same-origin only unless a task says otherwise.

## 5. Secrets and configuration
- `.env` never committed; `.env.example` has names only. Different keys for dev and production. Rotate any key that ever appears in a log, screenshot, chat, or commit.
- `APP_DEBUG=false` and `APP_ENV=production` in prod. Document root is `public/` only (never expose project root). Directory listing off. Server version hidden.
- Logs must not contain API keys, tokens, passwords, full webhook secrets, or full card data. Redact.
- Two Gemini keys (D-47): `GEMINI_API_KEY_FREE` belongs to a Google project WITHOUT billing and must never be linked to billing; `GEMINI_API_KEY_PAID` belongs to a separate project with billing and a budget alert, created only after the first paying customer. Free users can never reach the paid key. Keys live only in `.env`, and the agent never opens `.env`. A key that leaks is deleted and recreated.
- The browser never receives TMDB/Gemini/Midtrans server keys. Midtrans client key is public by design and only used on checkout.
- `/version` returns only the build id (never PHP, Laravel or package versions), is `no-store` and throttled.

## 6. Rate limiting (Laravel RateLimiter, named limiters)
| Limiter | Limit |
|---|---|
| search | 60/min per IP |
| ai | 6/min per user (+ daily quota) |
| title-import | 30/hour per IP and global 200/hour |
| request-title | 5/day per user |
| report-link | 10/day per user |
| login | 5/min per email+IP |
| register | 5/hour per IP |
| version | 30/min per IP |
| checkout | 10/hour per user |
| verify-resend | 3/hour per user and 5/hour per IP |
| webhook | no limiter, but signature + re-fetch required |
Return 429 with a friendly message (the 429 page or an inline message, DESIGN_SYSTEM.md section 11).

## 7. Quota and billing integrity
- Quota reserve/commit/release is atomic (DB statement with `count < limit` condition). [T] parallel test.
- Webhooks: verify authenticity, re-fetch status, idempotent via unique `webhook_events.event_key`, ignore stale events, never trust amounts from the client. [T]
- A user can never set their own plan, price, or period. Only `SubscriptionService` and verified webhooks change subscription state. [T]
- Manual plan grants (Plus or Pro) are admin-only, require reason + expiry, and are audit-logged.

## 8. SSRF and outbound requests
- We only call fixed hosts: TMDB, Gemini, Midtrans, YouTube oEmbed. Never fetch a URL provided by a user or the LLM.
- YouTube URLs are parsed with a strict host allow-list and an 11-char id regex, then rebuilt canonically before any request.
- Redirects: disabled by default; the single allowed redirect resolution (grounding links) checks the final host against the allow-list.

## 9. AI-specific security
See AI_BEHAVIOR.md section 6. Additionally: LLM output is untrusted (schema-validated, length-capped, escaped), no secrets in prompts, no tools beyond grounding, hostile-content tests with a fake LLM. Title mode answers general questions (D-41) but keeps every guardrail. The AI budget guard (D-45) is enforced server-side per user and never trusts client values. [T]

## 10. Logging, audit, monitoring
- `audit_logs` for every admin mutation (who, what, subject, IP hash).
- Log failed logins, 429s, webhook rejections, validation failures on sensitive endpoints (without bodies).
- Every unexpected 500 carries a short request id shown on the error page and written to the log; the log entry never contains secrets.
- Daily DB backup (provider snapshot or `mysqldump` to private storage), restore tested once before launch.
- Error tracking optional (Sentry free tier) with data scrubbing.

## 11. Dependencies and supply chain
- Allowed packages only (CODING_RULES.md). Commit `composer.lock` and `package-lock.json`.
- Run `composer audit` and `npm audit` before every deploy; fix high/critical.
- Pin versions; update regularly; remove unused packages.
- Runtime support (D-43): PHP, Laravel, Node and MySQL must stay on release lines that still receive security fixes. Apply patch releases within 14 days and critical advisories immediately; follow the monthly routine in OPERATIONS.md section 16.

## 12. Infrastructure (see OPERATIONS.md)
- HTTPS everywhere (Let's Encrypt). Redirect HTTP to HTTPS.
- Put Cloudflare (free) in front: DNS, TLS, basic WAF, bot fight, rate limiting rules for `/login`, `/register`, `/ai/messages`.
- Firewall: only 80/443 (and SSH by key only, no password login). Non-root deploy user. Database not exposed publicly.
- File permissions: `storage` and `bootstrap/cache` writable by the app user only.
- Cron runs `schedule:run` every minute under the app user.

## 13. Security test checklist (run before launch and after major changes)
- [ ] IDOR: change ids in URLs/requests for watchlist, history, requests, subscriptions.
- [ ] Mass assignment: try to post `role=admin`, `plan=pro`, `amount=1`, `status=active`.
- [ ] XSS: submit `<script>` / event handlers in name, search, request note, report details; check every output.
- [ ] SQL injection probes on search/filter/director fields.
- [ ] CSRF: submit forms without token.
- [ ] Quota race: fire 20 parallel AI requests with 5 remaining.
- [ ] Webhook: replay, tamper amount/status, wrong signature, duplicate event.
- [ ] Admin: access `/admin` as user/guest/admin-without-2FA.
- [ ] Rate limits: confirm 429 behavior.
- [ ] Headers: verify with securityheaders-style scan; CSP not breaking pages.
- [ ] `composer audit`, `npm audit` clean of high/critical.
- [ ] OWASP ZAP baseline scan against staging; fix findings.
- [ ] `.env`, `.git`, `storage`, `vendor` not reachable from the web.
- [ ] Debug off; error pages leak nothing.
- [ ] Guest access: open every non-public URL while logged out; each redirects to login. The landing page HTML contains no catalog data other than the decorative poster strip.
- [ ] Error pages: 404, 403, 419, 429, 500 and 503 show no stack trace, no `ADMIN_PATH` and no internal names; admin URLs give the normal 404.
- [ ] Cloudflare rules for `/login`, `/register` and `/ai/messages` are active.
- [ ] Verification link opened in a second browser: no automatic login; an expired link shows Resend.
- [ ] A Free user cannot reach the paid key or AI web clip search; the answer cache stores no personal data; `AI_DRIVER=fake` never calls Google; the free key's project shows no billing account.

## 14. If something goes wrong
Rotate affected keys immediately, invalidate sessions (`php artisan session:flush` equivalent for your driver), review `audit_logs` and access logs, notify affected users if personal data was exposed (see COMPLIANCE.md), write a short post-mortem and add a test for the root cause.
