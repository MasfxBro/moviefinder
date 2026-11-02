# DESIGN_SYSTEM

Style: casual, minimal, iOS-like, dark first, high contrast with a restrained accent. Figma will refine this; until the Figma tokens are exported, THIS file is the source of truth. When Figma changes a token, update this file and `tailwind.config.js` in the same change.

## 1. Color tokens (dark theme)
| Token | Hex | Use |
|---|---|---|
| `bg` | #000000 | page background |
| `surface` | #111113 | sections, rows |
| `card` | #1C1C1E | cards, inputs |
| `elevated` | #2C2C2E | modals, popovers, hover |
| `border` | rgba(255,255,255,0.08) | hairlines |
| `text` | #F5F5F7 | primary text |
| `muted` | #A1A1A6 | secondary text |
| `faint` | #6E6E73 | disabled, hints |
| `accent` | #0A84FF | primary buttons, links, focus |
| `accent-soft` | rgba(10,132,255,0.16) | accent backgrounds |
| `pro` | #FF9F0A | PRO badge (the PLUS badge uses `accent` text on `accent-soft`) |
| `success` | #30D158 | success |
| `danger` | #FF453A | errors, destructive |
| `warning` | #FFD60A | warnings, "Unverified source" |
Contrast: text on bg/card must meet WCAG AA (4.5:1). Never use color alone to convey meaning.

## 2. Typography
Font: Inter (self-hosted via Fontsource, weights 400/500/600/700) with system fallback `-apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, sans-serif`.
Scale: 12 / 14 / 16 / 20 / 24 / 32 / 44 px. Headings 600–700, body 400, tight tracking on large headings. Line height 1.5 body, 1.2 headings.

## 3. Shape, spacing, depth
Spacing scale 4px base (4, 8, 12, 16, 24, 32, 48, 64). Radii: 12 (inputs, chips), 16 (cards), 24 (modals, hero), full (badges, avatars). Depth by surface color, not heavy shadows; subtle shadow only on modals. Poster aspect ratio 2:3, backdrop 16:9.
Layout: max width 1280px, 16px side padding on mobile, 24–32px on desktop. Grid: 2 columns (mobile), 3–4 (tablet), 5–6 (desktop).

## 4. Motion
150–250 ms ease-out for hover, modal, drawer. Respect `prefers-reduced-motion` (disable non-essential motion). No parallax, no autoplay media.

## 5. Components (Blade components under `resources/views/components/`)
`navbar` (logo, Home, Movies, TV Shows, search, AI Chat button, avatar with badge; guests see the logo, Log in and Create account), `search-bar` (live results dropdown), `poster-card`, `title-row` (horizontal scroller), `title-grid`, `filter-bar` / `filter-drawer` (mobile), `detail-hero`, `meta-chips`, `cast-list`, `where-to-watch`, `trailer-embed` (click-to-load), `ai-chat` (messages, input, quota meter; variants `panel` and `embedded`), `ai-chat-panel` (the navbar panel that hosts `ai-chat`), `ai-nav-button` (the navbar icon button), `ai-title-card`, `clip-card` (thumbnail, source label, preview/full buttons), `preview-modal`, `quota-meter`, `plan-badge` (PLUS, PRO), `admin-badge`, `button` (primary, secondary, ghost, danger), `input`, `select`, `modal` (focus trap), `toast`, `empty-state`, `skeleton`, `pagination`, `footer` (legal links + attributions), `demo-banner`, `landing-hero`, `step-card`, `plan-compare`, `payment-methods` (small method labels), `smart-meter` (Pro Smart searches left), `upgrade-card`, `ai-status` (the state banner inside the chat), `tour-prompt`, `tour-step`, `update-toast`, `offline-banner`, `error-page`.
Rule: one component per concept; no copy-pasted markup; variants via props.

## 6. Required UX states (every data view)
Loading (skeletons), empty, error (friendly + retry), not-found ("We could not find that title yet" + request button), no-quota, no-clip, provider-down, offline-friendly messages. Forms: inline validation, disabled-while-submitting, success confirmation. Error pages and connection problems: section 11.

## 7. Accessibility
Keyboard reachable everywhere, visible focus ring (2px `accent`), semantic landmarks, labels for inputs, alt text from titles ("Poster of {title}"), modals trap focus and close with Esc, touch targets ≥ 44px, no content only on hover.

## 8. Responsive
Mobile first. Breakpoints: 640, 768, 1024, 1280. Filters become a bottom sheet on mobile. The global AI chat is a full-height sheet on mobile; the title chat is an inline card and is never full screen.

## 9. Tailwind mapping
Define tokens as CSS variables in `resources/css/app.css` (`:root`), map them in `tailwind.config.js` (`colors.bg`, `colors.card`, ...). No hard-coded hex values in Blade or JS outside the token file.

## 10. Interaction and responsiveness requirements
Interactive behaviors (build them with Alpine, no heavy libraries):
- Live search dropdown with debounce, keyboard navigation (arrows, Enter, Esc), highlighted matches, recent searches for logged-in users. Press `/` to focus search.
- Poster cards: hover/focus lift and quick actions (watchlist toggle, open detail). Watchlist toggle is optimistic with a toast and undo.
- Rows scroll horizontally with arrow buttons on desktop and swipe on touch; "Load more" for grids (progressively enhanced, keeps scroll position).
- Filters as chips plus a drawer/bottom sheet on mobile; active filters are removable chips; results update without a full reload and the URL stays shareable.
- Detail page: sticky action bar on mobile, expandable synopsis, click-to-load trailer, where-to-watch tabs.
- AI chat: progress steps, typing indicator, cancel button, quota meter, suggested prompts for empty state (the title chat opens with "What would you like to know about {title}?"), result cards that animate in, exit-warning modal.
- Clip cards: thumbnail click loads the preview inside a modal; Esc closes; focus returns to the trigger.
- Toasts for success/error, inline form validation, disabled-while-submitting buttons, skeleton loaders, smooth 150–250 ms transitions, `prefers-reduced-motion` respected.
- Admin UI: sortable/filterable tables, inline status changes, confirm dialogs for destructive actions, responsive tables (cards on mobile).
Responsive acceptance: verify at 360, 390, 768, 1024, 1440 px: no horizontal scroll, nothing overlaps or is cut off, touch targets at least 44 px, text at least 16 px on mobile inputs (prevents iOS zoom), safe-area padding on notched phones, images never stretch.
Performance and accessibility targets: Lighthouse mobile performance 85+ and accessibility 95+ on home, browse and detail; layout shift near zero; fonts preloaded and `font-display: swap`.

## 11. Error, offline and update states
Every state below has its own message and a next action. Texts are English. Error pages keep the site look (dark surface, wordmark, one `button` for the action). 500 and 503 use a minimal layout that needs no database or session.

| Case | HTTP | Title | Message | Action |
|---|---|---|---|---|
| Page not found | 404 | Page not found | We could not find that page. The link may be incorrect or the page may have moved. | [Go home] (logged-in users also get [Search titles]) |
| No permission | 403 | No access | You do not have permission to open this page. | [Go home] |
| Session expired | 419 | Session expired | Your session expired. Reload the page and try again. | [Reload] |
| Rate limited | 429 | Slow down a little | Too many requests. Try again in {n} seconds. | [Try again] enabled after the countdown |
| Server error | 500 | Something went wrong | An error occurred on our side. Please try again in a moment. Reference: {id} | [Try again], [Go home] |
| Maintenance | 503 | We will be back shortly | We are performing maintenance. {It should be over around HH:MM WIB, when an announcement gives an end time.} | [Refresh] |
| Offline (any page) | none | banner | You are offline. Some features will not work until you are back online. | none; the banner clears itself and a toast says "Back online" |
| Request failed (live search, chat, watchlist, report) | none | inline | The server could not be reached. Please check your connection and try again. | [Retry]; optimistic changes roll back with a toast |

Rules:
- An unknown title slug is a 404 page. A search that finds nothing is NOT an error: it shows the not-found state with the request button (F-15).
- Admin URLs for non-admins show the plain 404 above. No error page may mention the admin area or `ADMIN_PATH`.
- Offline detection: `navigator.onLine` plus the `online`/`offline` events, and any failed `fetch`. Honest limit: if the user is already offline when opening a new page, the browser shows its own offline page; the site cannot replace it.
- Feature-level problems keep their own strings in AI_BEHAVIOR.md section 9 and OPERATIONS.md section 8.
- Update toast (`update-toast`): small card at the bottom of the screen (above the mobile safe area), text "A new version is available", buttons [Update] and [Later], never covers the AI input, closes with Esc. Behavior in FEATURES.md F-23.

## 12. Identity (avoiding generic "AI look")
Goal: the site should look designed by a person with a point of view, not assembled from templates. Figma may refine this section (D-40); until then it is binding.
- Wordmark: text from `config('app.name')` in Inter 700 with tight tracking. The round mark seen in the reference mockup is a placeholder.
- One signature motif, the "scene frame": thin corner brackets (like a camera viewfinder) drawn with the `border` token. Use it on clip cards, the preview modal, the landing hero media and empty states. Use it nowhere else, so it stays recognizable.
- AI button icon: a speech bubble drawn with the scene-frame corner brackets, with the label "AI Chat" beside it on desktop (icon only on mobile, with an `aria-label`). No sparkle or star icons.
- Voice: short, concrete, plain words. Say what the product does ("Describe a scene. We find the film.").
- Accent color only for the primary action, links and focus. Everything else stays neutral.
Do:
- Real titles, real posters and real strings in every state; genre chips show genre names.
- One radius scale, one spacing scale, one motion curve, as defined above.
- Real UI crops or concrete examples on the landing page.
Don't:
- Decorative gradients (no purple or blue gradient heroes), glassmorphism blur, glow effects.
- Emoji used as icons, stock hype copy ("Welcome to the future of..."), generic three-icon feature grids.
- Placeholder or lorem text, design tokens or hex codes shown as text, gibberish text in screenshots or mockups.
- Different corner radii or shadows from one component to the next.

## 13. Reference mockup (image generated from this file)
The image made from an earlier version of this file is a visual reference only. This file wins on any difference.
Keep: navbar structure (wordmark, Home, Movies, TV Shows, search, AI Chat button, avatar with PRO badge), poster grid with title and genre chips below each poster, dark surfaces, the AI panel opening under the navbar button with a quota bar at its bottom.
Ignore or fix:
- The chips under the posters show `#A1A1A6`: a token leaked into the UI. Chips show genre names.
- The chat messages are garbled text. Use the strings in AI_BEHAVIOR.md section 9.
- The quota label reads "AI query remaining quota meters". Use "{n} of {limit} AI searches left today".
- A long title is cut off mid-word ("The Movie In"). Titles clamp to 2 lines with an ellipsis and the full title in the tooltip and `aria-label`.
- The navbar needs the final Home, Movies and TV Shows links to point to `/`, `/browse?type=movie` and `/browse?type=tv`.
- The mockup shows the global chat as a popover under the navbar button: that is the desktop design (D-38). The title chat is a card inside the detail page (D-38, F-08) and is not shown in the mockup.

## 14. Pricing page and plan display
- Three columns (Free, Plus, Pro) from `plan-compare`, every value read from settings; prices written as "Rp15.000 / month" and "Rp28.000 / month".
- Under each paid plan: the two ways to pay ("Auto-renew monthly" and "Pay for 30 days") and the accepted methods as small labels (`payment-methods`).
- The plan-change rule, renewal text and refund link sit next to the buttons, not hidden in the footer.
- Honest claims only: no "most popular", no fake urgency or countdowns, no "smarter" wording unless `ai:evaluate` supports it (D-44).
- The AI panel shows the quota meter and, for Pro, the Smart searches left. When the allowance is used, the panel shows the renewal date and, for Free and Plus, a single upgrade button.
- `upgrade-card`: a soft card with `accent-soft` background and `accent` text, one benefit sentence, a primary button "See plans" and a text link "Maybe later". It never uses `danger` or `warning`, has no countdown and no "most popular" claim, and appears only when a real limit or a locked feature applies.
- `ai-status`: the line at the top of the chat that states the real reason and the return time when AI is unavailable (copy in AI_BEHAVIOR.md section 9). It uses `muted` text with a small icon, not an alarm color.

## 15. Writing UI copy (D-48)
- Formal, standard English in plain words. No contractions ("You have", "do not", "cannot"), no slang, no exclamation marks except in rare celebratory moments.
- Short sentences, active voice, sentence case for titles and buttons ("Start the tour", "See plans").
- Say what happened, why, and what the user can do next. Give a time when something returns.
- One tone everywhere: errors, toasts, emails, tour steps, empty states, legal summaries.
- A test scans the translation files and Blade views for contractions (CODING_RULES.md section 10).
- AI chat replies follow the user's language (English or Indonesian); only the interface text is fixed to English.
