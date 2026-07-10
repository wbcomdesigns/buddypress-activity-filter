# Plugin: BuddyPress Activity Filter

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 0 REST endpoints, 0 AJAX handlers, 0 tables, 0 CPTs, 0 cron, 0 WP-CLI, 0 shortcodes, 0 blocks; 11 fired hooks/filters, 5 options, 2 admin pages. This is a hooks/options-driven BuddyPress addon. Use the manifest before grepping. See also [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), [`audit/ROLE_MATRIX.md`](audit/ROLE_MATRIX.md), and the wppqa baseline [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

> **Development conventions:** follow the **`/wp-plugin-development`** skill for ALL work here — the 16 critical admin rules, Part 6 (Admin UI: card-based page-shell + design tokens), escaping/security, and dev hygiene (no em-dash in i18n, Lucide over inline SVG/dashicons, no `alert()`/`confirm()`). This plugin currently predates those conventions (see Admin UI wrapper below).

## Quick reference
- **Main file**: `buddypress-activity-filter.php`
- **Version**: `3.2.1` (dev branch `3.2.1`)
- **Class prefix**: `BP_Activity_Filter_*` (global classes; no PHP namespace)
- **Text domain**: `bp-activity-filter`
- **Requires**: BuddyPress >= 12.0.0, PHP 8.0+. `Network: true`. Incompatible with BuddyBoss.
- **Extends**: null (standalone free plugin; no Pro pair)

## Key entry points
- Bootstrap singleton: `buddypress-activity-filter.php` (`BP_Activity_Filter`)
- Admin settings UI: `includes/class-bp-activity-filter-admin.php` (tabs: default | hidden | cpt | faq)
- Frontend BP integration: `includes/class-bp-activity-filter-frontend.php`
- CPT activity generation: `includes/class-bp-activity-filter-cpt.php`
- Helpers: `includes/class-bp-activity-filter-helper.php`
- Migration / option fallback: `includes/class-bp-activity-filter-migration.php`
- Wbcom glue: `includes/class-wbcom-integration.php`
- Uninstall cleanup: `uninstall.php`

## Admin UI wrapper — INTERMEDIATE (NOT the correct target)

This plugin uses the **Wbcom shared-dashboard wrapper** (INTERMEDIATE generation):

- `includes/shared-admin/class-wbcom-shared-dashboard.php` — shared dashboard renderer
- `includes/shared-admin/class-wbcom-shared-loader.php` — registers `wbcom-designs` top-level menu (`admin_menu` @5) + per-plugin submenus (`admin_menu` @10)
- `includes/shared-admin/wbcom-easy-setup.php` — `wbcom_integrate_plugin()` one-liner
- `includes/shared-admin/wbcom-shared-admin.{css,js}`, `wbcom-shared-tabs.css`
- `includes/class-wbcom-integration.php` — plugin-side registration glue

**Menu registration:** settings page slug `wbcom-activity-filter` under parent `wbcom-designs`, capability `manage_options`, registered by `Wbcom_Shared_Loader::add_plugin_submenus()` (hook `admin_menu`, priority 10). The slug is derived from `settings_url` (`admin.php?page=wbcom-activity-filter`). Menu label overridden to "BP Activity Filter" via the `wbcom_submenu_label` filter. The plugin's own classes do **not** call `add_submenu_page` for the settings page.

**Classification rationale:** per `/wp-plugin-development` Part 6, the modern target is the card-based page-shell with 3-layer design tokens. This shared-dashboard + `nav-tab-wrapper` + `form-table` + heavy inline `style=` pattern is the INTERMEDIATE wrapper and a future migration target — do not treat it as the correct end state.

## Settings options (group `bp_activity_filter_settings`)
- `bp_activity_filter_default` (string `0`) — site-wide default filter
- `bp_activity_filter_profile_default` (string `-1`) — profile default filter
- `bp_activity_filter_hidden` (array) — hidden activity types (core `activity_update`/`activity_comment` protected)
- `bp_activity_filter_cpt_settings` (array) — per-CPT enable/label + `_global.hide_sitewide`
- `bp_activity_filter_db_version` (string, internal, not registered)

Legacy options auto-migrated on `admin_init`: `bp-default-filter-name`, `bp-default-profile-filter-name`, `bp-hidden-filters-name`, `bp-cpt-filters-settings`. All reads go through `BP_Activity_Filter_Migration::get_option_with_fallback()`.

## Important patterns
- All component classes are singletons; init is gated on BuddyPress presence/version in `BP_Activity_Filter::init()`.
- Frontend filtering is **server-side** (`bp_after_has_activities_parse_args` / `bp_ajax_querystring`); inline JS only syncs the dropdown UI.
- CPT activities created on `transition_post_status` @999 with duplicate-prevention via activity + post meta.
- Settings save = plain form POST with nonce `bp_activity_filter_save_settings` + `manage_options` (no AJAX).
- Build: `gruntfile.js`. Note `.gitignore` ignores `*.json` — use `git add -f audit/manifest.json audit/manifest.summary.json`.

## Known cleanup candidates (informational, from onboarding — not fixed)
1. Dead AJAX wiring: `bpActivityFilterAdmin` (nonce/ajaxUrl/currentTab) is localized at `class-bp-activity-filter-admin.php:944` but no `wp_ajax_*` handler or JS consumer exists.
2. `assets/js/admin.js` and `assets/css/admin.css` exist but are not enqueued by current code.
3. INTERMEDIATE admin wrapper → modern card/token migration (see above).

## CSS selectors (for testing/dev)
- Admin: `.bp-activity-filter-admin`, `.wbcom-tab-wrapper`, `.wbcom-nav-tab`, `.bp-activity-checkbox`, `.cpt-settings-table`
- Frontend: `#activity-filter-by` (BuddyPress activity filter dropdown synced by this plugin)

## Recent changes
| Date | Type | Description | Files |
|---|---|---|---|
| 2026-07-10 | feature | Added a "Discover" tab (9 free Wbcom products with brand logos + CTAs) to the card-panel admin, in the existing `resources` group alongside FAQ. New `includes/admin/views/discover.php` + `assets/images/ecosystem/*` (copied from the Todo plugin) + `.bpaf-discover-*` / `.bpaf-btn-secondary` CSS in admin.css; registered in `get_tabs()` + `view_map`. readme.txt + main header `Tested up to: 7.0`; readme `Requires Plugins: buddypress` added; 3.2.1 changelog reformatted to action-prefix house style. Verified in-browser: desktop 3-col grid + 390px single-col, logos load. | includes/admin/class-bp-activity-filter-admin-panel.php, includes/admin/views/discover.php, assets/css/admin.css, assets/images/ecosystem/*, readme.txt, buddypress-activity-filter.php |
| 2026-06-05 | onboard | Generated audit manifest + feature/flow/role reports, graph, wppqa baseline, READ-FIRST CLAUDE.md. No code changes. | audit/*, CLAUDE.md |
