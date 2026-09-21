# Frequently Asked Questions

### What is the default activity filter?

By default the site-wide and profile defaults are both set to "Everything", so no filtering is applied until you choose a specific type. You can set independent defaults for the site-wide activity directory and for member profile activity streams on the Default Filters tab.

### Can I hide specific activity types completely?

Yes. On the Hidden Activities tab, tick the types you want to hide. Hidden types are removed from the filter dropdown and, where the plugin can intercept them, prevented from being created. Status updates and replies are core types and cannot be hidden.

### Does hiding a type delete existing activities?

No. Hiding stops a type from appearing in the dropdown and stops new activities of that type being created. Activities already in the database are not removed.

### How do custom post type activities appear?

When you enable a custom post type and then publish a post of that type, an activity is created reading "[Author] published a new [label]: [Post Title]", with the author and title linked. You can set a custom label per type.

### Will custom post type activities be created for posts I published earlier?

No. Only posts published after the type is enabled generate activities. Existing posts are not backfilled.

### Why is a custom post type missing from the Custom Post Types tab?

The tab lists public custom post types that have an admin UI and support a title. Built-in posts and pages are not listed. Some types are excluded automatically to avoid conflicts, such as WordPress internal types, Elementor template types, types that block post creation, and known types from BP Member Reviews and bbPress when those plugins are active. Excluded types are listed on the settings screen with the reason.

### Can I keep custom post type activities off the main stream?

Yes. Enable the global option to hide custom post type activities from the site-wide stream. Those activities then appear only on the author's profile activity stream.

### Do members keep their own filter choice?

Yes. When a member selects a filter from the dropdown, the choice is stored in a browser cookie and takes precedence over the admin default on their next visit.

### Is this compatible with BuddyBoss?

No. BuddyBoss has equivalent built-in filtering. The plugin detects BuddyBoss and shows a notice instead of running its features. Use the native BuddyBoss activity filtering.

### Which themes does it work with?

The dropdown handling supports the BuddyPress Nouveau theme (array-based filter output) and legacy theme templates (HTML string filter output).

### How do I reset to defaults?

Change a setting back to "Everything" for the defaults, or untick hidden and custom post type options. Because settings are stored as WordPress options, deactivating and reactivating does not by itself wipe them; a full uninstall removes plugin data unless you preserve it with the `bp_activity_filter_preserve_data_on_uninstall` filter.

### Can I customise filtering in code?

Yes. See [Hooks and Filters](developer-guide/hooks-and-filters.md) for the available actions and filters, including `bp_activity_filter_default` and the custom post type activity filters.
