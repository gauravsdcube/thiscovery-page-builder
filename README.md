# Thiscovery Page Builder

**Version 1.3.0**  
**Copyright (c) 2026 D Cube Consulting. All rights reserved.**  
**License:** [AGPL-3.0-or-later](LICENSE)

HumHub module for building public engagement pages (NHS ICB portals and general content) with a drag-and-drop page builder, collections, templates, Thiscovery Forms survey links, and moderated guest comments.

Repository: [github.com/gauravsdcube/thiscovery-page-builder](https://github.com/gauravsdcube/thiscovery-page-builder)

## Requirements

- HumHub **1.18+**
- PHP 8.1+
- [Thiscovery Editor](https://dcubeconsulting.co.uk) module (`thiscovery-editor`) for rich text
- Optional: [Thiscovery Forms](https://github.com/gauravsdcube) module for Survey CTA blocks

## Install

1. Copy this module into `protected/modules/engagement-pages`
2. Enable **Thiscovery Page Builder** in Administration → Modules
3. Run pending migrations (HumHub will apply module migrations on enable, or use `php protected/yii migrate/up --migrationPath=@engagement-pages/migrations`)

## Features (v1.3)

- **Global page builder** at `/page-builder`
- Public URLs: `/{homepage-slug}` and `/{homepage-slug}/{page-slug}` (homepage slug is editable in Settings)
- Drag-and-drop sections: hero, rich text, survey CTA, downloads, grid containers, phases, events, team, contact, updates, comments, accordion, callout, image, collections
- Page width and column layouts
- Page templates (save / create from template)
- Visibility: **public (guests)** or **community members only**
- **Comments** element with guest CAPTCHA, rate limits, optional anonymous name, show/hide approved comments, admin moderation history
- **Collections** can list pages, forms, spaces, or upcoming Calendar events
- **Get updates** subscriptions stored per page, with admin table and CSV export
- Space stream lockdown when the module is enabled on a space (ops-focused)

## Admin

- Administration → **Thiscovery Page Builder**
- Comment moderation: `/page-builder/comments`
- Subscriptions (Get updates emails): `/page-builder/subscriptions`

## Copyright

Copyright (c) 2026 **D Cube Consulting**. All rights reserved.

- Website: [dcubeconsulting.co.uk](https://dcubeconsulting.co.uk)
- Email: info@dcubeconsulting.co.uk

This program is free software under the GNU Affero General Public License v3 (or later). See [LICENSE](LICENSE).
