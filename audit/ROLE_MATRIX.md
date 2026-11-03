# Role Matrix - BuddyPress Activity Filter 4.0.0

Legend: C=Create, R=Read, U=Update, D=Delete, - = no access. Branch `4.0.0`.

The plugin defines **no custom capabilities**. Every admin surface is gated on one accessor, `BP_Activity_Filter_Admin_Panel::capability()` (`includes/admin/class-bp-activity-filter-admin-panel.php:130`), which returns the `bp_activity_filter_admin_capability` filter (:137), default `manage_options`.

| Feature | Administrator | Editor | Author | Subscriber |
|---|---|---|---|---|
| WB Plugins hub landing (`wbcomplugins`, when this plugin creates it) | R | - | - | - |
| Activity Filter settings page (`wbcom-activity-filter`) | R | - | - | - |
| Default Filters tab: save | U | - | - | - |
| Hidden Activities tab: save | U | - | - | - |
| FAQ and Discover tabs | R | - | - | - |
| Frontend: see default-filtered, hidden-type-free activity stream | R | R | R | R (any visitor) |
| Frontend: change own filter (cookie `bp-activity-filter` / sessionStorage) | U | U | U | U (any visitor) |

Enforcement points (both resolve through `capability()`, so a filter cannot move the menu without moving the save):
- `add_menu()` (:145): `add_menu_page` and `add_submenu_page` capability.
- `option_page_capability_bp_activity_filter_settings` filter (:116): the `options.php` save check.

Notes:
- Settings save is a plain form POST to `options.php` with the Settings API nonce (`settings_fields()` in `views/shell.php`); no AJAX endpoints and no REST routes exist, so there is no other write path.
- Delegating the screen below administrator: add a `bp_activity_filter_admin_capability` filter returning another capability (for example `edit_others_posts`). The shared `wbcomplugins` hub menu uses the same capability when this plugin creates it.
- Hidden types and defaults are site-wide; there is no per-role or per-user setting. Core types `activity_update` and `activity_comment` cannot be hidden by any role.
- Multisite: header `Network: true`; uninstall also calls `delete_site_option()` for each option.
- Frontend filtering applies to every activity-stream viewer regardless of login state. The visitor's own choice (cookie, sessionStorage) overrides the admin default until the stored defaults change or the chosen type is hidden.
- Uninstall runs with WordPress's own `delete_plugins` capability, not this plugin's.
