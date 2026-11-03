# Hidden Activities

**Included in Free**

Remove activity types from your community without deleting anything.

## Hide a type

1. Go to **WB Plugins > BP Activity Filter > Hidden Activities**.
2. Tick each type to hide.
3. Click **Save Settings**.

## What hiding does

- Removes the type from the site-wide, profile, group, friends, mentions and favorites streams.
- Removes it from the filter dropdown, including on Nouveau themes such as BuddyX and Reign.
- Applies to AJAX loads such as "Load More".
- Includes activities recorded before you hid the type.
- Leaves them visible in **Activity** in wp-admin, so you can still moderate them.
- Deletes nothing. Untick the type and its activities return.

## Types that cannot be hidden

"Posted a status update" and "Replied to a status update" are always visible, so the stream is never empty. They are listed at the top of the tab as "Always visible".

## Hidden types and defaults

A hidden type cannot be chosen as a default filter. See [Default Filters](default-filters.md).

## Activities from the removed Custom Post Types feature

Version 4.0.0 removed that feature, but activities it already created remain. They were recorded under the built-in "New blog post" type, so hide that type to hide them.
