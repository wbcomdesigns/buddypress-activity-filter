# BuddyPress Activity Filter — QA Release Checklist

> **The final gate before tagging a release.** Every row must pass, no exceptions.
> This is the backend counterpart to `PRE_RELEASE_SMOKE.md` (frontend/browser).
> Together they guarantee: code quality + feature behaviour + safe packaging.

**Target time:** 30 minutes end-to-end (plus the browser smoke).

Rows marked **N/A** are permanently not applicable to this plugin — it has 0 REST routes, 0 AJAX handlers, 0 custom tables, 0 CPTs, 0 cron events, 0 blocks, and no Pro pair. Do not silently skip anything else.

---

## 0 — Branch hygiene

- [ ] On the named release branch (e.g. `4.0.0`), NOT on `master`
- [ ] `git status` clean — no uncommitted changes
- [ ] `git fetch` run BEFORE concluding anything is missing — a stale checkout has made committed work look dropped before
- [ ] Branch up to date with origin
- [ ] `master` merged in (or the branch is deliberately ahead) — no stale base
- [ ] No `.DS_Store`, `.idea/`, `.vscode/`, `node_modules/`, `vendor/` staged

```bash
cd "$PLUGIN_PATH"
git fetch --all --tags
git status
git log --oneline origin/master..HEAD
```

## 1 — Version triangulation

- [ ] `buddypress-activity-filter.php` header `Version:` equals the release version
- [ ] `define( 'BP_ACTIVITY_FILTER_VERSION', ... )` matches
- [ ] `readme.txt` `Stable tag:` matches
- [ ] `languages/bp-activity-filter.pot` `Project-Id-Version` matches
- [ ] `readme.txt` has a changelog entry for this version in the action-prefix format (`New` / `Improve` / `Fix` / `Security` / `Dev` / `Compat`), no emoji, no em-dashes
- [ ] `audit/manifest.summary.json` `version` and `branch` match
- **N/A** — no `package.json`; `composer.json` carries no `version` key by design

```bash
grep -rE "Version:|BP_ACTIVITY_FILTER_VERSION|Stable tag|Project-Id-Version" \
  buddypress-activity-filter.php readme.txt languages/bp-activity-filter.pot
```

## 2 — Static analysis

### PHP lint

```bash
find . -name "*.php" -not -path "./vendor/*" | xargs -n1 php -l | grep -v "No syntax errors"
```
- [ ] No output

### PHPStan

```bash
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
diff <(git show HEAD:phpstan-baseline.neon) phpstan-baseline.neon
```
- [ ] Level 5 clean
- [ ] Baseline has not grown this release (or the diff is explained in the commit body)

### WPCS

- [ ] 0 **errors** across the tree. Warnings are accepted only if they are the known set: unused method params, reserved-keyword param names (`$object`, `$default`), and the one `WP_DEBUG`-guarded `error_log()` in the migration class. A NEW warning is a finding.
- [ ] No new `// phpcs:ignore` without a comment saying why

## 3 — Tests

- **N/A** — no PHPUnit suite. CI's PHP-lint matrix (8.1 / 8.2 / 8.3 / 8.4) is the compatibility gate.
- **N/A** — no JS test suite.

## 4 — Security sweep

- **N/A** — no REST routes, no AJAX handlers. If this release adds one, this row stops being N/A and needs `permission_callback` / `check_ajax_referer` review.
- [ ] Settings save is gated by `manage_options` (filterable via `bp_activity_filter_admin_capability`) AND the Settings API nonce — confirm the filter is applied to the menu and the save together, not just the menu
- [ ] `sanitize_hidden_list()` still strips `activity_update` and `activity_comment` — these must stay unhideable even against a forged POST
- [ ] The cross-tab sentinel (`bp_activity_filter_rendered_options[]`) is intact in every settings view and every sanitize callback. Removing it silently wipes the other tabs' options on save
- [ ] Every echoed variable is escaped; translator functions are the `esc_*__` variants in output context
- **N/A** — no `$wpdb` calls outside `uninstall.php`; those are `prepare()`d and use `$wpdb->prefix`

## 5 — Translations (i18n)

**This is the section that has broken twice. Read it, do not skim it.**

- [ ] `Text Domain:` header is **`bp-activity-filter`** — the **WP.org slug**, not the GitHub repo name `buddypress-activity-filter`. WordPress builds the language-pack path from the domain and WordPress.org names the file from the slug; if they differ, no community translation can ever load, and no PO-level check can see it
- [ ] Verify against the live listing, not memory: `wordpress.org/plugins/bp-activity-filter/` and `translate.wordpress.org/projects/wp-plugins/bp-activity-filter/` both resolve
- [ ] `bash bin/i18n.sh` runs clean — regenerates the POT, syncs every locale, compiles `.mo` AND `.l10n.php` with fuzzy excluded, and asserts msgid parity + collisions
- [ ] `.l10n.php` regenerated alongside every `.mo` — WP 6.5+ prefers `.l10n.php`, so a stale one silently wins and the update appears to do nothing
- [ ] No fuzzy entries in the compiled artifacts — `wp i18n make-mo` ships them where GNU msgfmt drops them, which is how "Open settings" shipped as "Save settings" in all four locales
- [ ] No two msgids share one msgstr (`bin/check-po-collisions.py` + `bin/check-l10n-collisions.php`)
- [ ] No em-dashes inside any translator function
- [ ] Product names are not translatable — use `printf( '%s add-on', 'Name' )` so the brand never enters the POT
- [ ] Verified in a **non-English locale in the browser**, not in `wp eval` — JED resolution differs and English hides everything because source == translation

## 6 — Readme + docs

- [ ] `readme.txt` validates at https://wordpress.org/plugins/developers/readme-validator/
- [ ] `Requires at least`, `Tested up to`, `Requires PHP` current
- [ ] Upgrade notice written if behaviour changes
- [ ] Every hook listed under Advanced Configuration is actually fired — grep the source both ways, do not just filter the existing list
- [ ] Every capability the readme claims exists in the UI. Claims of features that were never built (bulk select, caching) are the recurring defect here
- [ ] `CLAUDE.md` is how-to, not a changelog — no "recent changes" table, no bug list
- [ ] `audit/manifest.json` refreshed and its counts re-verified against source. A structural rescan does NOT refresh the `static_analysis` findings block — re-check each finding by hand
- [ ] `docs/qa/AGENT_SMOKE_RUNBOOK.md` section D has a new row for every customer-visible fix in this release
- [ ] Live KB (docs.wbcomdesigns.com, category `bp-activity-filter`) has no article describing a removed feature

## 7 — Browser smoke gate

- [ ] `docs/qa/.last-smoke-pass.json` exists
- [ ] `release_version` equals this release
- [ ] `ran_at` within the last 24 hours
- [ ] `failures[]` empty
- [ ] `debug_log_issues[]` empty
- [ ] `manual_required[]` reviewed — Firefox / Safari iOS flows spot-checked by a human

If missing or stale, run `/wp-plugin-smoke free` from the plugin directory.

## 8 — Packaging dry-run

This repo has **no build script**, so the zip is whatever the working tree holds, filtered by `.distignore`. That makes this section load-bearing.

- [ ] Zip has **NO**: `.git/`, `.github/`, `bin/`, `vendor/`, `audit/`, `node_modules/`, `composer.json`, `composer.lock`, `gruntfile.js`, `phpstan.neon`, `phpstan-baseline.neon`, `*.md`, `.distignore`, `.DS_Store`
- [ ] Zip **HAS**: `buddypress-activity-filter.php`, `readme.txt`, `includes/`, `assets/`, `languages/` (`.pot` + every `.po`/`.mo`/`.l10n.php`), `uninstall.php`, `license.txt`, `screenshot-*.png`
- [ ] Zip extracts to a folder named exactly `bp-activity-filter/` — that is the WP.org slug and therefore the installed directory name
- [ ] `unzip -l` reviewed by eye before tagging. Watch case (`Gruntfile.js` vs `gruntfile.js`) and near-misses (`phpcs.xml.dist`); a first composer-era release once shipped 19MB / 2699 files because `vendor/` was not excluded
- [ ] Zip size within 2x the previous release

## 9 — Install-in-anger

On a **second clean** Local site:

- [ ] `wp plugin install /tmp/bp-activity-filter-<version>.zip --activate` succeeds
- [ ] No fatal, no debug.log entry on activation
- [ ] Settings screen returns HTTP 200 on the first request
- [ ] Activating with BuddyPress inactive shows the requirement notice and does not fatal
- **N/A** — no tables to verify

Verify with `wp eval`, not a raw HTTP fetch: Local's opcache revalidates on a 2s timer, so swap-file-then-curl returns the OLD build and a correct diagnosis looks wrong.

## 10 — Upgrade-in-anger

On a **third clean** site with the previous stable version + real data:

- [ ] Update via the WP admin flow succeeds, no fatal
- [ ] Saved default filter and hidden types both survive and are still enforced
- [ ] Pre-3.0 legacy option names still migrate
- [ ] Activities created by the removed CPT feature still render and are still moderatable
- [ ] No new debug.log entries during the upgrade request
- **N/A** — no cron events to re-register

## 11 — Release metadata

- [ ] Annotated tag `v<version>` created on the release-branch commit
- [ ] GitHub Release drafted, body in the same action-prefix format as `readme.txt`, title `BuddyPress Activity Filter X.Y.Z - one-line summary` (no em-dash, no emoji)
- [ ] Release zip attached
- [ ] If a pre-ship QA bounce arrives, rebuild on the SAME tag (force-push + `gh release upload --clobber`). Do not bump the version for a bounce that never reached customers
- [ ] The attached zip is byte-for-byte the tag. Diff the asset against the tag before announcing — packaging from the working directory has shipped unpushed work invisibly before

## 12 — Post-tag

- [ ] CI green on the tag (PHP lint matrix, PHPStan, WPCS, i18n integrity)
- [ ] Release branch merged back to `master`
- [ ] WP.org SVN trunk + tag updated under slug `bp-activity-filter` (after internal sign-off)

## 13 — Customer-facing publish

- [ ] Live KB updated for anything added, changed or removed this release
- [ ] Basecamp cards for this release moved to Ready for Testing with a comment naming what to test
- **Docs are GitHub-only.** Do not call any publish/sync/upload tool against docs.wbcomdesigns.com — KB edits are a separate, human-approved action

## 14 — Post-release monitor (first 24h)

- [ ] WP.org support forum for `bp-activity-filter` — no "broke after update" threads
- [ ] Zoho Desk / Crisp — no matching tickets
- [ ] Basecamp Bugs column — no new cards matching the release
- [ ] No report of an activity stream going empty or a dropdown losing options

If any post-release signal is red, open a patch cycle immediately.

---

## Failure protocol

If ANY row in sections 0–11 fails:

1. **Stop.** Do not tag or publish.
2. Fix on the release branch.
3. Re-run from Section 0 — a fix can regress earlier sections.
4. Only tag after the entire checklist is green in one continuous run.

## Version-specific additions

### 4.0.0

- [ ] Text domain is `bp-activity-filter` and a WP.org-style pack loads in the browser (this release reverted a change that had pointed it at the repo name)
- [ ] `composer.json` is tracked in git — a blanket `*.json` ignore swallowed it once, which meant CI could not `composer install` and the PHPStan gate could not run
- [ ] The CI workflow exists **on the release branch**, not only on `master`, and its jobs actually fail on error (no `continue-on-error` + `|| true` theatre)
- [ ] Removed-CPT promise holds: 4.0.0 stops generating those activities but does not delete existing ones
