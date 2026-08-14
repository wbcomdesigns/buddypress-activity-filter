# Agent Smoke Runbook - BuddyPress Activity Filter

**Audience:** a browser-capable agent (Claude Sonnet or equivalent) with Playwright MCP + WP-CLI Bash access, OR a human QA person with the same access. Both should be able to execute every step of this runbook.

## How to read this runbook

Each C and E step describes a **customer contract**: what the feature promises, why it matters, the surfaces it touches, and what "working" looks like in customer terms. It does NOT prescribe the exact Playwright calls or selectors. Read the relevant plugin code, pick the right mechanism, and verify the contract. This freedom is the point: the verifier is expected to notice bugs we did not pre-imagine.

D (regression guards) stays specific - those are repros of past incidents; the exact fixture IS the contract.

## What this plugin is (read before walking)

Two features, nothing else:

1. **Hide activity types** so they never appear in the stream or in the filter dropdown.
2. **Set the default filter** members see, site-wide and on member profiles.

There are **0 REST routes, 0 AJAX handlers, 0 custom tables, 0 CPTs, 0 cron events, 0 blocks, 0 shortcodes**. Any runbook section that assumes one of those is marked N/A below and must be reported as `skipped`, not invented. The canonical inventory is `audit/manifest.json`.

## Global preconditions

- Working directory: `/Users/varundubey/Local Sites/wbcom-free/app/public/wp-content/plugins/buddypress-activity-filter`
- Site URL: `https://wbcom-free.local`
- WP-CLI: `wp --path="/Users/varundubey/Local Sites/wbcom-free/app/public" <cmd>`
- Admin auto-login: `?autologin=1` on any front-end URL
- Per-user auto-login: `?autologin=<user_login>`
- Playwright: one Chromium session throughout; restart with `browser_close` + `browser_navigate` if it dies.
- Plugin version constant: `BP_ACTIVITY_FILTER_VERSION`
- Settings screen: `/wp-admin/admin.php?page=wbcom-activity-filter`
- Requires BuddyPress active (>= 12.0.0). Pair plugin: none (standalone free plugin).

**Read this before deciding a filter bug is real.** BuddyX and other Nouveau themes load member streams via AJAX POST and replay `$_POST['filter']`, and the plugin stores each member's last filter in a `bp-activity-filter` cookie. A stale cookie looks exactly like "the setting does nothing". Do **not** clear cookies or storage to make a step pass - a real member arrives with dirty state, so that is the state the fix has to survive. Reproduce in one dirty session and note the stale-filter interaction explicitly.

## Output contract

At the end of the walk, write exactly one JSON file to `docs/qa/.last-smoke-pass.json`:

```json
{
  "mode": "free",
  "release_version": "<from BP_ACTIVITY_FILTER_VERSION>",
  "ran_at": "<ISO 8601 UTC>",
  "sections": {
    "A_fresh_install":     { "pass": 0, "fail": 0, "skipped": 0 },
    "B_upgrade":           { "pass": 0, "fail": 0, "skipped": 0 },
    "C_core_flows":        { "pass": 0, "fail": 0, "skipped": 0 },
    "D_regression_guards": { "pass": 0, "fail": 0, "skipped": 0 },
    "E_extensions":        { "pass": 0, "fail": 0, "skipped": 0 },
    "F_cross_browser":     { "pass": 0, "fail": 0, "skipped": 0 }
  },
  "failures": [
    { "id": "...", "origin": "from|for", "triage_note": "...", "expected": "...", "actual": "...", "url": "...", "screenshot": "..." }
  ],
  "debug_log_issues": [
    { "section": "...", "level": "fatal|warning|notice|deprecated", "line": "...", "file": "..." }
  ],
  "manual_required": []
}
```

Emit a Basecamp draft per failure on project id `37595485` (Bugs column `7416152192`).

## Fixture cleanup (before every walk)

```bash
wp --path="/Users/varundubey/Local Sites/wbcom-free/app/public" eval '
delete_option( "bp_activity_filter_default" );
delete_option( "bp_activity_filter_profile_default" );
delete_option( "bp_activity_filter_hidden" );
echo "fixtures cleaned\n";
'
```

Do NOT delete `bp_activity_filter_db_version` - removing it re-runs the migration path, which is Section B's job, not a precondition for A.

## Debug log protocol

Enable `WP_DEBUG` + `WP_DEBUG_LOG` + `WP_DEBUG_DISPLAY=false` before Section A. Baseline the `wp-content/debug.log` byte count. After every section, diff new lines into `debug_log_issues[]` classified by level. Any new fatal or warning is a failure unless explicitly whitelisted.

---

## A - Fresh install

### A.admin.activation
**What to verify:** activating on a site with BuddyPress active produces no fatal, no debug.log entry, and the settings screen is reachable on the first request.
**Acceptance:** `WB Plugins > Activity Filter` submenu exists; `/wp-admin/admin.php?page=wbcom-activity-filter` returns HTTP 200 and renders the card panel.

### A.admin.activation-without-buddypress
**What to verify:** activating with BuddyPress inactive does not fatal. The plugin gates its own init on BuddyPress presence and version.
**Acceptance:** admin loads, an admin notice explains the requirement, no PHP error.

### A.admin.defaults
**What to verify:** with no options stored, the screen renders its documented defaults (`bp_activity_filter_default` = `0` / Everything, `bp_activity_filter_profile_default` = `-1` / inherit) and the stream is unfiltered.

### A.n-a.schema
**N/A - report as skipped.** The plugin creates no custom tables. `bp_activity_filter_db_version` is a plain option, checked in Section B.

---

## B - Upgrade from previous version

### B.admin.upgrade-from-3.2.0
**What to verify:** installing 3.2.0, saving a default filter and a hidden type, then upgrading to this build leaves both settings intact and still enforced on the stream.
**Why it matters:** 3.x sites are the entire installed base (400 active installs on WP.org).

### B.admin.legacy-option-migration
**What to verify:** a site carrying only the pre-3.0 option names (`bp-default-filter-name`, `bp-default-profile-filter-name`, `bp-hidden-filters-name`) has them read correctly through `BP_Activity_Filter_Migration::get_option_with_fallback()` and migrated on `admin_init` without losing values.

### B.member.cpt-activities-survive
**What to verify:** activities the removed CPT feature created in 3.x are still listed and still moderatable after upgrading to 4.0.0. 4.0.0 stops generating them; it must not delete them.
**Why it matters:** this is the explicit promise in the 4.0.0 upgrade notice.

---

## C - Core customer flows

Persona ladder: Anonymous > Member > Admin. There is no moderator surface in this plugin. Exercise desktop 1280px and mobile 390px where the UI differs.

Each step is a contract, not a script. Verify the UI as a user would AND confirm the server-side effect to rule out a "looks right, didn't actually save" bug.

### C.anon.stream
**What to verify:** a logged-out visitor sees the activity directory with hidden types absent from both the stream and the filter dropdown, and with the site-wide default filter applied.

### C.member.default-filter-sitewide
**What to verify:** with a site-wide default set, a logged-in member landing on the activity directory sees the stream already filtered to that type AND the dropdown showing that type as selected. Stream and control must agree - a filtered stream under a dropdown reading "Everything" is a failure, not a cosmetic difference.

### C.member.default-filter-profile
**What to verify:** the profile default applies on member activity streams and is independent of the site-wide default. `-1` means "inherit the site-wide value".

### C.member.default-change-propagates
**What to verify:** after the admin changes the default, a member who has ALREADY visited the site (so carries a `bp-activity-filter` cookie) picks up the new default. Test with the dirty cookie present - do not clear it.

### C.member.hidden-types-absent
**What to verify:** a hidden activity type is absent from the stream on every scope: directory, member profile, group, friends, mentions, favourites. Activities recorded BEFORE the type was hidden must also be absent.

### C.member.hidden-types-not-in-dropdown
**What to verify:** hidden types do not appear as options in the activity filter dropdown, on both Legacy and Nouveau templates.

### C.member.mobile
**What to verify:** the stream and its filter dropdown are usable at 390px; no horizontal overflow.

### C.admin.settings-render
**What to verify:** every tab (Default Filters, Hidden Activities, FAQ, Discover) renders without PHP Notice/Warning/Fatal and without JS console errors.

### C.admin.settings-save-roundtrip
**What to verify:** each tab saves and the value survives a reload. **Hard contract:** saving one tab must NOT wipe the options owned by the other tabs. Save Default, reload, confirm Hidden is unchanged; then save Hidden and confirm Default is unchanged. See D.cross-tab-wipe.

### C.admin.core-types-protected
**What to verify:** `activity_update` and `activity_comment` cannot be hidden - the sanitizer strips them even if the request is forged.

### C.admin.capability
**What to verify:** a non-admin (Editor, Subscriber) cannot reach the settings screen and cannot save it by posting directly. The `bp_activity_filter_admin_capability` filter changes both the menu and the save gate together.

### C.admin.notices-not-suppressed
**What to verify:** other plugins' admin notices, WordPress update nags and Site Health warnings still render on this plugin's screen.

### C.n-a.rest-ajax-cron
**N/A - report as skipped.** 0 REST routes, 0 AJAX handlers, 0 cron events by design. If any appear, that is itself a failure - the manifest is the contract.

---

## CP - Presentation & product completeness (Tier 1 - the gate that decides RFT)

> Per the portfolio QA catalog: presentation and functional flow are PRIMARY; static code checks never substitute for this section. Every step runs in a real browser.

### CP.theme-fit
**What to verify:** the settings screen and the front-end dropdown across BuddyX, Reign and a default block theme (TT4/TT5), light and dark, desktop and 390px. Computed `font-family` inherits the theme; colours resolve through the token chain; unselected controls stay neutral; no raw hex bypassing tokens.
**Watch for:** `.buddypress .buddypress-wrap button` (specificity 0,2,1) restyles every plugin button flat/white and BuddyPress's sheet loads later - plugin rules must EXCEED that specificity, not tie it.

### CP.click-everything
**What to verify:** every tab, button, link and setting on the settings screen leads somewhere real. The Discover tab's nine plugin links must resolve. A tab that can never be populated, a link that 404s, or a setting whose effect is unreachable = FAIL.

### CP.states
**What to verify:** the Hidden Activities tab with zero hideable types shows a real empty state with guidance, not a bare sentence or a blank table.

### CP.entry-points
**What to verify:** both features are reachable from the front end (member sees the effect) and wp-admin (owner configures it). There is no REST surface by design - confirm the manifest documents that exception rather than treating it as a gap.

### CP.console-and-assets
**What to verify:** zero JS console errors on every page visited during this walk. The plugin's admin CSS/JS must NOT be enqueued on an unrelated admin page (network-tab probe on Dashboard and Posts).

### CP.i18n-loads
**What to verify:** switch the site to `fr_FR` and confirm the bundled translation actually renders on the settings screen.
**Why it matters:** WordPress builds the language-pack path from the **text domain**, WordPress.org names the file from the **slug**. Both must be `bp-activity-filter`. Test in the browser, never in `wp eval` - JED resolution differs. See D.text-domain-slug.

### CP.n-a.blocks
**N/A - report as skipped.** The plugin ships no blocks, shortcodes, widgets or template overrides.

---

## D - Known-regression guards

Each row is a repro of a past bug that caused customer pain. D rows stay specific - the fixture IS the contract.

| ID | Bug | Fixture + assertion |
|----|-----|---------------------|
| D.text-domain-slug | Text domain was changed to the GitHub repo name, which is not the WP.org slug. WordPress would have looked for `buddypress-activity-filter-fr_FR` while WP.org ships `bp-activity-filter-fr_FR`, so no community translation could load. | Assert the `Text Domain:` header is `bp-activity-filter`, `languages/bp-activity-filter.pot` exists, and no PHP file contains `'buddypress-activity-filter'` as a gettext domain. Then load the settings screen under `fr_FR` and confirm French renders. |
| D.default-filter-ignored | The default filter was passed to BuddyPress as `type`, which `bp_has_activities()` does not read, so the stream stayed unfiltered while the setting looked saved. | Set a site-wide default, load the activity directory as a member, assert the rendered stream contains only that activity type. Pass must be on `action`, not `type`. |
| D.default-pinned-into-cookie | The admin default was written into each visitor's saved preference, so a later change by the site owner never reached existing members. | Visit as a member (creates the cookie), change the admin default, revisit WITHOUT clearing the cookie, assert the new default applies. |
| D.default-missing-from-dropdown | A default that BuddyPress does not list for that screen left the dropdown reading "Everything" over a filtered stream. | Set a default BP does not list for the directory; assert the dropdown shows that filter as selected, not "Everything". |
| D.default-leaks-into-groups | The site-wide default was applied to group activity streams, emptying them while their dropdown still read "Everything". | Set a site-wide default, open a group's activity tab, assert the group stream is unfiltered. |
| D.friendships-combined-key | Setting the default to "New friendships" left the dropdown blank; BuddyPress lists friendships under the combined key `friendship_accepted,friendship_created`. | Set default to friendships, assert the dropdown selects the combined option. Match option keys on their comma-separated parts. |
| D.hidden-only-in-dropdown | Hidden types were removed from the dropdown but still appeared in the stream, including activities recorded before the type was hidden. | Record an activity of type X, hide X, assert it is gone from directory, member, group, friends, mentions and favourites streams - and still visible in wp-admin > Activity for moderation. |
| D.hidden-not-hidden-on-nouveau | Hidden types were removed from the dropdown on Legacy only; BuddyX/Reign still listed them. | Repeat the dropdown assertion on a Nouveau theme. The fix must filter `bp_get_activity_show_filters_options`, NOT `bp_get_activity_show_filters` - Nouveau discards edits to the latter's HTML. |
| D.hidden-friendships-still-listed | Hiding "New friendships" did not remove the option, again because of the combined key. | Hide friendships, assert the combined option is absent from the dropdown. |
| D.hidden-type-as-default | A type could be set as the default filter while also being hidden. The hide rule then stripped it from the query, so every visitor got an empty stream under a dropdown reading "Everything", with nothing explaining why. | Hide type X. Open Default Filters: X must NOT be offered in either select. Then set the default to Y, hide Y, and reopen: Y stays selected, is labelled "(hidden)", and a warning names the conflict. The stored value must never be silently rewritten. |
| D.cross-tab-wipe | All options share one option group, so the Settings API passed `null` for keys the submitting tab did not render and saving one tab wiped the others. | Set Default AND Hidden. Save the Hidden tab alone. Assert Default is unchanged. Then save Default alone and assert Hidden is unchanged. The hidden `bp_activity_filter_rendered_options[]` sentinel is what makes this pass - if it is removed, this row fails. |
| D.fuzzy-translations-shipped | `wp i18n make-mo` includes fuzzy entries where GNU msgfmt drops them, so all four locales served "Open settings" as "Save settings" and French served the plugin name as two headings. | Assert no compiled `.l10n.php` serves one msgstr for two msgids (`bin/check-l10n-collisions.php`), and that fuzzy entries fall back to English on screen. |
| D.stale-l10n-php | WP 6.5+ prefers `languages/*.l10n.php` over `.mo`, so rebuilding only the `.mo` left a stale file that silently won. | After any i18n change, assert `.mo` and `.l10n.php` were both regenerated and agree. |
| D.uninstall-leaves-meta | Deleting the plugin left four activity meta keys and two post meta keys behind. | Seed those keys, delete the plugin, assert all six are gone. |
| D.notices-suppressed | The settings screen hid other plugins' admin notices, including WordPress update and Site Health warnings. | Trigger a core update nag, open the settings screen, assert the nag renders. |
| D.mobile-overflow | The settings screen scrolled sideways at 390px - table cells and the sidebar overflowed the card. | Load every tab at 390px, assert `document.body.scrollWidth <= window.innerWidth`. |

Rule: every customer-visible fix adds a D row in the same PR. After 2 clean releases, a D row graduates into C.

---

## E - Extensions / addons / premium features

**N/A - report as skipped.** Standalone free plugin, no Pro pair, no addons.

---

## F - Cross-browser, RTL, accessibility

### F.chromium
Already covered by Sections A-CP.

### F.firefox-desktop and F.safari-ios
Chromium-only MCP cannot walk these. Populate `manual_required[]` with the filter-dropdown flow on both, since it is the only interactive front-end surface.

### F.rtl
**What to verify:** on an RTL locale the settings screen renders right-to-left without overflow at both viewports; spacing uses logical properties, not `margin-left/right`.

### F.a11y
**What to verify:** visible focus rings on every control; logical tab order; icon-only buttons carry `aria-label`; the Hidden Activities list is semantic markup, not styled divs.

---

## G - Post-release monitoring (first 24h after tag)

Watch the WP.org support forum for `bp-activity-filter`, new debug.log entries on the test host, and any report of a stream going empty or a dropdown losing options after update.

---

## Failure protocol

1. Screenshot on every failure: `browser_take_screenshot({ filename: "fail-<id>.png" })`.
2. **Triage: from vs for our plugin.**
   - `from` = our code is at fault.
   - `for` = failure surfaces while our plugin runs but root cause is elsewhere (theme / other plugin / stale cookie / legacy data).
3. Record in `failures[]` with `{ id, origin, triage_note, expected, actual, url, screenshot }`.
4. Never halt. Collect all failures in one pass.
5. Emit a Basecamp draft per failure with the origin line populated.

Triage is Sonnet's job; fix-or-document is the calling session's job.

## Step ID format

`<Section>.<persona>.<feature>` e.g. `C.member.default-filter-sitewide`. D rows: `D.<descriptor>`.
