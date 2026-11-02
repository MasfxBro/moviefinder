# FEATURES

Each feature has an ID, rules, and acceptance criteria (AC). A task is done only when its AC pass.

## Pages and routes
| Route | Page | Access |
|---|---|---|
| `/` | Landing page (F-22) | guests |
| `/` | Home: search bar, AI entry, rows (Trending, Popular, Top Rated, per type) | logged-in users |
| `/browse` | Grid with filters and sort, pagination (navbar "Movies" and "TV Shows" link here with the type preset) | auth |
| `/search?q=` | Normal search results | auth |
| `/titles/{slug}` | Title detail (+ title chat) | auth |
| `POST /ai/messages` | JSON endpoint behind both AI chats; there is no `/ai` page (D-38) | verified users (guests are redirected to login) |
| `GET /ai/status` | The user's current AI state (units, Smart, place, allowance, return time) | verified users |
| `/pricing` | Plans (Free, Plus, Pro), payment methods and subscribe | all (subscribe needs login) |
| `POST /billing/checkout` | Start a payment for a plan and mode | verified users |
| `POST /billing/webhook` | Midtrans notifications | Midtrans only (signature verified, no session) |
| `/account` | Profile, plan, quota meter, cancel/resume subscription, delete account | auth |
| `/watchlist` | Saved titles | auth |
| `/history` | Search history (clear button) | auth |
| `/requests/create`, `/requests` | Request a title, my requests | auth |
| `/login`, `/register`, `/forgot-password`, `/verify-email` | Auth | guest/auth |
| `/terms`, `/privacy`, `/copyright`, `/about` | Legal and attribution | all |
| `/{ADMIN_PATH}/*` | Admin login and admin panel (secret path from `.env`, never linked from the public site) | admin + 2FA |
| `/feedback` | Send critique or suggestions | verified users |
| `/version` | Build id for the update notice (JSON, no-store) | all |

Guest rule (D-35): a guest can open only the landing page, `/pricing`, the legal pages, the auth pages and `/version`, and Midtrans may call the signature-verified webhook. Any other URL redirects to `/login`, remembers the requested URL, and returns the user there after login. Logged-in users who open `/` see the home rows, never the landing page.

## F-01 Home and lists
Logged-in home at `/` (guests get F-22). Rows of poster cards (poster, title, year). Data from local catalog sorted by stored popularity/rating; "Trending" refreshed daily from TMDB into cache.
AC: renders with empty catalog (empty state); no N+1 queries; posters lazy-loaded with fixed aspect ratio (no layout shift).

## F-02 Title detail
Shows: poster/backdrop, title, year, type, genres, runtime or seasons/episodes, rating, directors/creators, top cast (max 8), synopsis, trailer (click-to-load YouTube), "Where to watch" (Indonesia by default, cached, with JustWatch attribution), watchlist button, title chat card (F-08), report-a-problem link.
AC: guests are redirected to login (and returned afterwards); opening a title not yet in the catalog imports it once (lazy import) then serves from DB; adult titles return 404; missing fields are hidden, never shown as "null".

## F-03 Normal search
Live search with debounce (300 ms, min 2 chars): prefix and full-text over title/original title in the local catalog. If nothing is found locally, query TMDB search (cached 24h) and show those results marked as "Not in our catalog yet — opening it adds it". If TMDB also finds nothing: not-found state (F-15).
AC: no AI used; requires login (guests are redirected); max 60 req/min per IP; XSS-safe highlighting.

## F-04 Filters
Type (movie/series), genre (multi), year (range), director (text, min 3 chars, matches local `title_people`), minimum rating, sort (popularity, rating, newest). Filters combine with AND and are reflected in the URL query string.
AC: filters work together and with pagination; invalid values are rejected by validation; empty result shows the not-found state.

## F-05 Auth and email verification (D-51)
Register (name, email, password, "I am 18+" checkbox, Terms and Privacy consent), email verification, login, logout, forgot/reset password.
Verification flow: after registering the user is logged in with an unverified account and sees "Please verify your email address. We have sent a link to {email}." with a Resend button. The verification email (branded, plain, no tracking pixels) has one button, "Verify email address", and states that the link expires in 60 minutes and that nothing further is required if the user did not create the account. Opening the link in the same browser verifies the account and keeps the user logged in; in another browser the user must log in first, then verification completes. Verification happens once per account. Changing the email address later requires verifying the new address.
Unverified users can browse the catalog but cannot use AI, the watchlist or requests. Unverified accounts older than 14 days are deleted by `users:prune-unverified` (setting `auth.unverified_ttl_days`).
AC: password rules + breached-password check; login throttled; session regenerated on login; resend throttled (3 per hour per user, 5 per hour per IP); an expired link shows a clear message with Resend; the link never logs in a different browser by itself; the pruning command removes only unverified accounts older than the limit; unverified users cannot use AI/watchlist/requests; emails render correctly in Mailpit.

## F-06 Roles, plans, badges
`User::isAdmin()`, `User::plan()` (Free, Plus or Pro, resolved by EntitlementService), `User::limits()` (the plan's limits from PlanCatalog). PLUS and PRO badges on the navbar avatar, account page and admin user list. Admin gets an "ADMIN" badge (different style).
AC: policy tests for each role; a paid plan ends automatically when the period ends (after grace); the highest plan wins when several apply.

## F-07 AI search (global)
Opened from the navbar AI button (icon plus label): a panel, popover on 1024px+ and full-height sheet on mobile (D-38). Movies and series only. User describes a title or scene in free text. Result: up to 3 (Free), 4 (Plus) or 5 (Pro) best-matching titles as cards (title, year, director(s), short description, "why it matches", link to detail page). If the user asks for a scene: clip cards (see F-09) or fallback links. If the guess is poor, still return the closest matches.
AC: every shown title is verified against TMDB and exists in the local catalog; quota enforced; errors do not consume quota; refusals follow AI_BEHAVIOR.md.

## F-08 Title chat (detail page)
A chat card inside the detail page with `scope=title`; same component as the global chat. It is part of the page layout and sized to the detail content box: never full screen, also on mobile, where it is an inline section below the action buttons with its own scroll (D-38). The title context (title, year, overview) is read from the DB, never from the client, and is the default context.
D-41: it answers any question, not only about this title: plot, cast, ending, trivia, scenes and clips, and also other topics. Safety refusals still apply (sexual/explicit, piracy, harm; AI_BEHAVIOR.md section 6). Scene questions about the title run ClipFinder.
When it opens it shows a default prompt built from the title, for example "What would you like to know about {title}?" This is UI text: no AI call, no quota, and it does not count as a message for the exit warning.
AC: the default prompt shows the opened title's name; questions about the title use its DB context; off-topic questions are answered unless refused for safety; counts toward the same daily quota; the card never goes full screen or wider than the detail layout at 360, 768 and 1440 px.

## F-09 Clip results and preview
Clip card: thumbnail (click to load), title, channel name, source label (Official / Trusted / Unverified), "Preview (first 1:00)" and "Watch full video" buttons. Rules:
- Embed only YouTube links from official/trusted channels. Preview runs at most 60 seconds (`start` from the link's `t=` value or 0, `end = start + 60`). When it ends (or the video is shorter), remove the player and show "Preview ended — continue on YouTube" with a button that opens the original video, plus the channel name. If the video is longer than the preview, say so.
- Unknown channel: link-out only, label "Unverified source".
- Other platforms (TikTok, Instagram, X, Facebook, Vimeo, Dailymotion): shown only when the host is on the allow-list, always link-out with an "External link" label. No embed in v1: the 60-second limit cannot be enforced there and their scripts add tracking and weight.
- No clip found: show "No clip found", a "Search on YouTube" link, and "Where to watch".
AC: iframe is created only after the user clicks; no autoplay; dead/unavailable videos are never shown; report button on every clip.

## F-10 AI chat memory and exit warning
Conversation continues while the user stays in the chat. Leaving the chat (navigating away, closing tab, refresh, back) with at least one message shows a warning that the conversation will be lost. Empty chat leaves without warning. After leaving, the next chat starts empty.
AC: in-app link clicks show a custom modal (Stay/Leave); tab close/refresh uses the browser's native prompt; the conversation id is kept only in page memory. The same warning applies to the navbar chat panel and to the [Update] button of the update toast (F-23).

## F-11 Quota, plans, Free places and AI budget (D-44, D-45, D-47)
Every limit comes from PlanCatalog (settings `plan.<free|plus|pro>.*`, editable in the admin). Defaults:
| Setting | Free | Plus | Pro |
|---|---|---|---|
| Price per month (IDR) | 0 | 15000 | 28000 |
| AI key used | no-billing key (`GEMINI_API_KEY_FREE`) | paid key (`GEMINI_API_KEY_PAID`) | paid key |
| AI units per day | 5 | 10 | 12 |
| of which Smart searches per day | 0 | 0 | 4 |
| AI web clip searches per period | 0 (saved clips and the search link only) | 10 | 20 |
| AI budget per period (IDR, internal) | none (daily places instead) | 6000 | 11300 |
| Title cards per answer | 3 | 4 | 5 |
| Clip candidates judged | not applicable | 5 | 8 |
| Clips shown per scene | 2 (saved clips) | 4 | 6 |
| Conversation turns kept | 8 | 12 | 16 |
| "Why it matches" | one short sentence | two sentences | detailed, with what matches and what does not |
| Critic step on Smart searches | no | no | yes |
| Watchlist items | 100 | 300 | 500 |
| Title requests per day | 2 | 5 | 10, shown first to the admin |
| AI messages per minute | 6 | 8 | 12 |
| Badge | none | PLUS | PRO |
Rules:
- A normal message costs 1 unit. A message that runs an AI web clip search (Plus and Pro only) costs 2 units and one clip search from the period's allowance.
- A Smart search (Pro only) uses the Smart model and the critic step. When the day's Smart searches are used, Pro continues with the Standard model. Smart searches are part of the daily total, not extra.
- Budget guard (paid plans, D-45): from 60 percent of the period's budget Pro falls back to Standard; from 80 percent AI web clip search stops (saved clips and the search link remain); at 100 percent AI pauses until the period renews. Units reset daily at 00:00 Asia/Jakarta; the budget window is the subscription period.
- Free places (D-47): Google's free quota is one pool shared by all Free users, so Free access is rationed by daily places. The first AI message of the day takes one place (atomic). A user who holds a place keeps the full daily units until midnight WIB, whatever other users do. Places are released in three waves (00:00, 08:00, 16:00 WIB, settings), so users later in the day also get a chance. Places per day = `ai.free.pool_daily_limit` / `plan.free.daily_units` x `ai.free.overbook_pct` (default 100). Plus and Pro never use places.
- The AI panel and the title chat show the real state before the user types (four situations in AI_BEHAVIOR.md section 9), the time the AI returns, and a soft upgrade card (DESIGN_SYSTEM.md section 14). Counter: "3 of 10 AI searches left today"; Pro also shows Smart searches left. The number of places left is never shown to Free users.
- Free users get the cheapest Standard model and a shared answer cache for non-personal movie questions (normalized text, 24 hours, never for personal data).
AC: parallel requests cannot exceed the limits or the places; a user who holds a place is never refused mid-conversation because places ran out; resets and waves work; failed AI calls are not counted and add no cost; the paid thresholds change behavior exactly at their percentages; changing a plan setting in the admin takes effect within 60 seconds; the admin sees paid spend against budget and Free pool use against the Google limit (F-17 module 11).

## F-12 Plans, payment and subscription (D-46)
`/pricing` shows three columns (Free, Plus Rp15.000, Pro Rp28.000 per month), what each includes, the payment methods, the renewal and cancellation terms, the plan-change rule and the refund policy, all BEFORE payment. The price is the same for every payment method and is final: no extra fee is added for the user.
Auto-renew monthly is the primary option and is promoted first: card (Visa, Mastercard, JCB) or GoPay. The first payment goes through Snap with tokenization; then the Midtrans Subscription API charges every month. Before paying, the user ticks an explicit consent box ("I authorize a monthly automatic charge of Rp{amount} until I cancel."). A reminder email is sent before each charge, a receipt after it, and cancellation takes two clicks.
Pay for 30 days (no auto-renew) is the alternative for methods that cannot be charged automatically: QRIS (works with OVO, DANA, LinkAja, ShopeePay, GoPay and bank apps), ShopeePay, GoPay, card, and for Pro also bank transfer (virtual account). Each payment adds 30 days; paying again before the end extends from the current end date. A reminder email with a pay link is sent 3 days before the end. Convenience stores are not enabled.
Allowed channels per plan and mode are settings (`billing.methods.*`) passed to Snap as the enabled-payments list. Plus excludes bank transfer because its fixed fee is too high for the price.
Auto-debit from other e-wallets (OVO, DANA, ShopeePay, LinkAja) is not available yet; the pricing page may show these as small "available at public launch" labels (F-21, D-49).
Plan changes: upgrade (Plus to Pro) starts immediately and is charged in full; the old subscription stops and its remaining days are forfeited, and the checkout says so before payment. Downgrade (Pro to Plus) starts at the next renewal. No proration.
Account: cancel in two clicks (auto mode; access remains until the period end), resume before the end, switch between modes at renewal, payment history. A failed auto-renew charge gives a 3-day grace, then the user returns to Free.
AC: all state changes happen from verified webhook events (or a verified status re-fetch); duplicate webhooks do nothing; the plan and the amount are computed on the server from settings and never taken from the client; automatic charging cannot start without the consent box; each payment sends a receipt email; the sandbox command can simulate renewal, failure, cancellation and expiry in both modes.

## F-13 Watchlist
Add/remove from cards and detail. Maximum items per user depends on the plan (Free 100, Plus 300, Pro 500).
AC: unique per user/title; only owner can read/change.

## F-14 Search history
Stores normal searches and the first prompt of each AI chat (truncated to 200 chars), 90 days retention. Clear all button.
AC: only owner can read; pruned by scheduled command.

## F-15 Not found and request a title
When a search finds nothing anywhere: "We could not find that title yet" + "Request this title" button -> `/requests/create` prefilled with the query. Form: type, title, year (optional), note. Limits per day per user by plan (Free 2, Plus 5, Pro 10; Pro requests are shown first to the admin), duplicates by title+year blocked. The admin reviews the queue: match the request to a TMDB result and import it, or create the title manually when TMDB lacks it, or reject with a note. The user is emailed and sees the status. Each not-found search also increments an aggregated `search_misses` counter (no user id) shown to the admin.
AC: guests are redirected to login then back; rate limited; admin action creates an audit log.

## F-16 Report a link
On every clip card: reason (dead link, wrong scene, copyright concern, inappropriate), optional details. Report marks the link `reported` (hidden from embedding until reviewed). Admin can restore, remove, or block the channel.
AC: 10 reports/day per user; reported link is not embedded.

## F-17 Admin panel
Access: secret path `/{ADMIN_PATH}` from `.env` (long random string), separate login page and `admin` guard, mandatory 2FA, optional IP allow-list, 404 for anyone else on every admin URL except the login page, no link anywhere on the public site, `noindex`. Admin accounts cannot log in through the public `/login`.

Modules:
1. Dashboard: users (total, new in 7 days), paid users by plan, revenue this month, estimated AI cost and gateway fees with the resulting margin (profit meter), AI usage today vs global cap, clip resolver budget, pending requests/reports/feedback, top missed searches, system health summary.
2. Titles (full CRUD): list/search/filter (source, hidden, stale), create a manual title (poster upload), import from TMDB (search or id), edit fields (edited fields are recorded in `locked_fields` and skipped by the refresh job), hide/unhide, delete (confirmation with dependency summary), refresh from TMDB, bulk import by list of ids.
3. Requests: queue with status filters; approve and import, create manually, reject with note; merge duplicates; user is notified.
4. Missed searches: aggregated not-found terms with counts; one-click create request/import; mark ignored.
5. Clip links: CRUD, "verify now" (oEmbed), set trust, bulk remove by channel.
6. Reports: queue; restore, remove link, block channel; resolve with a note.
7. Channel allow-list: CRUD (official / trusted / blocked).
8. Feedback inbox: list, filter by type/status, read, internal note, status (new/read/in progress/done/archived), optional email reply.
9. Users: search, summary (plan, usage today, counts, last login), grant/revoke a plan (reason + expiry), suspend/unsuspend, force password reset, reset the guided tour, delete on request. Admins cannot read an individual user's watchlist or search history (privacy); they see aggregates only.
10. Subscriptions and payments: list/filter, per-subscription timeline, cancel on the user's behalf, manual grants, CSV export.
11. AI monitoring: usage per day and plan, errors, refusals, token totals, estimated cost against the budget and against revenue per plan, cap and budget status.
12. Settings: plan limits and prices, AI budgets and model prices, payment methods, caps, grace days, feature toggles (AI, registration, requests, feedback, payments_live, AI clip search), contact and copyright emails.
13. Announcements: info/warning/maintenance banners with start and end time; live without a deploy.
14. Audit log: searchable, read-only.
15. System health: AI key status (driver, free key and paid key configured or not, Free pool use against the Google limit), scheduler last run, queue backlog and failed jobs (retry/forget), cache status, TMDB/Gemini/Midtrans reachability, DB size, app version.

AC: every mutation writes an audit log; admin routes require the admin guard + 2FA; changes to titles, settings and announcements are visible to users within 60 seconds without a deploy (targeted cache invalidation); delete actions need confirmation; all lists are paginated and filterable; the admin UI is responsive.

### Admin coverage matrix (every web entity has an admin counterpart)
| Web feature | Admin capability |
|---|---|
| Catalog and detail pages | Titles CRUD, import, refresh, hide |
| Not-found search | Missed searches, Requests |
| Request a title | Requests queue |
| Clips and previews | Clip links, Reports, Channel allow-list |
| AI search and quota | AI monitoring, Settings |
| Accounts and roles | Users |
| Subscriptions and payments | Subscriptions and payments |
| Watchlist, search history | Aggregates only (privacy) |
| Feedback | Feedback inbox |
| Site notices and maintenance | Announcements, feature toggles |
| Every admin action | Audit log |
| Platform health | System health |

## F-18 Legal and attribution pages
Terms, Privacy, Copyright/Takedown, About with TMDB and JustWatch attribution (see COMPLIANCE.md). Footer links on every page.
AC: pages exist and are linked; attribution text exact.

## F-19 Account
Change name/password, see plan and next renewal date, quota meter, a "Take the tour again" link (user-initiated only, F-25), delete account (removes personal data; keeps anonymized payment records as required).
AC: deletion requires password confirmation; active subscription is cancelled first.

## F-20 Feedback (critique and suggestions)
Form at `/feedback` (footer link): type (bug, suggestion, content issue, praise, other), message (10–1000 chars), optional rating 1–5. The current page path (same-origin path only) is attached automatically. Verified users only, 3 per day, honeypot field. Plain text, escaped on output.
AC: appears in the admin inbox immediately; rate limited; user sees a thank-you state.

## F-21 Announcements and maintenance notices
A banner component shows active announcements (info/warning/maintenance) for their time window; cached 60 seconds. When a feature is switched off in settings (for example AI search), users see "AI search is under maintenance" in place of the feature, never an error. Features that need paid resources and are not live yet (AI web clip search before a paid key is configured, extra auto-debit methods, real charging) show a friendly "available at public launch" message through the same mechanism, never an error (D-49). Full-site maintenance mode is a last resort (OPERATIONS.md).
AC: creating an announcement shows it without deploy; expired announcements disappear automatically; toggling a feature flag takes effect within 60 seconds.

## F-22 Landing page (guests)
Public page at `/` for guests. Goal: in 5 seconds a visitor understands what the product is and why to register. Sections, in order:
1. Hero: one-line promise, one short subline, [Create account] and [Log in].
2. How it works in 3 steps: describe a title or scene, get verified titles and clips, watch a 1-minute preview and continue on the original platform.
3. Feature highlights: AI scene search, official clip previews, watchlist. Use real UI crops or real examples, not a generic icon grid.
4. Free, Plus and Pro comparison in three columns with values read from settings (daily AI searches, Smart searches, results per answer, price), and the accepted payment methods as small labels.
5. Short FAQ: is this a streaming site, where do links go, is the AI always right.
6. Decorative poster strip: up to 8 popular local titles, cached, not linked, hidden when the catalog is empty.
7. Footer with legal links and attributions.
Copy is real product copy, never lorem ipsum. Every call to action goes to `/register` or `/login`.
AC: guests see it at `/`; logged-in users never do; no catalog browsing or search from it; renders with an empty catalog; responsive 360 to 1440 px; Lighthouse targets met; TMDB and JustWatch attributions present in the footer; Terms and Privacy linked.

## F-23 New version notice
Admin content changes are live within 60 seconds and need no notice. For code releases, a tab that stays open shows a non-blocking toast "A new version is available" with [Update] and [Later].
- The layout prints the build id in `<meta name="app-build">`. `updateNotice.js` (Alpine) fetches `/version` when the tab becomes visible and every `update_poll_minutes` (default 5). If the id differs, it shows the toast.
- [Update] reloads the page. If the AI chat has at least one message, the exit-warning modal (F-10) appears first.
- [Later] hides the toast until a check finds a different id again, or for 1 hour.
- Never auto-reload, never block the page.
- The old tab keeps working because hashed assets are kept for 14 days and AJAX endpoints stay compatible with the previous release (OPERATIONS.md section 7, CODING_RULES.md section 13).
- Known limit: pages are server-rendered, so any normal navigation already loads the newest version. The toast mainly serves tabs left open on one page.
AC: after a deploy an open tab shows the toast within the poll interval; [Later] dismisses it; [Update] with an active chat warns first; no toast when the ids match; `/version` returns only the build id with `Cache-Control: no-store` and is throttled.

## F-24 Errors, connection problems and failed requests
Every failure that is not the user's doing shows a specific, branded message with a next action (texts and layouts in DESIGN_SYSTEM.md section 11): 404, 403, 419 (session expired), 429 (with wait time), 500 (with a short reference id), 503 (maintenance), and offline.
- AJAX failures (live search, AI chat, watchlist toggle, report, clip preview) show an inline message with a retry; optimistic changes roll back with a toast.
- Admin URLs return exactly the same 404 as any unknown URL.
- Third-party problems use the feature-level messages in AI_BEHAVIOR.md section 9 and OPERATIONS.md section 8.
AC: each status renders its own page with the correct HTTP status; no stack trace, no `ADMIN_PATH`, no internal names; the offline banner appears when the browser goes offline and a "Back online" toast appears on reconnect; error pages render even when the database is down; a 500 shows a reference id that is also in the log.

## F-25 First-time guided tour (D-50)
A short guided tour is offered once to each new user: the first time a verified user reaches the logged-in home.
- The offer is a small card: "Welcome to {app name}. Would you like a short guided tour of the website?" with Start the tour and Skip. It is recorded as seen (`users.onboarding_seen_at`) the moment it is shown, so it never appears again automatically, whatever the user chooses (start, skip, close the tab, reload). The outcome (completed or skipped) is stored in `users.onboarding_outcome`.
- The tour highlights elements one at a time with a short explanation, Back and Next buttons, a position marker ("3 of 6") and an always-visible Skip; Esc exits. It runs on the home page and navbar only (no navigation to other pages).
- Steps depend on the plan when the tour starts. Free (6 steps): Search; Movies and TV Shows with filters; a poster (open it, save it to the watchlist, and each title has its own chat); AI Chat and the Free allowance; the account menu (watchlist, history, plan); Plans (informative, not pushy). Plus: the same, with the Plus allowance and AI clip searches in the AI step, and the allowance meter in the last step. Pro: the same, with Smart searches and the critic step in the AI step, and the Smart searches left in the last step.
- The Account page has a "Take the tour again" link; the tour only runs when the user presses it. Admin accounts never get the tour. Admins and the command `php artisan onboarding:reset {user}` can reset a user's tour for demos.
- Built with Alpine only (no tour library); keyboard accessible with focus kept inside the tour; on small screens the explanation is a bottom card; steps whose target is hidden are skipped; `prefers-reduced-motion` respected; texts in formal English (D-48).
AC: the offer appears exactly once per account, after verification, on the first logged-in home visit; never again after any choice; works for each plan variant; replay only from the Account link or after a reset; no tracking or analytics; accessible by keyboard.

## Cross-cutting requirements (apply to every task)
- Responsive: no horizontal scroll and no overlapping elements at 360, 390, 768, 1024, 1440 px; touch targets at least 44 px.
- Interactive: behaviors listed in DESIGN_SYSTEM.md section 10 (live search with keyboard navigation, optimistic toggles with toasts, skeleton loading, progress steps in the AI chat, smooth modals and drawers).
- Fast: Lighthouse mobile performance 85+ and accessibility 95+ on home, browse, and detail.
- Continuous: releases and maintenance do not disturb users (OPERATIONS.md).
- Guarded and clear: guests reach only the public pages in D-35, and every error state follows F-24.
- Copy: all UI text follows D-48 (formal, standard English, no contractions).

## Out of scope for v1
Reviews/comments, social features, notifications beyond email, light theme, multi-language UI, mobile app, episode-level data, non-YouTube embeds, provider logos and "More like this" (D-39), service workers or offline mode.
