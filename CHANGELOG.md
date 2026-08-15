# Changelog

## 1.4.0 — 2026-08-15

- Enh: HumHub module id is now `thiscovery-page-builder` (folder, routes, and namespace). Database tables stay `engagement_page*`. Legacy `/engagement-pages/...` admin URLs still resolve.

## 1.3.2 — 2026-08-14

- Enh: Quick poll block embeds a Thiscovery Forms poll so visitors can vote on the page

## 1.3.1 (Unreleased)

- Enh: Page builder rich text uses Thiscovery Editor (Lexical) instead of TinyMCE (headings, tables, images, media, source) instead of the HumHub markup editor
- Existing HumHub markdown content still renders until the page is saved again

## 1.3.0 — 2026-08-14

- Enh: Homepage URL slug is editable in the page builder (public pages use `/{slug}` and `/{slug}/{page}`)
- Enh: Admin builder, comments, and subscriptions use `/page-builder` instead of `/engagement-pages/...`
- Fix: Legacy `/pages` and `/engage/<slug>` URLs still resolve

## 1.2.0 — 2026-08-13

- Enh: Image blocks can be ticked as the collection / directory card image (overrides the hero fallback)
- Fix: Comments email field is hidden when “Ask for email” is unchecked
- Enh: Collections can list upcoming Calendar events (limit + link to the full calendar)
- Enh: Network-level subscription list with CSV export (Get updates form emails)

## 1.1.0 — 2026-08-13

- Fix: Contact cards in grid containers now share equal height, with the email button aligned at the bottom of each card
- Enh: Public page blocks inherit colours, type, cards, buttons, and accordions from the Thiscovery theme
- Fix: Section colour pickers override Thiscovery theme colours on that block

## 1.0.1 — 2026-08-12

- Per-section colour controls (background, text)
- Hero border colour picker and Show border toggle
- Background colour presets shown under the background field

## 1.0.0 — 2026-08-12

First stable release.

- Drag-and-drop page builder with section palette and layouts
- Public `/pages` directory and `/pages/<slug>` pages
- Page templates, width controls, collections
- Audience visibility (public guests vs community members)
- Comments block: CAPTCHA, rate limits, optional name/anonymous, show/hide approved comments
- Admin comment moderation with full history (pending / approved / rejected)
- Thiscovery Forms survey CTA integration
- Copyright (c) 2026 D Cube Consulting
