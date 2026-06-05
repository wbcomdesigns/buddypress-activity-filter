# Code Flows — BuddyPress Activity Filter 3.2.1

## Bootstrap
```
buddypress-activity-filter.php
  -> bp_activity_filter()  (singleton)
  -> setup_hooks(): plugins_loaded@20 = init; init@1 (admin) = init_wbcom_integration
  -> init(): guard BP active + BP>=12 + !BuddyBoss
       -> includes() (helper, migration, admin, frontend, cpt)
       -> init_components() (Migration, Admin if is_admin, Frontend, CPT)
       -> do_action('bp_activity_filter_init')
```

## Admin menu + settings page (INTERMEDIATE shared wrapper)
```
init@1 (admin) -> init_wbcom_integration()
   if function_exists(wbcom_integrate_plugin)  -> use external (wbcom-essential)
   elseif includes/shared-admin/wbcom-easy-setup.php exists -> wbcom_integrate_plugin(__FILE__)
        -> Wbcom_Shared_Loader::quick_register() -> register_plugin()
   else init_wbcom_integration_fallback()
        -> require class-wbcom-shared-loader.php + register_plugin()
        -> new BP_Activity_Filter_Wbcom_Integration() (assets + ensure_main_menu)

admin_menu@5  Wbcom_Shared_Loader::create_main_menu()  -> add_menu_page('wbcom-designs')   [guarded by menu_exists]
admin_menu@5  BP_Activity_Filter_Wbcom_Integration::ensure_main_menu() -> add_menu_page('wbcom-designs') [guarded by wbcom_menu_exists]
admin_menu@10 Wbcom_Shared_Loader::add_plugin_submenus()
        -> menu_slug = extract_menu_slug(settings_url) = 'wbcom-activity-filter'
        -> label = apply_filters('wbcom_submenu_label', ...) -> 'BP Activity Filter'
        -> add_submenu_page('wbcom-designs', 'wbcom-activity-filter', cap=manage_options, show_plugin_page)

Render: ?page=wbcom-activity-filter
   -> Wbcom_Shared_Loader::show_plugin_page() -> load_plugin_admin()
   -> BP_Activity_Filter::render_admin_page()
   -> BP_Activity_Filter_Admin::render_settings_page()
        nav tabs (default|hidden|cpt|faq) + form POST
```

## Settings save
```
render_settings_page() detects $_POST['bp_activity_filter_submit']=='1'
   -> save_settings()
        -> wp_verify_nonce('bp_activity_filter_save_settings', $_POST['bp_activity_filter_nonce'])
        -> current_user_can('manage_options')
        -> switch($_POST['current_tab']) save_default_filters | save_hidden_activities | save_cpt_settings
        -> add_settings_error feedback
```
Key files: `class-bp-activity-filter-admin.php`. Nonce: `bp_activity_filter_save_settings`. Cap: `manage_options`. No AJAX.

## Frontend default filter (server-side)
```
bp_after_has_activities_parse_args@10 -> apply_default_filter_server_side($args)
   if no filter/action/type set:
     cookie 'bp-activity-filter' (not 0/-1) -> $args['type']
     else get_default_filter() (context profile|sitewide) -> apply_filters('bp_activity_filter_default') -> $args['type']
bp_ajax_querystring@10 (object=activity) -> apply_default_filter_ajax() (same logic via querystring)
wp_footer@999 -> sync_dropdown_with_default() (inline JS sets #activity-filter-by)
```

## Hide activity types
```
bp_get_activity_show_filters@10 -> remove_hidden_from_dropdown() (Nouveau array unset | legacy regex strip)
bp_activity_before_save@1 -> maybe_prevent_activity_save() (blank type+component for hidden)
bp_init@999 -> remove_hidden_activity_hooks() (remove_action on friendship/new_member/profile creators)
```
Core protected: activity_update, activity_comment (filtered out of hidden list in get_hidden_activities()).

## CPT activity generation
```
transition_post_status@999 -> handle_post_transition($new,$old,$post)
   guard: publish only, BP active, skip post/page, skip excluded cache, must be enabled in cpt_settings, not duplicate
   -> create_activity_for_post()
        label = apply_filters('bp_activity_filter_cpt_activity_label')
        action = apply_filters('bp_activity_filter_cpt_activity_action')
        content = apply_filters('bp_activity_filter_cpt_activity_content')
        args = apply_filters('bp_activity_filter_cpt_activity_args')  type=new_blog_post
        -> bp_activity_add()  + activity/post meta
        -> do_action('bp_activity_filter_cpt_activity_created', $id,$post,$settings)
```
Eligibility: `Helper::get_eligible_post_types()` (public + show_ui + title support; excludes Elementor UI types, bbPress/BP Member Reviews CPTs when active, create_posts=do_not_allow).

## Migration
```
admin_init@10 -> Migration::maybe_migrate()
   fresh install -> stamp db_version
   else version_compare -> run_migration(): migrate legacy options, transform CPT format, ensure defaults, bump db_version
Migration::get_option_with_fallback() — single read path used by Frontend/CPT/Helper.
```

## Uninstall
```
uninstall.php (WP_UNINSTALL_PLUGIN guard)
   apply_filters('bp_activity_filter_preserve_data_on_uninstall', false)
   -> delete options (current + legacy), user meta, activity meta (raw $wpdb->delete on usermeta + bp_activity_meta)
```

## Permissions
All admin paths: `manage_options`. No custom capabilities. No per-context (post/term) caps.

## Required settings for features
- Default filter: `bp_activity_filter_default` / `bp_activity_filter_profile_default`
- Hide: `bp_activity_filter_hidden`
- CPT: `bp_activity_filter_cpt_settings` (per-type `enabled`/`label`, `_global.hide_sitewide`)

## Dependencies
BuddyPress >= 12.0.0 (required). PHP 8.0+. Incompatible with BuddyBoss. Optional: wbcom-essential (shared tab CSS).
