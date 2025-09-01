=== BuddyPress Activity Filter ===
Contributors: wbcomdesigns, vapvarun
Tags: buddypress, activity-filter, filter, buddypress-activity, hide-activity, default-activity, custom-post-type-activity
Donate link: https://wbcomdesigns.com/donate/
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 8.0
Stable tag: 3.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily manage your BuddyPress Activity Stream by filtering specific activity types, setting default filters, and enabling public Custom Post Types (CPT) activities.

== Description ==

The **BuddyPress Activity Filter** plugin helps site administrators customize the activity feed by setting default activity types and hiding irrelevant content. It also allows you to include activities from Custom Post Types (CPT) in the BuddyPress activity stream.

### Key Features

- **Default Activity Filters**: Set different default filters for site-wide and profile-specific activity streams
- **Hide Unwanted Activities**: Remove specific activity types from appearing in the activity feed
- **Custom Post Type Support**: Enable activity generation for custom post types when published
- **Clean & Lightweight**: Optimized code with minimal performance impact
- **Theme Compatible**: Works with BuddyPress default theme and Nouveau theme package
- **Easy Administration**: Simple settings interface with intuitive controls
- **Security Hardened**: Built with WordPress security best practices
- **Performance Optimized**: Efficient caching and minimal database queries
- **Developer Friendly**: Extensive hooks and filters for customization

### Perfect For

- Community sites wanting to streamline their activity feeds
- Sites with custom post types that need activity integration
- Administrators who want granular control over activity visibility
- Communities looking to improve user experience with focused content
- Developers needing customizable activity filtering solutions

### Configuration Options

**Default Filters Tab:**
- Site-wide Activity Default: Set the default filter for main activity streams
- Profile Activity Default: Set the default filter for user profile activity pages

**Hidden Activities Tab:**
- Select specific activity types to hide from all activity streams
- Professional activity labels for better clarity
- Bulk select/deselect options for efficient management

**Custom Post Types Tab:**
- Enable activity generation for any public custom post type
- Customize activity labels for each post type
- Automatic activity creation when CPT posts are published
- Global settings for CPT activity visibility

### Premium Extensions

Enhance your BuddyPress community with these premium add-ons:

- **[BuddyPress Hashtags](https://wbcomdesigns.com/downloads/buddypress-hashtags/)** - Add hashtag functionality to activities
- **[BuddyPress Polls](https://wbcomdesigns.com/downloads/buddypress-polls/)** - Create and participate in polls
- **[BuddyPress Quotes](https://wbcomdesigns.com/downloads/buddypress-quotes/)** - Share quotes with beautiful backgrounds
- **[BuddyPress Status & Reactions](https://wbcomdesigns.com/downloads/buddypress-status/)** - Custom statuses and emoji reactions
- **[BuddyPress Sticky Post](https://wbcomdesigns.com/downloads/buddypress-sticky-post/)** - Pin important activities
- **[WP Stories](https://wbcomdesigns.com/downloads/wp-stories/)** - Add Instagram-like stories feature

### Use Cases

1. **Corporate Communities**: Hide member registration activities, focus on business updates
2. **Educational Sites**: Highlight course activities, hide profile updates
3. **E-commerce Communities**: Show product activities, hide friendship notifications
4. **News Sites**: Display article publications as activities automatically
5. **Developer Communities**: Filter technical discussions by post type

### Developer Features

- **Clean Architecture**: Modern OOP design with singleton patterns
- **Extensive Hooks**: Over 15 action and filter hooks for customization
- **Backward Compatibility**: Automatic migration from older versions
- **Performance Optimized**: Smart caching and minimal database impact
- **Security First**: Nonce verification, input sanitization, and capability checks
- **Theme Agnostic**: Works with any BuddyPress-compatible theme
- **Documentation**: Comprehensive inline documentation and code comments

### Security & Performance

- **Input Sanitization**: All user inputs are properly sanitized and validated
- **Nonce Protection**: CSRF protection on all admin forms and AJAX requests
- **Capability Checks**: Proper permission verification for all admin functions
- **SQL Injection Prevention**: Use of WordPress database abstraction layer
- **XSS Protection**: Output escaping and content filtering
- **Performance Caching**: Intelligent caching of frequently accessed data

### Internationalization

- **Translation Ready**: Full support for translation and localization
- **RTL Support**: Right-to-left language compatibility
- **Professional Labels**: User-friendly activity type descriptions
- **Context-Aware Strings**: Proper string contexts for accurate translations

== Installation ==

### Automatic Installation

1. Go to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "BuddyPress Activity Filter"
4. Click "Install Now" and then "Activate"
5. Go to Settings > Activity Filter to configure

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/buddypress-activity-filter/`
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Navigate to Settings > Activity Filter to configure your preferences

### Post-Installation Setup

1. **Configure Default Filters**: Set your preferred defaults for site-wide and profile activities
2. **Hide Unwanted Activities**: Select activity types to hide from the stream
3. **Enable Custom Post Types**: Choose which custom post types should generate activities
4. **Test Configuration**: Visit your activity stream to verify settings are working

== Frequently Asked Questions ==

= What is the default activity filter? =

By default, "Everything" is shown in the activity feed. You can change this to any specific activity type like "Status Updates", "New Blog Posts", etc. The plugin allows different defaults for site-wide and profile activity streams.

= Can I hide specific activity types completely? =

Yes! Use the "Hidden Activities" tab to select which activity types should never appear in the activity stream. This completely removes them from the feed and dropdown options.

= How do Custom Post Type activities appear? =

When you enable a custom post type, publishing a new post of that type will automatically create an activity entry showing the author, post type, and post title with a link. You can customize the activity label for each post type.

= Will this work with my theme? =

Yes, the plugin is compatible with BuddyPress default themes and the Nouveau theme package. It also works with most third-party BuddyPress themes including Youzify, Kleo, and other popular community themes.

= Does this affect existing activities? =

No, the plugin only affects the display and filtering of activities. Existing activities remain unchanged in the database. The plugin works by modifying queries and display logic, not by deleting data.

= Can I customize the activity text for custom post types? =

Yes, when enabling a custom post type, you can specify a custom label that will be used in the activity text instead of the default post type name. This allows for more user-friendly activity descriptions.

= Is this compatible with BuddyBoss? =

No, BuddyBoss has similar built-in features, so this plugin is not compatible and will display a notice if BuddyBoss is detected. BuddyBoss users should use the native activity filtering features.

= How do I reset to default settings? =

You can reset individual settings by changing them back to their defaults, or deactivate and reactivate the plugin to restore all default values. The plugin also includes migration tools for upgrading from older versions.

= Can I filter activities programmatically? =

Yes! The plugin provides numerous hooks and filters for developers. See the documentation for `bp_activity_filter_default`, `bp_activity_filter_available_filters`, and other developer hooks.

= What happens during plugin updates? =

The plugin includes automatic migration tools that preserve your settings during updates. Major version updates may include additional migration steps, which are handled automatically.

= Does this plugin affect performance? =

The plugin is optimized for performance with smart caching, minimal database queries, and efficient code. It adds negligible overhead to your site while providing significant functionality.

== Screenshots ==

1. **Default Filters Settings** - Configure default activity filters for site-wide and profile streams with professional interface
2. **Hidden Activities Management** - Select which activity types to hide from the feed with bulk selection tools
3. **Custom Post Type Integration** - Enable activity generation for custom post types with preview functionality
4. **Frontend Activity Filter** - Clean activity filter dropdown on the frontend with theme compatibility
5. **Migration Notice** - Automatic migration system for seamless upgrades from older versions
6. **Admin Dashboard** - Professional admin interface with tabbed navigation and contextual help

== Changelog ==

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
**Major Update - Please Backup Before Upgrading**

This is a significant update with complete code rewrite for better performance, security, and maintainability. All existing functionality is preserved and enhanced. The plugin includes automatic migration tools to preserve your settings, but we recommend backing up your site before updating.

**What's New:**
* Modern OOP architecture with better performance
* Enhanced admin interface with tabbed navigation
* Improved security with comprehensive input validation
* Better theme compatibility and mobile responsiveness
* Advanced CPT integration with preview functionality
* Smart migration system for seamless upgrades

**After Updating:**
* Review your settings in the new admin interface
* Test activity filtering functionality
* Check any custom code for compatibility
* Clear any caching plugins if needed

== Advanced Configuration ==

### Custom Hooks and Filters

**Available Action Hooks:**
* `bp_activity_filter_init` - Plugin initialization
* `bp_activity_filter_settings_saved` - After settings save
* `bp_activity_filter_cpt_activity_created` - When CPT activity is created

**Available Filter Hooks:**
* `bp_activity_filter_default` - Modify default filter value
* `bp_activity_filter_available_filters` - Customize available filters
* `bp_activity_filter_query_args` - Modify activity query arguments
* `bp_activity_filter_eligible_post_types` - Filter eligible CPTs

### Custom Post Type Configuration

```php
// Enable activity for custom post type programmatically
add_filter( 'bp_activity_filter_eligible_post_types', function( $post_types ) {
    $post_types['my_custom_type'] = get_post_type_object( 'my_custom_type' );
    return $post_types;
});

// Customize activity action text
add_filter( 'bp_activity_filter_cpt_activity_action', function( $action, $post, $label ) {
    if ( 'my_custom_type' === $post->post_type ) {
        $action = sprintf( '%s shared a new %s', get_author_name(), $label );
    }
    return $action;
}, 10, 3 );
```

### Performance Optimization

The plugin includes several performance optimizations:

* **Query Caching**: Activity actions are cached to reduce database calls
* **Smart Loading**: Scripts only load on relevant pages
* **Minimal Footprint**: Optimized code with efficient algorithms
* **Database Optimization**: Indexed queries and reduced overhead

### Troubleshooting

**Common Issues:**

1. **Activities not filtering**: Check BuddyPress version compatibility
2. **Settings not saving**: Verify user permissions and nonce verification
3. **Custom post types not showing**: Ensure post types meet eligibility criteria
4. **Theme conflicts**: Test with default BuddyPress theme

**Debug Mode:**
Enable WordPress debug mode to see detailed error messages:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

== Support ==

For support, documentation, and feature requests:

- **Documentation**: [Plugin Documentation](https://docs.wbcomdesigns.com/doc_category/buddypress-activity-filter/)
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

This plugin does not collect or store any personal user data beyond what WordPress and BuddyPress already collect. Activity filtering preferences are stored locally in browser cookies and user meta fields as needed for functionality.

== Credits ==

Developed by [Wbcom Designs](https://wbcomdesigns.com/) - Your trusted WordPress development partner.

Special thanks to the BuddyPress community for feedback and contributions that made this plugin possible.
