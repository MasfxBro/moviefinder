# SETUP (Phase 0: owner does this once, by hand, before the first task)

Goal: when the first task prompt is sent, the project folder is complete: every tool installed, every allowed package installed, the database ready, the docs in place, and the agent verified. Task prompts assume this file is fully done.

Rules (D-42):
- The owner runs this file. Agents never install packages and never open, print or edit `.env` (ask the owner to change a value).
- Package list = CODING_RULES.md section 1 and nothing else. Adding one later needs a CODING_RULES.md change first.
- Facts below were checked in October 2026. Versions move; if a command fails, read the error and ask before improvising.
- Versions (D-43): the newest stable release that still gets security fixes, for everything: PHP 8.5.x, Laravel 13.x, MySQL 8.4 LTS, the latest Node LTS, Composer 2, Git. Never install an older PHP branch (8.2 loses security support on 31 Dec 2026). Never lower a version to make a package install; ask instead.

## 1. Accounts and keys (prepare now, use later)
Keep every key in `.env` only. Never paste a key into a chat, a Copilot prompt, a screenshot or git.
| Item | Needed from | Notes |
|---|---|---|
| TMDB account + API Read Access Token | T-08 | free, choose non-commercial use (D-13) |
| Two Google AI Studio (Gemini) keys: one from a project WITHOUT billing (Free users) and, only after the first paying customer, one from a separate billed project (Plus and Pro) | T-43, the end of the project | do NOT create them now. Task T-43 guides you step by step and the agent never sees `.env`. Never link billing to the free project; keep both separate from the keys used by dev tools (D-22) |
| Midtrans sandbox account and keys (server + client) | T-38 | sandbox only (`MIDTRANS_IS_PRODUCTION=false`). In the sandbox dashboard enable card, GoPay, ShopeePay, QRIS and bank transfer, and recurring for card and GoPay |
| Admin email for the seeder | T-31 | `ADMIN_EMAIL` in `.env` |
| Email | now: Mailpit; later: a provider | Mailpit (a local mail catcher bundled with Laragon) is used for development and the LSP demo and needs no account. The production provider is D-19 and usually needs your own domain |

## 2. Install the tools (Windows + Laragon)
1. **Laragon Full** (version 8.x at the time of writing). Laragon has been licensed software since version 7: free for non-commercial use without a license. Fine for development and the LSP demo; read the license at laragon.org before any commercial use (D-32).
2. In Laragon: `Tools > Path > Add Laragon to Path`, then close and reopen all terminals. Without this, VS Code uses the wrong or missing PHP.
3. Pick **PHP 8.5** (the latest 8.5.x patch; Laravel 13 supports 8.3 to 8.5, and 8.5 has security support until 31 Dec 2029) and **MySQL 8.4 LTS** (the long-term-support line; not 9.x, which is a short-support innovation track). Use `Menu > PHP / MySQL > Version`. If 8.5 or 8.4 is not listed, use `Tools > Quick add` or put the official Windows NTS x64 zip from windows.php.net into `C:\laragon\bin\php`.
4. PHP extensions: `Menu > PHP > Extensions`, make sure these are on: `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `zip`. Restart Laragon.
5. Node.js: use the latest LTS. Today that is **Node 24** (supported until 30 Apr 2028); when Node 26 is promoted to LTS (late October 2026) use Node 26 instead. Do not use Node 22 (maintenance only, ends 30 Apr 2027) or any odd-numbered release such as 25 (unsupported). Laragon's bundled Node may be older: add the right one with `Tools > Quick add`, or install it from nodejs.org, and keep the latest patch. Composer: the bundled Composer 2, updated with `composer self-update`.
6. **Git** (included in Laragon Full, or install Git for Windows). Set your identity once: `git config --global user.name "..."` and `git config --global user.email "..."`.
7. **VS Code** 1.122 or newer, with GitHub Copilot Chat. Recommended extensions: PHP Intelephense (`bmewburn.vscode-intelephense-client`), Tailwind CSS IntelliSense (`bradlc.vscode-tailwindcss`), EditorConfig.
8. If PowerShell blocks `npm`: `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned`.
9. 9Router is already connected and your combos are registered in VS Code (`webfast`, `webcoder`, `webui`, `webplan`, `webreview`, `websec`, `webtest`, `webdocs`, `webquick`, `webrescue`). Confirm that `Chat: Utility Small Model` is `webquick`.
10. Mail catcher: start **Mailpit** from Laragon's menu. By default it listens for mail on 127.0.0.1 port 1025 and shows the inbox at http://localhost:8025 (check the ports in Laragon). If Laragon does not bundle it, download Mailpit from its project page and run it. Emails sent by the app appear there, never on the internet.

## 3. Create the project
Open a Laragon terminal:
```
cd C:\laragon\www
composer create-project laravel/laravel moviefinder
cd moviefinder
composer require laravel/breeze --dev
php artisan breeze:install blade --pest
composer require midtrans/midtrans-php pragmarx/google2fa-laravel bacon/qr-code
composer require --dev larastan/larastan barryvdh/laravel-debugbar
npm install
npm install @fontsource/inter
```
Notes:
- Breeze (Blade + Alpine + Tailwind) is the only starter kit that fits D-02 (server-rendered Blade, no SPA, no UI kit). Laravel says Breeze no longer receives updates, but it supports the current Laravel and it copies its code into our app, so we own that code and the SECURITY.md checks cover it. Do not use the newer Livewire, React or Vue kits.
- Do not add `--dark`: the theme is dark only (D-15).
- Laravel 11 and later ship `laravel/pint`, `fakerphp/faker` and Vite already. `breeze --pest` installs Pest and its Laravel plugin.
- Check the Tailwind major version: `npm ls tailwindcss`. Breeze normally installs 3.x, which matches DESIGN_SYSTEM.md section 9 (`tailwind.config.js`). If you see 4.x, tell `webplan` first (T-01 handles this); do not downgrade by hand.

## 4. Database
Open HeidiSQL from Laragon (or any MySQL client) and run:
```
CREATE DATABASE moviefinder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE moviefinder_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'moviefinder'@'localhost' IDENTIFIED BY 'choose-a-password';
GRANT ALL ON moviefinder.* TO 'moviefinder'@'localhost';
GRANT ALL ON moviefinder_test.* TO 'moviefinder'@'localhost';
```
Tests must run on MySQL, not SQLite: the catalog uses FULLTEXT and the quota test needs real MySQL behavior.

## 5. Configure
1. `.env` (local values; names follow ARCHITECTURE.md section 7):
```
APP_NAME=MovieFinder
APP_ENV=local
APP_DEBUG=true
APP_URL=http://moviefinder.test
APP_TIMEZONE=UTC
BUSINESS_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moviefinder
DB_USERNAME=moviefinder
DB_PASSWORD=<the password you chose>
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS=no-reply@moviefinder.test
MAIL_FROM_NAME="${APP_NAME}"
AI_DRIVER=fake
LAUNCH_MODE=demo
ADMIN_PATH=ops-<24 random letters and digits>
ADMIN_EMAIL=<your admin email>
```
Leave `TMDB_READ_TOKEN`, `GEMINI_API_KEY_FREE`, `GEMINI_API_KEY_PAID` and `MIDTRANS_*` empty for now (`AI_DRIVER=fake` runs the chat with example answers until task T-43). Then copy the same names with empty values into `.env.example`.
2. `phpunit.xml`: set `DB_CONNECTION` to `mysql` and `DB_DATABASE` to `moviefinder_test` (replace the SQLite in-memory lines), and add `failOnDeprecation="true"` to the `<phpunit ...>` tag so deprecation notices fail the tests (D-43).
3. `phpstan.neon` in the project root:
```
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    paths:
        - app
    level: 5
```
4. Folders and docs: create `docs/` and copy all docs into it (`SETUP.md` included). Copy `copilot-instructions.md` to `.github/copilot-instructions.md`. Create `.vscode/` only if you want workspace settings.
5. Run `php artisan key:generate` if `APP_KEY` is empty, then `php artisan migrate`.
6. In Laragon, `Menu > Apache/Nginx > Reload`. Laragon creates `http://moviefinder.test` automatically (Auto Virtual Hosts).

## 6. First commit
```
git init
git add .
git commit -m "chore: initial Laravel project with docs"
```
From now on the owner commits, using the message the agent suggests (the agent never commits, see copilot-instructions.md).

## 7. Verification checklist (every box must pass)
| Check | Command / action | Expected |
|---|---|---|
| PHP | `php -v` | 8.5.x |
| Extensions | `php -m` and `php -r "var_dump(function_exists('imagewebp'));"` | the extensions in step 2.4 are listed; `bool(true)` |
| Composer, Node | `composer -V`, `node -v`, `npm -v` | Composer 2.x; Node 24 or newer LTS (not 22, not 25) |
| Laravel | `php artisan --version` | Laravel 13.x (or the latest stable) |
| Database | `php artisan migrate` | runs without errors |
| Site | open `http://moviefinder.test` | Laravel page loads |
| Auth scaffold | open `/register` and `/login` | Breeze pages render |
| Build | `npm run build` | succeeds |
| Style | `vendor/bin/pint --test` | passes |
| Static analysis | `vendor/bin/phpstan analyse` | passes at level 5 |
| Tests | `php artisan test` | green, running on MySQL |
| Tailwind | `npm ls tailwindcss` | note the major version |
| Packages | `composer show` | contains only the CODING_RULES.md section 1 packages plus the Laravel skeleton defaults |
| Security audit | `composer audit` and `npm audit` | no high or critical findings |
| Mail | in `php artisan tinker` run `Mail::raw('Test', fn ($m) => $m->to('test@example.com')->subject('Test'));` | the message appears in Mailpit at http://localhost:8025 |
| Secrets | `git status` | `.env` is not listed |

## 8. Agent check (before T-01)
Open the project in VS Code, open Copilot Chat in agent mode, start a NEW chat for each test.
1. Combo `webplan`: "Read .github/copilot-instructions.md and docs/DECISIONS.md. Change nothing. Summarize D-34 to D-53 in one line each." Expected: a correct summary, no file changes.
2. Combo `webplan`: "Is it better to build the AI chat with Livewire?" Expected: no file changes, a critique that cites D-02 and CODING_RULES.md section 1, and at most 2-3 specific questions. This proves the discussion-versus-command rule works.
3. Combo `webui`: attach one screenshot and ask it to describe it. Expected: a correct description (confirms image input).

## 9. Troubleshooting
- `php` or `composer` not found: redo step 2.2, reopen the terminal.
- `migrate` cannot connect: start MySQL in Laragon, recheck `DB_*` in `.env`.
- `moviefinder.test` not found: Laragon must be running, then `Menu > Apache > Reload`; Laragon may ask for admin rights to edit the hosts file.
- `npm` blocked: see step 2.8.
- A package refuses to install on PHP 8.5 or Laravel 13: Composer prints which constraint blocks it; send that to `webplan` or me. Never lower the PHP or Laravel version.
- `breeze:install` fails: send the full error to `webplan` or me. Do not switch to another starter kit without a DECISIONS.md change.

## 10. Done
Phase 0 is done when every box in section 7 passes, the agent check passes, and the first commit exists. Then start `docs/TASKS.md` at T-01.
