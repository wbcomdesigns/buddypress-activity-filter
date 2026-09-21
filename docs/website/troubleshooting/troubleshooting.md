# Troubleshooting

Work through the checks below when the plugin does not behave as expected.

## The plugin shows a requirement notice and does nothing

- **"Requires BuddyPress" notice.** BuddyPress is not active. Install and activate BuddyPress, then reload.
- **BuddyPress version notice.** The active BuddyPress version is below the required 12.0.0. Update BuddyPress.
- **BuddyBoss notice.** BuddyBoss is active. The plugin does not run alongside BuddyBoss; use the native BuddyBoss activity filtering instead.

## Settings do not save

- Saving requires the `manage_options` capability. Confirm you are an administrator.
- Each tab saves independently with its own **Save Settings** button and its own nonce. If the security check fails, reload the page to refresh the nonce and try again.
- If the screen reports "No changes were made", the submitted values matched the stored ones; nothing needed saving.

## The default filter is not applied

- Check the correct context. The profile default applies only on a member's own activity stream; the site-wide default applies to the main activity directory.
- A default of "Everything" (`0` site-wide, `-1` profile) means no filtering is applied by design.
- The plugin honours an existing filter choice first. If the member has a `bp-activity-filter` cookie set to a specific type, that overrides the admin default. Clear the cookie to see the default behaviour.
- If a request already specifies an activity `type` or `action`, the plugin does not override it.

## A hidden activity still appears

- The dropdown is filtered for the Nouveau theme (array output) and legacy themes (HTML output). A heavily customised or third-party theme that renders the filter differently may not be covered.
- Hiding prevents new activities and dropdown entries; it does not delete activities created before the type was hidden.
- Status updates and replies are core types and can never be hidden.

## A custom post type does not create activities

- Confirm the type is ticked as enabled on the Custom Post Types tab and the settings are saved.
- Only posts moving into the published status create an activity. Editing an already-published post does not.
- Existing posts are not backfilled; only posts published after enabling the type generate activities.
- The type may be excluded. Check the admin notice on the settings screen for the exclusion reason (internal WordPress type, Elementor template type, blocked post creation, or a known conflict with BP Member Reviews or bbPress).
- If another plugin already posts activity for the same publish event, the plugin's duplicate check (its tracking meta, plus any activity for the same post within the last 60 seconds) suppresses a second entry.

## Duplicate activities for a custom post type

The plugin prevents duplicates it can detect, but two independent plugins creating activity for the same post more than 60 seconds apart, or without shared tracking meta, can still produce separate entries. Disable the overlapping source, or adjust with the `bp_activity_filter_cpt_activity_args` filter.

## Enabling debug output

Set `WP_DEBUG` and `WP_DEBUG_LOG` to `true` in `wp-config.php`. In debug mode the plugin logs custom post type exclusions, skipped and duplicate publishes, created activities, and migration failures to the debug log.

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

## Getting support

- Documentation: https://docs.wbcomdesigns.com/
- Premium support: https://wbcomdesigns.com/support/
