# Thiscovery Page Builder

**Version 1.8.3**  
**Copyright (c) 2026 D Cube Consulting. All rights reserved.**  
**License:** [AGPL-3.0-or-later](LICENSE)

HumHub module for building public engagement pages (NHS ICB portals and general content) with a drag-and-drop page builder, collections, templates, Thiscovery Forms survey links, and moderated guest comments.

Repository: [github.com/gauravsdcube/thiscovery-page-builder](https://github.com/gauravsdcube/thiscovery-page-builder)

## Requirements

- HumHub **1.18+**
- PHP 8.1+
- [Thiscovery Editor](https://github.com/gauravsdcube/thiscovery-editor) module (`thiscovery-editor`) for rich text
- Optional: [Thiscovery Forms](https://github.com/gauravsdcube) module for Survey CTA and Quick poll blocks
- Optional Space modules for embeds: Tasks, Files (`cfiles`), Gallery, Calendar

## Install

1. Copy this module into `protected/modules/thiscovery-page-builder`
2. Enable **Thiscovery Page Builder** in Administration → Modules
3. Run pending migrations (HumHub will apply module migrations on enable, or use `php protected/yii migrate/up --migrationPath=@thiscovery-page-builder/migrations`)

## Features (v1.6)

- **In-product Help** for administrators and page creators (page list, studio, comments, subscriptions)

- **Global page builder** at `/page-builder`
- **Collections** as top-level URL prefixes (`/collection`) and **standalone** pages (`/about`)
- Pages nested under a collection: `/collection/page-slug`
- Configurable slugs with duplicate-slug warnings
- Drag-and-drop sections including **Button**, Space widgets (stream / tasks / files / gallery / calendar), hero, rich text, **Custom HTML** (site administrators only), **video embed** (YouTube / Vimeo), **HubSpot form**, survey CTA, quick poll, downloads, containers, phases, events, team, **contact card** (photo), contact, updates, comments, accordion, callout, image, collections
- Bind one **Space** per page for all space widgets
- **Top menu** entries per page; **site homepage** for guests, logged-in users, and groups (configure here, then disable the Homepage module)
- Page templates, audience visibility, comments moderation, subscriptions CSV

## Retiring Homepage module

1. Open a published page → Settings → Site homepage
2. Assign guest / logged-in / group homes and save
3. Confirm Home and post-login redirects
4. Disable **Homepage** under Administration → Modules

## Admin

- Administration → **Thiscovery Page Builder**
- In-product **Help** from the page list
- Comment moderation: `/page-builder/comments`
- Subscriptions (Get updates emails): `/page-builder/subscriptions`

## Copyright

Copyright (c) 2026 **D Cube Consulting**. All rights reserved.

- Website: [dcubeconsulting.co.uk](https://dcubeconsulting.co.uk)
- Email: info@dcubeconsulting.co.uk

This program is free software under the GNU Affero General Public License v3 (or later). See [LICENSE](LICENSE).
