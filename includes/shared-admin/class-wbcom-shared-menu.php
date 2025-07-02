<?php
/**
 * Wbcom Shared Menu System
 * 
 * Creates unified admin menu for all Wbcom plugins
 * 
 * @package Wbcom_Shared_Admin
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Wbcom_Shared_Menu {
    
    private static $instance = null;
    private $menu_created = false;
    private $plugins = array();
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Will be initialized by loader
    }
    
    /**
     * Initialize menu system with registered plugins
     */
    public function init($plugins) {
        $this->plugins = $plugins;
        $this->create_main_menu();
        
        // Sort submenus at the end
        add_action('admin_menu', array($this, 'sort_submenus'), 999);
    }
    
    /**
     * Create main Wbcom Designs menu
     */
    private function create_main_menu() {
        if ($this->menu_created) return;
        
        add_menu_page(
            esc_html__('Wbcom Designs', 'bp-activity-filter'),
            esc_html__('Wbcom Designs', 'bp-activity-filter'),
            'manage_options',
            'wbcom-designs',
            array($this, 'dashboard_page'),
            $this->get_menu_icon(),
            58.5
        );
        
        // Add dashboard as first submenu
        add_submenu_page(
            'wbcom-designs',
            esc_html__('Dashboard', 'bp-activity-filter'),
            esc_html__('Dashboard', 'bp-activity-filter'),
            'manage_options',
            'wbcom-designs',
            array($this, 'dashboard_page')
        );
        
        $this->menu_created = true;
    }
    
    /**
     * Dashboard page content
     */
    public function dashboard_page() {
        Wbcom_Shared_Dashboard::instance()->render($this->plugins);
    }
    
    /**
     * Sort submenus by priority
     */
    public function sort_submenus() {
        global $submenu;
        
        if (!isset($submenu['wbcom-designs'])) return;
        
        // Custom sort by priority
        $priorities = array();
        foreach ($this->plugins as $plugin) {
            $menu_slug = $this->extract_menu_slug($plugin['settings_url']);
            $priorities[$menu_slug] = isset($plugin['priority']) ? $plugin['priority'] : 999;
        }
        
        usort($submenu['wbcom-designs'], function($a, $b) use ($priorities) {
            $priority_a = isset($priorities[$a[2]]) ? $priorities[$a[2]] : 999;
            $priority_b = isset($priorities[$b[2]]) ? $priorities[$b[2]] : 999;
            return $priority_a - $priority_b;
        });
    }
    
    /**
     * Extract menu slug from settings URL
     */
    private function extract_menu_slug($settings_url) {
        $parsed = parse_url($settings_url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            return isset($params['page']) ? $params['page'] : '';
        }
        return '';
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
     * Get registered plugins
     */
    public function get_plugins() {
        return $this->plugins;
    }
}