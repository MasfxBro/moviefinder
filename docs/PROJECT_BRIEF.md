# PROJECT_BRIEF

## 1. Product in one paragraph
MovieFinder is a movie and series discovery website. Users browse a catalog, search by text, filter, and use an
AI assistant that understands vague descriptions ("a western duel in the middle of the film, I forgot the title")
and returns the best matching titles plus links to official clips or legal places to watch.
It is a search and guidance product. It is not a streaming service.
Visitors who are not logged in see a landing page that explains the product; the catalog and every feature sit behind login (D-35).

## 2. What it IS
- A public landing page for visitors (what it is, how it works, Free vs Plus vs Pro) with register and login.
- A catalog of titles (metadata only) with detail pages: year, directors/creators, genres, synopsis, cast, rating, runtime/seasons, trailer, where to watch.
- Normal search (text, live) and filters (type, genre, year, director name, rating, sort), for logged-in users.
- AI search: a global chat opened from the navbar (movies and series only) and a chat card inside each title's detail page that knows the title and answers any question (plot, cast, scenes, clips, other topics).
- Scene results are links to videos on licensed platforms (YouTube and other social platforms) with a short preview of at most 1:00 (60 seconds) when the platform allows it. After the preview ends, a button sends the user to the original video on its platform.
- Accounts with an admin role and three plans for users: Free, Plus (Rp15.000 per month) and Pro (Rp28.000 per month). AI limits and quality depend on the plan (D-44).
- Paid plans paid monthly by card or GoPay with automatic renewal, or for 30 days at a time by QRIS, ShopeePay, GoPay, card or bank transfer; easy cancellation (D-46).
- Watchlist, search history, "request a title" flow, report-a-link flow.
- A short guided tour for new users, shown once on the first visit after email verification (D-50).
- Admin panel to manage everything.

## 3. What it is NOT (hard boundaries)
- MovieFinder is a LISTING and SEARCH site, not a place to watch. It never plays a film or series. The only playback is a 60-second preview of a clip that lives on YouTube; "Where to watch" only lists legal services as links.
- No hosting, streaming, downloading, or re-encoding of any video or audio.
- No storing of subtitles, scripts, or dialogue text.
- No links to pirated sources. No "watch free full movie" results. The AI refuses such requests and offers legal options.
- No adult/explicit titles.
- No user-generated reviews/comments in v1.

## 4. Users and roles
| Role | How you get it | Can do |
|---|---|---|
| Guest | not logged in | see the landing page, pricing, legal pages, and register/login. Everything else needs login (D-35) |
| Free user | registered (email verified for AI, watchlist, requests) | browse the catalog, view details, normal search, filters, plus AI search (5 messages/day), watchlist, history, request a title, report a link |
| Plus user | has an active Plus subscription | everything free can + more AI searches, AI clip searches, more titles and clips per answer, bigger watchlist, PLUS badge |
| Pro user | has an active Pro subscription | everything Plus can + Smart searches (stronger model and a critic step that checks each candidate against your clues), richer explanations, most results, priority requests, PRO badge |
| Admin | `users.role = admin` (the owner) | logs in through a separate secret URL (never linked on the public site); manages and monitors the whole product (titles, requests, feedback, clips, users, subscriptions, AI usage, settings, announcements); not metered by AI quota |

## 5. Success criteria
- A first-time visitor understands the product in 5 seconds on the landing page, and a new user finds a title within 30 seconds of logging in.
- AI results never show a title that does not exist and never show a dead or fake link.
- The site runs fast on a small server and a small database (see DATABASE.md size budget).
- A stranger cannot read other users' data, bypass quota, get a paid plan for free, or break the site (see SECURITY.md).
- Code is clean, consistent, and explainable line by line by its author (LSP assessment, see CODING_RULES.md).
- Users are never disturbed by changes: admin edits go live instantly, releases cause no downtime, maintenance is announced and mostly invisible (see OPERATIONS.md). A user who keeps a tab open is told when a new version is available and chooses when to update.
- When something goes wrong that is not the user's doing, they see what happened and what to do next (404, offline, 429, 500, 503).
- The UI is responsive from 360px phones to large desktops and feels interactive (DESIGN_SYSTEM.md section 10).

## 6. Constraints
- Timeline: core must be done in days, not weeks. Scope control matters more than feature count.
- LSP window: the assessment runs on 2-5 November 2026 and the local demo must be ready before it. Priority if time runs short, as agreed earlier: cut real subscription handling (keep the sandbox simulation) first, then admin modules beyond titles, requests, feedback, announcements and settings, then the guided tour, then polish. Never cut: search, the AI pipeline, code quality, the security baseline.
- Budget: the target is Rp0 for development, the LSP demo, and the first production phase. Use free tiers only; never add a paid dependency without the owner's approval. Payments run in sandbox until launch gates pass (COMPLIANCE.md section 8). A free, non-commercial launch also matches TMDB's free-use terms.
- The author must be able to run, explain and maintain everything. No exotic tooling.
- Ready-made principle (D-53): the product is complete for the LSP with sandbox payments and demo data; going public is configuration, accounts and money, not coding (OPERATIONS.md section 18).
- Technology baseline: PHP 8.5, Laravel 13, MySQL 8.4 LTS, latest Node LTS; always the newest supported stable release of every tool, kept patched (D-43).

## 7. Glossary
- Title: a movie or a TV series row in our catalog.
- Catalog: the permanent local table of title metadata.
- Scene search: user asks for a specific moment of a title; we return clip links.
- ClipFinder: the server component that finds clip links (curated, cached, grounded, search-link).
- Quota: number of AI messages a user can send per day.
- Grace period: days after a failed renewal during which the paid plan stays active.
- Landing page: the public page guests see at `/` (F-22).
- Build id: the identifier of the running release, used by the update notice (F-23).
