# Wrapper Audit — BuddyPress Activity Filter

**Plugin:** buddypress-activity-filter  
**Branch:** 3.2.1  
**Wrapper type:** NEW card-panel (migration from INTERMEDIATE shared-dashboard complete)  
**Audit date:** 2026-06-05  
**Auditor:** AutoVAP read-only pass  

---

## 1. INTERMEDIATE Wrapper Removal — PASS

### Files deleted (verified absent)

| Path | Present? |
|---|---|
| `includes/shared-admin/` (entire directory, 7 files) | GONE — `ls` returns "No such file or directory" |
| `includes/class-wbcom-integration.php` | GONE — confirmed absent |

### Dangling reference grep — PASS (zero live hits)

The grep over all `.php`, `.js`, `.css` files for:

```
Wbcom_Shared_* | wbcom_integrate_plugin | wbcom-designs | BP_Activity_Filter_Wbcom_Integration
wbcom_submenu_label | wbcom-shared-tabs | wbcom-shared-admin | WBCOM_ESSENTIAL
init_wbcom_integration | render_admin_page | get_wbcom_integration | is_wbcom_integration_active
```

**Result:** zero matches in live code. The sole hit was a doc-comment in `includes/admin/class-bp-activity-filter-admin-panel.php:6` describing *what it replaced* — not a live reference. PASS.

---

## 2. Controller Contract — PASS (all methods present)

**File:** `includes/admin/class-bp-activity-filter-admin-panel.php`

| Contract method | Present? | Notes |
|---|---|---|
| `get_tabs()` | YES — static, lines 74–98 | 4 tabs: default, hidden, cpt, faq |
| `register()` | YES — lines 105–115 | Hooks: admin_menu, admin_menu @999, admin_init, admin_enqueue_scripts, in_admin_header |
| `add_menu()` | YES — lines 122–150 | Parent `wbcomplugins`; no `wbcom-designs` reference |
| `takeover_hub_landing()` | YES — lines 159–166 | @999; removes/re-adds `toplevel_page_wbcomplugins` |
| `register_settings()` | YES — lines 178–219 | All 4 options registered with sanitize_callback |
| `sanitize_default()` | YES — lines 284–293 | Sentinel-guarded |
| `sanitize_profile_default()` | YES — lines 302–311 | Sentinel-guarded |
| `sanitize_hidden()` | YES — lines 320–329 | Sentinel-guarded |
| `sanitize_cpt()` | YES — lines 339–349 | Sentinel-guarded |
| `enqueue_assets()` | YES — lines 357–393 | Screen-scoped via `is_our_screen()` |
| `suppress_foreign_notices()` | YES — lines 400–407 | Hooked on `in_admin_header @1` |
| `is_our_screen()` | YES — lines 417–423 | private; matches plugin page + hub |
| `render_hub()` | YES — lines 430–435 | Loads `includes/admin/views/hub.php` |
| `render_page()` | YES — lines 442–466 | Routes to active tab view via shell.php |

**Minor finding:** `$legacy = $this->legacy_admin()` is assigned and then immediately `unset( $legacy )` inside `register_settings()` (lines 179 + 218) without ever using the variable. This is dead code — the actual legacy delegation happens inside each sanitize_* method via their own `$this->legacy_admin()` calls. No functional impact; WPCS "unused variable" lint noise risk.

---

## 3. Menu Registration — PASS

- `add_menu()` checks `$GLOBALS['admin_page_hooks']['wbcomplugins']` and creates the top-level `wbcomplugins` hub only if not already present — correct race guard.  
- Submenu registered under parent `wbcomplugins`, slug `wbcom-activity-filter`, capability `manage_options`.  
- **Zero** `wbcom-designs` references anywhere in the codebase.  
- `plugin_action_links()` "Settings" link points to `admin.php?page=wbcom-activity-filter` (correct).  
- `handle_activation_redirect()` redirects to `admin.php?page=wbcom-activity-filter` (correct).

---

## 4. Path-Constant Correctness — PASS

Both path constants are defined in the **main plugin file** `buddypress-activity-filter.php` using `__FILE__`:

```
BP_ACTIVITY_FILTER_PLUGIN_DIR = plugin_dir_path( __FILE__ )   // → .../buddypress-activity-filter/
BP_ACTIVITY_FILTER_PLUGIN_URL = plugin_dir_url( __FILE__ )    // → https://.../buddypress-activity-filter/
```

Asset URLs used in `enqueue_assets()`:
- `BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/css/admin.css'` — file confirmed present
- `BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/js/admin.js'` — file confirmed present

View includes in `render_hub()` / `render_page()`:
- `BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/admin/views/hub.php'` — file confirmed present
- `BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/admin/views/shell.php'` — file confirmed present
- `BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/admin/views/' . $view . '.php'` — all 4 view files present

**Side-note:** `BP_Activity_Filter_Helper::get_plugin_dir()` (line 138) has a fallback `plugin_dir_path( __DIR__ )`. Since `__DIR__` in that file resolves to `.../buddypress-activity-filter/includes/`, the fallback would return `.../buddypress-activity-filter/includes/` — one directory *too deep*. However this fallback is only reached if `BP_ACTIVITY_FILTER_PLUGIN_DIR` is undefined, which is impossible during normal WordPress execution (the constant is set unconditionally at plugin load). **PASS with low-risk note** (see Finding #6).

---

## 5. Wrapper Structure — PASS

| File | Present? | Key contents |
|---|---|---|
| `includes/admin/views/shell.php` | YES | Page header, version pill, `<hr class="wp-header-end">`, sidebar nav from `get_tabs()`, body `include $view_path`, save bar when in settings group |
| `includes/admin/views/hub.php` | YES | WB Plugins hub landing card grid, auto-discovery from `$GLOBALS['submenu']['wbcomplugins']`, legacy wrapper slug filter |
| `includes/admin/views/settings-default.php` | YES | Default + profile dropdowns; sentinel for both keys |
| `includes/admin/views/settings-hidden.php` | YES | Checkbox list; sentinel for `bp_activity_filter_hidden` |
| `includes/admin/views/settings-cpt.php` | YES | Per-CPT toggle + label + global; sentinel for `bp_activity_filter_cpt_settings` |
| `includes/admin/views/faq.php` | YES | FAQ list; `group=resources` → no save bar rendered |

**Tab↔view map:** complete.  
- 4 tabs in `get_tabs()`: default → settings-default, hidden → settings-hidden, cpt → settings-cpt, faq → faq.  
- `hub.php` and `shell.php` are structural (not tabs) — correct.  
- No dead nav links, no orphan views.

**Legacy wrapper remnants:** none. No `admin/wbcom/`, no `[wbcom_admin_setting_header]` shortcode, no shared-admin handles. PASS.

---

## 6. Option Wiring — PASS

| Option | Registered | Rendered | Sanitized | Read back (frontend/CPT) | Sentinel emitted | Verdict |
|---|---|---|---|---|---|---|
| `bp_activity_filter_default` | YES (`register_setting`, OPTION_GROUP, `sanitize_default`) | YES (settings-default.php — `<select name="bp_activity_filter_default">`) | YES (`sanitize_default_filter` via legacy) | YES (frontend, Migration::get_option_with_fallback) | YES (settings-default.php line 27) | PASS |
| `bp_activity_filter_profile_default` | YES (`register_setting`, OPTION_GROUP, `sanitize_profile_default`) | YES (settings-default.php — `<select name="bp_activity_filter_profile_default">`) | YES (`sanitize_default_filter` via legacy) | YES (frontend, Migration::get_option_with_fallback) | YES (settings-default.php line 27) | PASS |
| `bp_activity_filter_hidden` | YES (`register_setting`, OPTION_GROUP, `sanitize_hidden`) | YES (settings-hidden.php — `name="bp_activity_filter_hidden[]"` checkboxes) | YES (`sanitize_hidden_activities` via legacy; core types protected) | YES (frontend: `get_option('bp_activity_filter_hidden')`) | YES (settings-hidden.php line 32) | PASS |
| `bp_activity_filter_cpt_settings` | YES (`register_setting`, OPTION_GROUP, `sanitize_cpt`) | YES (settings-cpt.php — `name="bp_activity_filter_cpt_settings[...][enabled/label]"` fields) | YES (`sanitize_cpt_settings` via legacy) | YES (CPT class: Migration::get_option_with_fallback) | YES (settings-cpt.php line 23) | PASS |

**Read-path consistency note:**
- `settings-default.php` reads both its options via `Migration::get_option_with_fallback()` — correct (handles legacy option migration).
- `settings-hidden.php` and `settings-cpt.php` read via plain `get_option()`. This is acceptable because those views only run after `admin_init` (where migration has already run), so legacy keys will already be migrated by the time these views render. No correctness risk.

**"All-unchecked" edge case:** The hidden-activities sentinel is always emitted (it is outside any conditional — line 32 of settings-hidden.php). So saving with zero boxes checked correctly sends the sentinel with an empty `bp_activity_filter_hidden[]` POST array, which the sanitizer receives as an empty array and persists as such. No silent-restore-to-stale-value bug.

---

## 7. Multi-tab Data-loss Guard (Playbook 7.1 Sentinel) — PASS

**Mechanism:** Each settings tab view emits hidden `bp_activity_filter_rendered_options[]` inputs listing only the option keys it owns. The `tab_rendered_option( $key )` helper in the controller reads this sentinel from `$_POST`; if the key is absent, the sanitize callback returns the currently stored value unchanged instead of coercing `null` to a default.

| Tab | Keys it declares | Keys it leaves to others | Correctly NOT declared in other tabs |
|---|---|---|---|
| settings-default | bp_activity_filter_default, bp_activity_filter_profile_default | hidden, cpt | YES |
| settings-hidden | bp_activity_filter_hidden | default, profile_default, cpt | YES |
| settings-cpt | bp_activity_filter_cpt_settings | default, profile_default, hidden | YES |
| faq | (none — group=resources, no form) | all | N/A |

No tab declares another tab's keys. Guard is complete and correct. **Data-loss risk: NONE.**

---

## 8. Dead bpActivityFilterAdmin AJAX Wiring — PASS (removed)

The old `bpActivityFilterAdmin` localize (nonce + ajaxUrl + currentTab on the `jquery` handle, class-bp-activity-filter-admin.php:944 in pre-3.2.1) is **gone**. The new `enqueue_assets()` localizes only `bpActivityFilterAdmin.i18n` strings onto the plugin's own `bp-activity-filter-admin` handle. No orphan AJAX localize remains.

---

## 9. Dead Assets — PASS (now active)

- `assets/js/admin.js` — previously unenqueued, now the card-panel's screen-scoped admin script. Confirmed enqueued via `wp_enqueue_script('bp-activity-filter-admin', ...)` in `enqueue_assets()`.
- `assets/css/admin.css` — previously unenqueued, now the card-panel stylesheet. Confirmed enqueued via `wp_enqueue_style('bp-activity-filter-admin', ...)`.

---

## 10. Hygiene Checks

### alert() / confirm() — PASS
Zero `alert(` or `confirm(` in any `.js` file. `admin.js` uses only DOM manipulation + `i18n` strings. The localized keys `confirmContinue` / `confirmCancel` are present in the localize call but are not consumed by any JS in the file (they appear reserved for future use — not a bug, no native dialog shown).

### Design tokens — PASS WITH MINOR NOTES

All layout, spacing, border-radius, shadow, and semantic color values use `var(--bpaf-admin-*)` tokens defined in `.bpaf-admin { }`. Token section runs lines 15–48 of admin.css.

**Minor: raw `#fff` in rule bodies** (lines 120, 166, 171, 774, 784). `--bpaf-admin-white: #ffffff` exists as a token but these five instances use `#fff` directly. All five occurrences are used for text-on-accent-background (e.g. version pill, brand icon, primary button) — functionally equivalent and unlikely to break dark-mode since the admin panel does not yet implement a dark-mode token layer. Low severity, cosmetic.

**Minor: three raw hex color values in rule bodies** — `#166534` (locked-row label), `#1e3a8a` (info-notice text), `#92400e` (warn-notice text). No corresponding tokens defined. Same severity as above.

**Minor: `margin-left` / `margin-right` instead of `margin-inline-*`** — three instances:
- `margin-left: var(--bpaf-admin-gap-lg)` (line 281, `.bpaf-settings-main`)
- `margin-left: 0` (line 956, inside `@media (max-width: 1024px)`)
- `margin-right: 0` (line 963, inside `@media (max-width: 640px)`)

These are not RTL-safe. In an RTL locale the sidebar would still be on the right and the main content's offset would be incorrect. Low severity for an admin-only plugin (WP admin RTL is a minority case) but should be converted to `margin-inline-start/end`.

### Tap targets — PASS
- `.bpaf-btn` has `min-height: 40px` (line 767).
- `.bpaf-activity-row__label-wrap` has `min-height: 40px` (line 449) — checkbox rows meet the 40px tap target.

### Screen-scoped enqueue — PASS
`enqueue_assets()` calls `get_current_screen()` and bails unless `is_our_screen()` returns true. Assets load only on the plugin page or the shared hub. No bleed to unrelated admin pages.

### Responsive — PASS (2 breakpoints, per skill Part 7.5)
Two `@media` blocks: `max-width: 1024px` (sidebar stacks) and `max-width: 640px` (form-table cells block, page header wraps). Matches the required 3-breakpoint discipline (desktop default + 1024 + 640/480).

### Inline `<style>` / `<script>` in views — PASS
Zero inline style or script blocks in any admin view file.

---

## 11. N+1 / Performance Note (Low)

In `settings-cpt.php`, `wp_count_posts( $bpaf_post_type )` is called inside the `foreach` loop over eligible post types (line 44). WordPress caches `wp_count_posts()` results in the object cache per post type, so this is one cache hit per type on the first load but not a true N+1 DB query pattern after the first request. Acceptable for an admin-only settings view.

---

## 12. Helper Fallback Path Note (Low)

`BP_Activity_Filter_Helper::get_plugin_dir()` (line 138) and `get_plugin_url()` (line 148) include fallbacks using `plugin_dir_path( __DIR__ )` / `plugin_dir_url( __DIR__ )`. Since `__DIR__` inside `includes/class-bp-activity-filter-helper.php` resolves to `.../buddypress-activity-filter/includes/`, the fallback would point one level too deep if the constants were missing. The constants are defined unconditionally on every load, so this path cannot trigger under normal WordPress operation. Risk: theoretical only. If constants are ever removed, the fallback is silently wrong.

---

## Severity-Ranked Findings

| # | Severity | Finding | File:line | Suggested fix |
|---|---|---|---|---|
| 1 | Low | `$legacy` assigned and `unset()` in `register_settings()` but never used between assignment and unset — dead code, WPCS unused-variable noise | `includes/admin/class-bp-activity-filter-admin-panel.php:179,218` | Remove both lines; the individual sanitize_* callbacks each call `$this->legacy_admin()` directly. |
| 2 | Low | 5× raw `#fff` in CSS rule bodies (not in token definition) — `--bpaf-admin-white` token exists | `assets/css/admin.css:120,166,171,774,784` | Replace with `var(--bpaf-admin-white)` to keep all non-token-section references token-driven. |
| 3 | Low | 3× raw hex values in rule bodies with no corresponding token: `#166534`, `#1e3a8a`, `#92400e` | `assets/css/admin.css:494,650,656` | Add named tokens (e.g. `--bpaf-admin-success-dark`, `--bpaf-admin-info-text`, `--bpaf-admin-warn-text`) and reference them. |
| 4 | Low | `margin-left` / `margin-right` (3 instances) — not RTL-safe; should be `margin-inline-start` / `margin-inline-end` | `assets/css/admin.css:281,956,963` | Convert to logical properties. |
| 5 | Low | `BP_Activity_Filter_Helper` fallback paths use `plugin_dir_path(__DIR__)` / `plugin_dir_url(__DIR__)` which would resolve to `.../includes/` not the plugin root if constants were absent | `includes/class-bp-activity-filter-helper.php:138,148` | Change fallback to `plugin_dir_path(dirname(__DIR__))` / `plugin_dir_url(dirname(__DIR__))`. |
| 6 | Info | `bpActivityFilterAdmin.i18n` keys `confirmContinue` + `confirmCancel` are localized but no JS consumer reads them — reserved but unused | `includes/admin/class-bp-activity-filter-admin-panel.php:387-388` | Either add a confirm-modal helper or remove the keys to reduce dead localize payload. |
| 7 | Info | Browser smoke pass not yet run (stated in BUILD-LOG.md — plugin was inactive during migration) | — | Activate plugin, render all 4 tabs, perform save round-trip, verify "Settings saved." banner placement, test at 390px viewport, check browser console + debug.log. |

---

## Overall Verdict: SHIP-CLEAN (pending browser smoke)

The INTERMEDIATE wrapper has been completely and correctly removed. All 7 deleted files are absent with zero dangling references. The new card-panel controller implements the full required contract. The menu is correctly under `wbcomplugins` with no `wbcom-designs` trace. All 4 options are registered, rendered, sanitized via the legacy sanitizers (preserving the option-save contract byte-for-byte), and read back via `Migration::get_option_with_fallback()` on the frontend/CPT side. The sentinel-guarded multi-tab merge is present and correct — no data-loss risk. The previously-dead `bpActivityFilterAdmin` AJAX localize and the dead `admin.js`/`admin.css` are both resolved: the old localize is gone; the assets are now the live card-panel scripts, properly enqueued and screen-scoped.

**Must-fix before release:** None (all blockers clear).  
**Should-fix (low):** Findings 1–5 above — cosmetic/polish level, no functional or data-loss risk.  
**Pending:** Finding 7 (browser smoke pass) should be completed before tagging a release.

