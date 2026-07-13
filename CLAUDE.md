# Plugin: BuddyPress Activity Filter

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 0 REST endpoints, 0 AJAX handlers, 0 tables, 0 CPTs, 0 cron, 0 WP-CLI, 0 shortcodes, 0 blocks; 11 fired hooks/filters, 5 options, 2 admin pages. This is a hooks/options-driven BuddyPress addon. Use the manifest before grepping. See also [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), [`audit/ROLE_MATRIX.md`](audit/ROLE_MATRIX.md), and the wppqa baseline [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

> **Development conventions:** follow the **`/wp-plugin-development`** skill for ALL work here — the 16 critical admin rules, Part 6 (Admin UI: card-based page-shell + design tokens), escaping/security, and dev hygiene (no em-dash in i18n, Lucide over inline SVG/dashicons, no `alert()`/`confirm()`). The admin was migrated onto the card-panel in 3.2.1, so it now matches those conventions.

## Quick reference
- **Main file**: `buddypress-activity-filter.php`
- **Version**: `3.2.1` (dev branch `3.2.1`)
- **Class prefix**: `BP_Activity_Filter_*` (global classes; no PHP namespace)
- **Text domain**: `bp-activity-filter`
- **Requires**: BuddyPress >= 12.0.0, PHP 8.0+. `Network: true`. Incompatible with BuddyBoss.
- **Extends**: null (standalone free plugin; no Pro pair)

## Key entry points
- Bootstrap singleton: `buddypress-activity-filter.php` (`BP_Activity_Filter`)
- Admin settings UI: `includes/admin/class-bp-activity-filter-admin-panel.php` + `includes/admin/views/` (tabs: default | hidden | cpt | faq | discover)
- Frontend BP integration: `includes/class-bp-activity-filter-frontend.php`
- CPT activity generation: `includes/class-bp-activity-filter-cpt.php`
- Helpers: `includes/class-bp-activity-filter-helper.php`
- Migration / option fallback: `includes/class-bp-activity-filter-migration.php`
- Uninstall cleanup: `uninstall.php`

That is the complete class list. There is no separate admin class, no shared-admin wrapper, and no Wbcom integration glue file — all were removed in 3.2.1.

## Admin UI — card-panel (current target)

`BP_Activity_Filter_Admin_Panel` renders the modern card-based page-shell with design tokens (`/wp-plugin-development` Part 6). The legacy Wbcom shared-dashboard wrapper (`includes/shared-admin/*`, `class-wbcom-integration.php`) and the old `BP_Activity_Filter_Admin` class were **deleted** in 3.2.1 — do not reintroduce them.

**Menu registration:** page slug `wbcom-activity-filter` under the shared `WB Plugins` menu, capability `manage_options`, registered by `BP_Activity_Filter_Admin_Panel::add_menu()`.

**Settings save — read this before touching the sanitizers.** All options share one option group but are split across tabs. The Settings API calls `update_option()` for *every* option in the group on save, passing `null` for options the submitted tab did not render. Each tab view therefore emits a hidden `bp_activity_filter_rendered_options[]` sentinel listing the keys it owns, and every sanitize callback checks `tab_rendered_option()` first, returning the stored value untouched when it does not own the key. Remove that guard and saving one tab wipes the others. Sanitizers (`sanitize_filter_value()`, `sanitize_hidden_list()`, `sanitize_cpt_map()`) live in this class only — `sanitize_hidden_list()` is what makes `activity_update` / `activity_comment` unhideable.

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

**Testing note:** BuddyX loads member streams via AJAX POST and `bp_nouveau_ajax_querystring()` replays `$_POST['filter']` (emitting BOTH `type=` and `action=`), so a stale filter in the browser cookie/localStorage masquerades as a bug. Clear cookies + storage between runs.

## CSS selectors (for testing/dev)
- Admin: `.bp-activity-filter-admin`, `.wbcom-tab-wrapper`, `.wbcom-nav-tab`, `.bp-activity-checkbox`, `.cpt-settings-table`
- Frontend: `#activity-filter-by` (BuddyPress activity filter dropdown synced by this plugin)

## Recent changes
| Date | Type | Description | Files |
|---|---|---|---|
| 2026-07-13 | refactor | Consolidated the duplicate admin path. `BP_Activity_Filter_Admin` had been reduced to a hookless sanitizer service that `Admin_Panel` delegated to, with a third unreachable inline fallback in each callback. Moved the three sanitizers into `Admin_Panel` (`sanitize_filter_value`/`sanitize_hidden_list`/`sanitize_cpt_map`), deleted the class + its include + `legacy_admin()` + the dead fallbacks. Browser-verified that the per-tab sentinel guard still prevents cross-tab data loss (saved Hidden tab -> Default/CPT preserved, and vice versa) and that core `activity_update`/`activity_comment` remain unhideable. Also corrected CLAUDE.md, which still documented three deleted files. | includes/admin/class-bp-activity-filter-admin-panel.php, includes/class-bp-activity-filter-admin.php (deleted), buddypress-activity-filter.php, CLAUDE.md |
| 2026-07-13 | bug-fix | BC card 9551701822 + cleanup. (1) **Default Filter never filtered anything**: the plugin set `$args['type']`, but `bp_has_activities()` has NO `type` arg - BuddyPress filters on **`action`** (`bp-activity-template.php` maps `$r['action']` into the filter array). The old code comment claimed the reverse. Fixed on both the parse_args and `bp_ajax_querystring` paths. (2) **Blank dropdown**: default of `friendship_created` never matched BP's combined option key `friendship_accepted,friendship_created`, so `select.value` assignment failed and `selectedIndex` went to -1. Sync script now matches on the comma-separated parts. (3) **Dead code removed**: `set_initial_default_filter_legacy()` (never hooked), `is_activity_page()` (only used by it), and `Helper::get_default_filter/sanitize_filter_value/get_plugin_dir/get_plugin_url` (0 callers; last two duplicated the main class). NOTE for testing: BuddyX loads member streams via AJAX POST, and Nouveau's `bp_nouveau_ajax_querystring()` emits BOTH `type=` and `action=` from `$_POST['filter']` - stale browser cookie/storage will masquerade as a bug, so clear client state between runs. | includes/class-bp-activity-filter-frontend.php, includes/class-bp-activity-filter-helper.php, readme.txt |
| 2026-07-13 | bug-fix | BC card 9551698455: hidden types were removed from the dropdown but never excluded from the stream, so activities recorded *before* a type was hidden stayed in the feed. Added `exclude_hidden_from_query()` on **`bp_activity_get_where_conditions`**. Note: `filter_query` is NOT usable here - `BP_Activity_Activity::get()` line ~487 uses `elseif`, so filter_query is ignored whenever a `scope` is set (just-me, friends, groups, mentions, favorites). The WHERE-conditions hook is ANDed after that branch, so it covers every scope, AJAX, and the pagination count (same `$where_sql`, line ~852). Guarded with `is_admin() && ! wp_doing_ajax()` so hidden items stay moderatable on the wp-admin Activity screen (front-end AJAX still passes, since admin-ajax sets `is_admin()` true). | includes/class-bp-activity-filter-frontend.php, readme.txt |
| 2026-07-13 | bug-fix | QA bounce (BC card 10087839176): hidden activity types still appeared in the filter dropdown on Nouveau/BuddyX. Root cause confirmed against BP 14.4.0 core: Nouveau hooks `bp_get_activity_show_filters` at the same priority 10 and returns the *original* `$filters` array (2nd param), discarding our edits to the HTML `$output`. Moved to `bp_get_activity_show_filters_options` (fires before the HTML is built), which fixes Legacy + Nouveau in one path. Also fixed a second, unreported bug found while verifying: BP collapses friendships into the combined key `friendship_accepted,friendship_created`, so hiding "New friendships" never matched — dropdown options are now matched per comma-part. Removed `remove_hidden_activity_hooks()` as dead code: 3 of its 4 mapped callbacks (`bp_friends_friendship_requested_activity`, `bp_activity_new_member_activity`) do not exist in BuddyPress, and `maybe_prevent_activity_save()` already blocks creation of every hidden type (verified: `bp_activity_add()` returns false). | includes/class-bp-activity-filter-frontend.php, readme.txt |
| 2026-07-10 | feature | Added a "Discover" tab (9 free Wbcom products with brand logos + CTAs) to the card-panel admin, in the existing `resources` group alongside FAQ. New `includes/admin/views/discover.php` + `assets/images/ecosystem/*` (copied from the Todo plugin) + `.bpaf-discover-*` / `.bpaf-btn-secondary` CSS in admin.css; registered in `get_tabs()` + `view_map`. readme.txt + main header `Tested up to: 7.0`; readme `Requires Plugins: buddypress` added; 3.2.1 changelog reformatted to action-prefix house style. Verified in-browser: desktop 3-col grid + 390px single-col, logos load. | includes/admin/class-bp-activity-filter-admin-panel.php, includes/admin/views/discover.php, assets/css/admin.css, assets/images/ecosystem/*, readme.txt, buddypress-activity-filter.php |
| 2026-06-05 | onboard | Generated audit manifest + feature/flow/role reports, graph, wppqa baseline, READ-FIRST CLAUDE.md. No code changes. | audit/*, CLAUDE.md |
