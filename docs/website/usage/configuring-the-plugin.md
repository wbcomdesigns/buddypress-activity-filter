# Configuring the Plugin

All settings live under **Wbcom Designs > BP Activity Filter** (`admin.php?page=wbcom-activity-filter`). The screen has four tabs. Each configuration tab has its own **Save Settings** button that saves only that tab.

Saving requires the `manage_options` capability and passes a nonce check.

## Default Filters tab

Set the activity type that loads by default.

1. **Site-wide Activity Default** - the default for the main activity directory. "Everything" is the out-of-the-box value.
2. **Profile Activity Default** - the default for member profile activity streams. "Everything" is the out-of-the-box value.
3. Click **Save Settings**.

A member who later picks a filter from the dropdown keeps their own choice; the default applies to visitors who have not chosen one. See [Default Filters](../features/default-filters.md).

## Hidden Activities tab

Choose which activity types to remove from the community.

1. Review the **Core Activities** group at the top. Status updates and replies are always visible and cannot be hidden.
2. In **Other Activity Types**, tick the box next to any type you want to hide. Ticked (red) means hidden; unticked (green) means visible.
3. Click **Save Settings**.

Hidden types are removed from the filter dropdown and, where possible, prevented from being created. See [Hidden Activities](../features/hidden-activities.md).

## Custom Post Types tab

Turn published custom post types into activity entries.

1. For each listed post type, tick **enabled** to generate an activity when a post of that type is published.
2. Optionally set a **Custom Activity Label** to control the wording in the activity sentence. Leave it empty to use the post type's own singular name.
3. Optionally tick the global option to **hide custom post type activities from the site-wide stream** while still showing them on author profiles.
4. Click **Save Settings**.

If no eligible post types exist, the tab explains why. If some post types are excluded, an admin notice lists them with the reason. See [Custom Post Type Activities](../features/custom-post-type-activities.md).

## FAQ tab

The FAQ tab provides in-dashboard answers to common questions and links to support and documentation. It has no settings to save. A wider FAQ is in [Frequently Asked Questions](../faq.md).
