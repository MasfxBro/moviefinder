# CODING_RULES

Goal: clean, consistent, explainable code. The author must be able to explain every file. These rules map to the certification units (see section 9).

## 1. Allowed packages (ask before adding any other)
Runtime: `laravel/framework`, `laravel/breeze` (Blade stack), `midtrans/midtrans-php`, `pragmarx/google2fa-laravel` + `bacon/qr-code` (admin 2FA).
Dev: `pestphp/pest` + `pest-plugin-laravel`, `laravel/pint`, `larastan/larastan`, `barryvdh/laravel-debugbar`, `fakerphp/faker`.
Frontend (npm): `tailwindcss`, `alpinejs`, `vite` + `laravel-vite-plugin`, `@fontsource/inter`.
Versions: install the newest stable release of every package at install time; `composer.lock` and `package-lock.json` pin them (D-43).
Use Laravel's `Http` facade for API calls (no extra HTTP libraries). Image re-encoding for admin poster uploads uses PHP's GD extension (no extra package). No jQuery, no UI kits, no CDN scripts at runtime except the YouTube IFrame API and the Midtrans Snap script loaded on demand.

## 2. PHP style
- `declare(strict_types=1);` in every PHP file. Type every parameter, return, and property.
- PSR-12 enforced by Pint (`vendor/bin/pint`). Larastan level 5+ must pass.
- Target PHP 8.5 and Laravel 13. Deprecation notices are failures: fix them, never silence them (PHPUnit `failOnDeprecation`).
- Naming: classes `PascalCase`, methods/variables `camelCase`, DB columns/config keys `snake_case`, routes `kebab-case`, enums `PascalCase` cases.
- One class per file. Small methods (aim ≤ 25 lines). Early returns over deep nesting. No magic numbers: use config/settings/enum constants.
- Enums for every fixed set of values (roles, statuses, trust levels). No raw strings for those.
- No static helpers with hidden state. Use dependency injection (constructor) and interfaces from `Contracts/`.

## 3. Structure rules
- Controller: validate (FormRequest) -> authorize (Policy) -> call one Service/Action -> return view/resource. No queries, no business rules.
- Service/Action: business logic, transactions, external calls through Contracts.
- Model: relations, casts, scopes, small accessors. No business workflows.
- Policies for authorization. Middleware for cross-cutting concerns.
- DTOs for data crossing layers (TMDB mapping, AI replies). No passing raw arrays of unknown shape.
- Routes grouped by area with names; no closures with logic in route files. Catalog and user routes are registered inside the `auth` group; only the routes listed in D-35 live in the public group.

## 4. Blade and frontend
- Blade components for anything used twice. No logic beyond simple conditionals/loops; compute in controllers/view models.
- `{{ }}` only for dynamic output. Never `{!! !!}` with user-derived data.
- Alpine components live in `resources/js/*.js` and are registered once; Blade only declares `x-data="aiChat(...)"` with safe data.
- No inline `<script>` blocks with logic; no inline styles; Tailwind utilities plus tokens from DESIGN_SYSTEM.md. No hard-coded colors.
- Every image has alt text and explicit dimensions or aspect ratio; below-the-fold images are `loading="lazy"`.
- All UI text in formal, standard English without contractions (D-48, DESIGN_SYSTEM.md section 15). Keep user-facing strings in lang files, one place per feature.

## 5. Database rules
- Migrations only (never edit the DB by hand). One concern per migration. Indexes and foreign keys as in DATABASE.md.
- Eloquent or query builder with bindings. Eager load (`with`) to avoid N+1. Paginate lists.
- Seeders and factories for demo/test data; no real personal data.
- Money as integer IDR.
- The database is MySQL 8.4 LTS (D-34). Do not write PostgreSQL- or SQLite-only SQL.

## 6. Error handling and logging
- Throw specific exceptions in services; catch at boundaries (controller/exception handler) and show friendly messages.
- Use `Log::warning/error` with context ids, never secrets or full payloads.
- External calls: timeout + retry policy + fallback as specified in INTEGRATIONS.md.
- User-facing failures use the messages and pages in DESIGN_SYSTEM.md section 11. Error views must not run queries or read the session.

## 7. Comments and docs
- Comment WHY, not WHAT. Short English comments. PHPDoc on public service methods (purpose, params that aren't obvious, exceptions).
- No commented-out code, no leftover TODO/FIXME, no `dd()`, `dump()`, `var_dump()`, `console.log` in committed code.
- Keep `README.md` current (setup, env, commands, architecture overview, how to run tests, demo steps).
- When behavior or data changes, update the affected doc in `docs/` in the same change.

## 8. Anti "AI slop" rules
- No placeholder text (lorem ipsum, "Coming soon" everywhere), no fake data in production paths, no unexplained magic.
- No unused code, imports, routes, components, config keys, or packages.
- No duplicate logic: extract once, reuse.
- Consistent naming and structure across features; follow existing patterns before inventing new ones.
- Prefer boring, readable solutions over clever ones.
- Don't generate boilerplate the project doesn't need (extra interfaces, abstract layers) beyond those named in ARCHITECTURE.md.
- Every file you add must have a reason you can state in one sentence.
- Visual identity rules (no decorative gradients, no emoji icons, no token codes shown as text) are in DESIGN_SYSTEM.md section 12.

## 9. Mapping to certification units
| Unit | Where it shows up |
|---|---|
| Implement UI | Blade pages and components matching the mockup and DESIGN_SYSTEM.md |
| Text, graphics, multimedia | typography tokens, posters/backdrops, click-to-load video previews |
| Organize functions, files, resources | folder structure in ARCHITECTURE.md; one concern per file |
| Code guidelines and best practices | this file, Pint, Larastan, naming, comments |
| Structured programming | services/actions with functions, conditionals (quota, roles), loops (rendering lists, batch refresh) |
| Use libraries/pre-built components | Breeze, Tailwind, Alpine, Midtrans SDK, Pest |

## 10. Testing rules
- Pest. Feature tests for: auth/roles/policies, guest access (public routes versus redirect to login), error pages (status code, no leaks), `/version`, quota (including parallel), AI pipeline (fake LlmClient/TmdbClient/ClipFinder), clip validation (PlatformUrl, ChannelPolicy, ClipRelevanceJudge), subscription state machine and webhook idempotency, request flow, filters, admin access.
- Use fakes (`Http::fake`) for every external service. No real network in tests. Tests run with `AI_DRIVER=fake`; no test calls Google.
- A `CopyStyle` test scans `lang/` and the Blade views for contractions (n't, 've, 're, 'll, 'm) in user-facing strings.
- A bug fix includes a test that fails before the fix.
- Never delete or weaken a test to get green.

## 11. Git rules
- One branch per task: `task/<number>-<short-name>`. Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`).
- Small commits. Never commit `.env`, `vendor/`, `node_modules/`, `storage/*.log`, keys, or dumps.

## 12. Definition of Done (check before replying "done")
- [ ] Acceptance criteria in FEATURES.md for the task are met
- [ ] Relevant security items in SECURITY.md are satisfied; policies/validation present
- [ ] Tests added/updated and `php artisan test` passes
- [ ] `vendor/bin/pint` and Larastan clean; `npm run build` succeeds
- [ ] No N+1 queries on touched pages; indexes exist for new filters
- [ ] UI has loading, empty, error states; responsive; accessible (DESIGN_SYSTEM.md)
- [ ] Access matches D-35 (new routes sit in the right group) and failures follow DESIGN_SYSTEM.md section 11
- [ ] No secrets, debug code, dead code, or TODOs
- [ ] Docs updated if behavior/data changed
- [ ] Reply lists changed files, verification steps, assumptions, and a suggested commit message

## 13. Continuity rules (changes must not disturb users)
- Migrations are backward-compatible: add columns as nullable or with defaults, backfill with a command/job, and remove or rename in a LATER release (expand/contract). Never drop or rename something the running release still uses. Avoid long table locks; large backfills run in chunks.
- Risky or new behavior sits behind a setting/feature flag (read through the Settings service) so it can be switched off without a deploy.
- Runtime values (quotas, caps, prices, texts) come from settings/config, not from code constants.
- Queue jobs and cached values stay compatible for one release: do not rename job classes or change payload shape while jobs may be pending; version cache keys (`v1:`) when value shapes change.
- Never clear the whole cache on deploy; invalidate targeted keys (for example when an admin edits a title or a setting).
- Never write state into the release folder: logs, uploads, sessions and cache go to shared `storage/` or external stores.
- Frontend assets use hashed file names (Vite); never depend on fixed asset names.
- AJAX/JSON endpoints stay compatible with the previous release's JavaScript for at least 14 days (open tabs keep running old JS): only add optional fields; remove or change a field's shape in a later release.
- Never hardcode the build id; it comes from the `BUILD_ID` file through `config('app.build')`.
- Every deploy-affecting change updates `CHANGELOG.md` and, if procedure changes, `docs/OPERATIONS.md`.
