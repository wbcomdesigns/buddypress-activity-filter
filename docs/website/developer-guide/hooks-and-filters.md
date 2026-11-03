# Hooks and Filters

**Included in Free**

These are all the hooks the plugin fires.

## Actions

### `bp_activity_filter_init`

Fires after the plugin finishes initialising. No arguments.

```php
add_action( 'bp_activity_filter_init', function () {
    // Plugin is ready.
} );
```

## Filters

| Filter | Arguments | Purpose |
| --- | --- | --- |
| `bp_activity_filter_default` | `string $default_filter`, `string $context` (`sitewide` or `profile`) | Change the default filter. |
| `bp_activity_filter_activity_actions` | `array $labels` | Change the activity type labels offered in the settings. |
| `bp_activity_filter_admin_tabs` | `array $tabs` | Add or remove settings tabs. |
| `bp_activity_filter_admin_capability` | `string $capability` | Who can see and save settings. Default `manage_options`. Applies to the menu and the save. |
| `bp_activity_filter_preserve_data_on_uninstall` | `bool $preserve` | Return `true` to keep all data when the plugin is deleted. Default `false`. |
| `wbcom_hub_wrapper_helper_slugs` | `array $slugs` | Shared by Wbcom plugins. Sets which plugin slugs the WB Plugins landing page treats as wrapper helpers. |

## Examples

```php
// Default the site-wide stream to status updates.
add_filter( 'bp_activity_filter_default', function ( $default_filter, $context ) {
    return 'sitewide' === $context ? 'activity_update' : $default_filter;
}, 10, 2 );

// Let editors manage the settings.
add_filter( 'bp_activity_filter_admin_capability', function () {
    return 'edit_others_posts';
} );

// Keep settings when the plugin is deleted.
add_filter( 'bp_activity_filter_preserve_data_on_uninstall', '__return_true' );
```

## Options

| Option | Default | Purpose |
| --- | --- | --- |
| `bp_activity_filter_default` | `0` (Everything) | Site-wide default filter. |
| `bp_activity_filter_profile_default` | `-1` (Everything) | Profile default filter. |
| `bp_activity_filter_hidden` | `array()` | Hidden activity types. |
| `bp_activity_filter_db_version` | plugin version | Internal migration marker. |

## Removed in 4.0.0

The Custom Post Types hooks (for example `bp_activity_filter_cpt_activity_created`) and the `bp_activity_filter_eligible_post_types` filter no longer exist.
