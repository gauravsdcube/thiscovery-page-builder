# Thiscovery Page Builder for administrators

This page is for people who enable the module, set permissions, and decide how public pages sit on the site. Page creators have their own guides.

Thiscovery Page Builder lets teams publish public pages (for example NHS ICB portals) with a drag-and-drop studio. Pages can live at **network (global)** level or inside a **space**. Rich text uses **Thiscovery Editor**. Optional Thiscovery Forms blocks add a survey call-to-action or an embedded quick poll. Optional space widgets embed stream, tasks, files, gallery, and calendar from one bound Space.

## Enable the module

1. Go to **Administration → Modules**.
2. Enable **Thiscovery Page Builder**.
3. Open **Administration → Thiscovery Page Builder** for network-level pages.
4. On each space that should have its own pages, enable the module for that space (**Space → Modules**).

Without the space-level enable, members will not see Thiscovery Page Builder in that space.

Network-level pages live outside a space. People reach them from **Administration → Thiscovery Page Builder**. Public visitors open the page URLs (for example `/about` or `/consultations/my-page`).

## Permissions

### Network (global)

| Permission | What it allows |
| --- | --- |
| Create global pages | Create network-level pages and collections |
| Manage global pages | Edit, delete, moderate comments, and export subscriptions |

Site administrators can always do both.

### Space

| Permission | What it allows |
| --- | --- |
| Create pages | Create pages in that space |
| Manage pages | Edit and delete pages in that space |

Space owners, admins, and moderators can create by default. Guests cannot.

## Space stream

When Thiscovery Page Builder is enabled on a space, the **space wall stream is for space administrators only**. Ordinary members should use pages (and space widgets on those pages) instead of the default stream. That is intentional for portal-style spaces.

## Site homepage (replace the Homepage module)

Network pages can become the site home:

1. Open a **Published** page in the studio → **Settings → Site homepage**.
2. Assign **Homepage for guests**, **Default homepage for logged-in users**, and optional **group** homes.
3. Save, then confirm Home and post-login redirects.
4. Disable the **Homepage** module under **Administration → Modules** so it does not compete.

Draft pages do not act as the homepage until they are published.

## Related modules

| Module | Why |
| --- | --- |
| Thiscovery Editor | Rich text in the builder |
| Thiscovery Forms | Survey CTA and Quick poll blocks |
| Tasks, Files, Gallery, Calendar | Space widget blocks on a bound Space |

## Comments and subscriptions

Network admins open **Comments** and **Subscriptions** from the page list. Approved and rejected comments stay in history. Subscriptions come from “Get updates” forms on pages; you can export them as CSV.

See [Comments and updates](creators-engagement.md) for how creators set those blocks up.
