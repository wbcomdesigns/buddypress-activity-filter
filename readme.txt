=== BuddyPress Activity Filter ===

Contributors: vapvarun,wbcomdesigns
Tags: buddypress,activity-filter, activity, filter , BuddyPress Activity, Activity Filter, Default Activity, Hide Activty, BuddyPress default activity
Donate link: https://wbcomdesigns.com/donate/
Requires at least: 4.0
Tested up to: 5.4.0
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


It will help to set default filter option with BuddyPress Activity and It will also allow removing some specific activity types from activities. It also allows to post certain public CPT type activies using simple enable option. Like if you have event post type, you allow to post a simple activity update inside BuddyPress activities [reference](https://codex.buddypress.org/plugindev/post-types-activities/).


== Description ==

Admin can set default and customised activities to be listed on front-end.

It will also allow to set your default option for activities. You can change default from everything to Post Updates.

If you need additional help you can contact us for [Custom Development](https://wbcomdesigns.com/contact/).


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/buddypress-activity-filter` directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the \'Plugins\' screen in WordPress

3. Use the Settings-> BuddyPress -> Options -> Activity Filter Settings screen to set default default activity type and hide specific activity type(s).


== Frequently Asked Questions ==

= Is this plugin requires another plugin?=

Yes, this plugin requires BuddyPress plugin.

= By default, which filters will be displayed in activity dropdown? =

By default, all filters will be displayed.

= By default, which filters will be hidden in activity dropdown? =

By default, no filter will be hidden.

= If I selected 'Display in Groups' then what will be happened? =

If you selected 'Display in Groups' option then when you add a new post in that specific custom post type, all BuddyPress groups display this activity.

= What will be displayed if 'Rename in Activity Stream' field empty?=

If this field is empty then the singular label of custom post type will be displayed.

= How to modify the custom post type activity content display on the front end?=

You can modify activity content by given filters.

1. bpaf_main_activity_content_override

2. bpaf_groups_content_override

== Screenshots ==


1. The screenshot shows settings to select activity type to display at activity page by default, corresponds to screenshot-1.(png|jpg|jpeg|gif).

2. The screenshot shows settings to select activity/activities you want to hide from dropdown list on activity options, corresponds to

   screenshot-2.(png|jpg|jpeg|gif).

3. The screenshot shows settings to add activities of custom post type, corresponds to screenshot-3.(png|jpg|jpeg|gif).

4. The screenshot shows FAQ(s) , corresponds to screenshot-4.(png|jpg|jpeg|gif).

== Changelog ==
= 2.2.1 =
* Fix: BuddyPress & BuddyBoss bp-settings not saved and display error message.

= 2.2.0 =
* Enhancement: Added default option for Sitewide Activity & Profile separately

= 2.1.0 =
* Fix: Compatibility with BuddyPress 5.1.2
* Fix: Updated UI for the enabling CPT updates inside the activity.

= 2.0.1
* Fix - Compatibility with BuddyPress 4.3.0. #33

= 2.0.0
* Fix - Compatibility with BuddyPress 4.1.0. #25
* Enhancement- Improve Backend UI where you can manage all wbcom plugin's settings at one place. #27

= 1.0.6 =
* Enhancement - Added French translation files – credits to Jean Pierre Michaud

= 1.0.5 =
* Fix - BuddyPress 3.2.0 Compatible.

= 1.0.4 =
* Fix - Multisite Support

= 1.0.3 =
* Fix - Changed plugin setting UI.
* Enhancement - Add activities settings for custom post type.


= 1.0.2 =
* Fix - Default filter fixes

= 1.0.1 =
* Fix - Admin table fixes

= 1.0.0 =
* Initial Release
