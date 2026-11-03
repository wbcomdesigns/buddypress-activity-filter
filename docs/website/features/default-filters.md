# Default Filters

Default filters decide which activity type loads automatically when a member arrives at an activity stream, before they touch the filter dropdown. You set two independent defaults: one for the site-wide activity directory and one for member profile activity streams.

## The two settings

| Setting | Option key | Default value |
| --- | --- | --- |
| Site-wide Activity Default | `bp_activity_filter_default` | `0` (Everything) |
| Profile Activity Default | `bp_activity_filter_profile_default` | `-1` (Everything) |

The value `0` (site-wide) and `-1` (profile) both mean "Everything", so out of the box no default filtering is applied. Choosing any specific activity type, such as status updates or new blog posts, makes that type the default view.

On the Profile default dropdown, the `new_member` and `updated_profile` types are intentionally left out, because those activities do not normally belong on an individual member profile stream.

## How the default is applied

Filtering runs server-side. The plugin hooks the BuddyPress activity query arguments (`bp_after_has_activities_parse_args`) and the activity AJAX query string (`bp_ajax_querystring`) so the default is honoured on the first page load and on subsequent "Load More" and AJAX requests.

The order of precedence is:

1. If the request already specifies an activity type or action, the plugin leaves it alone.
2. If the member has a saved preference in the `bp-activity-filter` cookie (and it is not "Everything"), that preference is used.
3. Otherwise the admin default for the current context (site-wide or profile) is applied.

This means a member who picks a filter from the dropdown keeps their choice, stored in a browser cookie, while first-time and cookieless visitors see the admin default.

## Context detection

The profile default is applied only on a member's own activity stream (the "just-me" view of a user activity page). Everywhere else the site-wide default is used.

Developers can override the resolved default with the `bp_activity_filter_default` filter. See [Hooks and Filters](../developer-guide/hooks-and-filters.md).
