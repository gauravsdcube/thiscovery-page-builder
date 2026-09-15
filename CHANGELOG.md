# Changelog

## 1.8.2 (September 15, 2026)

- Fix: Opening Page Builder from a space no longer 500s (folder/list URLs used the Yii array route with `createUrl`)

## 1.8.1 (September 14, 2026)

- Enh: HubSpot form section — paste the HubSpot embed (scripts are not allowed in rich text)

## 1.8.0 (September 14, 2026)

- Enh: Studio Settings tab matches Forms (left section rail and one pane at a time), including CSS, Share, and Versions
- Enh: Page list uses a folder sidebar: top-level pages, admin folders, then unfiled collections
- Enh: Nested admin folders to organise collections and standalone pages (public URLs unchanged)
- Enh: Collections and child pages are added to Thiscovery Navigation automatically; studio options control the live top bar and collection dropdown
- Enh: Named appearance themes in module configuration; each page can use the site default, another theme, or a detached custom style, with token overrides and custom CSS
- Enh: Draft revisions and published editions (same model as Thiscovery Forms); public URLs serve the published edition until you publish again
- Enh: Studio CSS and Versions tabs, Share “Publish current draft”, and manager preview (`?preview=1`)
- Enh: Extra wide page width (1600px) between Wide and Full
- Enh: Contact card section — portrait cards with optional photo upload, role, organisation, and email
- Enh: Corner radius setting on every section (theme default, square, or 0–64px)
- Fix: Contact cards no longer sit inside an extra background panel; radius applies to the cards themselves

## 1.7.4 (September 12, 2026)

- Enh: Accordion option to open one item at a time
- Enh: Sector card styles in accordion bodies; wide pages cap at 1440px
- Fix: Do not refresh the homepage table schema on every request

## 1.7.3 (September 11, 2026)

- Enh: Video embed section for YouTube and Vimeo (URL or pasted iframe)
- Fix: Restore Administration left-menu entry (module event cache was dropping the handler)

## 1.7.2 (September 2, 2026)

- Enh: Soft-dep on thiscovery-translate for block display copy (BaseBlock) and public page title
- Enh: Top menu engagement page labels use PageBuilderHook when translate is enabled
- Fix: onAfterLogin listens for UserEvent (compatible signature)

## 1.7.1 — 2026-09-01

- Change: When Thiscovery Navigation is enabled, pages are added to the top bar there instead of from this module
- Change: Studio Navigation section points to Site navigation

## 1.7.0 — 2026-08-29

- Enh: Extensible block registry (`RegisterBlocksEvent`) so other modules can register page sections
- Enh: Map embed section (map picker + height) when Thiscovery Mapping is installed and enabled
- Change: Map section is hidden from the palette when Mapping is off; existing embeds keep their settings and show a clear message
- Fix: Rich-text image wrap CSS tweak

## 1.6.1 — 2026-08-22

- Maintenance release (studio and help polish)

## 1.6.0 — 2026-08-20

- Enh: In-product Help with sections for administrators and page creators. Open it from the page list, studio, comments, and subscriptions

## 1.5.2 — 2026-08-20

- Enh: Studio navigation aligned with Thiscovery Forms (Back, Preview, Save, Open page)
- Enh: Settings tab uses collapsible sections with Expand all / Collapse all and ? guidance
- Enh: Preview saves then opens the public page; normal save stays in the editor
- Fix: Administration pages (list, editor, comments, etc.) keep the left Administration menu, matching Thiscovery Forms

## 1.5.1 — 2026-08-20

- Fix: Site homepage assignments only apply when the page is **Published** (draft pages were silently ignored)
- Fix: Flush homepage URL cache when a page is published or its slug changes
- Enh: Warn in the editor when assigning homepage on a draft page

## 1.5.0 — 2026-08-20

- Enh: Multiple collections and standalone top-level pages (not only one nested homepage prefix)
- Enh: Configurable URL slugs with clear collision messages when a slug is already used
- Enh: Button block with presets (link, page, form, space, login/register, mailto/tel, scroll, custom URL)
- Enh: Bind a Space once per page; embed Stream, Tasks, Files, Gallery, and Calendar widgets
- Enh: Add pages to the top menu; set guest / logged-in / group site homepages (retires need for Homepage module)
- Note: After configuring homepage assignments, disable the Homepage module

## 1.4.1 — 2026-08-15

- Enh: Database tables renamed to `thiscovery_page`, `thiscovery_page_follow`, and `thiscovery_page_comment`

## 1.4.0 — 2026-08-15

- Enh: HumHub module id is now `thiscovery-page-builder` (folder, routes, and namespace). Legacy `/engagement-pages/...` admin URLs still resolve.

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
