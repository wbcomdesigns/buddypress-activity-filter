# Plugin: BuddyPress Activity Filter

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 0 REST endpoints, 0 AJAX handlers, 0 tables, 0 CPTs, 0 cron, 0 WP-CLI, 0 shortcodes, 0 blocks; 5 fired hooks/filters, 4 options, 2 admin pages. This is a hooks/options-driven BuddyPress addon. Use the manifest before grepping. See also [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), [`audit/ROLE_MATRIX.md`](audit/ROLE_MATRIX.md), and the wppqa baseline [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

> **Development conventions:** follow the **`/wp-plugin-development`** skill for ALL work here — the 16 critical admin rules, Part 6 (Admin UI: card-based page-shell + design tokens), escaping/security, and dev hygiene (no em-dash in i18n, Lucide over inline SVG/dashicons, no `alert()`/`confirm()`). The admin was migrated onto the card-panel in 3.2.1, so it now matches those conventions.

## Quick reference
- **Main file**: `buddypress-activity-filter.php`
- **Version**: `4.0.0`
- **Scope**: hide activity types + set the default filter. That is the whole plugin. The CPT activity-generation feature was removed in 4.0.0 (see below) — do not reintroduce it.
- **Class prefix**: `BP_Activity_Filter_*` (global classes; no PHP namespace)
- **Text domain**: `bp-activity-filter`
- **Requires**: BuddyPress >= 12.0.0, PHP 8.0+. `Network: true`. Incompatible with BuddyBoss.
- **Extends**: null (standalone free plugin; no Pro pair)

## Key entry points
- Bootstrap singleton: `buddypress-activity-filter.php` (`BP_Activity_Filter`)
- Admin settings UI: `includes/admin/class-bp-activity-filter-admin-panel.php` + `includes/admin/views/` (tabs: default | hidden | faq | discover)
- Frontend BP integration: `includes/class-bp-activity-filter-frontend.php`
- Helpers: `includes/class-bp-activity-filter-helper.php`
- Migration / option fallback: `includes/class-bp-activity-filter-migration.php`
- Uninstall cleanup: `uninstall.php`

That is the complete class list. There is no separate admin class, no shared-admin wrapper, and no Wbcom integration glue file — all were removed in 3.2.1. There is no CPT class — removed in 4.0.0.

## Why the CPT feature was removed in 4.0.0 (do not rebuild it)

`BP_Activity_Filter_CPT` generated a BuddyPress activity when a post of an enabled custom post type was published. It was removed, not fixed, because it was structurally wrong in ways that fought this plugin's own purpose:

1. It recorded **every** CPT under `type => 'new_blog_post'`, so CPT activities could not be filtered or hidden separately — the exact thing this plugin exists to do.
2. It set `item_id => $post->ID` under that type, but BuddyPress core treats `new_blog_post`'s `item_id` as a **blog ID** (`bp_blogs_format_activity_action_new_blog_post()` calls `switch_to_blog( $activity->item_id )`). The data lied about its own meaning and broke on multisite.
3. There was **no deletion handling at all** — no `before_delete_post`, no trash hook. Deleting a post left its activity in the stream forever pointing at a dead permalink.
4. Duplicate prevention was a 60-second time-window guess, commented "might be from another plugin".

Removing it also deleted ~250 lines of post-type eligibility/conflict-detection helpers that existed only to serve it. `uninstall.php` still cleans the four activity-meta and two post-meta keys it wrote, because 3.x sites still carry those rows.

## Admin UI — card-panel (current target)

`BP_Activity_Filter_Admin_Panel` renders the modern card-based page-shell with design tokens (`/wp-plugin-development` Part 6). The legacy Wbcom shared-dashboard wrapper (`includes/shared-admin/*`, `class-wbcom-integration.php`) and the old `BP_Activity_Filter_Admin` class were **deleted** in 3.2.1 — do not reintroduce them.

**Menu registration:** page slug `wbcom-activity-filter` under the shared `WB Plugins` menu, capability `manage_options`, registered by `BP_Activity_Filter_Admin_Panel::add_menu()`.

**Settings save — read this before touching the sanitizers.** All options share one option group but are split across tabs. The Settings API calls `update_option()` for *every* option in the group on save, passing `null` for options the submitted tab did not render. Each tab view therefore emits a hidden `bp_activity_filter_rendered_options[]` sentinel listing the keys it owns, and every sanitize callback checks `tab_rendered_option()` first, returning the stored value untouched when it does not own the key. Remove that guard and saving one tab wipes the others. Sanitizers (`sanitize_filter_value()`, `sanitize_hidden_list()`) live in this class only — `sanitize_hidden_list()` is what makes `activity_update` / `activity_comment` unhideable.

## Settings options (group `bp_activity_filter_settings`)
- `bp_activity_filter_default` (string `0`) — site-wide default filter
- `bp_activity_filter_profile_default` (string `-1`) — profile default filter
- `bp_activity_filter_hidden` (array) — hidden activity types (core `activity_update`/`activity_comment` protected)
- `bp_activity_filter_db_version` (string, internal, not registered)

Legacy options auto-migrated on `admin_init`: `bp-default-filter-name`, `bp-default-profile-filter-name`, `bp-hidden-filters-name`. All reads go through `BP_Activity_Filter_Migration::get_option_with_fallback()`.

## Important patterns
- All component classes are singletons; init is gated on BuddyPress presence/version in `BP_Activity_Filter::init()`.
- Frontend filtering is **server-side** (`bp_after_has_activities_parse_args` / `bp_ajax_querystring`); inline JS only syncs the dropdown UI.
- Settings save = plain form POST with nonce `bp_activity_filter_save_settings` + `manage_options` (no AJAX).
- Build: `gruntfile.js`. Note `.gitignore` ignores `*.json` — use `git add -f audit/manifest.json audit/manifest.summary.json`.

## Known cleanup candidates
None outstanding. All three items from the 2026-06-05 onboarding audit were resolved in 3.2.1 and verified on 2026-07-13:
1. ~~Dead AJAX wiring (`bpActivityFilterAdmin` nonce/ajaxUrl with no handler)~~ — the localize now passes `i18n` only; `assets/js/admin.js` makes no AJAX call, and the plugin still has 0 AJAX handlers by design.
2. ~~`assets/js/admin.js` / `assets/css/admin.css` not enqueued~~ — both are enqueued by `Admin_Panel::enqueue_assets()`.
3. ~~INTERMEDIATE admin wrapper~~ — migrated to the card-panel; the shared-dashboard files are gone.

Dead code swept on 2026-07-13 (do not reintroduce): `set_initial_default_filter_legacy()`, `is_activity_page()`, `remove_hidden_activity_hooks()`, `Helper::get_default_filter()`, `Helper::sanitize_filter_value()`, `Helper::get_plugin_dir()`, `Helper::get_plugin_url()`, and the whole `BP_Activity_Filter_Admin` class.

## BuddyPress gotchas — READ BEFORE TOUCHING THE FRONTEND

Three BP behaviours caused every bug fixed in 3.2.1. All verified against BuddyPress 14.4.0 core:

1. **Dropdown options:** filter `bp_get_activity_show_filters_options` (`$filters, $context`), NEVER `bp_get_activity_show_filters`. Nouveau hooks the latter at the same priority 10 and returns the *original* `$filters` array, discarding any edit to the HTML `$output`.
2. **Filtering the loop:** pass `action`, NOT `type`. `bp_has_activities()` has no `type` argument; it filters on `action`. Setting `type` is silently ignored and looks like "the setting does nothing".
3. **Excluding types from the stream:** use `bp_activity_get_where_conditions`, NOT `filter_query`. `BP_Activity_Activity::get()` branches `if ( scope ) ... elseif ( filter_query )` — "scope takes precedence" — so `filter_query` is ignored on every scoped stream (just-me, friends, groups, mentions, favorites).

Also: BuddyPress collapses friendships into the single option key `friendship_accepted,friendship_created`, while the settings screen stores `friendship_created`. Always match dropdown option keys on their comma-separated parts.

**Testing note:** BuddyX loads member streams via AJAX POST and `bp_nouveau_ajax_querystring()` replays `$_POST['filter']` (emitting BOTH `type=` and `action=`), so a stale filter in the browser cookie/localStorage can look like a bug. Do NOT clear cookies/storage to make a run pass — a real member arrives with dirty state, so that is the state the fix has to survive. Reproduce in one dirty session and note the stale-filter interaction explicitly.

## CSS selectors (for testing/dev)
- Admin: `.bp-activity-filter-admin`, `.wbcom-tab-wrapper`, `.wbcom-nav-tab`, `.bp-activity-checkbox`
- Frontend: `#activity-filter-by` (BuddyPress activity filter dropdown synced by this plugin)

