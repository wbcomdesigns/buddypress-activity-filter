# BuddyPress Activity Filter — Pre-Release Smoke

> **Human walkthrough.** A QA person runs this in a real browser before any tag.
> The agent-executable version of the same walk is `AGENT_SMOKE_RUNBOOK.md`;
> the backend gate is `QA_RELEASE_CHECKLIST.md`.

**Target time:** 45 minutes. This plugin is small — two features — so the walk is short and there is no excuse for skipping it.

## Setup

| | |
|---|---|
| Site | https://wbcom-free.local |
| Settings screen | `/wp-admin/admin.php?page=wbcom-activity-filter` (WB Plugins > Activity Filter) |
| Admin login | append `?autologin=1` to any URL |
| Member login | append `?autologin=<user_login>` |
| Requires | BuddyPress active, >= 12.0.0 |

Before you start:

1. Enable `WP_DEBUG` and `WP_DEBUG_LOG`, set `WP_DEBUG_DISPLAY` to false.
2. Note the current size of `wp-content/debug.log`. Re-check it after each section. A new warning is a failure, not a curiosity.
3. Reset the plugin's options so you start from a known state:
   ```bash
   wp option delete bp_activity_filter_default bp_activity_filter_profile_default bp_activity_filter_hidden
   ```
   Leave `bp_activity_filter_db_version` alone.

**Do not clear cookies or site storage to make a step pass.** Members arrive carrying a `bp-activity-filter` cookie holding their last filter, and BuddyX replays it over AJAX. That dirty state is the state the plugin has to survive; clearing it has hidden a real bug through three bounces. Run the whole matrix in one dirty session.

---

## 1. Admin — the settings screen renders (10 min)

Open the settings screen as admin and walk all four tabs: **Default Filters**, **Hidden Activities**, **FAQ**, **Discover**.

- [ ] Every tab loads. No PHP notice, warning or fatal on screen or in debug.log
- [ ] No JS errors in the browser console on any tab
- [ ] The sidebar marks the tab you are on
- [ ] Repeat every tab at 390px: no sideways scrolling, nothing clipped, the save bar is reachable
- [ ] Discover tab: all nine plugin links open something real
- [ ] Other plugins' admin notices, the WordPress update nag and Site Health warnings all still show on this screen

## 2. Admin — settings actually save (10 min)

This is where the nastiest bug in this plugin's history lives, so do it in this exact order.

- [ ] Set a **site-wide default filter**. Save. Reload. The value is still there
- [ ] Set a **profile default filter**. Save. Reload. Still there
- [ ] Hide **two activity types**. Save. Reload. Both still hidden
- [ ] Now go back to **Default Filters** and save that tab alone. Return to **Hidden Activities** — **your two hidden types must still be hidden.** If they were cleared, stop and report it. Saving one tab must never wipe another's settings
- [ ] Do the reverse: save **Hidden Activities** alone, confirm the two defaults survived
- [ ] Confirm `activity_update` and `activity_comment` cannot be hidden — they should be shown as protected, with a reason

## 3. Front end — the default filter works (10 min)

As a **member** (not admin), with the cookie from any earlier visit still present:

- [ ] Open the activity directory. The stream is already filtered to the site-wide default
- [ ] The dropdown shows that same filter as selected. A filtered stream sitting under a dropdown that reads "Everything" is a failure
- [ ] Open a member profile activity tab. The profile default applies there and is independent of the site-wide one
- [ ] Set the profile default to "inherit" (`-1`). The site-wide value now applies on profiles too
- [ ] Set the default to **New friendships**. The dropdown must select it, not go blank
- [ ] Now change the site-wide default as admin, and revisit as that same member **without clearing anything**. The new default applies
- [ ] Open a **group's** activity tab. The site-wide default must NOT be applied there — the group stream stays unfiltered and its dropdown agrees

## 4. Front end — hidden types are really hidden (10 min)

Pick an activity type that already has activities in the stream, then hide it.

- [ ] It is gone from the **activity directory**
- [ ] Gone from a **member profile** stream
- [ ] Gone from a **group** stream
- [ ] Gone from **friends**, **mentions** and **favourites**
- [ ] Activities recorded **before** you hid the type are gone too, not just new ones
- [ ] It is **absent from the filter dropdown**
- [ ] Repeat the dropdown check on a **Nouveau** theme (BuddyX or Reign), not only Legacy — they behave differently
- [ ] Hide **New friendships** specifically and confirm the option disappears from the dropdown
- [ ] Confirm the hidden activities are **still visible in wp-admin > Activity**, so they can still be moderated. Hiding is a display rule, not a delete

## 5. Translations (5 min)

- [ ] Switch the site language to French. Open the settings screen. French text renders
- [ ] Check the site language and the **test user's own profile language** match what you think you are testing. BuddyPress renders over admin-ajax, so `determine_locale()` returns the **user's** language — an English profile on a French site reads exactly like a broken text domain. Translate one control string first so you can tell the two apart
- [ ] Nothing shows an obviously wrong label (a heading rendering as the plugin name, "Open settings" reading as "Save settings"). Those are fuzzy-translation leaks

Do this in the browser. `wp eval` resolves translations differently and will tell you it works when it doesn't.

## 6. Close out

- [ ] Diff `wp-content/debug.log` against your starting size. Zero new fatals and zero new warnings
- [ ] Deactivate the plugin: no fatal, no error
- [ ] Reactivate: settings still intact

## Reporting

Anything that fails: screenshot it, note the URL, the viewport, the theme, and whether the cause looks like ours or something else's, and file it on Basecamp project `37595485` (Bugs column). Do not stop the walk at the first failure — collect them all in one pass.

Every customer-visible fix that comes out of this walk earns a permanent row in Section D of `AGENT_SMOKE_RUNBOOK.md`, in the same PR as the fix.
