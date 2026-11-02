# COMPLIANCE

This file turns legal/policy concerns into build requirements. It is not legal advice. Before a real commercial launch, have a qualified person review the Terms, Privacy Policy, and licensing.

## 1. Core legal posture
- We host no video. We link to licensed platforms. We preview only via the platform's official embed. See PROJECT_BRIEF.md section 3.
- We never link to pirated sources; AI refuses such requests.
- We act on takedown/complaint reports quickly (section 4).

## 2. Attribution (exact requirements)
- TMDB: About page and footer show an approved TMDB logo and the sentence "This product uses the TMDB API but is not endorsed or certified by TMDB." The logo must be less prominent than MovieFinder's own branding and must not imply endorsement.
- JustWatch: wherever "Where to watch" appears: "Where-to-watch data provided by JustWatch."
- Google: when grounded results are shown, display Google's Search Suggestions chip exactly as provided.
- YouTube: previews use the official player; keep its branding and controls intact.
- YouTube terms: the Terms of Service include the YouTube clause (section 7) and the footer links to the YouTube Terms of Service and Google's Privacy Policy (see INTEGRATIONS.md section 6).

## 3. Content rules
- Adult titles excluded at import and at query time.
- AI refuses sexual/explicit content, piracy help and harm instructions everywhere, and off-topic requests in the global chat. The title chat answers general questions but keeps the other refusals (D-41).
- Age: registration requires confirming the user is 18 or older. Terms say the service is for adults.
- No user-generated public content in v1 (reduces moderation and liability).
- Public access (D-35): guests only see the landing page, pricing, the legal pages and the auth pages. Terms and Privacy are public and linked from the landing footer and from the registration form.

## 4. Copyright and takedown process
Public `/copyright` page includes: what we do and do not host; how rights holders report a link (email address from config, plus the in-app report button); what to include in a report (links, proof of ownership, contact); our commitment: review within a few business days, remove or hide reported links promptly, block repeat-offender channels, keep a record in `audit_logs`.
Technical support: reported links are immediately excluded from embedding (`status=reported`), admin can remove or block the channel (`channel_allowlist` blocked).

## 5. Personal data (privacy by design)
Data collected: name, email, password hash, plan/payment records (no card data), watchlist, search history (90 days), usage counters, security logs, title requests, feedback messages, and aggregated not-found search terms (no user id).
Requirements:
- Privacy Policy page covering: what is collected, why, retention (see DATABASE.md), processors (hosting, email, Midtrans, Google Gemini for AI processing), user rights (access, correction, deletion), contact, breach handling, cookies.
- Consent: checkbox at registration for Terms and Privacy; separate age confirmation.
- Minimization: don't collect what's not needed; no tracking pixels; no third-party analytics in v1.
- AI notice: the chat states that messages are processed by Google's Gemini and asks users not to share personal information (AI_BEHAVIOR.md section 9). The server never adds personal data to prompts.
- Unverified registrations (the email address was never confirmed) are deleted after 14 days; the first-time tour stores only a timestamp and an outcome, with no tracking.
- Deletion: account deletion feature (F-19) removes personal data; payments kept anonymized for accounting.
- Cookies: only essential cookies (session, CSRF). YouTube embeds load only after the user clicks, using the privacy-enhanced domain, and the UI says "Playing loads content from YouTube".
- Breach response: rotate secrets, assess scope, notify affected users and the relevant authority as required by law without undue delay.
- Indonesian personal-data law (UU PDP) and consumer-protection rules apply to services used in Indonesia; confirm the specifics with a legal reviewer before launch.

## 6. Payments and subscriptions
- Show the plan, the final price, the billing mode (auto-renew monthly, or pay for 30 days with no renewal), the cancellation terms and the plan-change rule BEFORE payment (pricing page and checkout). The price shown is final: no extra fee is added for the user.
- Reminder email 3 days before each renewal; receipt after each charge; failed-payment email with grace end date.
- Cancel anywhere in `/account` in two clicks; access remains until the period end; no dark patterns.
- Automatic charging needs an explicit consent box at checkout ("I authorize a monthly automatic charge of Rp{amount} until I cancel."), a reminder email before each charge, a receipt after it, and cancellation in two clicks; the consent is stored.
- Plan changes: an upgrade starts immediately and the old plan's remaining days are forfeited (stated on the checkout before payment); a downgrade starts at the next renewal; there is no proration. State this in the Terms and on the pricing page.
- Pay-for-30-days payments never renew by themselves; the pricing page, the checkout and the receipt say so, and a reminder email is sent 3 days before the end.
- Refund policy page text (owner decides; default: no refunds for the current period except where the law requires).
- Card data never touches our server or DB (gateway tokenization); QRIS, e-wallet and bank payments happen inside the provider's own screen.
- Taxes (e.g. VAT on digital services) and invoicing rules: confirm before commercial launch.
- Before applying for the production merchant account, have public Terms, Privacy, refund policy and a working contact email in place; payment providers commonly ask for them (verify Midtrans's current requirements).

## 7. Terms of Service (must cover)
Service description (search and guidance, not streaming), acceptable use (no scraping, no abuse of AI, no circumventing quotas), AI limitations (may be wrong; verify before relying), subscription terms, termination/suspension, disclaimer of warranties and limitation of liability, third-party links and content (we don't control them), IP and takedown, YouTube clause (by using the video previews you agree to be bound by the YouTube Terms of Service, and Google's Privacy Policy applies to that content), governing law and contact.

## 8. Launch gates (commercial go-live checklist)
Until ALL are done, run `LAUNCH_MODE=demo` (payments sandbox, no real charges, banner "Demo mode — no real payments").
- [ ] TMDB commercial license obtained, or the data source replaced. (A paid subscription site built on TMDB data is reported to count as commercial.)
- [ ] Midtrans production merchant approved, recurring/tokenization enabled for card and GoPay, channels activated (card, GoPay, ShopeePay, QRIS, bank transfer), notification URLs set.
- [ ] Gemini paid tier (or restricted regions) and AI processing disclosed.
- [ ] Legal pages reviewed by a qualified person.
- [ ] Business entity/registration requirements checked (including electronic-system-operator registration for services offered in Indonesia, and tax registration).
- [ ] Security checklist (SECURITY.md section 13) passed on production-like staging.
- [ ] Backups and restore tested; monitoring in place.
- [ ] Support/takedown email working.
- [ ] Unit economics checked with real prices and fees (OPERATIONS.md section 17).
- [ ] A paid Gemini key exists in a billed project with a budget alert; the free key's project has no billing (SECURITY.md section 5).
- [ ] A production email provider is chosen, the own domain is verified (SPF, DKIM), and the verification and reset emails are tested.
- [ ] The go-live items in OPERATIONS.md section 18 are reviewed.
- [ ] YouTube clause in the Terms and the footer links present; the AI data notice is visible in the chat.

## 9. Certification (LSP) note
The assessed deliverable is the mockup plus observation of the code and project. Keep the demo mode honest and visible; document the architecture (docs/) and be ready to explain structure, naming, functions, and library usage.
