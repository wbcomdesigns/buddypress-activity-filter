# Troubleshooting

**Included in Free**

## The plugin shows a notice and does nothing

- **BuddyPress missing or older than 12.0.0.** Install, activate or update BuddyPress.
- **BuddyBoss active.** The plugin does not run with BuddyBoss. Use its native filtering.

## Settings do not save

- You need the capability set by `bp_activity_filter_admin_capability`, `manage_options` by default.
- If a security check fails, reload the page and save again.

## The default filter is not applied

- The site-wide default does not apply to group streams.
- A member's own choice, remembered in their browser, wins over your default. Test in a private window or another account.
- The default is a hidden type. The stream would be empty, so the setting shows "(hidden)" and a warning. Pick a visible type.
- The profile list leaves out "New Member" and "Updated Profile".

## A hidden type still appears

- Clear your browser cookies and site cache. BuddyPress remembers the last filter a member used.
- Check the page. Hidden activities still show in wp-admin **Activity** by design.
- Status updates and replies cannot be hidden.
- A theme that builds its own activity query may bypass the filter. Test with a default BuddyPress theme.

## Old custom post type activities still show

Version 4.0.0 removed the feature but did not delete its activities. Hide the "New blog post" type to remove them from streams.

## Debug mode

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Check `wp-content/debug.log`.

## Support

- Documentation: https://docs.wbcomdesigns.com/
- Support: https://wbcomdesigns.com/support/
