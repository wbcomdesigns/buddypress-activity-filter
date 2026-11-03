# Installation

**Included in Free**

Install and activate BuddyPress first, then add this plugin.

## Install from the dashboard

1. Go to **Plugins > Add New**.
2. Search for "BuddyPress Activity Filter".
3. Click **Install Now**, then **Activate**.

## Install manually

1. Download the plugin ZIP file.
2. Upload the extracted folder to `/wp-content/plugins/`.
3. Activate **BuddyPress Activity Filter** on the **Plugins** screen.

## What activation does

- If BuddyPress is missing or older than 12.0.0, activation stops with an error.
- Activation only adds default settings that do not exist yet: site-wide default "Everything", profile default "Everything" and no hidden types. It never resets settings you already saved, so deactivating and reactivating changes nothing.

## Open the settings

Go to **WB Plugins > BP Activity Filter**. See [Configuring the Plugin](../usage/configuring-the-plugin.md).

## Uninstall

Deleting the plugin removes its settings. This covers the four plugin options, the older option names from earlier versions, and leftover activity and post meta written by the removed Custom Post Types feature. Your activities are not deleted.

To keep everything, return `true` from `bp_activity_filter_preserve_data_on_uninstall` before you delete the plugin. See [Hooks and Filters](../developer-guide/hooks-and-filters.md).
