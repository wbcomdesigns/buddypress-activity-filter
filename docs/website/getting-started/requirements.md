# Requirements

Confirm your environment meets the following before installing.

| Requirement | Value |
| --- | --- |
| WordPress | 5.0 or higher |
| PHP | 8.0 or higher |
| Tested up to | WordPress 6.8.2 |
| BuddyPress | Required, and active |
| Minimum BuddyPress version | 12.0.0 |

## BuddyPress dependency

BuddyPress must be installed and active. On load, the plugin checks for BuddyPress and, if it is missing, stops and shows an admin notice linking to the BuddyPress install screen instead of running.

The plugin also enforces a minimum BuddyPress version of 12.0.0. On an older BuddyPress version it stops and shows a notice reporting the required and current versions.

## BuddyBoss

The plugin is not compatible with BuddyBoss. When BuddyBoss is detected, the plugin does not initialise its features and shows a notice recommending the native BuddyBoss activity filtering instead.

## Multisite

The plugin declares network support and can be network activated. Options are read per site; on uninstall, both single-site and network options are cleaned up.
