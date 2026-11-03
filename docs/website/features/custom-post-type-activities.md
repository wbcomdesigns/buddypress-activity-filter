# Custom Post Type Activities

This feature turns a published custom post type (CPT) into a BuddyPress activity entry. When a post of an enabled type is published, the plugin creates an activity showing the author, a label, and a link to the post. Settings are stored in the `bp_activity_filter_cpt_settings` option.

## Which post types can be enabled

The Custom Post Types tab lists public custom post types that:

- are registered as `public` with `show_ui` enabled, and
- support a title.

Built-in `post` and `page` are not offered, because they have their own activity handling.

Some post types are excluded automatically to avoid duplicate or meaningless activities. Excluded types are listed on the settings screen with the reason. Exclusions cover:

- WordPress internal types such as `attachment`, `revision`, `nav_menu_item`, `wp_block`, `wp_template`, and other editor and customizer types.
- Elementor UI and template types (`elementor_library`, `e-landing-page`, `e-floating-buttons`, `elementor_font`, `elementor_icons`, `elementor_snippet`).
- Post types whose `create_posts` capability is set to `do_not_allow`.
- Known conflicting types when the owning plugin is active: `review` (BP Member Reviews) and `forum`, `topic`, `reply` (bbPress).

## Per-type settings

For each eligible post type you can set:

- **Enabled** - whether publishing a post of this type creates an activity.
- **Custom Activity Label** - optional text used in the activity sentence. If left empty, the post type's lowercase singular name is used.

The activity sentence reads: "[Author] published a new [label]: [Post Title]", with the author and title linked.

## Global setting

A single global option, **Hide custom post type activities from the site-wide activity stream** (`_global` > `hide_sitewide`), keeps these activities off the main directory while still showing them on the author's own profile stream.

## How and when activities are created

- Creation runs on `transition_post_status` at a late priority (999), only when a post moves into the `publish` status from a non-published status.
- The activity is stored with the BuddyPress activity type `new_blog_post`, component `activity`, linked to the post via `item_id` and `secondary_item_id`.
- The activity content is the post excerpt if present, otherwise the first 55 words of the content.

## Duplicate prevention

Before creating an activity, the plugin checks for an existing one using its own tracking meta (`bp_activity_filter_post_id` on the activity, `_bp_activity_filter_activity_id` on the post) and for any recent activity referencing the same post within the last 60 seconds. If a match is found, no new activity is created. This avoids double entries when another plugin also posts activity for the same publish event.

## Tracking meta

Created activities record several meta keys for tracking and cleanup: `bp_activity_filter_cpt`, `bp_activity_filter_post_id`, `bp_activity_filter_created_time`, and `bp_activity_filter_version`. The source post stores `_bp_activity_filter_activity_id` and `_bp_activity_filter_recorded`.

## Existing posts

Only posts published after a type is enabled generate activities. Posts published before that are not backfilled.
