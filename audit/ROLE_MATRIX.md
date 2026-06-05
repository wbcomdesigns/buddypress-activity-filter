# Role Matrix — BuddyPress Activity Filter 3.2.1

Legend: C=Create, R=Read, U=Update, D=Delete, - = no access.

The plugin defines **no custom capabilities**. Every admin surface is gated on `manage_options`.

| Feature | Administrator | Editor | Author | Subscriber |
|---|---|---|---|---|
| Wbcom Designs dashboard (`wbcom-designs`) | R | - | - | - |
| Activity Filter settings page (`wbcom-activity-filter`) | RU | - | - | - |
| Default Filters / Hidden / CPT settings save | U | - | - | - |
| FAQ tab | R | - | - | - |
| Frontend: see default-filtered activity stream | R | R | R | R (any visitor) |
| Frontend: change own filter (cookie) | U | U | U | U (any visitor) |
| CPT activity auto-created on publish | (actor = post author of the published CPT, any role that can publish that CPT) | | | |

Notes:
- No editor/author/subscriber-specific admin access — `manage_options` (administrator / network admin) only.
- Network: plugin header `Network: true`; menu + uninstall handle multisite (`delete_site_option`).
- Frontend filtering applies to all activity-stream viewers regardless of login state.
