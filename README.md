# BuddyPress Activity Filter

Hide the activity types you do not want in BuddyPress streams and choose the filter members see first.

## What it does

- Hides activity types from every activity stream, the filter dropdown and AJAX-loaded streams, including activities recorded before you hid the type. Nothing is deleted, and hidden items stay visible in Activity in wp-admin for moderation.
- Sets the default filter for the site-wide activity stream and, separately, for member profile streams.
- The default only sets the starting view of the plain stream. Searches, hashtag views and tabs added by other plugins show every matching item, and a filter a member picks is remembered and wins over your default.
- Works with the BuddyPress Legacy and Nouveau template packs. Status updates and replies can never be hidden.

## Who it is for

Site owners running BuddyPress who want a calmer activity stream, for example without "New member" or "Updated profile" noise, and a sensible first view for members.

## Get the plugin

- WordPress.org: https://wordpress.org/plugins/bp-activity-filter/
- Store: https://wbcomdesigns.com/downloads/buddypress-activity-filter/

## Documentation

- Docs: https://docs.wbcomdesigns.com/bp-family/buddypress-activity-filter/
- Release notes: https://wbcomdesigns.com/release-notes/buddypress-activity-filter/
- Source of the customer docs: [`docs/website/`](docs/website/)

## Requirements

- WordPress 6.9 or higher
- PHP 8.1 or higher
- BuddyPress 12.0.0 or higher with the Activity component active
- BuddyBoss is not supported: the plugin shows a notice and does not run

## Installation

1. Install and activate BuddyPress.
2. Install BuddyPress Activity Filter from Plugins > Add New, or upload the zip and activate it.
3. Open WB Plugins > BP Activity Filter to set the default filters and hidden types.

## For developers

- Text domain: `bp-activity-filter` (the WordPress.org slug). Classes are prefixed `BP_Activity_Filter_`, with no PHP namespace.
- Action `bp_activity_filter_init`; filters `bp_activity_filter_default`, `bp_activity_filter_default_scopes`, `bp_activity_filter_activity_actions`, `bp_activity_filter_admin_tabs`, `bp_activity_filter_admin_capability`, `bp_activity_filter_preserve_data_on_uninstall`. See the [hooks reference](docs/website/developer-guide/hooks-and-filters.md).
- Options: `bp_activity_filter_default`, `bp_activity_filter_profile_default`, `bp_activity_filter_hidden`, `bp_activity_filter_db_version`.
- No REST routes, AJAX handlers, WP-CLI commands, shortcodes, blocks or custom tables.

## Support and licence

- Support: https://wbcomdesigns.com/support/
- Licence: GPL-2.0-or-later
