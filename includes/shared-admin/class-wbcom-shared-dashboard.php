<?php
/**
 * Wbcom Shared Dashboard - Universal Dashboard for All Plugins
 * 
 * @package Wbcom_Shared_Admin
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

class Wbcom_Shared_Dashboard {
    
    private $registered_plugins = array();
    private $menu_created = false;
    
    /**
     * Constructor
     */
    public function __construct($plugins = array()) {
        $this->registered_plugins = $plugins;
        $this->init();
    }
    
    /**
     * Initialize dashboard
     */
    private function init() {
        add_action('admin_menu', array($this, 'create_main_menu'), 5);
        add_action('admin_menu', array($this, 'add_plugin_submenus'), 10);
    }
    
    /**
     * Create main Wbcom Designs menu
     */
    public function create_main_menu() {
        if ($this->menu_created) return;
        
        add_menu_page(
            esc_html__('Wbcom Designs', 'wbcom-shared'),
            esc_html__('Wbcom Designs', 'wbcom-shared'),
            'manage_options',
            'wbcom-designs',
            array($this, 'render_dashboard'),
            $this->get_menu_icon(),
            58.5
        );
        
        // Add dashboard as first submenu
        add_submenu_page(
            'wbcom-designs',
            esc_html__('Dashboard', 'wbcom-shared'),
            esc_html__('Dashboard', 'wbcom-shared'),
            'manage_options',
            'wbcom-designs',
            array($this, 'render_dashboard')
        );
        
        $this->menu_created = true;
    }
    
    /**
     * Add submenu for each registered plugin
     */
    public function add_plugin_submenus() {
        foreach ($this->registered_plugins as $plugin) {
            if ($plugin['status'] !== 'active') continue;
            
            $menu_slug = $this->extract_menu_slug($plugin['settings_url']);
            
            if (empty($menu_slug)) continue;
            
            add_submenu_page(
                'wbcom-designs',
                $plugin['name'],
                $plugin['name'],
                'manage_options',
                $menu_slug,
                '__return_null' // Plugin handles its own rendering
            );
        }
    }
    
    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        ?>
        <div class="wrap wbcom-shared-dashboard">
            <h1>
                🌟
                <?php esc_html_e('Wbcom Designs', 'wbcom-shared'); ?>
                <span class="wbcom-version">v<?php echo esc_html(Wbcom_Shared_Loader::VERSION); ?></span>
            </h1>
            
            <?php $this->render_admin_notices(); ?>
            
            <div class="wbcom-dashboard-content">
                <div class="wbcom-dashboard-main">
                    <?php $this->render_dashboard_tabs($active_tab); ?>
                </div>
                <div class="wbcom-dashboard-sidebar">
                    <?php $this->render_sidebar_widgets(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render dashboard tabs
     */
    private function render_dashboard_tabs($active_tab) {
        $tabs = array(
            'overview' => array(
                'title' => esc_html__('Overview', 'wbcom-shared'),
                'icon'  => 'dashicons-dashboard',
            ),
            'plugins' => array(
                'title' => esc_html__('Installed Plugins', 'wbcom-shared'),
                'icon'  => 'dashicons-admin-plugins',
            ),
            'premium' => array(
                'title' => esc_html__('Premium Plugins', 'wbcom-shared'),
                'icon'  => 'dashicons-star-filled',
            ),
            'themes' => array(
                'title' => esc_html__('Premium Themes', 'wbcom-shared'),
                'icon'  => 'dashicons-admin-appearance',
            ),
            'news' => array(
                'title' => esc_html__('News & Updates', 'wbcom-shared'),
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
                        $this->render_plugins_tab();
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
                        $this->render_overview_tab();
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
    private function render_overview_tab() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wbcom-welcome-panel">
            <h2><?php esc_html_e('Welcome to Wbcom Designs Dashboard', 'wbcom-shared'); ?></h2>
            <p class="about-description">
                <?php esc_html_e('Your central hub for managing all Wbcom Designs plugins. We create premium WordPress and BuddyPress solutions to enhance your community experience.', 'wbcom-shared'); ?>
            </p>
            
            <div class="wbcom-stats-overview">
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['total_plugins']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Total Plugins', 'wbcom-shared'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['active_plugins']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Active Plugins', 'wbcom-shared'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['wp_version']); ?></div>
                    <div class="stat-label"><?php esc_html_e('WordPress Version', 'wbcom-shared'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($stats['bp_version']); ?></div>
                    <div class="stat-label"><?php esc_html_e('BuddyPress Version', 'wbcom-shared'); ?></div>
                </div>
            </div>

            <div class="wbcom-welcome-panel-columns">
                <div class="wbcom-welcome-panel-column">
                    <h3><?php esc_html_e('Active Plugins', 'wbcom-shared'); ?></h3>
                    <ul class="wbcom-action-list">
                        <?php foreach ($this->get_active_plugins() as $plugin) : ?>
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
                    <h3><?php esc_html_e('Quick Links', 'wbcom-shared'); ?></h3>
                    <ul class="wbcom-action-list">
                        <li><a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary"><?php esc_html_e('Get Support', 'wbcom-shared'); ?></a></li>
                        <li><a href="https://wbcomdesigns.com/downloads/" target="_blank" class="button button-secondary"><?php esc_html_e('Browse Premium', 'wbcom-shared'); ?></a></li>
                        <li><a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-secondary"><?php esc_html_e('Documentation', 'wbcom-shared'); ?></a></li>
                    </ul>
                </div>
                
                <div class="wbcom-welcome-panel-column">
                    <h3><?php esc_html_e('System Status', 'wbcom-shared'); ?></h3>
                    <ul class="wbcom-system-status">
                        <li>
                            <span class="status-indicator <?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? 'active' : 'inactive'; ?>"></span>
                            <?php esc_html_e('WordPress Version', 'wbcom-shared'); ?>
                        </li>
                        <li>
                            <span class="status-indicator <?php echo function_exists('buddypress') ? 'active' : 'inactive'; ?>"></span>
                            <?php esc_html_e('BuddyPress Active', 'wbcom-shared'); ?>
                        </li>
                        <li>
                            <span class="status-indicator <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'inactive' : 'active'; ?>"></span>
                            <?php esc_html_e('Production Mode', 'wbcom-shared'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render plugins tab
     */
    private function render_plugins_tab() {
        ?>
        <div class="wbcom-plugins-header">
            <h2><?php esc_html_e('Installed Wbcom Plugins', 'wbcom-shared'); ?></h2>
        </div>

        <div class="wbcom-plugins-grid">
            <?php if (empty($this->registered_plugins)) : ?>
                <div class="wbcom-no-plugins">
                    <div class="no-plugins-icon">
                        <span class="dashicons dashicons-admin-plugins"></span>
                    </div>
                    <h3><?php esc_html_e('No Wbcom Plugins Found', 'wbcom-shared'); ?></h3>
                    <p><?php esc_html_e('Looks like you haven\'t installed any Wbcom Designs plugins yet.', 'wbcom-shared'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=premium')); ?>" class="button button-primary">
                        <?php esc_html_e('Browse Premium Plugins', 'wbcom-shared'); ?>
                    </a>
                </div>
            <?php else : ?>
                <?php foreach ($this->registered_plugins as $plugin) : ?>
                    <div class="wbcom-plugin-card plugin-status-<?php echo esc_attr($plugin['status']); ?>">
                        <div class="plugin-card-top">
                            <div class="plugin-card-header">
                                <h3><?php echo esc_html($plugin['name']); ?></h3>
                                <div class="plugin-status-badge <?php echo esc_attr($plugin['status']); ?>">
                                    <?php echo esc_html(ucfirst($plugin['status'])); ?>
                                </div>
                            </div>
                            <p class="plugin-description"><?php echo esc_html($plugin['description']); ?></p>
                            <div class="plugin-version">
                                <span class="version-label"><?php esc_html_e('Version:', 'wbcom-shared'); ?></span>
                                <span class="version-number"><?php echo esc_html($plugin['version']); ?></span>
                            </div>
                        </div>
                        <div class="plugin-card-bottom">
                            <div class="plugin-actions">
                                <?php if ($plugin['status'] === 'active' && !empty($plugin['settings_url'])) : ?>
                                    <a href="<?php echo esc_url($plugin['settings_url']); ?>" class="button button-primary">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php esc_html_e('Settings', 'wbcom-shared'); ?>
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
                <h2><?php esc_html_e('Premium BuddyPress Plugins', 'wbcom-shared'); ?></h2>
                <p><?php esc_html_e('Enhance your community with these powerful premium plugins designed specifically for BuddyPress.', 'wbcom-shared'); ?></p>
            </div>
            
            <div class="premium-plugins-list">
                <?php foreach ($premium_plugins as $plugin) : ?>
                    <div class="premium-plugin-item">
                        <div class="plugin-header">
                            <h3><?php echo esc_html($plugin['name']); ?></h3>
                            <div class="plugin-price">
                                <span class="price-amount"><?php echo esc_html($plugin['price']); ?></span>
                            </div>
                        </div>
                        <div class="plugin-content">
                            <p class="plugin-description"><?php echo esc_html($plugin['description']); ?></p>
                            <ul class="plugin-features">
                                <?php foreach (array_slice($plugin['features'], 0, 4) as $feature) : ?>
                                    <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                                <?php if (count($plugin['features']) > 4) : ?>
                                    <li class="more-features">+ <?php echo count($plugin['features']) - 4; ?> more features</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="plugin-actions">
                            <a href="<?php echo esc_url($plugin['url']); ?>" target="_blank" rel="noopener" class="button button-primary">
                                <?php esc_html_e('View Plugin', 'wbcom-shared'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="premium-footer">
                <p class="center-text">
                    <a href="https://wbcomdesigns.com/downloads/" target="_blank" rel="noopener" class="button button-secondary button-large">
                        <?php esc_html_e('Browse All Premium Plugins', 'wbcom-shared'); ?>
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
                <h2><?php esc_html_e('Premium BuddyPress Themes', 'wbcom-shared'); ?></h2>
                <p><?php esc_html_e('Professional WordPress themes designed specifically for BuddyPress communities.', 'wbcom-shared'); ?></p>
            </div>
            
            <div class="premium-themes-list">
                <?php foreach ($premium_themes as $theme) : ?>
                    <div class="premium-theme-item">
                        <div class="theme-header">
                            <h3><?php echo esc_html($theme['name']); ?></h3>
                            <div class="theme-price">
                                <span class="price-amount"><?php echo esc_html($theme['price']); ?></span>
                            </div>
                        </div>
                        <div class="theme-content">
                            <p class="theme-description"><?php echo esc_html($theme['description']); ?></p>
                            <ul class="theme-features">
                                <?php foreach (array_slice($theme['features'], 0, 4) as $feature) : ?>
                                    <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="theme-actions">
                            <a href="<?php echo esc_url($theme['url']); ?>" target="_blank" rel="noopener" class="button button-primary">
                                <?php esc_html_e('View Theme', 'wbcom-shared'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                            <?php if (!empty($theme['demo_url'])) : ?>
                                <a href="<?php echo esc_url($theme['demo_url']); ?>" target="_blank" rel="noopener" class="button button-secondary">
                                    <?php esc_html_e('Live Demo', 'wbcom-shared'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                <h2><?php esc_html_e('Latest News from Wbcom Designs', 'wbcom-shared'); ?></h2>
                <p><?php esc_html_e('Stay updated with the latest plugin releases, updates, and WordPress community news.', 'wbcom-shared'); ?></p>
            </div>
            
            <div id="wbcom-news-feed" class="wbcom-news-feed">
                <div class="news-loading">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Loading latest news...', 'wbcom-shared'); ?></p>
                </div>
            </div>

            <div class="news-footer">
                <p class="center-text">
                    <a href="https://wbcomdesigns.com/blog/" target="_blank" rel="noopener" class="button button-secondary">
                        <?php esc_html_e('Visit Our Blog', 'wbcom-shared'); ?>
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
    private function render_sidebar_widgets() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wbcom-sidebar-widget">
            <h3><?php esc_html_e('Quick Stats', 'wbcom-shared'); ?></h3>
            <ul class="wbcom-stats-list">
                <li>
                    <strong><?php echo esc_html($stats['total_plugins']); ?></strong>
                    <span><?php esc_html_e('Plugins Installed', 'wbcom-shared'); ?></span>
                </li>
                <li>
                    <strong><?php echo esc_html($stats['active_plugins']); ?></strong>
                    <span><?php esc_html_e('Plugins Active', 'wbcom-shared'); ?></span>
                </li>
                <li>
                    <strong><?php echo esc_html($stats['wp_version']); ?></strong>
                    <span><?php esc_html_e('WordPress Version', 'wbcom-shared'); ?></span>
                </li>
            </ul>
        </div>

        <div class="wbcom-sidebar-widget">
            <h3><?php esc_html_e('Need Help?', 'wbcom-shared'); ?></h3>
            <p><?php esc_html_e('Get expert support for all Wbcom Designs plugins and WordPress development.', 'wbcom-shared'); ?></p>
            <div class="widget-actions">
                <a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary button-large">
                    <span class="dashicons dashicons-sos"></span>
                    <?php esc_html_e('Get Support', 'wbcom-shared'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Helper methods
     */
    private function get_dashboard_stats() {
        return array(
            'total_plugins'  => count($this->registered_plugins),
            'active_plugins' => count($this->get_active_plugins()),
            'wp_version'     => get_bloginfo('version'),
            'bp_version'     => function_exists('buddypress') ? buddypress()->version : __('Not Active', 'wbcom-shared'),
        );
    }
    
    private function get_active_plugins() {
        return array_filter($this->registered_plugins, function($plugin) {
            return $plugin['status'] === 'active';
        });
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
    
    private function render_admin_notices() {
        $active_count = count($this->get_active_plugins());
        
        if ($active_count === 0) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Welcome to Wbcom Designs!', 'wbcom-shared'); ?></strong>
                    <?php esc_html_e('No Wbcom plugins are currently active. Activate plugins to see them here.', 'wbcom-shared'); ?>
                </p>
            </div>
            <?php
        }
    }
    
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
        );
    }
    
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
        );
    }
}