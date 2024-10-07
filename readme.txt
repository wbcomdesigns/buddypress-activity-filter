=== Wbcom Designs - BuddyPress Activity Filter ===
Contributors: vapvarun, wbcomdesigns
Tags: buddypress, activity-filter, filter, BuddyPress activity, hide activity, default activity, custom post type activity
Donate link: https://wbcomdesigns.com/donate/
Requires at least: 4.0
Tested up to: 6.6.2
Stable tag: 3.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily manage your BuddyPress Activity Stream by filtering specific activity types, setting default filters, and enabling public Custom Post Types (CPT) activities to enhance content visibility and user engagement.

== Description ==

The **BuddyPress Activity Filter** plugin helps site administrators customize the activity feed by setting default activity types and hiding irrelevant content. It also allows you to include activities from Custom Post Types (CPT) in the BuddyPress activity stream, ensuring that custom content is well-represented.

### Features:
- Set default activity filters for a cleaner user experience.
- Hide unwanted activity types from appearing on the front end.
- Enable Custom Post Type (CPT) activities in BuddyPress activity streams.

### Recommended Premium Add-ons for BuddyPress Communities:
Enhance your BuddyPress community with these premium extensions:

- **[BuddyPress Hashtags](https://wbcomdesigns.com/downloads/buddypress-hashtags/?utm_source=wp.org&utm_medium=plugins&utm_campaign=wp.org)**  
  Allow members to use hashtags in BuddyPress activities and bbPress topics to make content easier to find.
  
- **[BuddyPress Polls](https://wbcomdesigns.com/downloads/buddypress-polls/?utm_source=wp.org&utm_medium=plugins&utm_campaign=wp.org)**  
  Add poll functionality to BuddyPress activity posts, enabling members to create and participate in polls within the community.
  
- **[BuddyPress Quotes](https://wbcomdesigns.com/downloads/buddypress-quotes/?utm_source=wp.org&utm_medium=plugins&utm_campaign=wp.org)**  
  Allow users to post status updates with engaging background images or colors, creating visually appealing activity posts.
  
- **[BuddyPress Status & Reactions](https://wbcomdesigns.com/downloads/buddypress-status/?utm_source=wp.org&utm_medium=plugins&utm_campaign=wp.org)**  
  Enable custom statuses for profiles and provide a wide range of emoji reactions for activity posts.
  
- **[BuddyPress Sticky Post](https://wbcomdesigns.com/downloads/buddypress-sticky-post/?utm_source=wp.org&utm_medium=plugins&utm_campaign=wp.org)**  
  Pin important activity posts to the top of the activity feed to ensure maximum visibility.
  
- **[BuddyPress Profanity Filter](https://wbcomdesigns.com/downloads/buddypress-profanity/?utm_source=wp.org&utm_medium=plugins&utm_campaign=wp.org)**  
  Automatically censor inappropriate words in BuddyPress activities and private messages.

- **[WP Stories](https://wbcomdesigns.com/downloads/wp-stories/?utm_source=wp.org&utm_medium=plugins&utm_campaign=wp.org)**  
  Add the popular stories feature to your BuddyPress community, allowing members to share stories as short videos or images.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/buddypress-activity-filter` directory, or install the plugin directly through the WordPress plugins screen.

2. Activate the plugin through the \'Plugins\' screen in WordPress

3. Use the Settings-> BuddyPress -> Options -> Activity Filter Settings screen to set the default activity type and hide specific activity type(s).


== Frequently Asked Questions ==

= What is the default activity filter? =  
By default, the "Everything" filter is shown in the activity feed.

= Can I hide specific activity types? =  
Yes, you can select which activity types to hide in the plugin settings.

= How do Custom Post Type activities display in the activity stream? =  
Custom Post Type activities are automatically added to the activity stream when published.

== Screenshots ==

1. The screenshot shows settings to select the activity type to display on the activity page by default, corresponding to screenshot-1.(png|jpg|jpeg|gif).

2. The screenshot shows settings to select activity/activities you want to hide from the dropdown list on activity options, which corresponds to

   screenshot-2.(png|jpg|jpeg|gif).

3. The screenshot shows settings to add custom post-type activities, corresponding to screenshot-3.(png|jpg|jpeg|gif).

4. The screenshot shows FAQ(s) , corresponds to screenshot-4.(png|jpg|jpeg|gif).

== Changelog ==

= 3.0.1 =
* Fixed: Warning related to page parameter in activity query.
* Fixed: Pagination issue for activity streams where "Load More" button was not functioning correctly.
* Improvement: Added a check to ensure that $page is a string before processing, improving overall query handling.

= 3.0.0 =
* Fixed: PHP warning issue.
* Fixed: Issue in filtering activities.
* Fixed: The activity filter is now applied correctly when viewing "just-me" or "sitewide" activities.
* Fixed: Bypass default activity filter on profile other tabs.
* Fixed: Issue with the activity filter on single activity views.
* Improved: Deleting cookies when saving admin options.
* Added: Check to prevent setting the default activity filter on single activity views.

= 2.9.0 =
* Enhancement: Ensured lowercase post type names when no new label is provided.
* Fix: Corrected typos and updated the readme for clarity.
* Code Compliance: Removed deprecated filters, unused variables, and modernized PHP code.
* Security: Replaced FILTER_SANITIZE_STRING with sanitize_text_field() and added nonce validation for improved security.
* Optimization: Improved handling of undefined values and data sanitization.
* Access Control: Added proper permissions for managing options.
* UI Update: Managed backend options and improved responsive design.

= 2.8.9 =
* Fix: Fix: Compatibility check with WordPress 6.5.0

= 2.8.7 =
* Fix: BuddyPress v12 support

= 2.8.6 =
* Fix - Fixed phpcs error
* Fix - (#116) Fixed activity filter issue
* Fix - (#114) Fixed query monitor issue

= 2.8.5 =
* Fix - Disabled the plugin for bb due to similar features
* Fix - add default placeholder for cpt types
* Fix - (#111) Updated UI type checkbox and radio

= 2.8.3 =
* Fix - (#153)Fixed compatibility issue with BuddyPress Hashtag plugin 

= 2.8.2 =
* Fix - Updated Admin wrapper 

= 2.8.1 =
* Fix - (#97) Fixed remove activity settings are not saving

= 2.8.0 =
* Fix - Fixed phpcs error
* Fix - Removed install plugin button from the wrapper
* Fix - (#95) Fixed embed links issue
* Fix - (#95) Fixed Activity UI issue
* Fix - (#93) Fixed default activity filter issue with php 8

= 2.7.0 =
* Fix - #88 - Fatal Error Displaying When BuddyPress Activity Component is disabled
* Fix - #78 - topics and replies do not remove
* Fix - #89 - Ajax Warning in Remove Activity Tab
* Fix - Redirect stop when the buddypress plugin does not activate

= 2.6.0 =
* Fix - Update plugin backend UI
* Fix - (#75)Fixed load admin script on admin pages for all languages

= 2.5.0 =
* Fixed - PHPCS Fixes
* Fixed - Use Display Names instead for nicename for new cpt activities

= 2.4.0 =
* Fixed #67 - Default filter issue.

= 2.3.0 =
* Fixed #62 - Group Related Activities 
* Fixed #59 - Conflict with BuddyPress quotes plugin
* Fixed #63 - BuddyPress Profile: group activity
* Fixed: Comment not added when removing "Replied to a status update."
* Fixed: Add All Register Activity Type in Review Activity Setting & update code
* Fixed #53 - Error Log

= 2.2.1 =
* Fix: BuddyPress & BuddyBoss bp-settings are not saved and display the error message.

= 2.2.0 =
* Enhancement: Added default option for Sitewide Activity & Profile separately

= 2.1.0 =
* Fix: Compatibility with BuddyPress 5.1.2
* Fix: Updated UI for enabling CPT updates inside the activity.

= 2.0.1
* Fix - Compatibility with BuddyPress 4.3.0. #33

= 2.0.0
* Fix - Compatibility with BuddyPress 4.1.0. #25
* Enhancement- Improve the Backend UI so that you can manage all the wbcom plugin's settings in one place. #27

= 1.0.6 =
* Enhancement - Added French translation files – credits to Jean Pierre Michaud

= 1.0.5 =
* Fix - BuddyPress 3.2.0 Compatible.

= 1.0.4 =
* Fix - Multisite Support

= 1.0.3 =
* Fix - Changed plugin setting UI.
* Enhancement - Add activities settings for the custom post type.

= 1.0.2 =
* Fix - Default filter fixes

= 1.0.1 =
* Fix - Admin table fixes

= 1.0.0 =
* Initial Release
