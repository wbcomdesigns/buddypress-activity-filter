<?php
/**
 * Updated Wbcom Integration for BP Activity Filter
 * 
 * Integrates this plugin with the shared Wbcom admin system
 * 
 * @package BuddyPress_Activity_Filter
 * @version 4.0.0
 */

if (!defined('ABSPATH')) exit;

class BP_Activity_Filter_Wbcom_Integration {
    
    private $shared_loader;
    
    public function __construct() {
        add_action('admin_init', array($this, 'init_shared_integration'), 1);
    }
    
    /**
     * Initialize integration with shared system
     */
    public function init_shared_integration() {
        // Load shared system
        require_once dirname(__FILE__) . '/shared-admin/class-wbcom-shared-loader.php';
        
        // Plugin data for registration
        $plugin_data = array(
            'slug'         => 'bp-activity-filter',
            'name'         => 'BuddyPress Activity Filter',
            'version'      => BP_ACTIVITY_FILTER_VERSION,
            'file'         => plugin_basename(BP_ACTIVITY_FILTER_PLUGIN_DIR . 'buddypress-activity-filter.php'),
            'settings_url' => admin_url('admin.php?page=wbcom-activity-filter'),
            'priority'     => 10,
            'icon'         => 'dashicons-filter',
            'description'  => 'Filter and manage BuddyPress activity streams with default filters and custom post type support.',
        );
        
        // Initialize shared system (will only load once across all Wbcom plugins)
        $this->shared_loader = Wbcom_Shared_Loader::init($plugin_data);
        
        // Add our plugin's admin page
        add_action('admin_menu', array($this, 'add_plugin_admin_page'), 20);
    }
    
    /**
     * Add plugin-specific admin page
     */
    public function add_plugin_admin_page() {
        // Add submenu under Wbcom Designs
        $hook_suffix = add_submenu_page(
            'wbcom-designs',                    // Parent menu
            'BuddyPress Activity Filter',       // Page title
            'Activity Filter',                  // Menu title  
            'manage_options',                   // Capability
            'wbcom-activity-filter',           // Menu slug
            array($this, 'render_admin_page'), // Callback
            10                                  // Position
        );
        
        // Add help tabs for this page
        if ($hook_suffix) {
            add_action("load-{$hook_suffix}", array($this, 'add_help_tabs'));
        }
    }
    
    /**
     * Render plugin admin page
     */
    public function render_admin_page() {
        // Use the cleaned admin class
        if (class_exists('BP_Activity_Filter_Admin')) {
            $admin = BP_Activity_Filter_Admin::instance();
            $admin->render_settings_page();
        } else {
            echo '<div class="wrap"><h1>BuddyPress Activity Filter Settings</h1>';
            echo '<p>Admin class not found.</p></div>';
        }
    }
    
    /**
     * Add help tabs to admin page
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_overview',
                'title'   => esc_html__('Overview', 'bp-activity-filter'),
                'content' => $this->get_help_tab_content_overview(),
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_default_filters',
                'title'   => esc_html__('Default Filters', 'bp-activity-filter'),
                'content' => $this->get_help_tab_content_default_filters(),
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_hidden_activities',
                'title'   => esc_html__('Hidden Activities', 'bp-activity-filter'),
                'content' => $this->get_help_tab_content_hidden_activities(),
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_custom_post_types',
                'title'   => esc_html__('Custom Post Types', 'bp-activity-filter'),
                'content' => $this->get_help_tab_content_custom_post_types(),
            )
        );

        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__('For more information:', 'bp-activity-filter') . '</strong></p>' .
            '<p><a href="https://wbcomdesigns.com/support/" target="_blank">' . esc_html__('Support', 'bp-activity-filter') . '</a></p>' .
            '<p><a href="https://docs.wbcomdesigns.com/" target="_blank">' . esc_html__('Documentation', 'bp-activity-filter') . '</a></p>' .
            '<p><a href="https://wordpress.org/plugins/bp-activity-filter/" target="_blank">' . esc_html__('Plugin Page', 'bp-activity-filter') . '</a></p>'
        );
    }

    /**
     * Get help tab content for overview
     */
    private function get_help_tab_content_overview() {
        return '<h3>' . esc_html__('BuddyPress Activity Filter', 'bp-activity-filter') . '</h3>' .
               '<p>' . esc_html__('This plugin allows you to customize how BuddyPress activities are displayed and filtered on your site.', 'bp-activity-filter') . '</p>' .
               '<p>' . esc_html__('Use the tabs above to configure different aspects of activity filtering:', 'bp-activity-filter') . '</p>' .
               '<ul>' .
               '<li><strong>' . esc_html__('Default Filters:', 'bp-activity-filter') . '</strong> ' . esc_html__('Set what activity type is shown by default', 'bp-activity-filter') . '</li>' .
               '<li><strong>' . esc_html__('Hidden Activities:', 'bp-activity-filter') . '</strong> ' . esc_html__('Hide specific activity types completely', 'bp-activity-filter') . '</li>' .
               '<li><strong>' . esc_html__('Custom Post Types:', 'bp-activity-filter') . '</strong> ' . esc_html__('Enable activities for custom post types', 'bp-activity-filter') . '</li>' .
               '</ul>';
    }

    /**
     * Get help tab content for default filters
     */
    private function get_help_tab_content_default_filters() {
        return '<h3>' . esc_html__('Default Filters', 'bp-activity-filter') . '</h3>' .
               '<p>' . esc_html__('Default filters determine what type of activities are shown when users first visit activity streams.', 'bp-activity-filter') . '</p>' .
               '<h4>' . esc_html__('Site-wide Activity Default', 'bp-activity-filter') . '</h4>' .
               '<p>' . esc_html__('This setting applies to the main activity directory that all users see.', 'bp-activity-filter') . '</p>' .
               '<h4>' . esc_html__('Profile Activity Default', 'bp-activity-filter') . '</h4>' .
               '<p>' . esc_html__('This setting applies to individual user profile activity streams.', 'bp-activity-filter') . '</p>' .
               '<p><strong>' . esc_html__('Note:', 'bp-activity-filter') . '</strong> ' . esc_html__('Users can still change the filter using the dropdown, but these settings determine what they see initially.', 'bp-activity-filter') . '</p>';
    }

    /**
     * Get help tab content for hidden activities
     */
    private function get_help_tab_content_hidden_activities() {
        return '<h3>' . esc_html__('Hidden Activities', 'bp-activity-filter') . '</h3>' .
               '<p>' . esc_html__('Use this section to completely hide specific types of activities from all activity streams.', 'bp-activity-filter') . '</p>' .
               '<p>' . esc_html__('Hidden activities will not appear in:', 'bp-activity-filter') . '</p>' .
               '<ul>' .
               '<li>' . esc_html__('Activity streams (site-wide or profile)', 'bp-activity-filter') . '</li>' .
               '<li>' . esc_html__('Activity filter dropdown options', 'bp-activity-filter') . '</li>' .
               '<li>' . esc_html__('Activity feeds or notifications', 'bp-activity-filter') . '</li>' .
               '</ul>' .
               '<p><strong>' . esc_html__('Warning:', 'bp-activity-filter') . '</strong> ' . esc_html__('This affects all users on your site. Use this feature carefully as it cannot be overridden by individual users.', 'bp-activity-filter') . '</p>';
    }

    /**
     * Get help tab content for custom post types
     */
    private function get_help_tab_content_custom_post_types() {
        return '<h3>' . esc_html__('Custom Post Types', 'bp-activity-filter') . '</h3>' .
               '<p>' . esc_html__('Enable automatic activity generation when custom post types are published.', 'bp-activity-filter') . '</p>' .
               '<h4>' . esc_html__('Requirements', 'bp-activity-filter') . '</h4>' .
               '<p>' . esc_html__('Only custom post types that are public and have admin UI enabled will appear here.', 'bp-activity-filter') . '</p>' .
               '<h4>' . esc_html__('How it works', 'bp-activity-filter') . '</h4>' .
               '<p>' . esc_html__('When someone publishes a post of the enabled custom post type, an activity entry will be automatically created showing:', 'bp-activity-filter') . '</p>' .
               '<ul>' .
               '<li>' . esc_html__('The author name (linked to their profile)', 'bp-activity-filter') . '</li>' .
               '<li>' . esc_html__('The post type name or your custom label', 'bp-activity-filter') . '</li>' .
               '<li>' . esc_html__('The post title (linked to the post)', 'bp-activity-filter') . '</li>' .
               '</ul>' .
               '<p><strong>' . esc_html__('Note:', 'bp-activity-filter') . '</strong> ' . esc_html__('Only new posts published after enabling this feature will generate activities. Existing posts will not create activities.', 'bp-activity-filter') . '</p>';
    }
    
    /**
     * Get shared loader instance
     */
    public function get_shared_loader() {
        return $this->shared_loader;
    }
}