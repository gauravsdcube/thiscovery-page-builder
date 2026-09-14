# Versions, revisions, and editions

Open **Settings → Versions** after you have saved the page at least once. Versioning uses the **Thiscovery Versioning** module. If that module is off or Page Builder versioning is disabled, Versions is hidden and the public URL shows the working draft.

You can also **Publish current draft** from **Settings → Share**.

Templates are not versioned.

## Revisions vs editions

| Term | When it is created | What it is for |
| --- | --- |
| **Revision** | Every time you **Save** in the studio | A snapshot of the working draft (sections, settings, theme, and CSS) |
| **Edition** | When you **Publish** | The frozen definition visitors see on the public URL |

Revision numbers and edition numbers are separate (for example Revision #12 and Edition #3).

## Status still means availability

**Draft / Published / Archived** still control whether the public URL is available. They are independent of which edition is live.

- **Draft** — managers and preview only
- **Published** — visitors can open the URL and see the **current published edition**
- **Archived** — not listed as live; the published edition stays for restore

## Typical flow

1. Build the page in the studio.
2. **Publish current draft** from **Settings → Versions** or **Settings → Share**. This **saves** your latest studio changes and then freezes them as the live edition.
3. Set status to **Published** if the page should be reachable.
4. Later edits stay in the working draft until you publish again. Visitors keep the last edition until you publish.

## Restore

**Restore** on a revision replaces the working draft with that snapshot. It does **not** change the live published edition. Publish again when you want visitors to use the restored content.

## Preview

- **Preview** in the studio header (and Share) saves first, then opens the working draft with `?preview=1`.
- **Versions → Preview** opens that revision or edition snapshot.
- **Open page** / the public URL without preview shows the published edition.

## Delete

You can delete old revisions and editions you no longer need. You **cannot** delete the current published edition, or the revision that backs it.

## Permissions

Anyone who can manage the page can view, restore, publish, and delete versions by default.

Site administrators can turn Page Builder versioning on or off under **Administration → Modules → Thiscovery Versioning** (Configure). When off, Versions is hidden; existing history is kept.
