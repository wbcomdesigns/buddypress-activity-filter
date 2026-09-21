# Hooks and Filters

The hooks below are the ones registered in the plugin code. Use them to change defaults, adjust the list of hideable or eligible types, or customise the activities created for custom post types.

## Action hooks

### `bp_activity_filter_init`

Fires after the plugin has finished initialising (BuddyPress present, version compatible, components loaded). No arguments.

```php
add_action( 'bp_activity_filter_init', function () {
    // Plugin is ready.
} );
```

### `bp_activity_filter_cpt_activity_created`

Fires after a custom post type activity has been created.

Arguments: `int $activity_id`, `WP_Post $post`, `array $settings`.

```php
add_action( 'bp_activity_filter_cpt_activity_created', function ( $activity_id, $post, $settings ) {
    // React to the new activity.
}, 10, 3 );
```

## Filter hooks

### `bp_activity_filter_default`

Overrides the resolved default filter value for the current context.

Arguments: `string $default_filter`, `string $context` (`sitewide` or `profile`).

```php
add_filter( 'bp_activity_filter_default', function ( $default_filter, $context ) {
    if ( 'profile' === $context ) {
        return 'activity_update';
    }
    return $default_filter;
}, 10, 2 );
```

### `bp_activity_filter_activity_actions`

Filters the array of activity action labels the plugin uses in its dropdowns and hidden-activity list. Keyed by activity type.

Arguments: `array $labels`.

### `bp_activity_filter_eligible_post_types`

Filters the list of custom post types eligible for activity generation, before it is shown in the Custom Post Types tab.

Arguments: `array $eligible_types` (keyed by post type name, values are `WP_Post_Type` objects).

```php
add_filter( 'bp_activity_filter_eligible_post_types', function ( $post_types ) {
    $post_types['my_custom_type'] = get_post_type_object( 'my_custom_type' );
    return $post_types;
} );
```

### `bp_activity_filter_cpt_activity_args`

Filters the full argument array passed to `bp_activity_add()` when a custom post type activity is created.

Arguments: `array $activity_args`, `WP_Post $post`, `array $settings`.

### `bp_activity_filter_cpt_activity_label`

Filters the label used in the activity sentence for a custom post type.

Arguments: `string $label`, `array $settings`, `WP_Post_Type $post_type_obj`.

### `bp_activity_filter_cpt_activity_action`

Filters the full activity action sentence for a custom post type activity.

Arguments: `string $action`, `WP_Post $post`, `string $label`.

```php
add_filter( 'bp_activity_filter_cpt_activity_action', function ( $action, $post, $label ) {
    if ( 'my_custom_type' === $post->post_type ) {
        $action = sprintf( '%s shared a new %s', get_the_author_meta( 'display_name', $post->post_author ), $label );
    }
    return $action;
}, 10, 3 );
```

### `bp_activity_filter_cpt_activity_content`

Filters the activity content (excerpt or trimmed post content) for a custom post type activity.

Arguments: `string $content`, `WP_Post $post`.

### `bp_activity_filter_preserve_data_on_uninstall`

Return `true` to keep all plugin options and meta when the plugin is uninstalled. Defaults to `false` (data is removed).

Arguments: `bool $preserve_data`.

```php
add_filter( 'bp_activity_filter_preserve_data_on_uninstall', '__return_true' );
```

### `wbcom_submenu_label`

Used with the shared Wbcom admin menu to set this plugin's submenu label.

Arguments: `string $label`, `string $slug`, `array $plugin`.

## Options reference

| Option | Purpose | Default |
| --- | --- | --- |
| `bp_activity_filter_default` | Site-wide default filter | `0` (Everything) |
| `bp_activity_filter_profile_default` | Profile default filter | `-1` (Everything) |
| `bp_activity_filter_hidden` | Array of hidden activity types | `array()` |
| `bp_activity_filter_cpt_settings` | Per-type and global CPT settings | `array()` |
| `bp_activity_filter_db_version` | Stored schema/version marker for migrations | plugin version |

## Notes on the server-side filter

The default filter is applied by hooking BuddyPress core arguments (`bp_after_has_activities_parse_args`) and the activity AJAX query string (`bp_ajax_querystring`). These are BuddyPress hooks, not plugin-defined filters; the plugin reads its own options and the `bp-activity-filter` cookie to decide the value.
