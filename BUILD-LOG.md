# BUILD-LOG — BuddyPress Activity Filter wrapper migration

**Plugin:** buddypress-activity-filter
**Branch:** `3.2.1`
**Date:** 2026-06-05
**Scope:** Migrate the admin wrapper from the INTERMEDIATE Wbcom shared-dashboard
(`includes/shared-admin/` + `includes/class-wbcom-integration.php`) to the new
card-panel wrapper (contact-me pattern). UX + approved safe fixes only.
No version bump (header already `3.2.1`, matches branch).

---

## Deleted (7 files, all via `git rm`)

The entire INTERMEDIATE wrapper:

- `includes/shared-admin/class-wbcom-shared-dashboard.php`
- `includes/shared-admin/class-wbcom-shared-loader.php`
- `includes/shared-admin/wbcom-easy-setup.php`
- `includes/shared-admin/wbcom-shared-admin.css`
- `includes/shared-admin/wbcom-shared-admin.js`
- `includes/shared-admin/wbcom-shared-tabs.css`
- `includes/class-wbcom-integration.php` (plugin-side glue)

Post-delete grep proved **zero** remaining references to `shared-admin`,
`class-wbcom-integration`, `Wbcom_Shared_*`, `wbcom_integrate_plugin`,
`BP_Activity_Filter_Wbcom_Integration`, `wbcom-designs`, `wbcom_submenu_label`,
`wbcom-shared-tabs`/`wbcom-shared-admin` handles, or `WBCOM_ESSENTIAL`
(the only hit is a descriptive doc comment in the new controller naming what
it replaced — not a live reference).

## Created (9 files)

Controller + views (mirror contact-me, prefix `bpaf` / `BP_Activity_Filter_`):

- `includes/admin/class-bp-activity-filter-admin-panel.php` — controller:
  `get_tabs()`, `register()`, `add_menu()` (parent `wbcomplugins` + submenu
  slug `wbcom-activity-filter`), `takeover_hub_landing()` @999, `register_settings()`,
  4 sentinel-guarded sanitize callbacks, `enqueue_assets()` (screen-scoped),
  `suppress_foreign_notices()`, `is_our_screen()`, `render_hub()`, `render_page()`.
- `includes/admin/views/shell.php` — header card + sidebar nav + body slot,
  `wp-header-end` marker, settings form posts to `options.php`.
- `includes/admin/views/hub.php` — WB Plugins hub landing (card grid + wrapper
  helper-slug filter).
- `includes/admin/views/settings-default.php` — Default Filters tab (owns
  `bp_activity_filter_default` + `bp_activity_filter_profile_default`).
- `includes/admin/views/settings-hidden.php` — Hidden Activities tab (owns
  `bp_activity_filter_hidden`).
- `includes/admin/views/settings-cpt.php` — Custom Post Types tab (owns
  `bp_activity_filter_cpt_settings`).
- `includes/admin/views/faq.php` — FAQ tab (non-settings, no save bar).
- `BUILD-LOG.md` — this file.

(`CLAUDE.md` + `audit/` are pre-existing onboarding artifacts, untracked.)

## Modified (5 files)

- `buddypress-activity-filter.php`
  - Removed `init_wbcom_integration()`, `init_wbcom_integration_fallback()`,
    `render_admin_page()`, the `$wbcom_integration` property,
    `get_wbcom_integration()`, `is_wbcom_integration_active()`, and the
    `init:1 -> init_wbcom_integration` hook.
  - Added the new panel to `includes()` and registered it in
    `init_components()` via `BP_Activity_Filter_Admin_Panel::instance()->register()`.
  - `plugin_action_links()`: dropped the `wbcom-designs` "Dashboard" link;
    kept the "Settings" link (slug unchanged).
  - `handle_activation_redirect()`: now redirects to
    `admin.php?page=wbcom-activity-filter` (was `wbcom-designs`).
  - Removed the `wbcom_submenu_label` filter + its callback
    `bp_activity_filter_customize_submenu_label()` (the filter is no longer
    fired now the shared loader is gone).
- `includes/class-bp-activity-filter-admin.php` — slimmed to a sanitizer-only
  service. Removed all UI render methods (`render_settings_page`,
  `render_*_tab`, `render_admin_tabs`, `render_tab_content`,
  `render_cpt_*`), per-tab save handlers (`save_settings`, `save_default_filters`,
  `save_hidden_activities`, `save_cpt_settings`), `register_settings`,
  `enqueue_admin_scripts` (incl. the dead `bpActivityFilterAdmin` localize and
  the `wbcom-shared-tabs` enqueue), `get_current_tab`, and the `$current_tab`
  property. Kept the three battle-tested sanitizers
  (`sanitize_default_filter`, `sanitize_hidden_activities`,
  `sanitize_cpt_settings`) which the new panel delegates to. Docblock rewritten
  to describe the post-migration (sanitizer-only) scope.
- `assets/css/admin.css` — replaced the dead legacy CSS with the new
  token-driven card-panel stylesheet (`--bpaf-admin-*` tokens, two media
  blocks). Now enqueued by the new controller (no longer dead).
- `assets/js/admin.js` — replaced the dead 475-line legacy JS with a minimal
  screen-scoped script that syncs the Hidden Activities row visual state.
  Now enqueued (no longer dead). No native `alert()`/`confirm()`.
- `readme.txt` — changelog stub added under the existing `= 3.2.1 =` entry
  (card-panel rebuild + legacy wrapper retirement).

---

## Option keys preserved (old -> new proof)

The migration is UX-only — **zero** option/group renames.

| Option group | Option keys | Old (legacy `register_setting`) | New (`class-bp-activity-filter-admin-panel.php`) |
|---|---|---|---|
| `bp_activity_filter_settings` | `bp_activity_filter_default` | yes | yes |
| `bp_activity_filter_settings` | `bp_activity_filter_profile_default` | yes | yes |
| `bp_activity_filter_settings` | `bp_activity_filter_hidden` | yes | yes |
| `bp_activity_filter_settings` | `bp_activity_filter_cpt_settings` | yes | yes |
| (internal, not registered) | `bp_activity_filter_db_version` | Migration class | unchanged |

- Frontend / CPT / Helper still read via
  `BP_Activity_Filter_Migration::get_option_with_fallback()` and `get_option()`
  on the same keys — confirmed unchanged by grep. Legacy-option auto-migration
  (`bp-default-filter-name` etc.) on `admin_init` is untouched.
- Sanitize callbacks are the same legacy methods, delegated to from the new
  panel, so saved option rows are byte-identical to the pre-3.2.1 form.

## Save mechanism

Legacy used a plain self-POST page (`action=""`) with custom nonce
`bp_activity_filter_save_settings` + per-tab `update_option()` calls.
The new panel uses the **WordPress Settings API** (`options.php`) — the
contact-me pattern the brief mandates. The custom nonce + per-tab save methods
are removed (dead). `register_setting()` for the same group/keys already
existed, so this is a clean move to the canonical path.

### Multi-tab data-loss guard (Playbook 7.1 — sentinel-guarded merge)

`options.php` calls `update_option()` for **every** option registered to the
group on save, passing `null` for options the submitting tab did not render.
Without protection, saving the Default tab would null out `hidden` +
`cpt_settings` (and vice-versa) — data loss.

Fix: each settings tab view emits hidden
`bp_activity_filter_rendered_options[]` inputs listing the option keys it owns.
Each sanitize callback calls `tab_rendered_option()`; if its key was **not**
rendered by the submitting tab, it returns the currently stored value instead
of sanitizing a `null` into a default. Verified at runtime via `wp eval`:
with the Hidden tab submitting, `sanitize_default(null)` returned the stored
`'activity_update'` rather than `'0'`. Programmatic callers without the
sentinel fall through to normal sanitization (treated as owned).

## Safe fixes applied

1. **Dead AJAX wiring removed** — the `bpActivityFilterAdmin` localize
   (nonce/ajaxUrl/currentTab on the `jquery` handle) had no `wp_ajax_*` handler
   and no JS consumer; deleted with the legacy `enqueue_admin_scripts`. The new
   panel localizes only i18n strings onto its own enqueued handle.
2. **Dead assets repurposed** — the previously-unenqueued `assets/js/admin.js`
   and `assets/css/admin.css` are now the card-panel's enqueued admin JS/CSS,
   screen-scoped via `is_our_screen()`. No longer dead.
3. **No native alert/confirm** — none were present; the new JS uses none.
4. **Menu corrected** — dropped the separate `wbcom-designs` top-level; the
   plugin now attaches a single submenu to the shared `wbcomplugins` hub,
   preserving the `wbcom-activity-filter` page slug for URL continuity.
5. `current_user_can('manage_options')` is enforced on the settings page by the
   menu capability + the Settings API (`options.php` does its own
   `check_admin_referer` + capability map). No bespoke save handler remains that
   needed a manual cap add.

## Verify results

- `php -l`: clean on all 9 created/modified PHP files **and** the full plugin
  tree (`ALL PHP OK`).
- WPCS (wpcs MCP): **0 errors, 0 warnings** on `includes/admin/` (7 files),
  `buddypress-activity-filter.php`, and `includes/class-bp-activity-filter-admin.php`
  (one initial DocComment.ShortNotCapital error was fixed).
- Runtime (`wp eval`, BuddyPress active): new classes load, `add_menu` /
  `register_settings` hooks attach, tabs = `default,hidden,cpt,faq`,
  `MENU_SLUG=wbcom-activity-filter`, `OPTION_GROUP=bp_activity_filter_settings`,
  legacy sanitizer reachable, sentinel-guard proven (no data loss).
- Inline `<style>`/`<script>`: none in any admin file (the only hit,
  `class-bp-activity-filter-frontend.php`, is the pre-existing frontend
  dropdown-sync footer JS — out of scope, untouched).
- Em-dash: none in i18n strings (only in a PHP doc comment + an HTML `&mdash;`
  entity in CPT meta output).

## TODOs / needs human eyes

- **Browser smoke not run** — the brief forbids activate/deactivate, and the
  plugin is currently inactive on this site, so the Part 13 browser checklist
  (render every tab, save round-trip via options.php, 390px, console/debug.log,
  hub landing in a mixed install) was not executed. Load + hooks + sentinel
  logic were proven via `wp eval`. Recommend a browser pass after activation
  before release.
- **`/action-audit` not run** — this plugin has no admin AJAX/data-action
  buttons after migration (settings save is a Settings API form post; the only
  JS is the visual-state toggle). Cross-layer audit is low-risk here but could
  be run for completeness.
- No version bump performed (header `3.2.1` already matches branch `3.2.1`), per
  the brief.
