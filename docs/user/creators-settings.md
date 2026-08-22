# Page settings

The **Settings** tab is grouped into collapsible sections. **Basics** is open first; the others start closed. Use **Expand all** / **Collapse all**. Each field has a **?** control for a short in-product explanation.

## Basics

| Setting | What it does |
| --- | --- |
| Title | Name on the public page and in lists |
| URL slug | Public path. Top-level pages and collections are `/{slug}`. Nested pages are `/{collection}/{slug}` |
| Parent collection | Nest this page under a collection, or leave empty for a top-level page |
| Summary | Short text for directory cards |
| Status | Draft, Published, or Archived |
| Page width | How wide the public page is (including Wide and Full) |
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

| Setting | What it does |
| --- | --- |
| Show in top menu | Adds the page to the site top navigation |
| Menu label | Text in the menu (defaults to the title) |
| Menu order | Lower numbers appear first |
| Menu visibility | Guests, members, or everyone |

The page must be **Published** and the visitor must be allowed to view it.

## Site homepage (network pages)

Assign this **Published** page as:

- Homepage for guests
- Default homepage for logged-in users
- Homepage for a group

After this is working, an administrator should disable the **Homepage** module so it does not override these assignments. See [Thiscovery Page Builder for administrators](admins.md).
