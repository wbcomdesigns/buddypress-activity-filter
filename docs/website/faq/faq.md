# Frequently Asked Questions

**Included in Free**

## What is the default activity filter?

"Everything" for both the site-wide and profile streams. Change either on the Default Filters tab.

## Does hiding a type delete activities?

No. Hidden activities leave the streams and the dropdown, but stay in the database and in wp-admin **Activity**. Unhide the type and they return.

## What happened to the Custom Post Types tab?

It was removed in 4.0.0. It recorded every custom post type under the single "New blog post" type, so those activities could not be hidden or filtered separately, and it left the activity behind when the post was deleted. Activities it already created are not deleted. To put custom post types in the stream, use the BuddyPress Blogs component or a dedicated plugin.

## Does the site-wide default apply to groups?

No. Group streams are not given the site-wide default.

## Do members keep their own filter choice?

Yes. It is remembered in their browser and wins over your default on later visits.

## Can I hide status updates?

No. Status updates and replies are always visible.

## Which themes work?

Themes using the BuddyPress Legacy or Nouveau template pack.

## Is BuddyBoss supported?

No. The plugin shows a notice and does not run.

## How do I reset the settings?

Set both defaults back to "Everything" and untick every hidden type. Deactivating and reactivating does not reset anything.

## Does the plugin cache anything?

No. Filtering is added to the activity query BuddyPress already runs.

## Can I change filtering in code?

Yes. See [Hooks and Filters](../developer-guide/hooks-and-filters.md).
