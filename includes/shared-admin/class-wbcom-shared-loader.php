<?php
/**
 * Wbcom Shared Admin System - Main Coordinator (Fixed for Duplicates)
 * 
 * @package Wbcom_Shared_Admin
 * @version 2.1.2
 */

if (!defined('ABSPATH')) exit;

class Wbcom_Shared_Loader {
    
    const VERSION = '2.1.2';
    const GLOBAL_KEY = 'wbcom_shared_system';
    
    private static $instance = null;
    private $registered_plugins = array();
    private $is_primary_loader = false;
    private $loaded_from_plugin = '';
    private $shared_path = '';
    
    /**
     * Easy registration method - call this from plugin main file
     * 
     * @param array $plugin_data Plugin information
     * @return bool Success status
     */
    public static function register_plugin($plugin_data) {
        // Validate required plugin data
        $plugin_data = self::validate_plugin_data($plugin_data);
        if (!$plugin_data) {
            return false;
        }
        
        // Get or create shared system instance
        $shared_system = self::get_shared_instance($plugin_data);
        
        // Register the plugin
        $shared_system->add_plugin($plugin_data);
        
        return true;
    }
    
    /**
     * Get shared system instance (singleton across all plugins)
     */
    private static function get_shared_instance($plugin_data) {
        // Check if instance already exists globally
        if (isset($GLOBALS[self::GLOBAL_KEY])) {
            return $GLOBALS[self::GLOBAL_KEY];
        }
        
        // Create new instance
        $instance = new self();
        $instance->is_primary_loader = true;
        $instance->loaded_from_plugin = $plugin_data['slug'];
        $instance->shared_path = $plugin_data['shared_path'];
        $instance->init_shared_system();
        
        // Store globally for other plugins
        $GLOBALS[self::GLOBAL_KEY] = $instance;
        
        return $instance;
    }
    
    /**
     * Validate and sanitize plugin data
     */
    private static function validate_plugin_data($data) {
        $defaults = array(
            'slug'         => '',
            'name'         => '',
            'version'      => '1.0.0',
            'settings_url' => '',
            'icon'         => 'dashicons-admin-generic',
            'priority'     => 10,
            'description'  => '',
            'status'       => 'active',
            'has_premium'  => false,
            'docs_url'     => '',
            'support_url'  => '',
            'shared_path'  => '', // Path to shared-admin folder
        );
        
        $plugin_data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($plugin_data['slug']) || empty($plugin_data['name'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Wbcom Shared: Plugin registration failed - missing slug or name');
            }
            return false;
        }
        
        // Auto-detect shared path if not provided
        if (empty($plugin_data['shared_path'])) {
            $plugin_data['shared_path'] = self::detect_shared_path();
        }
        
        // Sanitize data
        $plugin_data['slug'] = sanitize_key($plugin_data['slug']);
        $plugin_data['name'] = sanitize_text_field($plugin_data['name']);
        $plugin_data['version'] = sanitize_text_field($plugin_data['version']);
        $plugin_data['description'] = sanitize_text_field($plugin_data['description']);
        $plugin_data['priority'] = absint($plugin_data['priority']);
        $plugin_data['has_premium'] = (bool) $plugin_data['has_premium'];
        
        return $plugin_data;
    }
    
    /**
     * Auto-detect shared folder path
     */
    private static function detect_shared_path() {
        // Get calling file path
        $backtrace = debug_backtrace();
        $calling_file = '';
        
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'shared-admin') === false) {
                $calling_file = $trace['file'];
                break;
            }
        }
        
        if (empty($calling_file)) {
            return '';
        }
        
        // Look for shared-admin folder
        $plugin_dir = dirname($calling_file);
        $possible_paths = array(
            $plugin_dir . '/includes/shared-admin/',
            $plugin_dir . '/shared-admin/',
            $plugin_dir . '/admin/shared-admin/',
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path . 'class-wbcom-shared-loader.php')) {
                return $path;
            }
        }
        
        return '';
    }
    
    /**
     * Add plugin to registry
     */
    public function add_plugin($plugin_data) {
        $this->registered_plugins[$plugin_data['slug']] = $plugin_data;
        
        // Sort by priority
        uasort($this->registered_plugins, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Initialize the shared admin system
     */
    private function init_shared_system() {
        if (!$this->is_primary_loader) return;
        
        // Load required classes
        $this->load_shared_classes();
        
        // Initialize main menu and dashboard
        add_action('admin_menu', array($this, 'create_main_menu'), 5);
        add_action('admin_menu', array($this, 'add_plugin_submenus'), 10);
        
        // Version check and conflict resolution
        add_action('admin_init', array($this, 'check_version_conflicts'));
    }
    
    /**
     * Load shared system classes
     */
    private function load_shared_classes() {
        $base_path = $this->shared_path;
        
        if (empty($base_path) || !is_dir($base_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Wbcom Shared: Could not find shared-admin directory at: ' . $base_path);
            }
            return;
        }
        
        $classes = array(
            'class-wbcom-shared-dashboard.php'
        );
        
        foreach ($classes as $class_file) {
            $file_path = $base_path . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Wbcom Shared: Could not load class file: ' . $file_path);
                }
            }
        }
    }
    
    /**
     * Create main Wbcom Designs menu
     */
    public function create_main_menu() {
        // Check if menu already exists
        if ($this->menu_exists()) {
            return;
        }
        
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
    
    /**
     * Add submenu for registered plugins - FIXED FOR DUPLICATES
     */
    public function add_plugin_submenus() {
        foreach ($this->registered_plugins as $plugin) {
            if ($plugin['status'] !== 'active' || empty($plugin['settings_url'])) {
                continue;
            }
            
            $menu_slug = $this->extract_menu_slug($plugin['settings_url']);
            
            if (empty($menu_slug)) {
                continue;
            }
            
            // Only add if submenu doesn't already exist
            if (!$this->submenu_exists($menu_slug)) {
                add_submenu_page(
                    'wbcom-designs',
                    $plugin['name'],
                    $plugin['name'],
                    'manage_options',
                    $menu_slug,
                    array($this, 'render_plugin_page') // Use our callback instead of __return_null
                );
            }
        }
    }
    
    /**
     * Render plugin page - Routes to the appropriate plugin callback
     */
    public function render_plugin_page() {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Find the plugin that matches this page
        foreach ($this->registered_plugins as $plugin) {
            $plugin_slug = $this->extract_menu_slug($plugin['settings_url']);
            
            if ($plugin_slug === $current_page) {
                // Try to call the plugin's render method
                $this->call_plugin_render_method($plugin);
                return;
            }
        }
        
        // Fallback if no plugin found
        $this->render_plugin_not_found();
    }
    
    /**
     * Call the plugin's render method
     */
    private function call_plugin_render_method($plugin) {
        $plugin_slug = $plugin['slug'];
        
        // Try different possible function names and class methods
        $possible_callbacks = array(
            // Function-based callbacks
            $plugin_slug . '_render_admin_page',
            str_replace('-', '_', $plugin_slug) . '_render_admin_page',
            
            // Class-based callbacks (most common pattern)
            array('BP_Activity_Filter', 'render_admin_page'),
            array(str_replace('-', '_', $plugin_slug), 'render_admin_page'),
            
            // Instance-based callbacks
            array($plugin_slug . '_instance', 'render_admin_page'),
        );
        
        // Try to find and call the callback
        foreach ($possible_callbacks as $callback) {
            if (is_callable($callback)) {
                call_user_func($callback);
                return;
            }
        }
        
        // Try to get plugin instance and call render method
        if (function_exists('bp_activity_filter')) {
            $instance = bp_activity_filter();
            if ($instance && method_exists($instance, 'render_admin_page')) {
                $instance->render_admin_page();
                return;
            }
        }
        
        // Final fallback
        $this->render_plugin_fallback($plugin);
    }
    
    /**
     * Render fallback page for plugins
     */
    private function render_plugin_fallback($plugin) {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($plugin['name']); ?></h1>
            <div class="notice notice-info">
                <p>
                    <strong>Plugin Loaded Successfully!</strong><br>
                    The plugin is active but the admin interface is loading. Please check that all plugin files are properly installed.
                </p>
            </div>
            
            <div class="card">
                <h2>Plugin Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Version:</th>
                        <td><?php echo esc_html($plugin['version']); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td><span style="color: #00a32a;">✓ Active</span></td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td><?php echo esc_html($plugin['description']); ?></td>
                    </tr>
                </table>
                
                <?php if (!empty($plugin['docs_url']) || !empty($plugin['support_url'])) : ?>
                    <p>
                        <?php if (!empty($plugin['docs_url'])) : ?>
                            <a href="<?php echo esc_url($plugin['docs_url']); ?>" target="_blank" class="button button-secondary">
                                <span class="dashicons dashicons-book"></span>
                                Documentation
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($plugin['support_url'])) : ?>
                            <a href="<?php echo esc_url($plugin['support_url']); ?>" target="_blank" class="button button-secondary">
                                <span class="dashicons dashicons-sos"></span>
                                Get Support
                            </a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render page when plugin not found
     */
    private function render_plugin_not_found() {
        ?>
        <div class="wrap">
            <h1>Plugin Not Found</h1>
            <div class="notice notice-error">
                <p>The requested plugin page could not be found or is not properly registered.</p>
            </div>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs')); ?>" class="button button-primary">← Back to Dashboard</a></p>
        </div>
        <?php
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        try {
            if (class_exists('Wbcom_Shared_Dashboard')) {
                $dashboard = new Wbcom_Shared_Dashboard($this->registered_plugins);
                $dashboard->render_dashboard();
            } else {
                $this->render_fallback_dashboard();
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Wbcom Shared Dashboard Error: ' . $e->getMessage());
            }
            $this->render_fallback_dashboard();
        }
    }
    
    /**
     * Render fallback dashboard if main dashboard fails
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
                <?php if (!empty($this->registered_plugins)) : ?>
                    <ul>
                        <?php foreach ($this->registered_plugins as $plugin) : ?>
                            <li>
                                <strong><?php echo esc_html($plugin['name']); ?></strong> 
                                (v<?php echo esc_html($plugin['version']); ?>)
                                <?php if (!empty($plugin['settings_url'])) : ?>
                                    - <a href="<?php echo esc_url($plugin['settings_url']); ?>">Settings</a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>No plugins registered yet.</p>
                <?php endif; ?>
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
     * Check for version conflicts between shared systems
     */
    public function check_version_conflicts() {
        // Check if multiple versions are loaded
        if (defined('WBCOM_SHARED_VERSION') && WBCOM_SHARED_VERSION !== self::VERSION) {
            add_action('admin_notices', array($this, 'version_conflict_notice'));
        } else {
            define('WBCOM_SHARED_VERSION', self::VERSION);
        }
    }
    
    /**
     * Display version conflict notice
     */
    public function version_conflict_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>Wbcom Shared System Conflict</strong><br>
                Multiple versions of the Wbcom shared system detected. Please update all Wbcom plugins to ensure compatibility.
            </p>
        </div>
        <?php
    }
    
    /**
     * Utility methods
     */
    private function menu_exists() {
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
    
    private function submenu_exists($menu_slug) {
        global $submenu;
        
        if (!isset($submenu['wbcom-designs'])) {
            return false;
        }
        
        foreach ($submenu['wbcom-designs'] as $item) {
            if (isset($item[2]) && $item[2] === $menu_slug) {
                return true;
            }
        }
        
        return false;
    }
    
    private function extract_menu_slug($settings_url) {
        $parsed = parse_url($settings_url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            return isset($params['page']) ? $params['page'] : '';
        }
        return '';
    }
    
    private function get_menu_icon() {
        $svg = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 2L13.09 8.26L20 9L14 12L15 20L10 17L5 20L6 12L0 9L6.91 8.26L10 2Z" fill="#a7aaad"/>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Static helper method for easy integration
     */
    public static function quick_register($plugin_name, $plugin_slug, $settings_page_slug, $plugin_version = '1.0.0') {
        return self::register_plugin(array(
            'slug'         => $plugin_slug,
            'name'         => $plugin_name,
            'version'      => $plugin_version,
            'settings_url' => admin_url('admin.php?page=' . $settings_page_slug),
            'icon'         => 'dashicons-admin-generic',
            'priority'     => 10,
            'status'       => 'active',
        ));
    }
    
    /**
     * Get all registered plugins
     */
    public function get_registered_plugins() {
        return $this->registered_plugins;
    }
    
    /**
     * Get active plugins only
     */
    public function get_active_plugins() {
        return array_filter($this->registered_plugins, function($plugin) {
            return $plugin['status'] === 'active';
        });
    }
    
    /**
     * Get instance for debugging
     */
    public static function get_instance() {
        return isset($GLOBALS[self::GLOBAL_KEY]) ? $GLOBALS[self::GLOBAL_KEY] : null;
    }
    
    /**
     * Debug information
     */
    public function get_debug_info() {
        return array(
            'version' => self::VERSION,
            'is_primary_loader' => $this->is_primary_loader,
            'loaded_from_plugin' => $this->loaded_from_plugin,
            'shared_path' => $this->shared_path,
            'registered_plugins_count' => count($this->registered_plugins),
            'registered_plugins' => array_keys($this->registered_plugins),
            'dashboard_class_exists' => class_exists('Wbcom_Shared_Dashboard'),
            'shared_path_exists' => is_dir($this->shared_path),
        );
    }
}