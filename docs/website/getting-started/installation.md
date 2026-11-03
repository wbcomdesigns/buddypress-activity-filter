# Installation

Install BuddyPress first, then add this plugin.

## Install from the WordPress dashboard

1. Go to **Plugins > Add New**.
2. Search for "BuddyPress Activity Filter".
3. Click **Install Now**, then **Activate**.

## Install manually

1. Download the plugin ZIP file.
2. Upload the extracted folder to `/wp-content/plugins/`.
3. Go to **Plugins** in the dashboard and activate **BuddyPress Activity Filter**.

## After activation

- If BuddyPress is not active, or the active BuddyPress version is below 12.0.0, an admin notice explains what is needed and the plugin features stay inactive until the requirement is met.
- On successful activation, the plugin sets its default options (site-wide default, profile default, hidden activities, and custom post type settings) and redirects once to the Wbcom Designs dashboard.
- A **Settings** link and a **Dashboard** link are added to the plugin's row on the Plugins screen.

## Open the settings

Go to **Wbcom Designs > BP Activity Filter** (`admin.php?page=wbcom-activity-filter`). From there, use the Default Filters, Hidden Activities, and Custom Post Types tabs to configure the plugin. See [Configuring the Plugin](../usage/configuring-the-plugin.md) for a walkthrough.

## Uninstall

Deleting the plugin runs its uninstall routine, which removes the plugin options (current and legacy), the `bp_activity_filter_preference` user meta, and the plugin's activity meta keys. To keep this data on uninstall, return `true` from the `bp_activity_filter_preserve_data_on_uninstall` filter before deleting.
