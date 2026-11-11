# GO_LIVE Runbook

This file turns the go-live table in OPERATIONS.md section 18 into exact, numbered steps for each item. Work through every item in order. Do not flip `LAUNCH_MODE` or charge real money until every gate is cleared.

> Nothing here needs new features. Each item is configuration, an account, or a legal review.

---

## Status Legend
- [ ] Not started
- [~] In progress
- [x] Done

---

## 1. `LAUNCH_MODE`, `payments_live`, Feature Flags

**What**: Switch the app from demo mode to live mode.

**Steps**:
1. SSH into the production server.
2. Open `shared/.env` in a text editor.
3. Set `LAUNCH_MODE=` (empty, or any value that is not `demo`). The demo banner disappears and the payment flow uses the real gateway.
4. Confirm `features.payments_live=true` is set in the admin Settings panel (not in `.env`), or add it via `php artisan tinker` if the setting row does not exist yet:
   ```php
   app(\App\Services\Settings::class)->set('features.payments_live', true);
   ```
5. Verify the demo banner is gone on the pricing page.
6. Confirm `/pricing` shows the real checkout button (not the demo notice).

**Cost**: none.

---

## 2. Gemini Free Key

**What**: Connect the free-tier Gemini key so Free-plan users can use AI search.

**Steps** (follow T-43 interactively for the full guided flow):
1. Go to [Google AI Studio](https://aistudio.google.com/app/apikey).
2. Create a **new Google Cloud project** — do not reuse an existing project and do not enable billing on this project.
3. Generate an API key for that project.
4. Verify on the Google Cloud Console that the project has **no billing account attached** (Billing page should say "This project has no billing account").
5. Add to `shared/.env` on the server:
   ```
   GEMINI_API_KEY_FREE=<the key>
   AI_DRIVER=gemini
   ```
6. Run `php artisan ai:check` and confirm the free key returns HTTP 200 and the model is available.
7. Set `AI_MODEL_STANDARD` and `AI_MODEL_SMART` in `shared/.env` to the model names shown by `ai:check`.
8. Set `ai.free.pool_daily_limit` in admin Settings to the daily request limit shown by `ai:check`.
9. Run `php artisan ai:evaluate` to check answer quality against the golden set.
10. Test the Free AI chat end to end: open `/`, log in as a Free user, chat, and confirm quota is consumed.

**Cost**: none (free tier; Free users never trigger billing charges).

---

## 3. Gemini Paid Key

**What**: Connect the paid-tier Gemini key for Plus and Pro users (AI clip search, Smart searches).

> Do this only after the first paying customer. Until then, AI web clip search shows the launch notice and Smart searches fall back to standard.

**Steps**:
1. Create a **separate** Google Cloud project — not the one used for the free key.
2. Enable billing on this project and set a budget alert (e.g. Rp 100.000/month) so you are notified before costs exceed the AI budget guard.
3. Generate an API key for this project in Google AI Studio.
4. Add to `shared/.env`:
   ```
   GEMINI_API_KEY_PAID=<the key>
   ```
5. Run `php artisan ai:check` and confirm the paid key returns HTTP 200.
6. Test Plus and Pro AI chat and AI clip search end to end.
7. Monitor the Google Cloud billing dashboard and the admin AI monitoring page daily for the first week.

**Cost**: per use; covered by paid plan budgets (see OPERATIONS.md section 17).

---

## 4. TMDB Commercial License

**What**: The TMDB API terms require a commercial license for paid services.

**Steps**:
1. Log in to your TMDB account at [themoviedb.org](https://www.themoviedb.org).
2. Navigate to your API settings and check the current terms for commercial use.
3. Submit a commercial license request (the process and cost are set by TMDB; verify on the day you apply).
4. Wait for approval before accepting any real payments.
5. Record the license details in a secure note in the owner's password manager.

**Cost**: unknown — ask TMDB.

---

## 5. Midtrans Production Account

**What**: Activate real payments.

**Steps**:
1. Log in to the [Midtrans dashboard](https://dashboard.midtrans.com).
2. Complete the merchant verification process (business documents, website URL, Terms and Privacy pages must be live and reviewed — see item 7 below).
3. In the Midtrans Production environment, activate: **card** (Visa, Mastercard, JCB), **GoPay**, **ShopeePay**, **QRIS**, **bank transfer**, and **recurring** (for card and GoPay auto-debit).
4. Set the notification URL in the Midtrans dashboard to `https://<your-domain>/billing/webhook`.
5. Copy the **production** Server Key and Client Key.
6. Add to `shared/.env`:
   ```
   MIDTRANS_SERVER_KEY=<production server key>
   MIDTRANS_CLIENT_KEY=<production client key>
   MIDTRANS_IS_PRODUCTION=true
   ```
7. Test a real end-to-end payment on the live site with a small amount.
8. Confirm the webhook fires, `webhook_events` row is inserted, and the subscription activates.

**Cost**: per transaction (see OPERATIONS.md section 17 for fee estimates).

---

## 6. Business Registration, Tax, PSE

**What**: Indonesian legal and regulatory requirements.

**Steps**:
1. Consult a local legal or tax advisor before accepting payments from Indonesian users.
2. Check if **electronic system operator (PSE) registration** applies (Kominfo / MCI registration for apps that process Indonesian user data).
3. Register the business entity if required.
4. Obtain a tax ID (NPWP) if applicable.
5. Confirm VAT obligations for digital services (PPN Digital).
6. Record all registrations and their renewal dates in a secure note.

**Cost**: varies; confirm with the advisor.

---

## 7. Terms and Privacy Review

**What**: The legal page drafts must be reviewed by a qualified person before charging anyone.

**Steps**:
1. Have a qualified person (lawyer or legal consultant familiar with Indonesian law and UU PDP) review:
   - `/terms` (Terms of Service)
   - `/privacy` (Privacy Policy)
   - `/copyright` (Copyright and takedown)
   - `/refund` (Refund policy)
2. Confirm coverage of: AI processing disclosure (Gemini), YouTube clause, subscription terms, UU PDP data subject rights, governing law.
3. Apply any required changes in the Blade views.
4. Record the reviewer's name, date, and scope of review.

**Cost**: varies.

---

## 8. Email Provider and Domain

**What**: Verification and reset emails must come from your own domain in production.

**Steps**:
1. Choose a transactional email provider (e.g. Resend, Mailgun, Postmark — a free tier is available on each; verify current limits).
2. Add your domain to the provider and verify it (SPF, DKIM DNS records).
3. Update `shared/.env`:
   ```
   MAIL_MAILER=<driver name, e.g. resend>
   MAIL_HOST=<smtp host>
   MAIL_PORT=<port>
   MAIL_USERNAME=<username or key>
   MAIL_PASSWORD=<password or key>
   MAIL_FROM_ADDRESS=no-reply@<your-domain>
   ```
4. Send a test email to a real inbox: `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('you@example.com')->subject('Test'));`
5. Confirm SPF and DKIM pass (use [mail-tester.com](https://www.mail-tester.com) or similar).
6. Test the full verification email flow: register a new account, receive the email, click the link.

**Cost**: domain yearly (~Rp 150.000–300.000); email free tier (verify limits).

---

## 9. Hosting

**What**: Choose and configure the production server.

**Steps**:
1. Review OPERATIONS.md section 14 for hosting options.
2. Choose a host that supports PHP 8.5, MySQL 8.4, SSH, cron, and a queue worker.
3. Provision the server and configure:
   - Nginx or Apache with document root pointing to `current/public`
   - PHP-FPM 8.5
   - MySQL 8.4 (create `moviefinder` database, user and password)
   - Cron entry: `* * * * * cd /var/www/moviefinder/current && php artisan schedule:run >> /dev/null 2>&1`
   - Supervisor or systemd for `php artisan queue:work --queue=default --tries=3`
4. Set up Cloudflare (free) in front: DNS, HTTPS, WAF rules for `/login`, `/register`, `/ai/messages`.
5. Create the shared directory structure:
   ```bash
   mkdir -p /var/www/moviefinder/releases
   mkdir -p /var/www/moviefinder/shared/storage
   cp .env /var/www/moviefinder/shared/.env
   ```
6. Run `bash scripts/deploy.sh` for the first deploy.
7. Run the smoke test checklist: OPERATIONS.md section 12.

**Cost**: monthly if paid VPS; free on Oracle Cloud Always Free or similar (verify availability).

---

## 10. Monthly Routine (ongoing after go-live)

Perform on the first Monday of each month. See OPERATIONS.md sections 16 and 17 for the full routine.

**Steps**:
1. `composer outdated --direct` and `npm outdated` — review Dependabot PRs opened during the week.
2. `composer update` and `npm update` within allowed ranges.
3. `composer audit` and `npm audit` — fix any high or critical findings.
4. Run the full test suite: `php artisan test`.
5. Run Pint and Larastan.
6. Deploy through the normal release procedure (`scripts/deploy.sh`).
7. Check the admin profit meter: revenue, estimated fees, estimated AI cost, margin per plan.
8. Compare AI cost estimates with the real Google Cloud bill; adjust `ai.cost.*` settings if needed.
9. If margin per paid user is below 40%: lower AI budget or clip allowance first (settings, no deploy), then raise prices if necessary.
10. Check provider price pages for changes (Midtrans fees, Gemini pricing).

---

## Pre-flight Checklist (complete before flipping LAUNCH_MODE)

- [ ] Staging passed the full smoke test (OPERATIONS.md section 12)
- [ ] `scripts/deploy.sh` and rollback tested on staging
- [ ] Backup and restore tested (OPERATIONS.md section 10)
- [ ] Uptime monitor on `/up` active with email alerts
- [ ] Admin health page green: scheduler heartbeat, queue workers, AI key status
- [ ] `composer audit` and `npm audit` clean
- [ ] All items 1–9 above are complete or have a written exception
- [ ] `php artisan ai:check` green for the free key
- [ ] `ai:evaluate` run and answer quality acceptable
- [ ] Verification and reset emails tested against a real inbox
- [ ] Error pages checked: 404, 403, 419, 429, 500, 503 show no stack trace, no admin path
- [ ] Security checklist (SECURITY.md section 13) passed on staging
