# MovieFinder — Agent Instructions (read first, every session)

Working name: MovieFinder (never hardcode it; use `config('app.name')`).
One line: a legal, AI-assisted SEARCH site for movies and series. It finds titles and points users to
licensed places to watch or preview. It never hosts, streams, downloads or stores video.

## Interaction mode (discussion vs. command) — applies to every message
- Classify each owner message first: COMMAND (a clear instruction to do something, e.g. "do it", "lanjut", "apply", a numbered task) or DISCUSSION (a question, doubt, confusion, thinking aloud, "what if", "is it better to...").
- DISCUSSION: do NOT change files and do NOT run commands. Work out what the owner is actually confused about, answer it, and ask only the questions needed to settle it (at most 2-3, specific to the topic, never generic). Wait for approval.
- Execute only after the owner agrees and tells you to. If you cannot tell whether a message is a command, ask one short question.
- A request that is not covered by the docs, or that conflicts with them: critique it against the docs (name the file and rule), propose the doc edit, ask for confirmation, then act. Stay inside the product's purpose.
- Reply to the owner in Indonesian. Code, comments, commit messages, docs and UI text stay in English.

## Before you write any code
1. Read, in order: `docs/PROJECT_BRIEF.md` -> `docs/DECISIONS.md` -> the docs relevant to the task (map below) -> `docs/CODING_RULES.md`.
2. The docs are the source of truth. If a request contradicts a doc, STOP, say which doc and which rule, and propose a doc edit first. Do not silently deviate.
3. Do only what the current task asks. No extra features, no extra packages, no refactors outside the task.
4. If something needed is missing from the docs: for small implementation details, make the smallest reasonable assumption and list it under "Assumptions" at the end of your reply. If it would change product behavior, scope, UX, data, security, or cost, do NOT assume: critique it against the docs, ask the owner, and wait. Never invent features.

## Doc map
| Topic | File |
|---|---|
| What the product is / is not | docs/PROJECT_BRIEF.md |
| Locked and open decisions | docs/DECISIONS.md |
| Features and acceptance criteria, routes | docs/FEATURES.md |
| Stack, folders, flows, jobs, env vars | docs/ARCHITECTURE.md |
| Tables, columns, indexes, retention | docs/DATABASE.md |
| TMDB, Gemini, YouTube, Midtrans, email | docs/INTEGRATIONS.md |
| AI search behaviour, prompts, guardrails, quota | docs/AI_BEHAVIOR.md |
| Security rules and tests | docs/SECURITY.md |
| Legal, policy, attribution, launch gates | docs/COMPLIANCE.md |
| Colors, type, components, UX states, error pages, identity | docs/DESIGN_SYSTEM.md |
| Code style, structure, definition of done | docs/CODING_RULES.md |
| Deployment, zero-downtime releases, maintenance, backups | docs/OPERATIONS.md |
| Ordered tasks | docs/TASKS.md |
| Local setup (owner only; agents never run it) | docs/SETUP.md |

## Hard rules (never break)
- No secret in code, in frontend, in logs, or in git. Keys live in `.env` only. Never log request bodies that contain keys or tokens.
- Third-party APIs (TMDB, Gemini, Midtrans) are called from the server only.
- Never store video, subtitles, dialogue text, or poster files. Images are loaded from the TMDB image CDN by path.
- The AI does the searching (web search tool) and the judging of relevance, but a link reaches users only after our code validated it (host allow-list, existence check, channel policy). A URL the model merely typed from memory is never shown. See AI_BEHAVIOR.md and INTEGRATIONS.md.
- All user input is validated by a FormRequest. All output in Blade uses `{{ }}`. `{!! !!}` is forbidden for anything user-derived.
- Authorization is enforced by Policies/Gates/middleware on the server, never by hiding buttons.
- Quota checks are atomic and server-side. A user's plan (Free, Plus, Pro) is derived from an active subscription or manual grant through EntitlementService, never from a stored flag alone. AI spending is limited per user by the AI budget guard (D-45): never bypass or weaken it.
- Adult titles are excluded everywhere (`include_adult=false` and the local `adult` column must be false).
- UI language is English. Code, comments, commit messages are English.
- The admin area lives at a secret path from `ADMIN_PATH` in `.env`. It is never linked, never in robots.txt or sitemaps, returns 404 to non-admins, and has its own login and guard. See SECURITY.md.
- Changes must never disturb users: migrations are backward-compatible (expand/contract), risky behavior sits behind settings/feature flags, content changes happen in the admin without a deploy, and sessions/cache/queues/uploads live outside release folders. See OPERATIONS.md.
- Every page and component is responsive (360px to 1440px+) and interactive as defined in DESIGN_SYSTEM.md section 10.
- Guests only reach the landing page (`/`), pricing, legal pages and auth pages. Every other route requires login and redirects guests to the login page (D-35).
- A new release never reloads a user's page by itself. Open tabs get a non-blocking "new version available" toast and the user chooses when to update (F-23, OPERATIONS.md).
- Every failure that is not the user's doing shows a specific, branded message with a next action (404, 403, 419, 429, 500, 503, offline, failed requests). No stack traces, no internal names, never `ADMIN_PATH` (F-24, DESIGN_SYSTEM.md section 11).
- Never copy placeholder or garbled text, or design tokens printed as text, from the reference mockup (DESIGN_SYSTEM.md section 13).
- All UI text is formal, standard English without contractions (D-48). AI replies follow the user's language.
- Free users run on the no-billing Gemini key with daily places (D-47) and never trigger AI web clip search; only Plus and Pro use the paid key.
- The real Gemini keys are set up only in task T-43, guided. Until then `AI_DRIVER=fake`. When the owner finishes the last task before T-43, remind them that the Gemini key is not configured yet and offer to start T-43.
- The first-time guided tour appears once per account (D-50), and email verification follows D-51; do not change either without a DECISIONS.md update.
- Never open, print or edit `.env`, and never install packages: the owner does both (docs/SETUP.md, D-42). Ask the owner to change a value or add a package.

## Stack (do not change without a DECISIONS.md update)
PHP 8.5, Laravel 13, MySQL 8.4 LTS, Blade + Tailwind CSS + Alpine.js (Vite), Pest for tests, Laravel Pint for style (newest supported versions, D-43). Write code for PHP 8.5: no deprecated features; deprecation notices fail the tests. The database is MySQL 8.4 LTS (D-34): do not write PostgreSQL- or SQLite-specific code.
Allowed packages are listed in docs/CODING_RULES.md. Ask before adding any other.

## Task protocol
1. Restate the task in 2-3 lines and list the files you will create or change.
2. Implement in small steps. Keep controllers thin; logic in Services/Actions.
3. Run: `vendor/bin/pint`, `php artisan test`, `npm run build`. Fix failures. Do not skip or delete tests to make them pass.
4. Self-review against "Definition of Done" in docs/CODING_RULES.md.
5. Reply with: what changed (files), how to verify (commands/URLs), assumptions, and a suggested commit message (Conventional Commits).
6. Do not run `git commit`, `git push`, `git reset` or any history-rewriting command unless the task says so.

## Out-of-task fixes
When the user asks for a fix or tweak that is not a numbered task: find the relevant docs, obey the same rules, change the minimum, add or update a test if behaviour changes, and update the affected doc if behaviour or data changes.
