<?php
/**
 * Enhanced Wbcom Integration for BP Activity Filter with All Dashboard Tabs
 * 
 * @package BuddyPress_Activity_Filter
 * @version 4.0.0
 */

if (!defined('ABSPATH')) exit;

class BP_Activity_Filter_Wbcom_Integration {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_items'), 10);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
    }
    
    /**
     * Add menu items
     */
    public function add_menu_items() {
        // Check if Wbcom menu already exists
        $menu_exists = $this->wbcom_menu_exists();
        
        if (!$menu_exists) {
            // Create main Wbcom menu
            add_menu_page(
                esc_html__('Wbcom Designs', 'bp-activity-filter'),
                esc_html__('Wbcom Designs', 'bp-activity-filter'),
                'manage_options',
                'wbcom-designs',
                array($this, 'dashboard_page'),
                $this->get_menu_icon(),
                58.5
            );
        }
        
        // Add our plugin submenu
        add_submenu_page(
            'wbcom-designs',
            esc_html__('BuddyPress Activity Filter', 'bp-activity-filter'),
            esc_html__('Activity Filter', 'bp-activity-filter'),
            'manage_options',
            'wbcom-activity-filter',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets($hook_suffix) {
        if (strpos($hook_suffix, 'wbcom-designs') === false) {
            return;
        }
        
        // Add inline styles for better dashboard appearance
        wp_add_inline_style('wp-admin', $this->get_dashboard_styles());
        
        // Add inline scripts for dashboard functionality
        wp_add_inline_script('jquery', $this->get_dashboard_scripts());
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
     * Get menu icon
     */
    private function get_menu_icon() {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDJMMTMuMDkgOC4yNkwyMCA5TDE0IDEyTDE1IDIwTDEwIDE3TDUgMjBMNiAxMkwwIDlMNi45MSA4LjI2TDEwIDJaIiBmaWxsPSIjYTdhYWFkIi8+Cjwvc3ZnPgo=';
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        ?>
        <div class="wrap wbcom-dashboard">
            <h1>
                <span style="margin-right: 10px;">🌟</span>
                <?php esc_html_e('Wbcom Designs', 'bp-activity-filter'); ?>
                <span style="font-size: 14px; color: #666; background: #f0f0f1; padding: 2px 8px; border-radius: 12px; margin-left: 10px;">v<?php echo esc_html(BP_ACTIVITY_FILTER_VERSION); ?></span>
            </h1>
            
            <div style="margin-top: 20px;">
                <nav class="nav-tab-wrapper">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=overview')); ?>" 
                       class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php esc_html_e('Overview', 'bp-activity-filter'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=plugins')); ?>" 
                       class="nav-tab <?php echo $active_tab === 'plugins' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php esc_html_e('Plugins', 'bp-activity-filter'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=premium')); ?>" 
                       class="nav-tab <?php echo $active_tab === 'premium' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php esc_html_e('Premium Plugins', 'bp-activity-filter'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=themes')); ?>" 
                       class="nav-tab <?php echo $active_tab === 'themes' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <?php esc_html_e('Premium Themes', 'bp-activity-filter'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=news')); ?>" 
                       class="nav-tab <?php echo $active_tab === 'news' ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons dashicons-rss"></span>
                        <?php esc_html_e('News & Updates', 'bp-activity-filter'); ?>
                    </a>
                </nav>

                <div class="tab-content wbcom-tab-content">
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
        </div>
        <?php
    }
    
    /**
     * Render overview tab
     */
    private function render_overview_tab() {
        ?>
        <div class="wbcom-welcome-panel">
            <h2><?php esc_html_e('Welcome to Wbcom Designs Dashboard', 'bp-activity-filter'); ?></h2>
            <p class="about-description">
                <?php esc_html_e('Your central hub for managing all Wbcom Designs plugins. We create premium WordPress and BuddyPress solutions to enhance your community experience.', 'bp-activity-filter'); ?>
            </p>
            
            <!-- Quick Stats -->
            <div class="wbcom-stats-overview">
                <div class="stat-box">
                    <div class="stat-number">1</div>
                    <div class="stat-label"><?php esc_html_e('Total Plugins', 'bp-activity-filter'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">1</div>
                    <div class="stat-label"><?php esc_html_e('Active Plugins', 'bp-activity-filter'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html(get_bloginfo('version')); ?></div>
                    <div class="stat-label"><?php esc_html_e('WordPress Version', 'bp-activity-filter'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo function_exists('buddypress') ? esc_html(buddypress()->version) : esc_html__('N/A', 'bp-activity-filter'); ?></div>
                    <div class="stat-label"><?php esc_html_e('BuddyPress Version', 'bp-activity-filter'); ?></div>
                </div>
            </div>
            
            <div class="wbcom-welcome-panel-columns">
                <div class="wbcom-welcome-panel-column">
                    <h3><?php esc_html_e('Quick Actions', 'bp-activity-filter'); ?></h3>
                    <ul class="wbcom-action-list">
                        <li>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-activity-filter')); ?>" class="button button-primary">
                                <span class="dashicons dashicons-filter"></span>
                                <?php esc_html_e('Activity Filter Settings', 'bp-activity-filter'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-designs&tab=premium')); ?>" class="button button-secondary">
                                <span class="dashicons dashicons-star-filled"></span>
                                <?php esc_html_e('Browse Premium Plugins', 'bp-activity-filter'); ?>
                            </a>
                        </li>
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
                
                <div class="wbcom-welcome-panel-column">
                    <h3><?php esc_html_e('Quick Links', 'bp-activity-filter'); ?></h3>
                    <ul class="wbcom-action-list">
                        <li>
                            <a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary">
                                <span class="dashicons dashicons-sos"></span>
                                <?php esc_html_e('Get Support', 'bp-activity-filter'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-secondary">
                                <span class="dashicons dashicons-book"></span>
                                <?php esc_html_e('Documentation', 'bp-activity-filter'); ?>
                            </a>
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
        <div>
            <h2><?php esc_html_e('Installed Wbcom Plugins', 'bp-activity-filter'); ?></h2>
            
            <div class="wbcom-plugins-grid">
                <div class="wbcom-plugin-card plugin-status-active">
                    <div class="plugin-card-top">
                        <div class="plugin-card-header">
                            <h3><?php esc_html_e('BuddyPress Activity Filter', 'bp-activity-filter'); ?></h3>
                            <div class="plugin-status-badge active">
                                <?php esc_html_e('ACTIVE', 'bp-activity-filter'); ?>
                            </div>
                        </div>
                        <p class="plugin-description">
                            <?php esc_html_e('Filter and manage BuddyPress activity streams with default filters and custom post type support.', 'bp-activity-filter'); ?>
                        </p>
                        <div class="plugin-version">
                            <span class="version-label"><?php esc_html_e('Version:', 'bp-activity-filter'); ?></span>
                            <span class="version-number"><?php echo esc_html(BP_ACTIVITY_FILTER_VERSION); ?></span>
                        </div>
                    </div>
                    <div class="plugin-card-bottom">
                        <div class="plugin-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wbcom-activity-filter')); ?>" class="button button-primary">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('Settings', 'bp-activity-filter'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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
                                <?php if (count($theme['features']) > 4) : ?>
                                    <li class="more-features">+ <?php echo count($theme['features']) - 4; ?> more features</li>
                                <?php endif; ?>
                            </ul>
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

            <div class="news-footer">
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
     * Get premium plugins data
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
        );
    }
    
    /**
     * Get premium themes data
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
        );
    }
    
    /**
     * Get dashboard styles
     */
    private function get_dashboard_styles() {
        return '
        /* Wbcom Dashboard Styles */
        .wbcom-dashboard .nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 16px;
        }
        
        .wbcom-tab-content {
            background: #fff;
            padding: 20px;
            margin-top: -1px;
            border: 1px solid #c3c4c7;
            border-radius: 0 0 4px 4px;
        }
        
        .wbcom-welcome-panel {
            background: #fff;
            border: 1px solid #c3c4c7;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .wbcom-stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 20px 0;
            border-top: 1px solid #f0f0f1;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e2e4e7;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 13px;
            color: #646970;
            margin-top: 5px;
        }
        
        .wbcom-welcome-panel-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .wbcom-action-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .wbcom-action-list li {
            margin-bottom: 10px;
        }
        
        .wbcom-system-status {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .wbcom-system-status li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .status-indicator.active {
            background-color: #00a32a;
        }
        
        .status-indicator.inactive {
            background-color: #dba617;
        }
        
        .wbcom-plugins-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .wbcom-plugin-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .wbcom-plugin-card.plugin-status-active {
            border-left: 4px solid #00a32a;
        }
        
        .plugin-card-top {
            padding: 20px;
        }
        
        .plugin-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .plugin-card-header h3 {
            margin: 0;
            font-size: 16px;
            line-height: 1.3;
        }
        
        .plugin-status-badge {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 12px;
            letter-spacing: 0.5px;
        }
        
        .plugin-status-badge.active {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .plugin-description {
            color: #646970;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 12px 0;
        }
        
        .plugin-version {
            font-size: 12px;
            color: #8c8f94;
        }
        
        .version-label {
            font-weight: 500;
        }
        
        .plugin-card-bottom {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #f0f0f1;
        }
        
        .plugin-actions {
            display: flex;
            gap: 8px;
        }
        
        .plugin-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }
        
        /* Premium Plugins/Themes Lists */
        .premium-plugins-list,
        .premium-themes-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .premium-plugin-item,
        .premium-theme-item {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 6px;
            padding: 20px;
            transition: all 0.2s ease;
        }
        
        .premium-plugin-item:hover,
        .premium-theme-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            border-color: #0073aa;
        }
        
        .plugin-header,
        .theme-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .plugin-header h3,
        .theme-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #0073aa;
            line-height: 1.3;
            flex: 1;
        }
        
        .plugin-price,
        .theme-price {
            flex-shrink: 0;
            margin-left: 15px;
        }
        
        .price-amount {
            font-size: 24px;
            font-weight: 700;
            color: #0073aa;
            background: #e7f3ff;
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid #0073aa;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        
        .plugin-content,
        .theme-content {
            margin-bottom: 20px;
        }
        
        .plugin-features,
        .theme-features {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 8px;
        }
        
        .plugin-features li,
        .theme-features li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #23282d;
            padding: 4px 0;
        }
        
        .plugin-features .dashicons,
        .theme-features .dashicons {
            color: #00a32a;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .more-features {
            font-style: italic;
            color: #8c8f94 !important;
        }
        
        .plugin-actions,
        .theme-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            padding-top: 15px;
            border-top: 1px solid #f0f0f1;
        }
        
        .plugin-actions .button,
        .theme-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .premium-footer,
        .themes-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f1;
        }
        
        .center-text {
            text-align: center;
            margin: 0;
        }
        
        .button-large {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
        }
        
        /* News Section */
        .wbcom-news-section {
            max-width: 800px;
        }
        
        .news-loading {
            text-align: center;
            padding: 40px;
            color: #646970;
        }
        
        .news-loading .spinner {
            float: none;
            margin: 0 auto 15px;
        }
        
        .news-item {
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .news-item h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .news-item h4 a {
            text-decoration: none;
            color: #0073aa;
        }
        
        .news-item p {
            margin: 0 0 8px 0;
            color: #646970;
            line-height: 1.5;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .wbcom-welcome-panel-columns {
                grid-template-columns: 1fr;
            }
            
            .wbcom-stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .wbcom-plugins-grid {
                grid-template-columns: 1fr;
            }
        }
        ';
    }
    
    /**
     * Get dashboard scripts
     */
    private function get_dashboard_scripts() {
        return '
        jQuery(document).ready(function($) {
            // Load news feed
            if ($("#wbcom-news-feed").length > 0) {
                $.ajax({
                    url: "https://wbcomdesigns.com/wp-json/wp/v2/posts",
                    data: { per_page: 5 },
                    timeout: 10000,
                    success: function(posts) {
                        var newsHtml = "";
                        if (posts && posts.length > 0) {
                            posts.forEach(function(post) {
                                var excerpt = $("<div>").html(post.excerpt.rendered).text();
                                var date = new Date(post.date).toLocaleDateString();
                                newsHtml += "<div class=\"news-item\">";
                                newsHtml += "<h4><a href=\"" + post.link + "\" target=\"_blank\">" + post.title.rendered + "</a></h4>";
                                newsHtml += "<p>" + excerpt + "</p>";
                                newsHtml += "<small>" + date + "</small>";
                                newsHtml += "</div>";
                            });
                            $("#wbcom-news-feed").html(newsHtml);
                        } else {
                            $("#wbcom-news-feed").html("<div class=\"news-empty\"><h3>No News Available</h3><p>Unable to load recent news at this time.</p></div>");
                        }
                    },
                    error: function() {
                        $("#wbcom-news-feed").html("<div class=\"news-error\"><h3>Unable to Load News</h3><p>Please check your internet connection and try again later.</p></div>");
                    }
                });
            }
        });
        ';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (class_exists('BP_Activity_Filter_Admin')) {
            $admin = BP_Activity_Filter_Admin::instance();
            $admin->render_settings_page();
        } else {
            echo '<div class="wrap"><h1>BuddyPress Activity Filter Settings</h1>';
            echo '<p>Admin class not found. Please ensure the plugin is properly installed.</p></div>';
        }
    }
}