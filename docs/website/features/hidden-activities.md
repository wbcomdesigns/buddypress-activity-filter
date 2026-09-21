# Hidden Activities

Hiding an activity type removes it from the community in three ways: it is dropped from the filter dropdown, and, where the plugin can intercept creation, new activities of that type are prevented from being saved. The selected types are stored in the `bp_activity_filter_hidden` option.

## What "hidden" covers

For a hidden activity type, the plugin:

1. **Removes it from the filter dropdown.** The dropdown options are filtered (`bp_get_activity_show_filters`) for both the Nouveau theme (array format) and legacy theme (HTML string format).
2. **Blocks the activity at save time.** On `bp_activity_before_save`, an activity whose type is hidden has its type and component cleared so BuddyPress does not persist it.
3. **Unhooks known creators.** On `bp_init`, the plugin removes the BuddyPress callbacks that generate a set of known activity types when the type is hidden, so the activity is never queued in the first place.

The activity types with a dedicated unhook mapping are:

- `friendship_created` (new friendships)
- `friendship_accepted`
- `new_member`
- `updated_profile`

Other hidden types are handled by the dropdown removal and the save-time block.

## Core activities are protected

Two activity types can never be hidden, because they are essential to basic BuddyPress use:

- `activity_update` (status updates)
- `activity_comment` (replies to status updates)

The Hidden Activities tab shows these in a separate, read-only "Core Activities" group. Even if a hidden value for one of them is submitted, the plugin strips it out on save and again when reading the hidden list.

## Available activity types

The list of hideable types is built from BuddyPress's registered activity actions. Two registration artifacts are filtered out so they do not appear as choices:

- `friendship_accepted` is skipped, because BuddyPress records the `friendship_created` type rather than a separate accepted type.
- `friends_register_activity_action` is skipped, because it is a registration helper, not a real activity type.

The `friendship_created` label is presented as "New friendships" for clarity.

## Effect on existing activities

Hiding a type stops it appearing in the dropdown and stops new ones being created. It does not delete activities of that type that already exist in the database.
