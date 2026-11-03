=== BuddyPress Activity Filter ===
Contributors: wbcomdesigns, vapvarun
Tags: buddypress, activity-filter, filter, buddypress-activity, hide-activity
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: buddypress
Stable tag: 4.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide activity types from your BuddyPress activity streams and choose the default filter members see.

== Description ==

The **BuddyPress Activity Filter** plugin does two things, and does them completely:

1. Hides activity types you do not want in the stream.
2. Sets which filter is selected by default, site-wide and on member profiles.

Hidden types are removed from the stream itself, not just the filter dropdown. That means activities recorded before you hid a type disappear too, across every stream scope - site-wide, profiles, groups, friends, mentions and favourites - including streams loaded over AJAX. Hidden activities remain visible in wp-admin, so you can still moderate them.

### Key Features

- **Hide Unwanted Activities**: Remove specific activity types from every activity stream
- **Default Activity Filters**: Set different default filters for site-wide and profile activity streams
- **Complete Filtering**: Hidden types are excluded from the stream, the dropdown, and pagination counts
- **Still Moderatable**: Hidden activities stay visible on the wp-admin Activity screen
- **Core Types Protected**: Status updates and replies can never be hidden, so the stream is never empty
- **Theme Compatible**: Works with the BuddyPress Legacy and Nouveau template packs
- **Clean and Lightweight**: Four PHP classes, no custom tables, no cron, no AJAX handlers
- **Developer Friendly**: Hooks and filters for customization

### Perfect For

- Communities that want a focused stream, for example hiding "changed their profile photo" or "became a registered member" entries
- Sites where most members should land on one kind of activity, such as status updates, by default
- Administrators who want to tidy the stream without deleting anything

### Configuration Options

Settings live under **WB Plugins > BP Activity Filter**.

**Default Filters tab:**
- Site-wide activity default: the filter selected when members open the main activity directory
- Profile activity default: the filter selected on member profile activity pages

**Hidden Activities tab:**
- Tick the activity types to remove from every activity stream
- Status updates and replies are always available and cannot be hidden

### Documentation

Full guides for setup, settings and developer hooks: [BuddyPress Activity Filter documentation](https://docs.wbcomdesigns.com/bp-family/buddypress-activity-filter/).

### For Developers

- Seven documented hooks, each with an example in the [developer guide](https://docs.wbcomdesigns.com/bp-family/buddypress-activity-filter/)
- Settings saved with nonce and capability checks; the capability is filterable
- No custom tables, no cron jobs, no AJAX handlers and no caching layer
- Translation ready, with German, German (formal), French and Dutch included

== More from Wbcom Designs ==

Free and premium tools from the team behind BuddyPress Activity Filter:

* **[BuddyNext](https://buddynext.com/features/)** - A complete community platform for WordPress with feeds, groups, messaging and moderation built in.
* **[BuddyX](https://buddyxtheme.com/features/)** - A free community theme for BuddyPress with a clean activity stream layout and dark mode.
* **[MediaVerse](https://wpmediaverse.com/features/)** - Photo and video galleries, albums and media uploads for community members.
* **[Jetonomy](https://jetonomy.org/features/)** - Q&A forums and discussion spaces that sit alongside your activity stream.

== Installation ==

### Automatic Installation

1. Go to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "BuddyPress Activity Filter"
4. Click "Install Now" and then "Activate"
5. Go to WB Plugins > BP Activity Filter to configure

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/bp-activity-filter/`
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Go to WB Plugins > BP Activity Filter to configure your preferences

### Post-Installation Setup

1. **Configure Default Filters**: Set your preferred defaults for site-wide and profile activities
2. **Hide Unwanted Activities**: Select activity types to hide from the stream
3. **Test Configuration**: Visit your activity stream to verify settings are working

== Frequently Asked Questions ==

= What is the default activity filter? =

By default, "Everything" is shown in the activity feed. You can change this to any specific activity type like "Status Updates", "New Blog Posts", etc. The plugin allows different defaults for site-wide and profile activity streams.

= Can I hide specific activity types completely? =

Yes! Use the "Hidden Activities" tab to select which activity types should never appear in the activity stream. This completely removes them from the feed and dropdown options.

= Does hiding a type remove activities that already exist? =

They stop appearing in the stream, but nothing is deleted. Hiding works by excluding the type from the activity query, so older activities of that type disappear from the feed immediately and come back if you unhide the type. They also stay visible on the wp-admin Activity screen so you can still moderate them.

= Will this work with my theme? =

It works with both BuddyPress template packs, Legacy and Nouveau. Hidden types are removed from the filter dropdown on Nouveau based themes such as BuddyX, BuddyX Pro and Reign as well as on Legacy themes. If a theme replaces the activity stream with its own markup, test hiding one type before relying on it.

= Does this affect existing activities? =

No, the plugin only affects the display and filtering of activities. Existing activities remain unchanged in the database. The plugin works by modifying queries and display logic, not by deleting data.

= What happened to the Custom Post Types tab? =

It was removed in 4.0.0. The feature recorded every custom post type under the built-in `new_blog_post` activity type, which meant those activities could not be filtered or hidden separately - the opposite of what this plugin is for - and it never cleaned up activities when the underlying post was deleted. Rather than leave a half-working feature in place, it was removed so the plugin does one job completely. To put custom post types into the activity stream, use the BuddyPress Blogs component or a dedicated plugin.

= Is this compatible with BuddyBoss? =

No, BuddyBoss has similar built-in features, so this plugin is not compatible and will display a notice if BuddyBoss is detected. BuddyBoss users should use the native activity filtering features.

= How do I reset to default settings? =

Set both defaults back to "Everything" on the Default Filters tab and untick every type on the Hidden Activities tab, then save. Deactivating and reactivating does not reset anything: activation only adds settings that are missing.

= Can I filter activities programmatically? =

Yes. The plugin fires seven filters and actions, listed with examples in the [developer guide](https://docs.wbcomdesigns.com/bp-family/buddypress-activity-filter/). `bp_activity_filter_default` is the one to use for changing the default filter per context.

= What happens during plugin updates? =

The plugin includes automatic migration tools that preserve your settings during updates. Major version updates may include additional migration steps, which are handled automatically.

= Does this plugin affect performance? =

Filtering happens server-side inside the query BuddyPress already runs, so the plugin adds no extra database queries to the activity stream. It performs no caching of its own and stores its settings in four options.

== Screenshots ==

1. The activity filter on the front end: "Updates" is the default, and hidden types such as New Members and Friendships are gone from the list.
2. Default Filters tab: choose what the site-wide and profile activity streams show first.
3. Hidden Activities tab: tick the types to remove from every stream. Status updates and replies always stay visible.

== Changelog ==

= 4.0.0 - November 2026 =

Everything released since 3.2.0. The plugin is narrowed to what it does completely: hiding activity types and setting the default filter. The Custom Post Types feature has been removed, and the settings screen has been rebuilt.

* New      - Discover tab in the settings panel linking to nine free Wbcom Designs community tools.
* Improve  - Removed the Custom Post Types tab and its activity generation. It recorded every custom post type under the built-in "new blog post" activity type, so those activities could not be hidden or filtered separately, and it never removed the activity when the post was deleted. Existing activities it created are left untouched and can be hidden or moderated as normal.
* Improve  - Rebuilt the settings screen on the modern WB Plugins card panel with sidebar navigation, token-based styling, and a mobile-friendly layout, moved under the shared WB Plugins menu.
* Improve  - Retired the legacy Wbcom shared-dashboard admin wrapper and its separate Wbcom Designs menu.
* Fix      - The Default Filter setting now actually filters the activity stream. It was passing an argument BuddyPress does not read, so the stream stayed unfiltered.
* Fix      - Changing the default activity filter now takes effect for existing visitors. The old default was being written into each visitor's saved preference, which then overrode any later change by the site owner.
* Fix      - A default filter that BuddyPress does not list for that screen is now added to the filter dropdown, so the control shows the filter the stream is actually using instead of falling back to "Everything".
* Fix      - The site-wide default filter is no longer applied to group activity streams. Setting a default could empty a group's stream while its filter dropdown still read "Everything".
* Fix      - An activity type you have hidden can no longer be chosen as a default filter. Choosing one emptied the stream for every visitor, with the filter dropdown still reading "Everything" and nothing explaining why. If a type is already set as a default when you hide it, it stays selected and is marked as hidden, with a warning telling you how to resolve it, rather than the setting being changed for you.
* Fix      - Setting the default filter to "New friendships" no longer leaves the filter dropdown blank. BuddyPress lists friendships under a combined key, which the dropdown sync did not match.
* Fix      - Hidden activity types are now excluded from the activity stream itself, not only from the filter dropdown. Activities recorded before a type was hidden are no longer listed, across the directory, member, group, friends, mentions and favorites streams. They remain visible in the WordPress admin Activity screen so they can still be moderated.
* Fix      - Hidden activity types are now removed from the activity filter dropdown on Nouveau based themes such as BuddyX and Reign, not only on Legacy.
* Fix      - Hiding "New friendships" now removes the friendships option from the filter dropdown, which BuddyPress renders under a combined key.
* Fix      - Unreviewed machine-matched translations are no longer shipped. The compiled translation files included every fuzzy entry, so all four locales showed "Open settings" as "Save settings" and French showed the plugin name in place of two headings. Affected strings now fall back to English until a translator confirms them.
* Fix      - Removed translations that had been carried onto the wrong string. French showed the plugin name in place of the "Default Activity Filters" and "Hidden Activity Types" headings, and German in place of "Default Activity Filters".
* Fix      - Rebuilt the German formal translation, which had been unmaintained since 2020 with most strings untranslated.
* Fix      - Regenerated the translation template, which still declared version 3.1.0.
* Fix      - Deactivating or deleting the plugin no longer empties the whole site object cache, which on sites with Redis or Memcached forced every cached page and query to rebuild.
* Fix      - Deleting the plugin now removes all of its data. Four activity meta keys and two post meta keys were left behind on every uninstall.
* Fix      - The settings screen no longer scrolls sideways on phones. Table cells and the sidebar overflowed the card at 390px.
* Fix      - Activity rows on the Hidden Activities tab no longer squeeze their label into a narrow column on phones.
* Fix      - The settings screen no longer hides other plugins' admin notices, including WordPress update and Site Health warnings.
* Fix      - Corrected the documented developer hooks. Four filters listed in the readme were never fired by the plugin, so code written against them silently did nothing.
* Fix      - Removed a readme claim of bulk select and deselect controls on the Hidden Activities tab, which the screen has never had.
* Fix      - Corrected the readme claim that activity actions are cached. The plugin performs no caching.
* Fix      - Corrected WordPress Coding Standards compliance across all PHP files.
* Fix      - Added direct file access protection and output escaping throughout.
* Fix      - Resolved Plugin Check compatibility warnings.
* Dev      - The admin capability is now filterable through bp_activity_filter_admin_capability, applied to both the menu and the settings save.
* Dev      - Added a translation check that fails the build when two different strings share one translation, in the source files and again in the compiled files, which is the fault behind the label errors above.
* Dev      - Removed roughly 900 lines of code left unreachable by the Custom Post Types removal, including the whole post type eligibility and conflict detection subsystem.
* Dev      - Removed dead code: an unused legacy JavaScript filter method, four unused helper methods, and the redundant admin class whose sanitizers now live in the settings panel.
* Dev      - Corrected the @since tags throughout, which all read 4.0.0 while the plugin was at 3.2.1.
* Dev      - Raised the minimum PHP version to 8.1.
* Compat   - Tested up to WordPress 7.1.

= 3.2.0 =
* **Major Fix**: Hidden activity types are now properly prevented from being created
* **Improved Performance**: Default filters now work server-side for faster page loads
* **Better UI**: Fixed dropdown filter resetting issue on page reload
* **Cleaner Options**: Removed duplicate friendship options and non-existent activity types
* **CPT Enhancement**: Elementor templates are now properly excluded from activity generation
* **Bug Fixes**: Resolved database serialization issues and duplicate text in activity messages
* **Developer**: Added debug mode and improved activity prevention mechanisms

= 3.1.0 =
* New: Introduced a redesigned backend UI for better usability.
* New: Added vertical layout support for hidden activities with core protection.
* New: Implemented custom wrapper structure for improved layout and organization.
* New: Added condition checks for BuddyPress compatibility.
* Enhancement: Cleaned up and optimized shared folders and unused code.
* Enhancement: Updated asset loading for improved performance.
* Enhancement: Improved frontend filter styling and selection UI.
* Enhancement: Updated frontend wrapper code and applied CSS improvements.
* Enhancement: Refined frontend JS to prevent conflicts with admin default filter settings.
* Enhancement: Filter enhancements to prevent duplicate or previously registered activities.
* Developer: Introduced `BP_Activity_Filter_Migration` for smoother transitions.
* Developer: Improved structure through modular wrapper additions and CSS.
* Fix: Resolved UI inconsistencies with the new wrapper layout.
* Fix: Removed debug logs and cleaned up dev artifacts.

= 3.0.1 =
* **Fixed**: Warning related to page parameter in activity query
* **Fixed**: Pagination issue for activity streams where "Load More" button was not functioning correctly
* **Improvement**: Added check to ensure $page is a string before processing

= 3.0.0 =
* **Fixed**: PHP warning issue
* **Fixed**: Issue in filtering activities
* **Fixed**: Activity filter applied correctly when viewing "just-me" or "sitewide" activities
* **Fixed**: Bypass default activity filter on profile other tabs
* **Improved**: Cookie deletion when saving admin options
* **Added**: Check to prevent setting default activity filter on single activity views

= 2.9.0 =
* **Enhancement**: Ensured lowercase post type names when no new label is provided
* **Fix**: Corrected typos and updated readme for clarity
* **Code Compliance**: Removed deprecated filters and modernized PHP code
* **Security**: Replaced deprecated functions with modern alternatives
* **Optimization**: Improved data sanitization and validation

== Upgrade Notice ==

= 3.2.0 =
Important bug fixes and performance improvements. This version fixes critical issues with activity filtering and prevention. Server-side filtering improves performance and reliability. Backup recommended before upgrading.

= 4.0.0 =
The Custom Post Types tab is removed, so custom post types no longer post to the activity stream. Activities it already created stay. Default Filters and Hidden Activities settings are unchanged. Requires PHP 8.1.

== Support ==

For support, documentation, and feature requests:

- **Documentation**: [Plugin Documentation](https://docs.wbcomdesigns.com/bp-family/buddypress-activity-filter/)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/bp-activity-filter/)
- **Premium Support**: [Wbcom Designs Support](https://wbcomdesigns.com/support/)
- **GitHub**: [Development Repository](https://github.com/wbcomdesigns/buddypress-activity-filter)

== Contributing ==

We welcome contributions! Please see our [GitHub repository](https://github.com/wbcomdesigns/buddypress-activity-filter) for development guidelines and to submit pull requests.

**Ways to Contribute:**
* Report bugs and suggest features
* Submit translations
* Contribute code improvements
* Help with documentation
* Test beta releases

== Privacy Policy ==

This plugin does not collect or store any personal user data beyond what WordPress and BuddyPress already collect. A member's filter choice is remembered only in their own browser (a cookie and BuddyPress's session storage); the plugin writes no personal data to the database.

== Credits ==

Developed by [Wbcom Designs](https://wbcomdesigns.com/) - Your trusted WordPress development partner.

Special thanks to the BuddyPress community for feedback and contributions that made this plugin possible.
