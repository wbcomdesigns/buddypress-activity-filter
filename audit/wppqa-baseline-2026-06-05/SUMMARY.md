# wppqa Baseline — BuddyPress Activity Filter 3.2.1

Date: 2026-06-05 · Branch: `3.2.1` · Run as part of onboarding Phase 0.

## Per-check results

| Check | Passed | Failed | Skipped | Verdict |
|---|---|---|---|---|
| `wppqa_check_plugin_dev_rules` | 9 | 0 | 0 | PASS — no issues |
| `wppqa_check_rest_js_contract` | 0 | 0 | 1 | SKIPPED — plugin registers zero REST routes |
| `wppqa_check_wiring_completeness` | 0 | 0 | 1 | SKIPPED — no `templates/` dir; settings read by service-layer PHP, not templates |

`wppqa_audit_plugin` equivalent: **failed = 0** → no release-blocking findings from the automated bug-finder.

## Top findings (manual, beyond wppqa heuristics)

1. **Dead AJAX wiring (low).** `class-bp-activity-filter-admin.php:944` localizes `bpActivityFilterAdmin` (`nonce`, `ajaxUrl`, `currentTab`) on the `jquery` handle, but the plugin registers **no `wp_ajax_*` handler** and no JS consumer. `assets/js/admin.js` (checkbox state + form validation only) is not even enqueued. Settings save is a plain form POST. The localized nonce/ajaxUrl is dead code.
2. **Unenqueued assets (low).** `assets/js/admin.js` and `assets/css/admin.css` exist but are never enqueued by current code — only the shared-admin assets and inline styles are wired. Dead files or a regression from a refactor.
3. **INTERMEDIATE admin wrapper (informational).** Admin UI runs through `includes/shared-admin/` (Wbcom shared-dashboard + nav-tab + form-table + heavy inline styles). Not the modern `/wp-plugin-development` Part 6 card/token pattern — flagged as a future migration target, not a bug.

## False-positive pre-triage note (per task)

`wppqa_check_plugin_dev_rules` is known to false-positive `nonce-no-cap` on `nopriv` AJAX actions. **N/A here** — this plugin has zero AJAX actions (`nopriv` or otherwise), so no such false positives were emitted. The 9/9 pass is genuine.
