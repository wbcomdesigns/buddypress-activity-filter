<?php
/**
 * Wbcom Shared Admin Loader
 * 
 * Ensures only one instance loads across all Wbcom plugins
 * 
 * @package Wbcom_Shared_Admin
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Wbcom_Shared_Loader {
    
    const VERSION = '1.0.0';
    const GLOBAL_KEY = 'wbcom_shared_admin_instance';
    
    private static $instance = null;
    private $is_primary_loader = false;
    private $registered_plugins = array();
    private $loaded_from_plugin = '';
    
    /**
     * Initialize shared system
     */
    public static function init($plugin_data) {
        // Check if already loaded by another plugin
        if (isset($GLOBALS[self::GLOBAL_KEY])) {
            // Just register this plugin with existing system
            $GLOBALS[self::GLOBAL_KEY]->register_plugin($plugin_data);
            return $GLOBALS[self::GLOBAL_KEY];
        }
        
        // We're the first - create and load the system
        $instance = new self();
        $instance->is_primary_loader = true;
        $instance->loaded_from_plugin = $plugin_data['slug'];
        $instance->register_plugin($plugin_data);
        $instance->load_shared_system();
        
        // Store globally for other plugins
        $GLOBALS[self::GLOBAL_KEY] = $instance;
        
        return $instance;
    }
    
    /**
     * Register a plugin with the shared system
     */
    public function register_plugin($plugin_data) {
        $this->registered_plugins[$plugin_data['slug']] = $plugin_data;
        
        // If system already loaded, integrate immediately
        if ($this->is_primary_loader && class_exists('Wbcom_Shared_Menu')) {
            do_action('wbcom_shared_plugin_registered', $plugin_data);
        }
    }
    
    /**
     * Load the shared admin system
     */
    private function load_shared_system() {
        if (!$this->is_primary_loader) return;
        
        $base_path = dirname(__FILE__);
        
        // Load core classes
        require_once $base_path . '/class-wbcom-shared-menu.php';
        require_once $base_path . '/class-wbcom-shared-dashboard.php';
        
        // Initialize systems
        add_action('admin_menu', function() {
            Wbcom_Shared_Menu::instance()->init($this->registered_plugins);
        }, 5);
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_shared_assets'));
        
        // Debug info
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_notices', array($this, 'debug_notice'), 999);
        }
    }
    
    /**
     * Enqueue shared CSS and JS
     */
    public function enqueue_shared_assets($hook_suffix) {
        // Only load on Wbcom admin pages
        if (strpos($hook_suffix, 'wbcom') === false) return;
        
        $base_url = plugin_dir_url(__FILE__);
        
        wp_enqueue_style(
            'wbcom-shared-admin',
            $base_url . 'wbcom-shared-admin.css',
            array(),
            self::VERSION
        );
        
        wp_enqueue_script(
            'wbcom-shared-admin',
            $base_url . 'wbcom-shared-admin.js',
            array('jquery'),
            self::VERSION,
            true
        );
        
        // Localize for JS
        wp_localize_script('wbcom-shared-admin', 'wbcomShared', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wbcom_shared_nonce'),
            'plugins' => $this->registered_plugins
        ));
    }
    
    /**
     * Debug notice for development
     */
    public function debug_notice() {
        if (!current_user_can('manage_options')) return;
        
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wbcom') === false) return;
        
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Wbcom Debug:</strong> Shared system loaded by: ' . esc_html($this->loaded_from_plugin);
        echo ' | Registered plugins: ' . count($this->registered_plugins) . '</p>';
        echo '</div>';
    }
    
    /**
     * Get all registered plugins
     */
    public function get_registered_plugins() {
        return $this->registered_plugins;
    }
    
    /**
     * Check if this is the primary loader
     */
    public function is_primary() {
        return $this->is_primary_loader;
    }
}