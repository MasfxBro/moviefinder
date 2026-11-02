# INTEGRATIONS

All integrations run server-side behind interfaces in `app/Contracts` so they can be replaced and faked in tests.
Every outbound call: explicit timeout, limited retry with backoff, error mapped to a friendly fallback, no secrets in logs.
Verify current API details in the official docs while implementing; if reality differs from this file, update this file in the same change.

## 1. TMDB (metadata)
- Auth: Bearer read token (`TMDB_READ_TOKEN`). Base: `https://api.themoviedb.org/3`.
- Use: search (`/search/movie`, `/search/tv`) with `include_adult=false`; details (`/movie/{id}`, `/tv/{id}` with `append_to_response=credits,videos`); trending/popular/top_rated; genres; watch providers (`/{type}/{id}/watch/providers`, read the `ID` region by default).
- Images: build URLs from `TMDB_IMAGE_BASE` + size + stored path. Never download or store image files.
- Mapping: `TmdbMapper` converts API payloads into `TitleData`. Keep max 3 directors/creators and 8 cast. Drop the title if `adult` is true.
- Rate: wrap in a limiter (default 10 req/s) and cache per `docs/ARCHITECTURE.md` section 5.
- Attribution (required, in About page and footer): use an approved TMDB logo and the exact sentence: "This product uses the TMDB API but is not endorsed or certified by TMDB." The logo must be less prominent than the app's own branding. The footer is global, so the attribution also appears on the landing page, which shows TMDB posters in its decorative strip.
- Watch-provider data: show "Data provided by JustWatch" wherever it is displayed.
- LICENSE WARNING: TMDB's API is free for non-commercial use. A site that charges users for access to content related to TMDB data is reported to count as commercial and needs a written commercial license. Therefore real payments stay off (`LAUNCH_MODE=demo`) until the license is obtained or the data source changes. See COMPLIANCE.md section 8.
- Data age: see DATABASE.md "TMDB data age rule".

## 2. Gemini (LLM)
- Keys (D-47, D-52): two keys of the app's own, never the developer-tool keys. `GEMINI_API_KEY_FREE` comes from a Google project WITHOUT billing (used for Free users; it can never be charged, Google can only refuse requests when the free quota is used). `GEMINI_API_KEY_PAID` comes from a separate project with billing and a budget alert (used for Plus and Pro), created only after the first paying customer. Never link billing to the free project. `AI_DRIVER=fake|gemini` selects the canned `FakeLlmClient` or the real client; the real keys are configured at the end of the project (T-43). `php artisan ai:check` reports the driver, which keys are set, the HTTP status of a test call per key, the model list, the rate limits it can see and whether grounding is accepted, without ever printing a key. Models from `AI_MODEL_STANDARD` (Free, Plus, and Pro after its Smart searches) and `AI_MODEL_SMART` (Pro Smart searches and the critic step); never hardcode model names. Providers retire preview model names often, so keep them in config only.
- Calls: `LlmClient::structured(messages, schema)` returns validated JSON. Timeout 25 s, max 1 retry on 429/5xx with backoff.
- Cost: every call returns token counts. `AiCostCalculator` turns them into rupiah with the model prices in settings (`ai.cost.*`, including the fee per grounded request and a safety multiplier) and `AiBudgetService` enforces each user's budget (D-45). Check the provider's price page monthly and update the settings; budgets are in rupiah, so a price rise reduces volume, never margin. One source indicates introductory Flash prices change on 1 January 2027: verify.
- Safety: set the provider's safety settings to block harmful categories; our own guardrails (AI_BEHAVIOR.md) still apply.
- Grounding with Google Search is used ONLY by `AiSearchClipFinder`. Rules when grounding is used:
  - The provider requires displaying Google's Search Suggestions chip exactly as provided (rendered content from the response) wherever grounded results are shown. Implement it in the clip results UI. Do not modify its colors, fonts or appearance.
  - Free grounding allowances are limited and change over time (daily or monthly caps, then per-request billing). Enforce `clips.grounded_daily_budget` and fall back to the search link when it is exhausted.
  - Grounded tool use may not combine with strict JSON output; parse candidate URLs from grounding metadata and text with strict patterns instead (see section 3).
  - Grounding links can be redirect URLs. Resolve a redirect at most once, accept only if the final host is an allowed YouTube host, never follow arbitrary redirects.
- Privacy/terms: free-tier data may be used by the provider to improve its products, and the free tier has region restrictions for serving end users in some regions. Before real public launch use a paid tier or restrict regions, and disclose AI processing in the Privacy Policy. Never send emails, passwords, tokens or other personal data to the LLM. Users type free text that does reach the provider, so the chat shows a data notice (AI_BEHAVIOR.md section 9) and the Privacy Policy lists Google Gemini as a processor (COMPLIANCE.md section 5).

## 3. Clips (AI web search + validation)
The AI does the searching. For a scene request, Gemini uses Google Search grounding to find real videos on YouTube and other platforms that match what the user described. Our code is the gate: a link is shown only after validation. A URL the model typed from memory (not present in the search tool's results) is discarded.

Interface: `ClipFinder::find(Title $title, SceneQuery $scene, Plan $plan): ClipResultSet`.
Order (stop when enough results):
1. `CuratedClipFinder`: `clip_links` with source=admin and status=active for the title.
2. `StoredClipFinder`: `clip_links` with source=resolver, same `scene_key`, status=active, verified within 30 days.
3. `AiSearchClipFinder` (only if `features.ai_clip_search`, budget remains):
   a. Build a search brief from the structured understanding: title, year, scene description, keywords, approximate position, language.
   b. Call Gemini with grounding; ask it to find videos (YouTube first, then other platforms) that show that scene, with a one-line reason each.
   c. Collect candidate URLs ONLY from the grounding metadata (source chunks), plus URLs in the response text that also appear in that metadata.
   d. Normalize with `PlatformUrl::parse()` (host allow-list below). Redirect links are resolved at most once and accepted only if the final host is allowed.
   e. Validate per platform (table below).
   f. Apply `ChannelPolicy`.
   g. `ClipRelevanceJudge`: one cheap LLM call scores each surviving candidate (title, channel, search snippet) against the user's scene request. Keep score >= 0.6, sort descending. The number judged depends on the plan (Free 3, Plus 5, Pro 8).
   h. Store accepted candidates in `clip_links` (source=resolver) and return them.
4. `SearchLinkFinder`: always available; builds `https://www.youtube.com/results?search_query=` + url-encoded "<title> <year> <scene keywords> scene" as a fallback link (never presented as a verified clip).
`YoutubeApiClipFinder` (YouTube Data API search) may exist behind `features.clip_driver_youtube_api` (default false); do not enable without the owner's decision because its quota is small.
Negative results are cached 24 h.

### Platform allow-list
| Platform | Hosts | Validation | Display |
|---|---|---|---|
| YouTube | youtube.com, www.youtube.com, m.youtube.com, youtu.be | oEmbed check (below) | embed preview if channel is official/trusted, else link-out "Unverified source" |
| Vimeo | vimeo.com, player.vimeo.com | oEmbed endpoint of Vimeo (verify) | link-out "External link" |
| Dailymotion | dailymotion.com, dai.ly | oEmbed endpoint of Dailymotion (verify) | link-out "External link" |
| TikTok | tiktok.com, vm.tiktok.com | oEmbed endpoint of TikTok (verify) | link-out "External link" |
| Instagram, Facebook, X | instagram.com, facebook.com, fb.watch, x.com, twitter.com | no keyless validation: accept only if the URL came from grounding results and passes a strict shape check; no fetch | link-out "External link", ranked lowest |
Rules: only fixed oEmbed endpoints are ever called, always with a canonical URL rebuilt by our code after strict parsing (no SSRF). If an oEmbed endpoint is unavailable, treat the link as unvalidated: keep it only if it came from grounding, rank it lower, and never embed it. Admin-blocked channels are removed on every platform.

### YoutubeOembed::check
GET `https://www.youtube.com/oembed?url=<canonical watch url>&format=json` (no API key). Success returns title, channel name and thumbnail. Unavailable, private or non-embeddable videos return an error status: treat any non-200 as invalid. The URL is rebuilt from the 11-char video id. Timeout 5 s.

### ChannelPolicy
Look up channel name/id in `channel_allowlist`: blocked -> reject; official or trusted -> embeddable; not listed -> unverified (link-out only).

### Preview player (frontend)
- Show the thumbnail and a "Play preview" button; create the iframe only on click.
- Embed URL: `https://www.youtube-nocookie.com/embed/<id>?enablejsapi=1&origin=<APP_URL>&start=<S>&end=<S+60>&rel=0&playsinline=1` where `S` is the `t=` offset of the source link if present, otherwise 0. (`end` is measured from the start of the video, not from `start`.)
- With the IFrame Player API listen for the ended state and also poll `getCurrentTime()` every 250 ms as a fallback; at `S+60` seconds (or when the state is ended) call `destroy()` on the player (this also prevents YouTube end-screen recommendations) and show the overlay card: "Preview ended — continue on YouTube" with a button to the original video URL and the channel name.
- Read `getDuration()` after ready; if the video is shorter than 60 s the overlay still appears at its end.
- Do not overlay, hide or alter YouTube branding/controls. No autoplay.

## 4. Midtrans (payments)
- Env: `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION=false` in demo. SDK: `midtrans/midtrans-php`.
- Checkout is always Snap (hosted by Midtrans). Activate these channels in the Midtrans dashboard, in sandbox and in production: credit card (3-D Secure), GoPay, ShopeePay, QRIS, bank transfer (virtual accounts). Convenience stores (Indomaret, Alfamart) are not enabled in v1: they need a separate arrangement and have fixed fees.
- The Subscription API supports only card (saved token) and GoPay tokenization (verify in the current docs). Every other method can only be a one-time payment. Hence two modes (D-46):
  - Auto-renew: Snap transaction with `enabled_payments` limited to credit card and GoPay, with card tokenization or GoPay tokenization for the first charge -> after success create the monthly subscription through the Subscription API using the saved token -> Midtrans charges each cycle and sends recurring notifications.
  - Pay for 30 days: a one-time Snap transaction with `enabled_payments` from `billing.methods.<plan>.manual` (default Plus: QRIS, ShopeePay, GoPay, card; Pro: the same plus bank transfer). No subscription object is created; paid-until = max(now, current end) + 30 days.
- Set the Recurring Notification URL and the Payment Notification URL (`POST /billing/webhook`) in the Midtrans dashboard. Recurring and GoPay tokenization must be enabled on the merchant account first.
- The plan and the amount are computed on the server from settings; the order id encodes user and plan; never take a plan or a price from the client.
- Automatic charging starts only after the user ticks the consent box at checkout (D-46); the consent text, the time and the plan are stored with the subscription.
- Plan changes: an upgrade is a new payment that starts a new period at once and disables the old gateway subscription; a downgrade sets `pending_plan` and, in auto mode, updates the gateway subscription amount (Subscription API update) before the next charge.
- Subscription API supports pause, resume, cancel, update. Use cancel when the user cancels (access remains until period end in our DB).
- Webhooks: verify authenticity (signature rules in the current docs; for standard notifications the signature is SHA-512 of order_id + status_code + gross_amount + server key). Then ALSO re-fetch the status from Midtrans before changing local state. Store every event in `webhook_events` with a unique key to make processing idempotent. Respond 200 quickly; do heavy work in a job. The webhook route has no session, no CSRF and no login; its only protection is the signature plus the re-fetch.
- Fees: Midtrans charges per successful transaction (a percentage for card, GoPay, ShopeePay and QRIS; a fixed amount for bank transfer; VAT is added to Midtrans's fee). The user never pays them. Store an estimate per payment (`payments.est_fee_idr`, from `billing.fee.*` settings) for the admin profit meter, and verify the current fee table in the Midtrans dashboard before launch.
- Never store card numbers or CVV. Never put the server key in the frontend. Card entry happens in Midtrans-hosted/tokenized UI only; QRIS, e-wallet and bank payments happen inside the provider's own screen.
- Currency IDR, integer amounts. Order ids are unique (`MF-<user>-<timestamp>-<rand>`).
- Demo support: `php artisan billing:simulate {user} {renew|fail|cancel|expire} --mode=auto|manual --plan=plus|pro` works only when `APP_ENV != production` and mutates local state through the same services the webhook uses.
- Production approval and what the merchant account may sell are launch gates (D-13, COMPLIANCE.md section 8).

## 5. Email
Laravel Mail through a mail provider; we never run our own mail server. Development and the LSP demo: Mailpit (bundled with Laragon; SMTP on the local machine and an inbox in the browser), so the verification flow can be shown with real emails and no account. Production: a transactional email provider on a free tier, chosen after the LSP (D-19); check its current free limit and whether it needs your own domain verified with SPF and DKIM (usually yes, for good delivery).
Required emails: verify email, reset password, payment succeeded (receipt), payment failed (with the grace end date), renewal reminder (auto mode: before the charge; manual mode: before the end, with a pay link), request resolved. Plain, branded, formal English, no tracking pixels.
Verification email: subject "Verify your email address"; body "Hello {name}, thank you for creating an account. Please confirm that this email address belongs to you by selecting the button below."; button "Verify email address"; footer "This link expires in 60 minutes. If you did not create an account, no further action is required."; the plain link is repeated below the button as a fallback.
Rules (D-51): the URL is signed and valid for 60 minutes; resending is throttled; opening it in the same browser keeps the session, and in another browser the user must log in first (no auto-login from an email link); changing the address requires verifying the new one; unverified accounts older than 14 days are deleted.

## 6. Platform terms to respect
- YouTube: only official embeds and links; no downloading, no scraping of YouTube pages, no hiding of the player UI.
- YouTube API Services (the IFrame Player API counts as one): the Terms of Service must say that using the site means agreeing to the YouTube Terms of Service (https://www.youtube.com/t/terms), and the site must link Google's Privacy Policy (https://policies.google.com/privacy); the footer links both. Verify the current YouTube API Services Terms and Developer Policies before launch and update this file if they differ. Ending the preview with `destroy()` after 60 seconds uses the official player API; never overlay, hide or alter the player UI.
- TMDB and JustWatch: attribution as above.
- Google (Gemini): grounding display requirements and data terms above.

## 7. Not approved yet (D-39): do not build
Provider logos in "Where to watch" (TMDB returns logo paths), and a "More like this" row from TMDB recommendations. They would add user value at little cost, but they are new features. Until the owner approves them in DECISIONS.md, "Where to watch" shows provider names as text and no recommendations row exists.

## 8. Provider switch checklist (D-52)
Gemini is replaced only if it becomes unworkable. Treat it as unworkable if any of these holds for more than a few days: (a) the free tier is removed or restricted for the owner's region; (b) the free pool is too small for meaningful daily places; (c) prices rise until the budget guard cannot cover a paid plan; (d) the models in use keep being retired without an equivalent; (e) `ai:check` fails repeatedly.
All calls go through `LlmClient` and `AI_DRIVER`, so a new provider is one new driver plus configuration: the pipeline, quotas, places, budgets and screens do not change. Before choosing, verify: structured JSON output, price per million input and output tokens, free-tier terms and region, whether data is used for training, rate limits, and whether a web-search tool exists for clip search (otherwise clip search falls back to saved clips and the search link). Record the decision in DECISIONS.md and add a task.

## 9. After the LSP (not part of the 46 tasks)
Xendit as a second gateway for auto-debit from OVO, DANA, ShopeePay and LinkAja (activation by Xendit sales and provider approval; a new driver behind `PaymentGateway`). Other ideas are in the TASKS.md backlog. Bringing your own key (BYOK) was rejected (D-47).
