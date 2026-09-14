# Appearance and CSS

The **CSS** section in **Settings** is where you choose a shared theme, override tokens for this page, and add extra CSS.

## Themes

Administrators define named themes under **Administration → Thiscovery Page Builder → Themes**. A theme can be the **site default**. New pages start on that default.

On each page:

| Choice | What happens |
| --- | --- |
| A named theme (including site default) | The page uses that theme. Values you fill in below override it for this page only. Updating the theme later updates every page that still uses it. |
| **Custom (detached from theme)** | The page keeps only its own tokens and CSS. It no longer follows a shared theme. |

Leave a token blank to inherit the theme (or the site HumHub look if the theme left it blank too).

## Token groups

| Group | Typical use |
| --- | --- |
| Page | Background, text, max width, padding for `#ep-page` |
| Headings | Colour and weight for h1–h3 |
| Body text | Colour and font size |
| Links | Link colour |
| Hero | Hero panel colours, radius, padding |
| Cards | Contact, callout, collection, and similar cards |
| Buttons | Primary button colours and corner radius |
| Contact card | Accent ring/underline and email colour |

## Custom CSS

Optional extra CSS for this page. Prefer selectors under `#ep-page` so you do not restyle the rest of the site. Theme custom CSS is included first; page custom CSS comes after.

## Publishing

Appearance is part of the page definition. Visitors see the **published edition**. Save CSS in **Settings → CSS**, then **Publish current draft** from Share or Versions when you want the live page to pick up the new look.
