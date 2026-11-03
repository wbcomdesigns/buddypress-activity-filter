# Feature Audit - BuddyPress Activity Filter 4.0.0

> Derived from `audit/manifest.json`, checked against the source. Branch `4.0.0`. Small plugin (hooks/options driven). No REST, no AJAX, no DB tables, no CPTs, no cron, no WP-CLI, no shortcodes, no blocks. Scope: hide activity types and set the default filter.

## 1. Frontend behaviour (pure BuddyPress hook integration, no templates)

All in `includes/class-bp-activity-filter-frontend.php` (`BP_Activity_Filter_Frontend`, singleton). Hooks registered in `setup_hooks()`.

| Feature | Mechanism | Hook (line) |
|---|---|---|
| Default filter on the loop | Sets `action` (never `type`) from member preference, else the stored default; skipped when `filter_query`/`action`/`type` already set | `bp_after_has_activities_parse_args` :72 |
| Hide types from every stream | Adds `a.type NOT IN (...)` under key `bp_activity_filter_hidden` (works on scoped streams, unlike `filter_query`) | `bp_activity_get_where_conditions` :79 |
| Default filter on AJAX streams | Rewrites the activity querystring; drops a remembered filter that is now hidden | `bp_ajax_querystring` :82 |
| Reset stale remembered filter | Inline JS clears sessionStorage `bp-activity` filter and cookie `bp-activity-filter` when the defaults signature changed or the remembered type is hidden; stamps cookie `bpaf-applied-default` | `wp_head` @1 :87 |
| Dropdown sync (UI only) | Inline JS sets `#activity-filter-by` to the member preference or default | `wp_footer` @999 :90 |
| Hide types from the dropdown | Removes hidden options (matches comma keys such as `friendship_accepted,friendship_created`); re-adds a hidden stored default so the select does not rewrite it | `bp_get_activity_show_filters_options` :96 |
| Prevent hidden activity save | Blanks `type` and `component`, adds WP_Error `bp_activity_type_disabled` | `bp_activity_before_save` @1 :103 |

Member choice precedence (`get_member_preference()` :214): null = no choice (use admin default); empty string = member chose Everything (no filter, admin default NOT applied); a type = apply it. A preference is ignored when the defaults changed since last seen or the type is now hidden.

Context (`get_filter_context()` :787): `profile` on member activity, `sitewide` on the activity directory, otherwise no default.

Core protected types (never hideable, stripped in `get_hidden_activities()` :805 and in the sanitizer): `activity_update`, `activity_comment`.

## 2. Settings screen (card panel)

`BP_Activity_Filter_Admin_Panel` (`includes/admin/class-bp-activity-filter-admin-panel.php`), rendered through `views/shell.php`. Tabs come from `get_tabs()` (:74), filterable via `bp_activity_filter_admin_tabs` (:97).

| Tab | Group | View | Owns / content |
|---|---|---|---|
| `default` (Default Filters) | settings | `views/settings-default.php` | `bp_activity_filter_default` (site-wide select) and `bp_activity_filter_profile_default` (profile select; `new_member` and `updated_profile` left out). Hidden types are omitted unless already the stored value, then labelled "(hidden)" with a warning |
| `hidden` (Hidden Activities) | settings | `views/settings-hidden.php` | `bp_activity_filter_hidden`. Core types listed read-only as always visible; every other type is a checkbox (checked = hidden) |
| `faq` (FAQ) | resources | `views/faq.php` | Static Q&A, no form |
| `discover` (Discover) | resources | `views/discover.php` | Static product cards, no form |

Settings tabs post to `options.php` (option group `bp_activity_filter_settings`) with a Save Settings bar. Each settings view emits hidden `bp_activity_filter_rendered_options[]` sentinels; every sanitizer checks `tab_rendered_option()` (:313) and returns the stored value when its tab did not render the key, so saving one tab never wipes the other. Unknown tab slugs fall back to the first tab (`render_page()` :450).

Menu: submenu `wbcom-activity-filter` ("BP Activity Filter") under the shared `wbcomplugins` ("WB Plugins") hub (`add_menu()` :145). The hub top-level menu is created only if no other Wbcom plugin registered it, and `takeover_hub_landing()` (:182, `admin_menu` @999) reclaims the hub landing render. Hub view `views/hub.php` lists peer plugins and has the `wbcom_hub_wrapper_helper_slugs` filter (:24).

Admin assets (`enqueue_assets()` :382): `assets/css/admin.css` and `assets/js/admin.js` (handle `bp-activity-filter-admin`, dep `jquery`), only on the plugin screen and the hub landing (`is_our_screen()` :425). Localized data is `bpActivityFilterAdmin.i18n { visible, hidden }`; no nonce, no ajaxUrl.

Plugins list action link "Settings" (`plugin_action_links()` :259, site and network). Activation redirect to the settings page via transient `bp_activity_filter_activation_redirect` (`handle_activation_redirect()` :417, `admin_init` :473).

## 3. Options

| Option | Type / default | Registered | Sanitizer |
|---|---|---|---|
| `bp_activity_filter_default` | string `0` | yes | `sanitize_default()` |
| `bp_activity_filter_profile_default` | string `-1` | yes | `sanitize_profile_default()` |
| `bp_activity_filter_hidden` | array `[]` | yes | `sanitize_hidden()` (drops core types, unknown types, duplicates) |
| `bp_activity_filter_db_version` | string `0` | no (internal) | n/a |

Values are validated against `BP_Activity_Filter_Helper::get_activity_actions()` plus `0` and `-1`. Reads of the two defaults go through `BP_Activity_Filter_Migration::get_option_with_fallback()`.

## 4. Migration

`BP_Activity_Filter_Migration` (`admin_init` :66 -> `maybe_migrate()`): fresh install with no legacy options just stamps `bp_activity_filter_db_version`; otherwise, when the stored version is below the plugin version, copies `bp-default-filter-name`, `bp-default-profile-filter-name`, `bp-hidden-filters-name` into the new keys (never overwriting a non-empty new value), adds any missing defaults, then stamps the version. On the front end `get_option_with_fallback()` lazily does the same per key.

## 5. Public extension points

| Hook | Type | Where |
|---|---|---|
| `bp_activity_filter_init` | action | `buddypress-activity-filter.php:146` |
| `bp_activity_filter_activity_actions` | filter (array $labels) | `includes/class-bp-activity-filter-helper.php:67` |
| `bp_activity_filter_default` | filter (string $default_filter, string $context) | `includes/class-bp-activity-filter-frontend.php:769` |
| `bp_activity_filter_admin_tabs` | filter (array $tabs) | `includes/admin/class-bp-activity-filter-admin-panel.php:97` |
| `bp_activity_filter_admin_capability` | filter (string $capability, default `manage_options`) | `includes/admin/class-bp-activity-filter-admin-panel.php:137` |
| `bp_activity_filter_preserve_data_on_uninstall` | filter (bool, default false) | `uninstall.php:26` |
| `wbcom_hub_wrapper_helper_slugs` | filter (shared hub namespace) | `includes/admin/views/hub.php:24` |

## 6. Uninstall

`uninstall.php`: unless `bp_activity_filter_preserve_data_on_uninstall` returns true, deletes the four current options plus legacy keys (`bp-default-filter-name`, `bp-default-profile-filter-name`, `bp-hidden-filters-name`, `bp-cpt-filters-settings`, `bp_activity_filter_cpt_settings`) with `delete_option` and `delete_site_option`; deletes four activity-meta keys (`bp_activity_filter_cpt`, `bp_activity_filter_post_id`, `bp_activity_filter_created_time`, `bp_activity_filter_version`) when the BP activity component and `bp_activity_meta` table exist; deletes post-meta keys `_bp_activity_filter_activity_id` and `_bp_activity_filter_recorded` with `delete_post_meta_by_key()` (runs even if BuddyPress is gone); flushes the object cache.

## 7. Requirements and guards

BuddyPress >= 12.0.0, PHP 8.0+ (header: WordPress 6.9+, `Requires Plugins: buddypress`, `Network: true`). `BP_Activity_Filter::init()` bails with an admin notice when BuddyPress is missing, too old, or BuddyBoss is active. Activation refuses without BuddyPress and seeds the three default options.

## Removed in 4.0.0

The custom post type activity generation feature (with its settings and class) was removed, and the old `BP_Activity_Filter_Admin` class is gone. Uninstall still cleans the rows older versions wrote.
