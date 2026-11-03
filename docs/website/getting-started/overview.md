# Overview

BuddyPress Activity Filter gives site administrators control over what appears in the BuddyPress activity stream. It sets the filter that loads by default, hides activity types you do not want members to see, and can turn published custom post types into activity entries.

The plugin works with the BuddyPress activity component. Filtering is applied server-side, before activities are queried, so the correct set of activities loads on the first page view and on "Load More" requests without relying on client-side scripts to re-query the feed.

## What the plugin does

- **Sets a default filter** for the site-wide activity directory and, separately, for individual member profile activity streams.
- **Hides selected activity types** so they no longer appear in the stream, in the filter dropdown, or get created in the first place.
- **Publishes custom post types as activities**, creating an activity entry when a post of an enabled type is published.

## Where it runs

- **Admin settings** live under the Wbcom Designs menu at **Wbcom Designs > BP Activity Filter** (`admin.php?page=wbcom-activity-filter`). The screen is organised into tabs: Default Filters, Hidden Activities, Custom Post Types, and FAQ.
- **Frontend behaviour** applies to the BuddyPress activity directory and member profile activity pages.

## What it does not change

The plugin affects how activities are displayed, filtered, and created going forward. It does not delete or rewrite existing activities in the database. Turning a custom post type on generates activities only for posts published after the setting is enabled; existing posts are not backfilled.

## Compatibility notes

- Built for BuddyPress. The plugin checks for BuddyPress on load and shows an admin notice if it is missing.
- Not compatible with BuddyBoss. BuddyBoss ships equivalent built-in filtering, so the plugin detects it and displays a notice rather than running alongside it.
