# Default Filters

**Included in Free**

Set which filter is selected the first time a visitor opens an activity stream. Members can still change it with the dropdown.

## Settings

Go to **WB Plugins > BP Activity Filter > Default Filters**.

| Setting | Default | Applies to |
| --- | --- | --- |
| Site-wide stream default | Everything | The main site-wide activity stream |
| Profile stream default | Everything | Each member's profile activity stream |

The profile list leaves out "New Member" and "Updated Profile", which make no sense on a profile.

## What to know

- The site-wide default is not applied to group activity streams.
- If you choose a filter that BuddyPress does not list for that screen, the plugin adds it to the dropdown so the control matches the stream.
- Hidden types are not offered as defaults. See [Hidden Activities](hidden-activities.md).
- A member's own filter choice is remembered in their browser (a cookie or BuddyPress session storage) and wins over your default on later visits. Changing your default affects visitors who have not picked a filter.

## Change the default in code

Use the `bp_activity_filter_default` filter. See [Hooks and Filters](../developer-guide/hooks-and-filters.md).
