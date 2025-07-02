<?php
/**
 * Wbcom Integration for BP Activity Filter
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
        add_submenu_page(
            'wbcom-designs',                    // Parent menu
            'BuddyPress Activity Filter',       // Page title
            'Activity Filter',                  // Menu title  
            'manage_options',                   // Capability
            'wbcom-activity-filter',           // Menu slug
            array($this, 'render_admin_page'), // Callback
            10                                  // Position
        );
    }
    
    /**
     * Render plugin admin page
     */
    public function render_admin_page() {
        // Use existing admin class but without menu creation
        if (class_exists('BP_Activity_Filter_Admin_Enhanced')) {
            $admin = BP_Activity_Filter_Admin_Enhanced::instance();
            $admin->render_settings_page_only(); // New method - settings only
        } else {
            echo '<div class="wrap"><h1>BuddyPress Activity Filter Settings</h1>';
            echo '<p>Admin class not found.</p></div>';
        }
    }
    
    /**
     * Get shared loader instance
     */
    public function get_shared_loader() {
        return $this->shared_loader;
    }
}