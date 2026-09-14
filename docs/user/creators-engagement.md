# Comments, forms, and space widgets

How pages collect comments, send update emails, link to Thiscovery Forms, and show Space content.

## Comments

Add a **Comments** section on the builder. Typical options include CAPTCHA, rate limits, asking for a name, allowing anonymous comments, and whether approved comments show on the page.

Visitors’ comments wait for approval unless you configure otherwise. Network managers open **Comments** from the page list:

- Filter pending, approved, rejected, or all
- Approve or reject; history is kept
- Delete only when you need the comment gone for good

## Subscriptions (Get updates)

An **Updates** section lets people leave an email address. Network managers open **Subscriptions** from the page list and can **Export CSV**.

Use this for “email me about this consultation”, not for Thiscovery Forms panel invites.

## Thiscovery Forms

| Block | What it does |
| --- | --- |
| Survey CTA | Sends the visitor to a Thiscovery Form (fill page) |
| Quick poll | Embeds a one-question poll so they can vote without leaving the page |

Thiscovery Forms must be enabled. Pick the form in the block. Polls should be the **Quick poll** type; longer surveys should use Survey CTA.

## HubSpot form

Paste the embed HubSpot gives you into a **HubSpot form** section. **Rich text strips `<script>` tags**, so the form will not run there.

Only HubSpot form loaders (`*.hsforms.net`) are output. The page nonce is added automatically — you can leave HubSpot’s `RANDOM_NONCE_VALUE` as it is.

## Space widgets

On **Settings**, choose a **Bound Space**. Then add:

- Space stream
- Space tasks
- Space files
- Space gallery
- Space calendar

Those modules must be enabled on the space. Files uses the space Files (`cfiles`) module.

If Thiscovery Page Builder is enabled on that space, ordinary members do not get the default space wall; put a **Space stream** block on a page they can open.

## Next

See [Builder and sections](creators-builder.md) for the full block list, and [Thiscovery Page Builder for administrators](admins.md) for permissions and homepage setup.
