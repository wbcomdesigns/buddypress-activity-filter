# Overview

**Included in Free**

BuddyPress Activity Filter does two things. It hides the activity types you do not want members to see, and it sets which filter is selected by default on the site-wide and profile activity streams.

## What the plugin does

- **Hides activity types** from every activity stream, from the filter dropdown, and from activity streams loaded over AJAX. This includes activities recorded before you hid the type. Nothing is deleted, and hidden activities stay visible in **Activity** in wp-admin so you can still moderate them.
- **Sets the default filter** for the site-wide activity stream and, separately, for member profile streams. The site-wide default is not applied to group streams.

Status updates and replies can never be hidden, so a stream is never empty.

## Where to find it

Open **WB Plugins > BP Activity Filter** in wp-admin. The screen has four tabs: Default Filters, Hidden Activities, FAQ and Discover.

## What it does not change

The plugin never deletes or rewrites activities. Unhide a type and its activities return to the stream.

## Custom post type activities were removed

Version 4.0.0 removed the Custom Post Types tab and the activities it generated. Activities it already created are not deleted. They stay in the stream, and you can hide them or moderate them like any other activity. Your Default Filters and Hidden Activities settings are unchanged.

## Compatibility

- Works with the BuddyPress Legacy and Nouveau template packs.
- BuddyBoss is not supported. The plugin shows a notice and does not run.
