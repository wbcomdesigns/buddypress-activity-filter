<?php
/**
 * Wbcom Shared Admin System - Main Coordinator
 * 
 * @package Wbcom_Shared_Admin
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

class Wbcom_Shared_Loader {
    
    const VERSION = '2.0.0';
    const GLOBAL_KEY = 'wbcom_shared_system';
    
    private static $instance = null;
    private $registered_plugins = array();
    private $is_primary_loader = false;
    private $loaded_from_plugin = '';
    
    /**
     * Register a plugin with the shared system
     */
    public static function register_plugin($plugin_data) {
        // Validate required plugin data
        $plugin_data = self::validate_plugin_data($plugin_data);
        
        // Get or create shared system instance
        $shared_system = self::get_shared_instance($plugin_data['slug']);
        
        // Register the plugin
        $shared_system->add_plugin($plugin_data);
        
        return $shared_system;
    }
    
    /**
     * Get shared system instance (singleton across all plugins)
     */
    private static function get_shared_instance($plugin_slug) {
        // Check if instance already exists globally
        if (isset($GLOBALS[self::GLOBAL_KEY])) {
            return $GLOBALS[self::GLOBAL_KEY];
        }
        
        // Create new instance
        $instance = new self();
        $instance->is_primary_loader = true;
        $instance->loaded_from_plugin = $plugin_slug;
        $instance->init_shared_system();
        
        // Store globally for other plugins
        $GLOBALS[self::GLOBAL_KEY] = $instance;
        
        return $instance;
    }
    
    /**
     * Validate plugin data
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
        );
        
        $plugin_data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($plugin_data['slug']) || empty($plugin_data['name'])) {
            return false;
        }
        
        // Sanitize data
        $plugin_data['slug'] = sanitize_key($plugin_data['slug']);
        $plugin_data['name'] = sanitize_text_field($plugin_data['name']);
        $plugin_data['version'] = sanitize_text_field($plugin_data['version']);
        $plugin_data['description'] = sanitize_text_field($plugin_data['description']);
        $plugin_data['priority'] = absint($plugin_data['priority']);
        
        return $plugin_data;
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
        
        // Initialize dashboard
        add_action('admin_menu', array($this, 'init_dashboard'), 5);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_shared_assets'));
    }
    
    /**
     * Load shared system classes
     */
    private function load_shared_classes() {
        $base_path = dirname(__FILE__);
        
        $classes = array(
            'class-wbcom-shared-dashboard.php'
        );
        
        foreach ($classes as $class_file) {
            $file_path = $base_path . '/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize dashboard
     */
    public function init_dashboard() {
        if (!class_exists('Wbcom_Shared_Dashboard')) {
            return;
        }
        
        new Wbcom_Shared_Dashboard($this->registered_plugins);
    }
    
    /**
     * Enqueue shared assets
     */
    public function enqueue_shared_assets($hook_suffix) {
        // Only load on Wbcom admin pages
        if (strpos($hook_suffix, 'wbcom') === false) {
            return;
        }
        
        $base_url = plugin_dir_url(__FILE__);
        
        // Enqueue CSS
        wp_enqueue_style(
            'wbcom-shared-admin',
            $base_url . 'wbcom-shared-admin.css',
            array(),
            self::VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'wbcom-shared-admin',
            $base_url . 'wbcom-shared-admin.js',
            array('jquery'),
            self::VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('wbcom-shared-admin', 'wbcomShared', array(
            'version' => self::VERSION,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wbcom_shared_nonce'),
            'plugins' => $this->registered_plugins
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
}