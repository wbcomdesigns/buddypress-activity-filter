=== BuddyPress Activity Filter ===
Contributors: wbcomdesigns, vapvarun
Tags: buddypress, activity-filter, filter, buddypress-activity, hide-activity, default-activity, custom-post-type-activity
Donate link: https://wbcomdesigns.com/donate/
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 4.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily manage your BuddyPress Activity Stream by filtering specific activity types, setting default filters, and enabling public Custom Post Types (CPT) activities.

== Description ==

The **BuddyPress Activity Filter** plugin helps site administrators customize the activity feed by setting default activity types and hiding irrelevant content. It also allows you to include activities from Custom Post Types (CPT) in the BuddyPress activity stream.

### ✨ Key Features

- **Default Activity Filters**: Set different default filters for site-wide and profile-specific activity streams
- **Hide Unwanted Activities**: Remove specific activity types from appearing in the activity feed
- **Custom Post Type Support**: Enable activity generation for custom post types when published
- **Clean & Lightweight**: Optimized code with minimal performance impact
- **Theme Compatible**: Works with BuddyPress default theme and Nouveau theme package
- **Easy Administration**: Simple settings interface with intuitive controls

### 🎯 Perfect For

- Community sites wanting to streamline their activity feeds
- Sites with custom post types that need activity integration
- Administrators who want granular control over activity visibility
- Communities looking to improve user experience with focused content

### 🔧 Configuration Options

**Default Filters Tab:**
- Site-wide Activity Default: Set the default filter for main activity streams
- Profile Activity Default: Set the default filter for user profile activity pages

**Hidden Activities Tab:**
- Select specific activity types to hide from all activity streams
- Professional activity labels for better clarity

**Custom Post Types Tab:**
- Enable activity generation for any public custom post type
- Customize activity labels for each post type
- Automatic activity creation when CPT posts are published

### 🌟 Premium Extensions

Enhance your BuddyPress community with these premium add-ons:

- **[BuddyPress Hashtags](https://wbcomdesigns.com/downloads/buddypress-hashtags/)** - Add hashtag functionality to activities
- **[BuddyPress Polls](https://wbcomdesigns.com/downloads/buddypress-polls/)** - Create and participate in polls
- **[BuddyPress Quotes](https://wbcomdesigns.com/downloads/buddypress-quotes/)** - Share quotes with beautiful backgrounds
- **[BuddyPress Status & Reactions](https://wbcomdesigns.com/downloads/buddypress-status/)** - Custom statuses and emoji reactions
- **[BuddyPress Sticky Post](https://wbcomdesigns.com/downloads/buddypress-sticky-post/)** - Pin important activities
- **[WP Stories](https://wbcomdesigns.com/downloads/wp-stories/)** - Add Instagram-like stories feature

### 💡 Use Cases

1. **Corporate Communities**: Hide member registration activities, focus on business updates
2. **Educational Sites**: Highlight course activities, hide profile updates
3. **E-commerce Communities**: Show product activities, hide friendship notifications
4. **News Sites**: Display article publications as activities automatically

### 🛠️ Developer Friendly

- Clean, documented code following WordPress standards
- Multiple hooks and filters for customization
- Modular architecture for easy extension
- Compatible with popular BuddyPress themes

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/buddypress-activity-filter/` or install directly through WordPress admin
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Settings > Activity Filter to configure your preferences
4. Set your default filters and hidden activities as needed
5. Enable custom post types if desired

== Frequently Asked Questions ==

= What is the default activity filter? =

By default, "Everything" is shown in the activity feed. You can change this to any specific activity type like "Status Updates", "New Blog Posts", etc.

= Can I hide specific activity types completely? =

Yes! Use the "Hidden Activities" tab to select which activity types should never appear in the activity stream.

= How do Custom Post Type activities appear? =

When you enable a custom post type, publishing a new post of that type will automatically create an activity entry showing the author, post type, and post title with a link.

= Will this work with my theme? =

Yes, the plugin is compatible with BuddyPress default themes and the Nouveau theme package. It also works with most third-party BuddyPress themes.

= Does this affect existing activities? =

No, the plugin only affects the display and filtering of activities. Existing activities remain unchanged in the database.

= Can I customize the activity text for custom post types? =

Yes, when enabling a custom post type, you can specify a custom label that will be used in the activity text instead of the default post type name.

= Is this compatible with BuddyBoss? =

No, BuddyBoss has similar built-in features, so this plugin is not compatible and will display a notice if BuddyBoss is detected.

= How do I reset to default settings? =

Simply deactivate and reactivate the plugin, or set all filters back to "Everything" and uncheck all hidden activities.

== Screenshots ==

1. **Default Filters Settings** - Configure default activity filters for site-wide and profile streams
2. **Hidden Activities Management** - Select which activity types to hide from the feed
3. **Custom Post Type Integration** - Enable activity generation for custom post types
4. **Frontend Activity Filter** - Clean activity filter dropdown on the frontend

== Changelog ==

= 4.0.0 =
* **Major Update**: Complete plugin rewrite with modern WordPress standards
* **New**: Modular class-based architecture for better maintainability
* **New**: Enhanced admin interface with tabbed navigation
* **New**: Improved frontend JavaScript with better AJAX handling
* **New**: Professional activity labels for better user experience
* **New**: Better theme compatibility and responsive design
* **New**: Comprehensive helper functions and utilities
* **Improved**: Performance optimization with reduced database queries
* **Improved**: Security enhancements with proper nonce validation
* **Improved**: Code documentation and WordPress coding standards compliance
* **Fixed**: Various PHP warnings and compatibility issues
* **Fixed**: Cookie handling for default filters
* **Fixed**: Activity filtering edge cases

= 3.0.1 =
* Fixed: Warning related to page parameter in activity query
* Fixed: Pagination issue for activity streams where "Load More" button was not functioning correctly
* Improvement: Added check to ensure $page is a string before processing

= 3.0.0 =
* Fixed: PHP warning issue
* Fixed: Issue in filtering activities
* Fixed: Activity filter applied correctly when viewing "just-me" or "sitewide" activities
* Fixed: Bypass default activity filter on profile other tabs
* Improved: Cookie deletion when saving admin options
* Added: Check to prevent setting default activity filter on single activity views

= 2.9.0 =
* Enhancement: Ensured lowercase post type names when no new label is provided
* Fix: Corrected typos and updated readme for clarity
* Code Compliance: Removed deprecated filters and modernized PHP code
* Security: Replaced deprecated functions with modern alternatives
* Optimization: Improved data sanitization and validation

== Upgrade Notice ==

= 4.0.0 =
This is a major update with significant improvements. Please backup your site before updating. The plugin has been completely rewritten for better performance and maintainability while maintaining all existing functionality.

== Support ==

For support, documentation, and feature requests, please visit:

- **Documentation**: [Plugin Documentation](https://docs.wbcomdesigns.com/doc_category/buddypress-activity-filter/)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/bp-activity-filter/)
- **Premium Support**: [Wbcom Designs Support](https://wbcomdesigns.com/support/)

== Contributing ==

We welcome contributions! Please see our [GitHub repository](https://github.com/wbcomdesigns/buddypress-activity-filter) for development guidelines and to submit pull requests.