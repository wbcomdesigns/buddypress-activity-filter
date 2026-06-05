# Feature Audit — BuddyPress Activity Filter 3.2.1

> Derived from `audit/manifest.json`. Branch `3.2.1`. Small plugin (13 PHP files), hooks/options driven. No REST, no AJAX, no DB tables, no CPTs, no cron, no WP-CLI, no shortcodes, no blocks.

## 1. Frontend behaviour (no tabs/templates — pure BP hook integration)

| Feature | Mechanism | Source |
|---|---|---|
| Site-wide default filter | Filters `bp_after_has_activities_parse_args` to inject `type` from cookie or `bp_activity_filter_default` | `class-bp-activity-filter-frontend.php:60` |
| AJAX stream default filter | Filters `bp_ajax_querystring` for `activity` object | `:63` |
| Dropdown sync (UI only) | Inline JS in `wp_footer` sets `#activity-filter-by` to cookie/default | `:66` |
| Hide types from dropdown | Filters `bp_get_activity_show_filters` (Nouveau array + legacy HTML) | `:69` |
| Prevent hidden activity save | `bp_activity_before_save` @1 blanks type/component | `:73` |
| Remove creation hooks | `bp_init` @999 `remove_action` for friendship/new_member/profile activities | `:77` |
| CPT activity generation | `transition_post_status` @999 creates BP activity on publish of enabled public CPT | `class-bp-activity-filter-cpt.php:76` |

Core protected types (never hideable): `activity_update`, `activity_comment`.

## 2. AJAX handlers
_None._ (See `static_analysis.dead_localized_data` — a nonce/ajaxUrl is localized but no handler exists.)

## 3. REST endpoints
_None._

## 4. Admin pages / settings

| Page | Slug | Parent | Cap | Registered by |
|---|---|---|---|---|
| BuddyPress Activity Filter | `wbcom-activity-filter` | `wbcom-designs` | `manage_options` | shared loader `add_plugin_submenus()` (admin_menu @10) |
| Wbcom Designs (dashboard) | `wbcom-designs` | top-level | `manage_options` | `ensure_main_menu()` / shared loader `create_main_menu()` (admin_menu @5, guarded) |

Settings page tabs: **Default Filters**, **Hidden Activities**, **Custom Post Types**, **FAQ** (`?tab=` query arg, default `default`).

Options (group `bp_activity_filter_settings`):
- `bp_activity_filter_default` (string, `0`)
- `bp_activity_filter_profile_default` (string, `-1`)
- `bp_activity_filter_hidden` (array, `[]`)
- `bp_activity_filter_cpt_settings` (array, `[]`)
- `bp_activity_filter_db_version` (string, internal — not registered)

Legacy options migrated on `admin_init`: `bp-default-filter-name`, `bp-default-profile-filter-name`, `bp-hidden-filters-name`, `bp-cpt-filters-settings`.

## 5. Shortcodes / 6. Content types / 10. Cron / 11. DB tables / WP-CLI
_None._

## 7. JS modules
- Inline footer JS (dropdown sync) — `frontend.php`.
- `assets/js/admin.js` — checkbox state + form validation; **NOT enqueued**.
- `includes/shared-admin/wbcom-shared-admin.js` — enqueued on Wbcom pages.

## 8. CSS modules
- `includes/shared-admin/wbcom-shared-tabs.css` + `wbcom-shared-admin.css` (enqueued).
- Heavy inline `<style>` and inline `style=` attributes in admin tab renderers.
- `assets/css/admin.css` — exists but **NOT enqueued**.

## 9. Email templates
_None._

## 12. Integrations
- **BuddyPress** (required, >= 12.0.0) — all components gated on BP presence.
- **BuddyBoss** — explicitly incompatible (admin notice + bail).
- **Wbcom shared admin system** — INTERMEDIATE dashboard wrapper; also auto-detects `wbcom_integrate_plugin()` from wbcom-essential / `WBCOM_ESSENTIAL_URL` for shared tab CSS.
- CPT eligibility excludes bbPress (`forum`/`topic`/`reply`) and BP Member Reviews (`review`) when those plugins are active; excludes Elementor UI CPTs.

## Meta keys
Activity meta: `bp_activity_filter_cpt`, `bp_activity_filter_post_id`, `bp_activity_filter_created_time`, `bp_activity_filter_version`.
Post meta: `_bp_activity_filter_activity_id`, `_bp_activity_filter_recorded`.
User meta (uninstall-only): `bp_activity_filter_preference`.

## Extension hooks (fired by this plugin)
10 own filters/actions + consumes `wbcom_submenu_label`. See `audit/manifest.json#/hooks_fired`.
