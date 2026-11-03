# Configuring the Plugin

**Included in Free**

All settings live at **WB Plugins > BP Activity Filter** (`admin.php?page=wbcom-activity-filter`). By default only administrators (the `manage_options` capability) can see and save them. A developer can change this with the `bp_activity_filter_admin_capability` filter.

## Tabs

| Tab | What it does |
| --- | --- |
| Default Filters | Choose the filter selected by default on the site-wide and profile streams. |
| Hidden Activities | Choose activity types to remove from every stream. |
| FAQ | Short answers inside wp-admin. |
| Discover | Links to other free Wbcom Designs tools. It has no settings. |

## Set up the plugin

1. Open **Hidden Activities** and tick the types to hide.
2. Click **Save Settings**.
3. Open **Default Filters** and pick a default for each stream.
4. Click **Save Settings**.
5. Visit your activity page and check the dropdown and the stream.

Each tab saves on its own. Saving one tab does not change the other.

## Order matters

A type you hide cannot be chosen as a default, because the stream would be empty. If a default is already set to a type you then hide, it stays selected, is marked "(hidden)" and shows a warning. Pick a visible type to fix it.

See [Default Filters](../features/default-filters.md) and [Hidden Activities](../features/hidden-activities.md).
