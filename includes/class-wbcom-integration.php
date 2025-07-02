<?php
/**
 * Wbcom Integration for BP Activity Filter - Main Menu Only
 * 
 * @package BuddyPress_Activity_Filter
 * @version 4.0.0
 */

if (!defined('ABSPATH')) exit;

class BP_Activity_Filter_Wbcom_Integration {
    
    private $plugin_data;
    private $shared_system_loaded = false;
    private $shared_path = '';
    
    public function __construct() {
        $this->setup_plugin_data();
        $this->init();
    }
    
    /**
     * Setup plugin data for registration
     */
    private function setup_plugin_data() {
        $this->shared_path = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/shared-admin/';
        
        $this->plugin_data = array(
            'slug'         => 'bp-activity-filter',
            'name'         => 'BuddyPress Activity Filter',
            'version'      => BP_ACTIVITY_FILTER_VERSION,
            'settings_url' => admin_url('admin.php?page=wbcom-activity-filter'),
            'icon'         => 'dashicons-filter',
            'priority'     => 5,
            'description'  => 'Filter and manage BuddyPress activity streams with default filters and custom post type support.',
            'status'       => 'active',
            'has_premium'  => false,
            'docs_url'     => 'https://docs.wbcomdesigns.com/bp-activity-filter/',
            'support_url'  => 'https://wbcomdesigns.com/support/',
            'shared_path'  => $this->shared_path,
        );
    }
    
    /**
     * Initialize integration
     */
    private function init() {
        // Load shared system
        $this->load_shared_system();
        
        // ONLY setup main Wbcom menu - plugins handle their own submenus
        add_action('admin_menu', array($this, 'ensure_main_menu'), 5);
        
        // Enqueue shared assets for Wbcom pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_shared_assets'), 5);
        
        // Add integration notice on our settings page
        add_action('admin_notices', array($this, 'add_integration_notice'));
    }
    
    /**
     * Load shared admin system
     */
    private function load_shared_system() {
        $loader_path = $this->shared_path . 'class-wbcom-shared-loader.php';
        
        if (file_exists($loader_path)) {
            require_once $loader_path;
            
            // Register plugin with shared system
            if (class_exists('Wbcom_Shared_Loader')) {
                $success = Wbcom_Shared_Loader::register_plugin($this->plugin_data);
                $this->shared_system_loaded = $success;
            }
        }
    }
    
    /**
     * Ensure main Wbcom Designs menu exists - DON'T CREATE SUBMENUS
     *
     * @since 4.0.0
     */
    public function ensure_main_menu() {
        // Only create main menu if it doesn't exist
        if (!$this->wbcom_menu_exists()) {
            add_menu_page(
                'Wbcom Designs',
                'Wbcom Designs',
                'manage_options',
                'wbcom-designs',
                array($this, 'render_dashboard'),
                $this->get_menu_icon(),
                58.5
            );
            
            // Add dashboard as first submenu
            add_submenu_page(
                'wbcom-designs',
                'Dashboard',
                'Dashboard',
                'manage_options',
                'wbcom-designs',
                array($this, 'render_dashboard')
            );
        }
    }
    
    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        try {
            if (class_exists('Wbcom_Shared_Dashboard')) {
                $dashboard = new Wbcom_Shared_Dashboard($this->get_registered_plugins());
                $dashboard->render_dashboard();
            } else {
                $this->render_fallback_dashboard();
            }
        } catch (Exception $e) {
            $this->render_fallback_dashboard();
        }
    }
    
    /**
     * Render fallback dashboard
     */
    private function render_fallback_dashboard() {
        ?>
        <div class="wrap">
            <h1>🌟 Wbcom Designs</h1>
            
            <div class="notice notice-info">
                <p><strong>Welcome to Wbcom Designs!</strong> Your plugins are being loaded...</p>
            </div>
            
            <div class="card">
                <h2>Installed Wbcom Plugins</h2>
                <ul>
                    <li>
                        <strong>BuddyPress Activity Filter</strong> 
                        (v<?php echo esc_html(BP_ACTIVITY_FILTER_VERSION); ?>)
                        - <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-activity-filter')); ?>">Settings</a>
                    </li>
                </ul>
            </div>
            
            <div class="card">
                <h2>Quick Links</h2>
                <p>
                    <a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary">Get Support</a>
                    <a href="https://wbcomdesigns.com/downloads/" target="_blank" class="button button-secondary">Browse Premium Plugins</a>
                    <a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-secondary">Documentation</a>
                </p>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #c3c4c7;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .card h2 {
            margin-top: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Get menu icon
     */
    private function get_menu_icon() {
        $svg = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 2L13.09 8.26L20 9L14 12L15 20L10 17L5 20L6 12L0 9L6.91 8.26L10 2Z" fill="#a7aaad"/>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Get registered plugins from shared system
     */
    private function get_registered_plugins() {
        if (class_exists('Wbcom_Shared_Loader')) {
            $instance = Wbcom_Shared_Loader::get_instance();
            if ($instance) {
                return $instance->get_registered_plugins();
            }
        }
        
        // Fallback: return this plugin's data
        return array($this->plugin_data['slug'] => $this->plugin_data);
    }
    
    /**
     * Check if Wbcom menu exists
     */
    private function wbcom_menu_exists() {
        global $menu;
        
        if (!is_array($menu)) {
            return false;
        }
        
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'wbcom-designs') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enqueue shared CSS and JS assets
     */
    public function enqueue_shared_assets($hook_suffix) {
        // Only load on Wbcom admin pages
        if (!$this->is_wbcom_admin_page($hook_suffix)) {
            return;
        }
        
        // Check if already enqueued to prevent duplicates
        if (wp_style_is('wbcom-shared-admin', 'enqueued') || wp_style_is('wbcom-shared-admin', 'done')) {
            return;
        }
        
        $assets_url = BP_ACTIVITY_FILTER_PLUGIN_URL . 'includes/shared-admin/';
        $version = BP_ACTIVITY_FILTER_VERSION;
        
        // Verify files exist before enqueueing
        $css_file = $this->shared_path . 'wbcom-shared-admin.css';
        $js_file = $this->shared_path . 'wbcom-shared-admin.js';
        
        // Enqueue CSS
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'wbcom-shared-admin',
                $assets_url . 'wbcom-shared-admin.css',
                array(),
                $version
            );
        } else {
            // Fallback: add basic inline styles
            $this->add_fallback_styles();
        }
        
        // Enqueue JS
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'wbcom-shared-admin',
                $assets_url . 'wbcom-shared-admin.js',
                array('jquery'),
                $version,
                true
            );
            
            // Localize script with data
            wp_localize_script('wbcom-shared-admin', 'wbcomShared', array(
                'version' => $version,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wbcom_shared_nonce'),
                'pluginData' => $this->plugin_data,
                'isSharedSystem' => $this->shared_system_loaded,
                'strings' => array(
                    'loading' => __('Loading...', 'bp-activity-filter'),
                    'error' => __('Error loading content.', 'bp-activity-filter'),
                    'success' => __('Settings saved successfully.', 'bp-activity-filter'),
                )
            ));
        }
    }
    
    /**
     * Check if current page is a Wbcom admin page
     */
    private function is_wbcom_admin_page($hook_suffix) {
        // List of Wbcom admin pages
        $wbcom_pages = array(
            'toplevel_page_wbcom-designs',
            'wbcom-designs_page_wbcom-activity-filter',
            'admin_page_wbcom-activity-filter',
            'settings_page_wbcom-activity-filter',
        );
        
        // Check by hook suffix
        if (in_array($hook_suffix, $wbcom_pages)) {
            return true;
        }
        
        // Check by page parameter
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $wbcom_page_slugs = array(
            'wbcom-designs',
            'wbcom-activity-filter',
        );
        
        if (in_array($page, $wbcom_page_slugs)) {
            return true;
        }
        
        // Check if it contains 'wbcom' in the hook suffix
        return strpos($hook_suffix, 'wbcom') !== false;
    }
    
    /**
     * Add fallback styles if CSS file is missing
     */
    private function add_fallback_styles() {
        wp_add_inline_style('wp-admin', '
            .wbcom-shared-dashboard h1 {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .wbcom-version {
                font-size: 14px;
                color: #666;
                background: #f0f0f1;
                padding: 2px 8px;
                border-radius: 12px;
                font-weight: normal;
            }
            
            .nav-tab {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            
            .tab-content {
                background: #fff;
                padding: 20px;
                border: 1px solid #c3c4c7;
                border-radius: 0 0 4px 4px;
                margin-top: -1px;
            }
            
            .card {
                background: #fff;
                border: 1px solid #c3c4c7;
                padding: 20px;
                margin: 20px 0;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
        ');
    }
    
    /**
     * Add integration notice to admin pages
     */
    public function add_integration_notice() {
        if (!$this->shared_system_loaded) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wbcom-activity-filter') === false) {
            return;
        }
        ?>
        <?php
    }
    
    /**
     * Get plugin data
     */
    public function get_plugin_data() {
        return $this->plugin_data;
    }
    
    /**
     * Check if shared system is active
     */
    public function is_shared_system_active() {
        return $this->shared_system_loaded;
    }
    
    /**
     * Get admin URL for plugin settings
     */
    public function get_settings_url() {
        return $this->plugin_data['settings_url'];
    }
    
    /**
     * Get shared assets URL
     */
    public function get_shared_assets_url() {
        return plugin_dir_url($this->shared_path);
    }
    
    /**
     * Check if shared assets are available
     */
    public function are_shared_assets_available() {
        return file_exists($this->shared_path . 'wbcom-shared-admin.css') && 
               file_exists($this->shared_path . 'wbcom-shared-admin.js');
    }
}