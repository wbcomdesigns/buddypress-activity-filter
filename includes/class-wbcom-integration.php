<?php
/**
 * Wbcom Integration for BP Activity Filter - With Shared Assets Loading
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
            'name'         => 'BuddyPress Activity Filter', // Plain text to avoid translation issues
            'version'      => BP_ACTIVITY_FILTER_VERSION,
            'settings_url' => admin_url('admin.php?page=wbcom-activity-filter'),
            'icon'         => 'dashicons-filter',
            'priority'     => 5, // High priority as it's a core plugin
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
        
        // Setup admin menu
        add_action('admin_menu', array($this, 'setup_admin_menu'), 10);
        
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
                
                if (!$success && defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('BP Activity Filter: Failed to register with Wbcom shared system');
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BP Activity Filter: Shared system loader not found at: ' . $loader_path);
            }
        }
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
            // Just add our integration styles
            $this->add_integration_styles();
            return;
        }
        
        // Use correct URL calculation
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
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Wbcom: Enqueued CSS from ' . $assets_url . 'wbcom-shared-admin.css');
            }
        } else {
            // Fallback: add basic inline styles
            $this->add_fallback_styles();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Wbcom: CSS file not found at ' . $css_file);
            }
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
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Wbcom: Enqueued JS from ' . $assets_url . 'wbcom-shared-admin.js');
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Wbcom: JS file not found at ' . $js_file);
            }
        }
        
        // Add plugin-specific styles for better integration
        $this->add_integration_styles();
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
            /* Wbcom Shared Admin Fallback Styles */
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
            
            .wbcom-dashboard-content {
                display: flex;
                gap: 20px;
                margin-top: 20px;
            }
            
            .wbcom-dashboard-main {
                flex: 1;
            }
            
            .wbcom-dashboard-sidebar {
                width: 300px;
            }
            
            .wbcom-welcome-panel {
                background: #fff;
                border: 1px solid #c3c4c7;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 4px;
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
            
            @media (max-width: 768px) {
                .wbcom-dashboard-content {
                    flex-direction: column;
                }
                .wbcom-dashboard-sidebar {
                    width: 100%;
                }
            }
        ');
    }
    
    /**
     * Add integration-specific styles
     */
    private function add_integration_styles() {
        wp_add_inline_style('wp-admin', '
            .wbcom-integration-notice {
                background: #e7f3ff;
                border-left: 4px solid #0073aa;
                padding: 12px;
                margin: 15px 0;
            }
            
            .wbcom-integration-notice p {
                margin: 0;
                color: #0073aa;
            }
            
            .bp-activity-filter-admin .nav-tab {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            
            .bp-activity-filter-admin .wbcom-version {
                font-size: 12px;
                background: #0073aa;
                color: #fff;
                padding: 2px 6px;
                border-radius: 10px;
                margin-left: 8px;
            }
            
            .wbcom-debug-info {
                position: fixed;
                bottom: 10px;
                right: 10px;
                background: rgba(0,0,0,0.8);
                color: #fff;
                padding: 10px;
                font-size: 11px;
                border-radius: 4px;
                z-index: 9999;
                max-width: 250px;
                display: none;
            }
            
            .wbcom-debug-info.show {
                display: block;
            }
            
            .wbcom-shared-loading {
                text-align: center;
                padding: 40px;
                color: #666;
            }
            
            .wbcom-shared-loading .spinner {
                visibility: visible;
                float: none;
                margin: 0 auto 10px;
            }
        ');
    }
    
    /**
     * Setup admin menu (shared or fallback)
     */
    public function setup_admin_menu() {
        if ($this->shared_system_loaded && $this->wbcom_menu_exists()) {
            // Add submenu to shared Wbcom menu
            $this->add_submenu_to_shared();
        } else {
            // Fallback: create standalone menu
            $this->create_standalone_menu();
        }
    }
    
    /**
     * Add submenu to shared Wbcom menu
     */
    private function add_submenu_to_shared() {
        add_submenu_page(
            'wbcom-designs',
            $this->plugin_data['name'],
            'Activity Filter', // Use plain text
            'manage_options',
            'wbcom-activity-filter',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Create standalone menu if shared system not available
     */
    private function create_standalone_menu() {
        add_menu_page(
            $this->plugin_data['name'],
            'Activity Filter', // Use plain text
            'manage_options',
            'wbcom-activity-filter',
            array($this, 'render_settings_page'),
            $this->plugin_data['icon'],
            59
        );
        
        // Add submenu for consistency
        add_submenu_page(
            'wbcom-activity-filter',
            'Settings', // Use plain text
            'Settings',
            'manage_options',
            'wbcom-activity-filter',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Add debug info if in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->add_debug_info();
        }
        
        // Load admin class and render
        if (class_exists('BP_Activity_Filter_Admin')) {
            $admin = BP_Activity_Filter_Admin::instance();
            $admin->render_settings_page();
        } else {
            $this->render_fallback_page();
        }
    }
    
    /**
     * Add debug information to page
     */
    private function add_debug_info() {
        ?>
        <div class="wbcom-debug-info" id="wbcom-debug-info">
            <strong>Debug Info:</strong><br>
            Shared System: <?php echo $this->shared_system_loaded ? 'LOADED' : 'NOT LOADED'; ?><br>
            CSS File: <?php echo file_exists($this->shared_path . 'wbcom-shared-admin.css') ? 'EXISTS' : 'MISSING'; ?><br>
            JS File: <?php echo file_exists($this->shared_path . 'wbcom-shared-admin.js') ? 'EXISTS' : 'MISSING'; ?><br>
            Shared Path: <?php echo is_dir($this->shared_path) ? 'EXISTS' : 'MISSING'; ?><br>
            <button type="button" onclick="this.parentNode.style.display='none'" style="float: right; background: none; border: none; color: #fff; cursor: pointer;">×</button>
        </div>
        
        <script>
        // Show debug info for 10 seconds
        setTimeout(function() {
            var debug = document.getElementById('wbcom-debug-info');
            if (debug) {
                debug.classList.add('show');
                setTimeout(function() {
                    debug.classList.remove('show');
                }, 10000);
            }
        }, 1000);
        </script>
        <?php
    }
    
    /**
     * Render fallback page if admin class not found
     */
    private function render_fallback_page() {
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-filter" style="margin-right: 10px; color: #0073aa;"></span>
                <?php echo esc_html($this->plugin_data['name']); ?>
            </h1>
            <div class="notice notice-error">
                <p>
                    <strong><?php esc_html_e('Error:', 'bp-activity-filter'); ?></strong>
                    <?php esc_html_e('Admin class not found. Please ensure the plugin is properly installed and BuddyPress is active.', 'bp-activity-filter'); ?>
                </p>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Plugin Information', 'bp-activity-filter'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Version:', 'bp-activity-filter'); ?></th>
                        <td><?php echo esc_html($this->plugin_data['version']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Status:', 'bp-activity-filter'); ?></th>
                        <td>
                            <?php if (function_exists('buddypress')) : ?>
                                <span style="color: #00a32a;">✓ <?php esc_html_e('BuddyPress Active', 'bp-activity-filter'); ?></span>
                            <?php else : ?>
                                <span style="color: #d63638;">✗ <?php esc_html_e('BuddyPress Required', 'bp-activity-filter'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Shared System:', 'bp-activity-filter'); ?></th>
                        <td>
                            <?php if ($this->shared_system_loaded) : ?>
                                <span style="color: #00a32a;">✓ <?php esc_html_e('Loaded', 'bp-activity-filter'); ?></span>
                            <?php else : ?>
                                <span style="color: #dba617;">⚠ <?php esc_html_e('Standalone Mode', 'bp-activity-filter'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Shared Assets:', 'bp-activity-filter'); ?></th>
                        <td>
                            CSS: <?php echo file_exists($this->shared_path . 'wbcom-shared-admin.css') ? '✓' : '✗'; ?><br>
                            JS: <?php echo file_exists($this->shared_path . 'wbcom-shared-admin.js') ? '✓' : '✗'; ?>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <a href="<?php echo esc_url($this->plugin_data['docs_url']); ?>" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-book"></span>
                        <?php esc_html_e('Documentation', 'bp-activity-filter'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url($this->plugin_data['support_url']); ?>" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-sos"></span>
                        <?php esc_html_e('Get Support', 'bp-activity-filter'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
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
        <div class="wbcom-integration-notice">
            <p>
                <strong><?php esc_html_e('Wbcom Integration Active', 'bp-activity-filter'); ?></strong> - 
                <?php 
                printf(
                    esc_html__('This plugin is integrated with the Wbcom Designs dashboard. %s', 'bp-activity-filter'),
                    '<a href="' . esc_url(admin_url('admin.php?page=wbcom-designs')) . '">' . esc_html__('View Dashboard', 'bp-activity-filter') . '</a>'
                );
                ?>
            </p>
        </div>
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