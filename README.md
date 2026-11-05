# MovieFinder

A film and series **search and listing website** with an AI assistant. Describe a movie in your own words, and MovieFinder helps you find the title, its details, and an official clip preview.

> MovieFinder is **not** a streaming site. It does not host, store, or download video. The only playback is a **60-second preview** through the official YouTube embed, followed by a link to the original video.

Working name: `MovieFinder` (read from `config('app.name')`). Built as a dynamic website project for the Junior Web Developer certification (LSP), November 2026.

---

## Table of Contents

1. [Features](#features)
2. [Roles and Access](#roles-and-access)
3. [Plans](#plans)
4. [Tech Stack](#tech-stack)
5. [Architecture Overview](#architecture-overview)
6. [Requirements](#requirements)
7. [Installation](#installation)
8. [Environment Variables](#environment-variables)
9. [Running the Project](#running-the-project)
10. [Demo Mode](#demo-mode)
11. [Testing and Code Quality](#testing-and-code-quality)
12. [Project Structure](#project-structure)
13. [Documentation](#documentation)
14. [Development Status](#development-status)
15. [Security](#security)
16. [Data Sources and Attribution](#data-sources-and-attribution)
17. [Roadmap](#roadmap)
18. [License](#license)

---

## Features

**For users**
- Landing page, registration with 18+ confirmation, email verification, login
- Home page with poster rows, live search, and filters
- Title detail page with an AI chat card
- General AI chat in the navbar (films and series only)
- Clip preview (60 seconds), then a link to the original video
- Watchlist and 90-day search history
- "Title not found" flow with a request button
- Feedback form and broken-link reports
- First-time guided tour
- Subscription plans (Free, Plus, Pro)

**For admins**
- Secret admin URL, separate login, mandatory 2FA (TOTP)
- Dashboard, title management (CRUD, poster upload, TMDB import)
- Title requests, failed searches, clips, reports, channel allowlist
- Feedback inbox, users, subscriptions and payments
- AI monitoring, settings, announcements, audit log, system health

**System**
- AI answers are validated; links typed from the model's memory are never displayed
- Cost guard for paid plans, daily "AI places" for the Free plan
- Version toast, friendly error and offline pages

## Roles and Access

| Area | Guest | User | Admin |
|---|:-:|:-:|:-:|
| Landing, pricing, Terms, Privacy | Yes | Yes | Yes |
| Register and login | Yes | - | - |
| Home, search, detail, AI chat, watchlist | No | Yes | - |
| Admin panel (secret URL, 2FA) | No (404) | No (404) | Yes |

A user's plan (Free, Plus, Pro) is derived from the subscription, not stored as a role.

## Plans

| | Free | Plus | Pro |
|---|---|---|---|
| Price per month | Rp0 | Rp15.000 | Rp28.000 |
| AI units per day | 5 | 10 | 12 (4 Smart) |
| Web clip searches per period | 0 | 10 | 20 |
| Titles per answer | 3 | 4 | 5 |
| Clips shown | 2 | 4 | 6 |
| Chat memory (turns) | 8 | 12 | 16 |
| Watchlist | 100 | 300 | 500 |
| Title requests per day | 2 | 5 | 10 (priority) |
| Badge | - | PLUS | PRO |

All values are defaults and can be changed in the admin settings.

## Tech Stack

| Layer | Technology |
|---|---|
| Language and framework | PHP 8.5, Laravel 13 |
| Database | MySQL 8.4 LTS |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Authentication | Laravel Breeze, TOTP 2FA (`google2fa-laravel`) |
| AI | Google Gemini (structured JSON output) |
| Film data | TMDB API |
| Payments | Midtrans (sandbox in demo mode) |
| Testing and quality | Pest, Laravel Pint, Larastan (PHPStan) |
| Local environment | Laragon, Mailpit, Git |

## Architecture Overview

MovieFinder is a classic server-rendered Laravel application (MVC), without a single-page app.

```
Browser  ->  Routes  ->  Middleware (auth, verified, rate limit)
                              |
                        Controllers
                              |
              Services (AI pipeline, ClipFinder, Billing, Catalog)
                              |
         Models / MySQL     TMDB API     Gemini API     Midtrans
```

**AI pipeline:** input validation, access check (daily place for Free, cost guard for paid), atomic quota reservation, load chat memory from cache, build prompt, call the LLM, validate JSON, verify each title candidate against TMDB, find clips through the ClipFinder chain, validate every link, save the turn, and record the cost.

**Catalog:** a permanent local catalog imported lazily from TMDB. Records stay fresh for at most 150 days. The database stores only metadata and poster paths. No video or images are stored.

## Requirements

- PHP 8.5 with the extensions Laravel requires (`mbstring`, `openssl`, `pdo_mysql`, `curl`, `fileinfo`, `intl`)
- Composer 2
- MySQL 8.4 LTS
- Node.js LTS (build only)
- Git
- Recommended on Windows: [Laragon](https://laragon.org)
- Mailpit (local email testing)

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/<your-username>/moviefinder.git
cd moviefinder

# 2. Install PHP dependencies
composer install

# 3. Install frontend dependencies
npm install

# 4. Create the environment file and application key
cp .env.example .env
php artisan key:generate
```

Create two empty databases in MySQL: `moviefinder` and `moviefinder_test`. Then set your database credentials in `.env` (see below) and run:

```bash
# 5. Create tables
php artisan migrate

# 6. (Optional) Seed demo data
php artisan db:seed
```

> Never commit `.env`. All API keys live only in that file.

## Environment Variables

| Variable | Purpose | Example |
|---|---|---|
| `APP_NAME` | Application name | `MovieFinder` |
| `APP_URL` | Base URL | `http://moviefinder.test` |
| `APP_TIMEZONE` | Framework time zone | `UTC` |
| `BUSINESS_TIMEZONE` | Business rules (daily reset) | `Asia/Jakarta` |
| `DB_DATABASE` | Database name | `moviefinder` |
| `DB_USERNAME` / `DB_PASSWORD` | Database credentials | - |
| `MAIL_HOST` / `MAIL_PORT` | Mailpit | `127.0.0.1` / `1025` |
| `TMDB_API_KEY` | Film data | (your key) |
| `AI_DRIVER` | `fake` for sample answers, or the real driver | `fake` |
| `GEMINI_API_KEY` | Gemini key for the application | (your key) |
| `LAUNCH_MODE` | `demo` disables real payments | `demo` |
| `MIDTRANS_SERVER_KEY` / `MIDTRANS_CLIENT_KEY` | Midtrans sandbox keys | (your keys) |
| `ADMIN_PATH` | Secret admin URL segment | (choose a random string) |

## Running the Project

With Laragon, the site is served at `http://moviefinder.test`, so `php artisan serve` is not needed.

```bash
# Frontend assets (development)
npm run dev

# or a production build
npm run build

# Queue worker (required: verification emails are queued)
php artisan queue:work
```

Open Mailpit at `http://localhost:8025` to read verification emails.

## Demo Mode

| Setting | Effect |
|---|---|
| `LAUNCH_MODE=demo` | Payments use the sandbox. A banner shows "Demo mode - no real payments". |
| `AI_DRIVER=fake` | The AI returns sample answers. A banner shows "AI demo mode". |

Real payments stay disabled until the launch gate passes (commercial TMDB license, production Midtrans account, legal review, business registration).

## Testing and Code Quality

```bash
php artisan test        # Pest test suite
./vendor/bin/pint       # code style
./vendor/bin/phpstan    # static analysis (Larastan, level 5)
```

Performance testing: record page load results (for example Lighthouse or browser DevTools) in the table below before the presentation.

| Page | Tool | Result |
|---|---|---|
| Landing | | |
| Home | | |
| Search | | |
| Title detail | | |

## Project Structure

```
moviefinder/
├── app/
│   ├── Http/Controllers/    # request handling
│   ├── Models/              # Eloquent models
│   └── Services/            # AI pipeline, ClipFinder, billing, catalog
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── views/               # Blade templates
│   ├── css/  js/            # Tailwind and Alpine
├── routes/web.php
├── tests/                   # Pest tests
├── docs/                    # project documentation
├── .github/copilot-instructions.md
└── opencode.json
```

> Adjust this tree to match the actual repository.

## Documentation

All design decisions are written in `docs/`:

| File | Content |
|---|---|
| `PROJECT_BRIEF.md` | Product summary |
| `DECISIONS.md` | Decisions D-01 to D-53 |
| `FEATURES.md` | Features F-01 to F-25 |
| `ARCHITECTURE.md` | System architecture |
| `DATABASE.md` | Tables and relations |
| `INTEGRATIONS.md` | TMDB, Gemini, Midtrans, YouTube |
| `AI_BEHAVIOR.md` | AI rules and prompts |
| `SECURITY.md` | Security baseline |
| `COMPLIANCE.md` | Legal and licensing |
| `DESIGN_SYSTEM.md` | Colors, typography, components |
| `CODING_RULES.md` | Code conventions |
| `OPERATIONS.md` | Releases, hosting, unit economics |
| `SETUP.md` | Environment setup |
| `TASKS.md` | Task list and priorities |

The product requirements are in `PRD.md`.

## Development Status

Tick each item only when it works. Do not describe unfinished work as done.

- [x] Documentation (D-01 to D-53, F-01 to F-25)
- [x] Environment setup (Laravel 13, MySQL 8.4, packages)
- [ ] Authentication and email verification
- [ ] Catalog and home page
- [ ] Search and filters
- [ ] Title detail page
- [ ] AI chat (fake driver)
- [ ] Clips and 60-second preview
- [ ] Watchlist
- [ ] Plans and quotas
- [ ] Admin panel
- [ ] Payments (sandbox)
- [ ] Real Gemini key (final task)

## Security

- Passwords are hashed; CSRF protection and input validation are enabled
- Rate limits on AI messages, checkout, and email resend
- Admin area uses a secret URL, a separate guard, and mandatory TOTP 2FA; outsiders receive a 404
- Verification links are signed and expire after 60 minutes, with no auto-login from another browser
- Payment webhooks are verified by signature
- API keys are stored only in `.env`

To report a vulnerability, open a private issue or contact the maintainer.

## Data Sources and Attribution

- Film and series metadata and images: [TMDB](https://www.themoviedb.org). *This product uses the TMDB API but is not endorsed or certified by TMDB.*
- Video previews: official YouTube embed, subject to the YouTube Terms of Service.
- Commercial use requires a TMDB commercial license.

## Roadmap

After the certification: production hosting, production email provider with a custom domain, final name and domain, Xendit as an additional payment option, legal review of Terms and Privacy, and the public launch gate (D-13).

## License

Add your chosen license here (for example MIT). Third-party data remains under the licenses of its providers.

## Author

`Elmaliq S. A` — Junior Web Developer candidate.
