# Publishing and URLs

How people open the page, how collections nest, and how you reuse a design.

## Share

| Link | Use |
| --- | --- |
| Public URL | The live path visitors use. Works when status is **Published** and **Who can view** allows them. They see the last **published edition**, not unsaved studio work. |
| Preview | **Preview** in the studio header saves your latest work, then opens the working draft (`?preview=1`). |
| Publish current draft | Saves, then freezes that snapshot as the live edition. |

Copy the public link from **Settings → Share**. Always use the `https://` URL.

See [Versions and publishing](creators-versioning.md) for revisions vs editions.

## URL shapes

| Kind | Example |
| --- | --- |
| Collection | `/consultations` |
| Page under a collection | `/consultations/my-page` |
| Standalone top-level page | `/about` |

Slugs are lowercase letters, numbers, and hyphens. Two pages cannot share the same public path.

Older `/pages`, `/engage/<slug>`, and `/engagement-pages/...` addresses still resolve if something linked to them.

## Status and audience

Visitors only see **Published** pages that match **Who can view**:

- **Public** — guests and members
- **Community members only** — signed-in people

Draft and archived pages stay in the studio for managers.

## Templates

**Save as template** (studio footer, or Settings → Share) stores a copy of the design without treating it as a live page. Templates stay as drafts and are not listed publicly. On the page list, create a new page **from a template**.

## Collections

Use a collection when several pages should share a URL prefix and a listing. Use a standalone page when the topic is one-off (About, Contact). Use a **folder** only to organise the admin list; it never appears in the public path.

Add child pages with **Add page** on the collection (from the page list sidebar). List them on the collection with a **Collection** section in the builder.
