# Page settings

The **Settings** tab uses the same studio layout as Forms: a **left rail** of sections, and one pane at a time. **Basics** opens first. Each field has a **?** control for a short in-product explanation.

CSS, Share, and Versions are also in this rail (not separate top tabs).

## Basics

| Setting | What it does |
| --- | --- |
| Title | Name on the public page and in lists |
| URL slug | Public path. Top-level pages and collections are `/{slug}`. Nested pages are `/{collection}/{slug}` |
| Parent collection | Nest this page under a collection, or leave empty for a top-level page |
| Folder | Admin grouping on the page list. Does not change the public URL. Leave **Unfiled** for top-level pages or unfiled collections. Child pages stay with their collection. |
| Summary | Short text for directory cards |
| Status | Draft, Published, or Archived |
| Page width | How wide the public page is: Narrow 720, Standard 960, Comfortable 1100, Wide 1440, Extra wide 1600, or Full browser width |
| Who can view | Public guests, or community members only (sign-in) |

Draft is for managers and preview. **Published** is what visitors see. **Archived** hides the page from the public site. Templates always stay as drafts.

If a slug is already used, the studio tells you which page owns it. Change the slug and save.

## Bound Space

Set once per page. **Space stream, tasks, files, gallery, and calendar** widgets all use this Space. You do not pick a space on each widget.

## Directory listing

| Setting | What it does |
| --- | --- |
| Show in public directory | The page can appear in Collection blocks that list pages |
| Featured on directory | Sorted first when a Collection prefers featured items |
| Category | Optional label on cards (for example Consultation) |
| Closes at | Optional deadline on cards |

A collection homepage is never listed as a card on itself. Add a **Collection** section in the builder to list children.

## Navigation (network pages)

When **Thiscovery Navigation** is enabled, saving a **collection** (and each child page) adds it to **Administration → Thiscovery Navigation**. That is the site tree, not automatically the live top bar.

| Setting | What it does |
| --- | --- |
| Show this collection in the top bar | Collection appears as a top-bar item (must be **Published**) |
| Show under the collection in the top bar | Child page appears in that collection’s dropdown |
| Show in top bar | Standalone top-level pages |
| Menu label | Text in the menu (defaults to the title) |
| Menu order | Lower numbers appear first |
| Menu visibility | Guests, members, or everyone |

Child pages only show in the live dropdown if the **collection** is also shown in the top bar. You can still rearrange items in Site navigation.

## Site homepage (network pages)

Assign this **Published** page as:

- Homepage for guests
- Default homepage for logged-in users
- Homepage for a group

After this is working, an administrator should disable the **Homepage** module so it does not override these assignments. See [Thiscovery Page Builder for administrators](admins.md).
