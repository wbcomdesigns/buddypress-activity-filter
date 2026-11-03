# Code Flows - BuddyPress Activity Filter 4.0.0

Branch `4.0.0`. Line numbers verified against source.

## Bootstrap
```
buddypress-activity-filter.php
  -> bp_activity_filter()  (singleton BP_Activity_Filter, :464)
  -> setup_hooks() :96  plugins_loaded@20 = init; activation/deactivation hooks;
                        plugin_action_links_* and network_admin_plugin_action_links_* (:104-105)
  -> admin_init :473 = handle_activation_redirect
  -> init() :113: guard BuddyPress active + BP >= 12.0.0 + not BuddyBoss
        (failures add admin_notices / network_admin_notices and return)
       -> includes() :154   helper, migration, admin-panel, frontend
       -> init_components() :183
            new BP_Activity_Filter_Migration()
            if is_admin(): BP_Activity_Filter_Admin_Panel::instance()->register()
            BP_Activity_Filter_Frontend::instance()
       -> do_action('bp_activity_filter_init')   :146
```

## Activation
```
activate() :205
  -> meets_requirements() else deactivate_plugins + wp_die
  -> add_option() if missing: bp_activity_filter_default='0',
       bp_activity_filter_profile_default='-1', bp_activity_filter_hidden=[]
  -> set_transient bp_activity_filter_activation_redirect (30s)
  -> flush_rewrite_rules()
admin_init -> handle_activation_redirect() :417 -> wp_safe_redirect(admin.php?page=wbcom-activity-filter)
  (skipped for activate-multi and AJAX)
deactivate() :241 -> delete transient, wp_cache_flush(), flush_rewrite_rules()
```

## Admin menu + settings page (card panel)
```
Admin_Panel::register() :105
  admin_menu@10   add_menu() :145
        cap = capability() -> apply_filters('bp_activity_filter_admin_capability','manage_options')
        if no $admin_page_hooks['wbcomplugins']: add_menu_page('WB Plugins', render_hub, dashicons-lightbulb, 59)
        add_submenu_page('wbcomplugins', slug 'wbcom-activity-filter', render_page)
  admin_menu@999  takeover_hub_landing() :182 -> remove_all_actions + add_action('toplevel_page_wbcomplugins', render_hub)
  admin_init      register_settings() :200  (3 options, group bp_activity_filter_settings)
  admin_enqueue_scripts  enqueue_assets() :382  (only if is_our_screen())
  option_page_capability_bp_activity_filter_settings -> capability()

Render: ?page=wbcom-activity-filter[&tab=default|hidden|faq|discover]
   -> render_page() :450
        tabs = get_tabs() (filter bp_activity_filter_admin_tabs); unknown ?tab falls back to first tab
        view_map: default->settings-default, hidden->settings-hidden, faq->faq, discover->discover
        -> include views/shell.php (sidebar nav grouped Settings / Resources + docs link)
             settings group: <form method=post action=options.php> + settings_fields() + view + Save bar
             resources group: view only, no form
Hub: render_hub() :438 -> views/hub.php (peers read from $GLOBALS['submenu']['wbcomplugins'],
        filter wbcom_hub_wrapper_helper_slugs)
```

## Settings save
```
Submit (options.php, nonce + capability() check via option_page_capability_*)
  -> update_option() called for EVERY option in the group
  -> sanitize_default() :335 / sanitize_profile_default() :350 / sanitize_hidden() :365
        tab_rendered_option($key) :313 reads POST bp_activity_filter_rendered_options[]
          no sentinel at all -> treated as owned (programmatic writes)
          sentinel present but key not listed -> return get_option() unchanged (other tab's value kept)
          owned -> sanitize_filter_value() :238 (must be a known action key, '0' or '-1', else fallback)
                   or sanitize_hidden_list() :258 (drops activity_update / activity_comment,
                      unknown types, duplicates)
```

## Migration
```
admin_init -> Migration::maybe_migrate() (class-bp-activity-filter-migration.php:74)
  db_version '0' and no legacy options -> stamp bp_activity_filter_db_version, stop
  db_version >= plugin version -> stop
  else run_migration():
     migrate_legacy_options()  bp-default-filter-name -> bp_activity_filter_default
                               bp-default-profile-filter-name -> bp_activity_filter_profile_default
                               bp-hidden-filters-name -> bp_activity_filter_hidden (coerced to array)
                               (skipped per key when the new option already has a non-empty value)
     ensure_required_options_exist()  add_option defaults if missing
     update_option(bp_activity_filter_db_version)
Front end: get_option_with_fallback($key,$default) :204  new option -> legacy option (copied forward) -> add_option default
```

## Frontend: default filter and hiding
```
Frontend::setup_hooks() :70
 1 bp_after_has_activities_parse_args :72 -> apply_default_filter_server_side()
      skip if filter_query/action/type already set
      skip if AJAX and member_chose_filter_this_request()
      get_member_preference(): null -> fall through; '' (Everything) -> no filter; type -> args['action']
      else get_default_filter(): context profile|sitewide, option via Migration::get_option_with_fallback,
           apply_filters('bp_activity_filter_default', $value, $context)  :769
           '0'/'-1'/empty -> no filter, otherwise args['action'] = value
 2 bp_activity_get_where_conditions :79 -> exclude_hidden_from_query()
      front end + AJAX only; adds "a.type NOT IN (hidden...)" under key bp_activity_filter_hidden
 3 bp_ajax_querystring :82 -> apply_default_filter_ajax()  (object 'activity' only; same precedence as 1;
      a requested filter that is fully hidden is unset)
 4 wp_head@1 :87 -> reset_stored_filter_on_default_change()  (activity directory / member activity only)
      inline JS: if defaults signature (sitewide|profile) differs from cookie bpaf-applied-default,
      or the remembered filter is all hidden -> clear sessionStorage 'bp-activity'.filter and
      cookie bp-activity-filter, re-stamp bpaf-applied-default
 5 wp_footer@999 :90 -> sync_dropdown_with_default()  (inline JS, 50ms after DOMContentLoaded,
      sets #activity-filter-by to the preference/default; UI only)
 6 bp_get_activity_show_filters_options :96 -> remove_hidden_from_dropdown()
      drops options whose comma-separated keys are all hidden (friendship_created also hides
      friendship_accepted); ensure_default_is_selectable() re-adds a hidden stored default
 7 bp_activity_before_save@1 :103 -> maybe_prevent_activity_save()
      hidden type -> type='' and component='', WP_Error bp_activity_type_disabled added
Hidden list = get_hidden_activities() :805: option bp_activity_filter_hidden minus activity_update/activity_comment
```

## Helper: activity type list
```
BP_Activity_Filter_Helper::get_activity_actions()
  bp_activity_get_actions() flattened key => label; skips friendship_accepted and
  friends_register_activity_action; relabels friendship_created "New friendships"
  -> apply_filters('bp_activity_filter_activity_actions')  helper:67
Used by: both settings tabs and the sanitizers.
```

## Uninstall
```
uninstall.php  (WP_UNINSTALL_PLUGIN guard)
  preserve = apply_filters('bp_activity_filter_preserve_data_on_uninstall', false) :26 -> true: stop
  remove_options():  4 current + 5 legacy keys, delete_option + delete_site_option
  remove_activity_meta(): only if BP activity active and wp bp_activity_meta table exists;
        deletes bp_activity_filter_cpt, _post_id, _created_time, _version
  remove_post_meta(): delete_post_meta_by_key _bp_activity_filter_activity_id, _bp_activity_filter_recorded
  wp_cache_flush(); error_log only if WP_DEBUG
```

## Data stores
No custom tables, post types, cron, REST or AJAX. Entry points: frontend (activity stream + dropdown), backend (settings panel), options via Settings API. No REST route by design.
