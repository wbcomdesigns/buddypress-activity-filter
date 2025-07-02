<?php
/**
 * Wbcom Shared Dashboard - Complete Implementation
 * 
 * Unified dashboard for all Wbcom plugins with full functionality
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
                <span class="wbcom-version">v<?php echo esc_html($this->get_dashboard_version()); ?></span>
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
     * Render admin notices
     */
    private function render_admin_notices($plugins) {
        $active_count = count($plugins);
        
        if ($active_count === 0) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Welcome to Wbcom Designs!', 'bp-activity-filter'); ?></strong>
                    <?php esc_html_e('No Wbcom plugins are currently active. Activate plugins to see them here.', 'bp-activity-filter'); ?>
                </p>
            </div>
            <?php
        } elseif ($active_count === 1) {
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e('Great start!', 'bp-activity-filter'); ?></strong>
                    <?php esc_html_e('You have 1 Wbcom plugin active. Explore our other plugins to enhance your site further.', 'bp-activity-filter'); ?>
                </p>
            </div>
            <?php
        }
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
                <div class="wbcom-welcome-panel-column">
                    <h3><?php esc_html_e('System Status', 'bp-activity-filter'); ?></h3>
                    <ul class="wbcom-system-status">
                        <li>
                            <span class="status-indicator <?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? 'active' : 'inactive'; ?>"></span>
                            <?php esc_html_e('WordPress Version', 'bp-activity-filter'); ?>
                        </li>
                        <li>
                            <span class="status-indicator <?php echo function_exists('buddypress') ? 'active' : 'inactive'; ?>"></span>
                            <?php esc_html_e('BuddyPress Active', 'bp-activity-filter'); ?>
                        </li>
                        <li>
                            <span class="status-indicator <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'inactive' : 'active'; ?>"></span>
                            <?php esc_html_e('Production Mode', 'bp-activity-filter'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render installed plugins tab
     */
    private function render_plugins_tab($plugins) {
        ?>
        <div class="wbcom-plugins-header">
            <h2><?php esc_html_e('Installed Wbcom Plugins', 'bp-activity-filter'); ?></h2>
            <div class="wbcom-plugins-filters">
                <button type="button" class="button filter-btn active" data-filter="all"><?php esc_html_e('All', 'bp-activity-filter'); ?></button>
                <button type="button" class="button filter-btn" data-filter="active"><?php esc_html_e('Active', 'bp-activity-filter'); ?></button>
            </div>
        </div>

        <div class="wbcom-plugins-grid">
            <?php if (empty($plugins)) : ?>
                <div class="wbcom-no-plugins">
                    <div class="no-plugins-icon">
                        <span class="dashicons dashicons-admin-plugins"></span>
                    </div>
                    <h3><?php esc_html_e('No Wbcom Plugins Found', 'bp-activity-filter'); ?></h3>
                    <p><?php esc_html_e('Looks like you haven\'t installed any Wbcom Designs plugins yet.', 'bp-activity-filter'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=premium')); ?>" class="button button-primary">
                        <?php esc_html_e('Browse Premium Plugins', 'bp-activity-filter'); ?>
                    </a>
                </div>
            <?php else : ?>
                <?php foreach ($plugins as $plugin) : ?>
                    <div class="wbcom-plugin-card plugin-status-active" data-status="active">
                        <div class="plugin-card-top">
                            <div class="plugin-card-header">
                                <h3><?php echo esc_html($plugin['name']); ?></h3>
                                <div class="plugin-status-badge active">
                                    <?php esc_html_e('Active', 'bp-activity-filter'); ?>
                                </div>
                            </div>
                            <p class="plugin-description"><?php echo esc_html(wp_trim_words($plugin['description'], 20)); ?></p>
                            <?php if (!empty($plugin['version'])) : ?>
                                <div class="plugin-version">
                                    <span class="version-label"><?php esc_html_e('Version:', 'bp-activity-filter'); ?></span>
                                    <span class="version-number"><?php echo esc_html($plugin['version']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="plugin-card-bottom">
                            <div class="plugin-actions">
                                <?php if (!empty($plugin['settings_url'])) : ?>
                                    <a href="<?php echo esc_url($plugin['settings_url']); ?>" class="button button-primary">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php esc_html_e('Settings', 'bp-activity-filter'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render premium plugins tab
     */
    private function render_premium_tab() {
        $premium_plugins = $this->get_premium_plugins();
        ?>
        <div class="wbcom-premium-section">
            <div class="wbcom-premium-header">
                <h2><?php esc_html_e('Premium BuddyPress Plugins', 'bp-activity-filter'); ?></h2>
                <p><?php esc_html_e('Enhance your community with these powerful premium plugins designed specifically for BuddyPress.', 'bp-activity-filter'); ?></p>
            </div>
            
            <div class="premium-plugins-list">
                <?php foreach ($premium_plugins as $plugin) : ?>
                    <div class="premium-plugin-item">
                        <div class="plugin-header">
                            <h3><?php echo esc_html($plugin['name']); ?></h3>
                            <?php if (!empty($plugin['price'])) : ?>
                                <div class="plugin-price">
                                    <span class="price-amount"><?php echo esc_html($plugin['price']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="plugin-content">
                            <p class="plugin-description"><?php echo esc_html($plugin['description']); ?></p>
                            <?php if (!empty($plugin['features'])) : ?>
                                <ul class="plugin-features">
                                    <?php foreach (array_slice($plugin['features'], 0, 4) as $feature) : ?>
                                        <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html($feature); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($plugin['features']) > 4) : ?>
                                        <li class="more-features">+ <?php echo count($plugin['features']) - 4; ?> more features</li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="plugin-actions">
                            <a href="<?php echo esc_url($plugin['url']); ?>" target="_blank" rel="noopener" class="button button-primary">
                                <?php esc_html_e('View Plugin', 'bp-activity-filter'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="premium-footer">
                <p class="center-text">
                    <a href="https://wbcomdesigns.com/downloads/" target="_blank" rel="noopener" class="button button-secondary button-large">
                        <?php esc_html_e('Browse All Premium Plugins', 'bp-activity-filter'); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render themes tab
     */
    private function render_themes_tab() {
        $premium_themes = $this->get_premium_themes();
        ?>
        <div class="wbcom-themes-section">
            <div class="wbcom-themes-header">
                <h2><?php esc_html_e('Premium BuddyPress Themes', 'bp-activity-filter'); ?></h2>
                <p><?php esc_html_e('Professional WordPress themes designed specifically for BuddyPress communities with modern designs and advanced features.', 'bp-activity-filter'); ?></p>
            </div>
            
            <div class="premium-themes-list">
                <?php foreach ($premium_themes as $theme) : ?>
                    <div class="premium-theme-item">
                        <div class="theme-header">
                            <h3><?php echo esc_html($theme['name']); ?></h3>
                            <?php if (!empty($theme['price'])) : ?>
                                <div class="theme-price">
                                    <span class="price-amount"><?php echo esc_html($theme['price']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="theme-content">
                            <p class="theme-description"><?php echo esc_html($theme['description']); ?></p>
                            <?php if (!empty($theme['features'])) : ?>
                                <ul class="theme-features">
                                    <?php foreach (array_slice($theme['features'], 0, 4) as $feature) : ?>
                                        <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html($feature); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($theme['features']) > 4) : ?>
                                        <li class="more-features">+ <?php echo count($theme['features']) - 4; ?> more features</li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="theme-actions">
                            <a href="<?php echo esc_url($theme['url']); ?>" target="_blank" rel="noopener" class="button button-primary">
                                <?php esc_html_e('View Theme', 'bp-activity-filter'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                            <?php if (!empty($theme['demo_url'])) : ?>
                                <a href="<?php echo esc_url($theme['demo_url']); ?>" target="_blank" rel="noopener" class="button button-secondary">
                                    <?php esc_html_e('Live Demo', 'bp-activity-filter'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="themes-footer">
                <p class="center-text">
                    <a href="https://wbcomdesigns.com/themes/" target="_blank" rel="noopener" class="button button-secondary button-large">
                        <?php esc_html_e('Browse All Premium Themes', 'bp-activity-filter'); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render news tab
     */
    private function render_news_tab() {
        ?>
        <div class="wbcom-news-section">
            <div class="wbcom-news-header">
                <h2><?php esc_html_e('Latest News from Wbcom Designs', 'bp-activity-filter'); ?></h2>
                <p><?php esc_html_e('Stay updated with the latest plugin releases, updates, and WordPress community news.', 'bp-activity-filter'); ?></p>
            </div>
            
            <div id="wbcom-news-feed" class="wbcom-news-feed">
                <div class="news-loading">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Loading latest news...', 'bp-activity-filter'); ?></p>
                </div>
            </div>

            <div class="news-footer" style="display: none;">
                <p class="center-text">
                    <a href="https://wbcomdesigns.com/blog/" target="_blank" rel="noopener" class="button button-secondary">
                        <?php esc_html_e('Visit Our Blog', 'bp-activity-filter'); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render sidebar widgets
     */
    private function render_sidebar_widgets($plugins) {
        ?>
        <div class="wbcom-sidebar-widget">
            <h3><?php esc_html_e('Quick Stats', 'bp-activity-filter'); ?></h3>
            <?php
            $stats = $this->get_dashboard_stats($plugins);
            ?>
            <ul class="wbcom-stats-list">
                <li>
                    <strong><?php echo esc_html($stats['total_plugins']); ?></strong>
                    <span><?php esc_html_e('Plugins Installed', 'bp-activity-filter'); ?></span>
                </li>
                <li>
                    <strong><?php echo esc_html($stats['active_plugins']); ?></strong>
                    <span><?php esc_html_e('Plugins Active', 'bp-activity-filter'); ?></span>
                </li>
                <li>
                    <strong><?php echo esc_html($stats['wp_version']); ?></strong>
                    <span><?php esc_html_e('WordPress Version', 'bp-activity-filter'); ?></span>
                </li>
            </ul>
        </div>

        <div class="wbcom-sidebar-widget">
            <h3><?php esc_html_e('Need Help?', 'bp-activity-filter'); ?></h3>
            <p><?php esc_html_e('Get expert support for all Wbcom Designs plugins and WordPress development.', 'bp-activity-filter'); ?></p>
            <div class="widget-actions">
                <a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary button-large">
                    <span class="dashicons dashicons-sos"></span>
                    <?php esc_html_e('Get Support', 'bp-activity-filter'); ?>
                </a>
                <a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-link">
                    <?php esc_html_e('Documentation', 'bp-activity-filter'); ?>
                </a>
            </div>
        </div>

        <div class="wbcom-sidebar-widget">
            <h3><?php esc_html_e('Community', 'bp-activity-filter'); ?></h3>
            <p><?php esc_html_e('Join our community and stay connected with updates and discussions.', 'bp-activity-filter'); ?></p>
            <div class="widget-actions">
                <a href="https://wordpress.org/support/plugin/bp-activity-filter/reviews/#new-post" target="_blank" class="button button-secondary">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Leave Review', 'bp-activity-filter'); ?>
                </a>
                <div class="social-links">
                    <a href="https://twitter.com/wbcomdesigns" target="_blank" title="<?php esc_attr_e('Follow on Twitter', 'bp-activity-filter'); ?>">
                        <span class="dashicons dashicons-twitter"></span>
                    </a>
                    <a href="https://www.facebook.com/wbcomdesigns/" target="_blank" title="<?php esc_attr_e('Like on Facebook', 'bp-activity-filter'); ?>">
                        <span class="dashicons dashicons-facebook"></span>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
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
     * Get list of premium plugins
     */
    private function get_premium_plugins() {
        return array(
            array(
                'name'        => 'BuddyPress Hashtags',
                'description' => 'Add Instagram-style hashtag functionality to BuddyPress activities with trending tags, search, and comprehensive analytics.',
                'price'       => '$49',
                'url'         => 'https://wbcomdesigns.com/downloads/buddypress-hashtags/',
                'features'    => array(
                    'Instagram-style hashtag functionality',
                    'Trending hashtags widget and analytics',
                    'Advanced hashtag search and filtering',
                    'Custom hashtag colors and styling',
                    'Hashtag notifications and mentions',
                    'Comprehensive analytics dashboard'
                ),
            ),
            array(
                'name'        => 'BuddyPress Polls',
                'description' => 'Create engaging polls and surveys within your BuddyPress community with real-time results and advanced analytics.',
                'price'       => '$59',
                'url'         => 'https://wbcomdesigns.com/downloads/buddypress-polls/',
                'features'    => array(
                    'Multiple poll types (single/multiple choice)',
                    'Real-time voting results with charts',
                    'Poll scheduling and expiration dates',
                    'Voting restrictions and permissions',
                    'Anonymous voting options',
                    'Export results to CSV/PDF'
                ),
            ),
            array(
                'name'        => 'BuddyPress Quotes',
                'description' => 'Share inspirational quotes with beautiful background templates, custom typography, and social sharing integration.',
                'price'       => '$39',
                'url'         => 'https://wbcomdesigns.com/downloads/buddypress-quotes/',
                'features'    => array(
                    '100+ beautiful background templates',
                    'Custom typography and font options',
                    'Quote categories and tagging system',
                    'Social media sharing integration',
                    'Quote of the day widget',
                    'User-submitted quotes moderation'
                ),
            ),
            array(
                'name'        => 'BuddyPress Status & Reactions',
                'description' => 'Advanced member status system with emoji reactions, mood tracking, and comprehensive engagement analytics.',
                'price'       => '$69',
                'url'         => 'https://wbcomdesigns.com/downloads/buddypress-status-reactions/',
                'features'    => array(
                    'Custom member status indicators',
                    'Emoji reactions system (like, love, laugh)',
                    'Advanced mood tracking and analytics',
                    'Status change notifications',
                    'Custom status messages and icons',
                    'Detailed engagement statistics'
                ),
            ),
            array(
                'name'        => 'BuddyPress Sticky Post',
                'description' => 'Pin important activities and announcements to the top of activity streams with advanced scheduling features.',
                'price'       => '$29',
                'url'         => 'https://wbcomdesigns.com/downloads/buddypress-sticky-post/',
                'features'    => array(
                    'Pin activities to top of streams',
                    'Advanced scheduling and expiration',
                    'Group-specific sticky posts',
                    'Priority levels and ordering',
                    'Bulk sticky post management',
                    'Analytics and engagement tracking'
                ),
            ),
        );
    }
    
    /**
     * Get list of premium themes
     */
    private function get_premium_themes() {
        return array(
            array(
                'name'        => 'Reign Theme',
                'description' => 'Modern BuddyPress community theme with advanced customization options, multiple layouts, and integrated social features.',
                'price'       => '$99',
                'url'         => 'https://wbcomdesigns.com/downloads/reign-buddypress-theme/',
                'demo_url'    => 'https://reign-theme.com/',
                'features'    => array(
                    'Drag & drop page builder integration',
                    'Multiple header and layout options',
                    'Advanced BuddyPress styling',
                    'WooCommerce compatibility',
                    'Mobile-responsive design',
                    'SEO optimized structure'
                ),
            ),
            array(
                'name'        => 'BuddyX Theme',
                'description' => 'Clean and modern BuddyPress theme perfect for communities, with focus on user experience and performance.',
                'price'       => '$79',
                'url'         => 'https://wbcomdesigns.com/downloads/buddyx-theme/',
                'demo_url'    => 'https://buddyx.com/',
                'features'    => array(
                    'Gutenberg block editor support',
                    'Multiple community layouts',
                    'Advanced member directory',
                    'Event management integration',
                    'Learning management system support',
                    'Performance optimized'
                ),
            ),
            array(
                'name'        => 'SocialPress Theme',
                'description' => 'Social networking theme with advanced community features, real-time notifications, and modern design.',
                'price'       => '$89',
                'url'         => 'https://wbcomdesigns.com/downloads/socialpress-theme/',
                'demo_url'    => 'https://socialpress-demo.com/',
                'features'    => array(
                    'Real-time notifications system',
                    'Advanced messaging integration',
                    'Social media style interface',
                    'Custom profile layouts',
                    'Activity stream customization',
                    'Dark mode support'
                ),
            ),
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
    
    /**
     * Get dashboard version
     */
    private function get_dashboard_version() {
        return defined('BP_ACTIVITY_FILTER_VERSION') ? BP_ACTIVITY_FILTER_VERSION : '1.0.0';
    }
}