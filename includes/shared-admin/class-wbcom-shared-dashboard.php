<?php
/**
 * Wbcom Shared Dashboard
 * 
 * Unified dashboard for all Wbcom plugins
 * 
 * @package Wbcom_Shared_Admin  
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Wbcom_Shared_Dashboard {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize dashboard
    }
    
    /**
     * Render dashboard page
     */
    public function render($plugins) {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        ?>
        <div class="wrap wbcom-dashboard">
            <h1>
                <span class="wbcom-logo">
                    <?php echo $this->get_wbcom_logo(); ?>
                </span>
                <?php esc_html_e('Wbcom Designs', 'bp-activity-filter'); ?>
                <span class="wbcom-version">v1.0.0</span>
            </h1>
            
            <?php $this->render_admin_notices($plugins); ?>
            
            <div class="wbcom-dashboard-content">
                <div class="wbcom-dashboard-main">
                    <?php $this->render_dashboard_tabs($active_tab, $plugins); ?>
                </div>
                <div class="wbcom-dashboard-sidebar">
                    <?php $this->render_sidebar_widgets($plugins); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render dashboard tabs
     */
    private function render_dashboard_tabs($active_tab, $plugins) {
        $tabs = array(
            'overview' => array(
                'title' => esc_html__('Overview', 'bp-activity-filter'),
                'icon'  => 'dashicons-dashboard',
            ),
            'plugins' => array(
                'title' => esc_html__('Installed Plugins', 'bp-activity-filter'),
                'icon'  => 'dashicons-admin-plugins',
            ),
            'premium' => array(
                'title' => esc_html__('Premium Plugins', 'bp-activity-filter'),
                'icon'  => 'dashicons-star-filled',
            ),
            'themes' => array(
                'title' => esc_html__('Premium Themes', 'bp-activity-filter'),
                'icon'  => 'dashicons-admin-appearance',
            ),
            'news' => array(
                'title' => esc_html__('News & Updates', 'bp-activity-filter'),
                'icon'  => 'dashicons-rss',
            ),
        );
        ?>
        <div class="wbcom-dashboard-tabs">
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_key => $tab_data) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=' . $tab_key)); ?>" 
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($tab_data['icon']); ?>"></span>
                        <?php echo esc_html($tab_data['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'plugins':
                        $this->render_plugins_tab($plugins);
                        break;
                    case 'premium':
                        $this->render_premium_tab();
                        break;
                    case 'themes':
                        $this->render_themes_tab();
                        break;
                    case 'news':
                        $this->render_news_tab();
                        break;
                    case 'overview':
                    default:
                        $this->render_overview_tab($plugins);
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render overview tab
     */
    private function render_overview_tab($plugins) {
        $stats = $this->get_dashboard_stats($plugins);
        ?>
        <div class="wbcom-welcome-panel">
            <h2><?php esc_html_e('Welcome to Wbcom Designs Dashboard', 'bp-activity-filter'); ?></h2>
            <p class="about-description">
                <?php esc_html_e('Your central hub for managing all Wbcom Designs plugins. We create premium WordPress and BuddyPress solutions to enhance your community experience.', 'bp-activity-filter'); ?>
            </p>
            
            <div class="wbcom-stats-overview">
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['total_plugins']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Total Plugins', 'bp-activity-filter'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['active_plugins']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Active Plugins', 'bp-activity-filter'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['wp_version']); ?></div>
                    <div class="stat-label"><?php esc_html_e('WordPress Version', 'bp-activity-filter'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['bp_version']); ?></div>
                    <div class="stat-label"><?php esc_html_e('BuddyPress Version', 'bp-activity-filter'); ?></div>
                </div>
            </div>

            <div class="wbcom-welcome-panel-column-container">
                <div class="wbcom-welcome-panel-column">
                    <h3><?php esc_html_e('Active Plugins', 'bp-activity-filter'); ?></h3>
                    <ul class="wbcom-action-list">
                        <?php foreach ($plugins as $plugin) : ?>
                            <li>
                                <a href="<?php echo esc_url($plugin['settings_url']); ?>" class="button button-secondary">
                                    <span class="dashicons <?php echo esc_attr($plugin['icon']); ?>"></span>
                                    <?php echo esc_html($plugin['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="wbcom-welcome-panel-column">
                    <h3><?php esc_html_e('Quick Links', 'bp-activity-filter'); ?></h3>
                    <ul class="wbcom-action-list">
                        <li><a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary"><?php esc_html_e('Get Support', 'bp-activity-filter'); ?></a></li>
                        <li><a href="https://wbcomdesigns.com/downloads/" target="_blank" class="button button-secondary"><?php esc_html_e('Browse Premium', 'bp-activity-filter'); ?></a></li>
                        <li><a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-secondary"><?php esc_html_e('Documentation', 'bp-activity-filter'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Include all the render methods from the previous dashboard implementation
    // (render_plugins_tab, render_premium_tab, render_themes_tab, etc.)
    // [Copy from the previous complete implementation]
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats($plugins) {
        return array(
            'total_plugins'  => count($plugins),
            'active_plugins' => count($plugins), // All registered plugins are active
            'wp_version'     => get_bloginfo('version'),
            'bp_version'     => function_exists('buddypress') ? buddypress()->version : __('Not Active', 'bp-activity-filter'),
        );
    }
    
    /**
     * Get Wbcom logo SVG
     */
    private function get_wbcom_logo() {
        return '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 4L20.944 13.216L32 14.4L22.4 19.2L24 32L16 27.2L8 32L9.6 19.2L0 14.4L11.056 13.216L16 4Z" fill="#0073aa"/>
        </svg>';
    }
    
    // Additional render methods (copy from previous implementation)...
}