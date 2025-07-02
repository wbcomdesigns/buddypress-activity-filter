<?php
/**
 * Enhanced Admin Menu Integration for BP Activity Filter - Production Ready
 *
 * This file provides a robust, production-ready admin menu system with
 * proper error handling, fallback mechanisms, and enhanced UI.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enhanced BP Activity Filter Admin Class - Production Ready
 */
class BP_Activity_Filter_Admin_Enhanced {

    /**
     * Class instance.
     *
     * @since 4.0.0
     * @var BP_Activity_Filter_Admin_Enhanced|null Singleton instance.
     */
    private static $instance = null;

    /**
     * Current admin page tab.
     *
     * @since 4.0.0
     * @var string Current active tab.
     */
    private $current_tab = 'default';

    /**
     * Menu creation method used.
     *
     * @since 4.0.0
     * @var string Method used to create the menu.
     */
    private $menu_method = 'none';

    /**
     * Page hook suffix.
     *
     * @since 4.0.0
     * @var string|false Page hook suffix or false if failed.
     */
    private $page_hook = false;

    /**
     * Get class instance.
     *
     * @since 4.0.0
     * @return BP_Activity_Filter_Admin_Enhanced Singleton instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 4.0.0
     */
    private function __construct() {
        $this->setup_hooks();
    }

    /**
     * Setup admin hooks and filters.
     *
     * @since 4.0.0
     */
    private function setup_hooks() {
        add_action( 'admin_menu', array( $this, 'create_admin_menu' ), 15 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
        add_action( 'admin_head', array( $this, 'add_admin_head_styles' ) );
        add_action( 'wp_ajax_bp_activity_filter_test_action', array( $this, 'handle_ajax_test' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( BP_ACTIVITY_FILTER_PLUGIN_DIR . 'buddypress-activity-filter.php' ), array( $this, 'plugin_action_links' ) );
    }

    /**
     * Create admin menu with multiple fallback strategies.
     *
     * @since 4.0.0
     * @return string|false Page hook string on success, false on failure.
     */
    public function create_admin_menu() {
        $this->page_hook = false;

        // Strategy 1: Try unified Wbcom menu system
        if ( class_exists( 'Wbcom_Designs_Menu' ) ) {
            $this->page_hook = $this->try_unified_menu();
            if ( $this->page_hook ) {
                $this->menu_method = 'unified_menu';
            }
        }

        // Strategy 2: Try existing Wbcom menu
        if ( ! $this->page_hook ) {
            $this->page_hook = $this->try_existing_wbcom_menu();
            if ( $this->page_hook ) {
                $this->menu_method = 'existing_wbcom_menu';
            }
        }

        // Strategy 3: Create our own Wbcom menu
        if ( ! $this->page_hook ) {
            $this->page_hook = $this->create_wbcom_menu();
            if ( $this->page_hook ) {
                $this->menu_method = 'created_wbcom_menu';
            }
        }

        // Strategy 4: Use WordPress settings menu
        if ( ! $this->page_hook ) {
            $this->page_hook = $this->add_to_settings_menu();
            if ( $this->page_hook ) {
                $this->menu_method = 'settings_menu';
            }
        }

        // Strategy 5: Create standalone menu (last resort)
        if ( ! $this->page_hook ) {
            $this->page_hook = $this->create_standalone_menu();
            if ( $this->page_hook ) {
                $this->menu_method = 'standalone_menu';
            }
        }

        // Add help tabs if successful
        if ( $this->page_hook ) {
            add_action( "load-{$this->page_hook}", array( $this, 'add_help_tabs' ) );
        }

        // Store menu creation info for debugging
        update_option( 'bp_activity_filter_menu_info', array(
            'method'      => $this->menu_method,
            'page_hook'   => $this->page_hook,
            'timestamp'   => current_time( 'mysql' ),
            'wp_version'  => get_bloginfo( 'version' ),
            'php_version' => PHP_VERSION,
        ) );

        return $this->page_hook;
    }

    /**
     * Try to use unified Wbcom menu system.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function try_unified_menu() {
        try {
            $wbcom_menu = Wbcom_Designs_Menu::instance();
            $menu_status = $wbcom_menu->get_menu_status();

            if ( ! ( $menu_status['menu_exists'] || $menu_status['menu_created'] ) ) {
                return false;
            }

            return $wbcom_menu->add_submenu(
                'activity-filter',
                esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
                esc_html__( 'Activity Filter', 'bp-activity-filter' ),
                'manage_options',
                'wbcom-activity-filter',
                array( $this, 'admin_page' )
            );
        } catch ( Exception $e ) {
            error_log( 'BP Activity Filter: Unified menu error - ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Try to add to existing Wbcom menu.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function try_existing_wbcom_menu() {
        if ( ! $this->wbcom_menu_exists() ) {
            return false;
        }

        return add_submenu_page(
            'wbcom-designs',
            esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'wbcom-activity-filter',
            array( $this, 'admin_page' )
        );
    }

    /**
     * Create Wbcom menu if it doesn't exist.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function create_wbcom_menu() {
        // Create main menu
        $main_menu = add_menu_page(
            esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
            esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
            'manage_options',
            'wbcom-designs',
            array( $this, 'wbcom_dashboard_page' ),
            $this->get_menu_icon(),
            58.5
        );

        if ( ! $main_menu ) {
            return false;
        }

        // Add our submenu
        return add_submenu_page(
            'wbcom-designs',
            esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'wbcom-activity-filter',
            array( $this, 'admin_page' )
        );
    }

    /**
     * Add to WordPress settings menu.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function add_to_settings_menu() {
        return add_options_page(
            esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'bp-activity-filter-settings',
            array( $this, 'admin_page' )
        );
    }

    /**
     * Create standalone menu as last resort.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function create_standalone_menu() {
        return add_menu_page(
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'bp-activity-filter-standalone',
            array( $this, 'admin_page' ),
            'dashicons-filter',
            30
        );
    }

    /**
     * Check if Wbcom menu exists.
     *
     * @since 4.0.0
     * @return bool True if menu exists, false otherwise.
     */
    private function wbcom_menu_exists() {
        global $menu;

        if ( ! is_array( $menu ) ) {
            return false;
        }

        foreach ( $menu as $item ) {
            if ( isset( $item[2] ) && $item[2] === 'wbcom-designs' ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get menu icon as base64 SVG.
     *
     * @since 4.0.0
     * @return string Base64 encoded SVG icon.
     */
    private function get_menu_icon() {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDJMMTMuMDkgOC4yNkwyMCA5TDE0IDEyTDE1IDIwTDEwIDE3TDUgMjBMNiAxMkwwIDlMNi45MSA4LjI2TDEwIDJaIiBmaWxsPSIjYTdhYWFkIi8+Cjwvc3ZnPgo=';
    }

    /**
     * Display admin page content with enhanced UI.
     *
     * @since 4.0.0
     */
    public function admin_page() {
        $current_tab = $this->get_current_tab();
        
        // Handle form submission
        if ( isset( $_POST['bp_activity_filter_submit'] ) && '1' === $_POST['bp_activity_filter_submit'] ) {
            $this->save_settings();
        }

        ?>
        <div class="wrap bp-activity-filter-admin">
            <h1>
                <span class="dashicons dashicons-filter" style="margin-right: 10px; color: #0073aa;"></span>
                <?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?>
                <span class="wbcom-version">v<?php echo esc_html( BP_ACTIVITY_FILTER_VERSION ); ?></span>
            </h1>

            <?php $this->render_menu_method_notice(); ?>
            <?php settings_errors( 'bp_activity_filter_settings' ); ?>

            <div class="tab-content-wrapper">
                <nav class="nav-tab-wrapper" role="tablist">
                    <?php $this->render_admin_tabs( $current_tab ); ?>
                </nav>

                <form method="post" action="" novalidate="novalidate">
                    <?php 
                    wp_nonce_field( 'bp_activity_filter_save_settings', 'bp_activity_filter_nonce' );
                    ?>
                    <input type="hidden" name="current_tab" value="<?php echo esc_attr( $current_tab ); ?>" />
                    <input type="hidden" name="bp_activity_filter_submit" value="1" />

                    <?php $this->render_tab_content( $current_tab ); ?>

                    <div class="submit">
                        <?php submit_button( esc_html__( 'Save Settings', 'bp-activity-filter' ), 'primary', 'submit', false, array( 'id' => 'bp-activity-filter-submit' ) ); ?>
                        <span class="spinner" id="bp-activity-filter-spinner"></span>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render menu method notice for debugging.
     *
     * @since 4.0.0
     */
    private function render_menu_method_notice() {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $method_labels = array(
            'unified_menu'        => 'Unified Wbcom Menu System',
            'existing_wbcom_menu' => 'Existing Wbcom Menu',
            'created_wbcom_menu'  => 'Created Wbcom Menu',
            'settings_menu'       => 'WordPress Settings Menu',
            'standalone_menu'     => 'Standalone Menu',
        );

        $method_colors = array(
            'unified_menu'        => 'notice-success',
            'existing_wbcom_menu' => 'notice-success',
            'created_wbcom_menu'  => 'notice-info',
            'settings_menu'       => 'notice-warning',
            'standalone_menu'     => 'notice-error',
        );

        $method_label = isset( $method_labels[ $this->menu_method ] ) ? $method_labels[ $this->menu_method ] : $this->menu_method;
        $notice_class = isset( $method_colors[ $this->menu_method ] ) ? $method_colors[ $this->menu_method ] : 'notice-info';

        ?>
        <div class="notice <?php echo esc_attr( $notice_class ); ?> notice-alt" style="margin-top: 10px;">
            <p>
                <strong><?php esc_html_e( 'Debug Info:', 'bp-activity-filter' ); ?></strong>
                <?php
                printf(
                    /* translators: %s: Menu creation method */
                    esc_html__( 'Menu created using: %s', 'bp-activity-filter' ),
                    '<code>' . esc_html( $method_label ) . '</code>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render admin navigation tabs with enhanced styling.
     *
     * @since 4.0.0
     * @param string $current_tab Current active tab.
     */
    private function render_admin_tabs( $current_tab ) {
        $tabs = array(
            'default' => array(
                'title' => esc_html__( 'Default Filters', 'bp-activity-filter' ),
                'icon'  => 'dashicons-admin-settings',
            ),
            'hidden'  => array(
                'title' => esc_html__( 'Hidden Activities', 'bp-activity-filter' ),
                'icon'  => 'dashicons-hidden',
            ),
            'cpt'     => array(
                'title' => esc_html__( 'Custom Post Types', 'bp-activity-filter' ),
                'icon'  => 'dashicons-admin-post',
            ),
        );

        $base_url = $this->get_admin_page_url();

        foreach ( $tabs as $tab_key => $tab_data ) {
            $url = add_query_arg( array( 'tab' => $tab_key ), $base_url );
            $active_class = $current_tab === $tab_key ? 'nav-tab-active' : '';
            
            printf(
                '<a href="%s" class="nav-tab %s" role="tab" aria-selected="%s" data-tab="%s">
                    <span class="dashicons %s"></span>
                    %s
                </a>',
                esc_url( $url ),
                esc_attr( $active_class ),
                $current_tab === $tab_key ? 'true' : 'false',
                esc_attr( $tab_key ),
                esc_attr( $tab_data['icon'] ),
                esc_html( $tab_data['title'] )
            );
        }
    }

    /**
     * Get current admin page URL.
     *
     * @since 4.0.0
     * @return string Admin page URL.
     */
    private function get_admin_page_url() {
        $page_map = array(
            'unified_menu'        => 'wbcom-activity-filter',
            'existing_wbcom_menu' => 'wbcom-activity-filter',
            'created_wbcom_menu'  => 'wbcom-activity-filter',
            'settings_menu'       => 'bp-activity-filter-settings',
            'standalone_menu'     => 'bp-activity-filter-standalone',
        );

        $page_slug = isset( $page_map[ $this->menu_method ] ) ? $page_map[ $this->menu_method ] : 'wbcom-activity-filter';
        $admin_page = ( 'settings_menu' === $this->menu_method ) ? 'options-general.php' : 'admin.php';

        return admin_url( $admin_page . '?page=' . $page_slug );
    }

    /**
     * Render tab content based on current tab.
     *
     * @since 4.0.0
     * @param string $current_tab Current active tab.
     */
    private function render_tab_content( $current_tab ) {
        switch ( $current_tab ) {
            case 'hidden':
                $this->render_hidden_activities_tab();
                break;
            case 'cpt':
                $this->render_cpt_tab();
                break;
            default:
                $this->render_default_filters_tab();
                break;
        }
    }

    /**
     * Render default filters tab content.
     *
     * @since 4.0.0
     */
    private function render_default_filters_tab() {
        $default_filter = get_option( 'bp_activity_filter_default', '0' );
        $profile_default_filter = get_option( 'bp_activity_filter_profile_default', '-1' );
        $activity_actions = BP_Activity_Filter_Helper::get_activity_actions();

        ?>
        <div class="settings-section">
            <h2><?php esc_html_e( 'Default Activity Filters', 'bp-activity-filter' ); ?></h2>
            <p><?php esc_html_e( 'Configure the default activity filter for different contexts. These settings determine what type of activities are shown by default when users visit activity streams.', 'bp-activity-filter' ); ?></p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="bp_activity_filter_default">
                                <?php esc_html_e( 'Site-wide Activity Default', 'bp-activity-filter' ); ?>
                            </label>
                        </th>
                        <td>
                            <select name="bp_activity_filter_default" id="bp_activity_filter_default" class="regular-text">
                                <option value="0" <?php selected( $default_filter, '0' ); ?>>
                                    <?php esc_html_e( 'Everything', 'bp-activity-filter' ); ?>
                                </option>
                                <?php foreach ( $activity_actions as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default_filter, $key ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Default filter applied to the main site-wide activity stream. Users can still change this using the activity filter dropdown.', 'bp-activity-filter' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bp_activity_filter_profile_default">
                                <?php esc_html_e( 'Profile Activity Default', 'bp-activity-filter' ); ?>
                            </label>
                        </th>
                        <td>
                            <select name="bp_activity_filter_profile_default" id="bp_activity_filter_profile_default" class="regular-text">
                                <option value="-1" <?php selected( $profile_default_filter, '-1' ); ?>>
                                    <?php esc_html_e( 'Everything', 'bp-activity-filter' ); ?>
                                </option>
                                <?php foreach ( $activity_actions as $key => $label ) : ?>
                                    <?php if ( ! in_array( $key, array( 'new_member', 'updated_profile' ), true ) ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile_default_filter, $key ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Default filter applied to individual user profile activity streams. Some activity types like "New Member" are excluded as they don\'t typically appear on user profiles.', 'bp-activity-filter' ); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render hidden activities tab content.
     *
     * @since 4.0.0
     */
    private function render_hidden_activities_tab() {
        $hidden_activities = get_option( 'bp_activity_filter_hidden', array() );
        $activity_actions = BP_Activity_Filter_Helper::get_activity_actions();
        ?>
        <div class="settings-section">
            <h2><?php esc_html_e( 'Hidden Activity Types', 'bp-activity-filter' ); ?></h2>
            <p><?php esc_html_e( 'Select activity types to completely hide from all activity streams. Hidden activities will not appear in the activity feed or filter dropdown options.', 'bp-activity-filter' ); ?></p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Activity Types to Hide', 'bp-activity-filter' ); ?></th>
                        <td>
                            <?php if ( empty( $activity_actions ) ) : ?>
                                <div class="notice notice-warning inline">
                                    <p><?php esc_html_e( 'No activity types available. Make sure BuddyPress is properly installed and activated.', 'bp-activity-filter' ); ?></p>
                                </div>
                            <?php else : ?>
                                <fieldset id="bp-hidden-activities-fieldset">
                                    <legend class="screen-reader-text">
                                        <?php esc_html_e( 'Select activity types to hide from the activity stream', 'bp-activity-filter' ); ?>
                                    </legend>
                                    <?php foreach ( $activity_actions as $key => $label ) : ?>
                                        <?php
                                        $is_checked = in_array( $key, $hidden_activities, true );
                                        $checkbox_id = 'bp_hidden_' . sanitize_html_class( $key );
                                        ?>
                                        <label for="<?php echo esc_attr( $checkbox_id ); ?>" class="bp-activity-checkbox-label <?php echo $is_checked ? 'checked' : ''; ?>">
                                            <input type="checkbox" 
                                                   id="<?php echo esc_attr( $checkbox_id ); ?>"
                                                   name="bp_activity_filter_hidden[]" 
                                                   value="<?php echo esc_attr( $key ); ?>" 
                                                   <?php checked( $is_checked ); ?>
                                                   class="bp-activity-checkbox">
                                            <div class="checkbox-content">
                                                <span class="checkbox-label-text"><?php echo esc_html( $label ); ?></span>
                                                <code class="activity-key"><?php echo esc_html( $key ); ?></code>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                                
                                <div class="hidden-activities-actions">
                                    <button type="button" id="select-all-hidden" class="button button-secondary">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e( 'Select All', 'bp-activity-filter' ); ?>
                                    </button>
                                    <button type="button" id="deselect-all-hidden" class="button button-secondary">
                                        <span class="dashicons dashicons-no"></span>
                                        <?php esc_html_e( 'Deselect All', 'bp-activity-filter' ); ?>
                                    </button>
                                </div>

                                <div class="notice notice-info inline" style="margin-top: 20px;">
                                    <p>
                                        <strong><?php esc_html_e( 'Important:', 'bp-activity-filter' ); ?></strong>
                                        <?php esc_html_e( 'Hidden activity types will be completely removed from the activity stream and will not appear in filter dropdown options. This action affects all users on your site.', 'bp-activity-filter' ); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render custom post types tab content.
     *
     * @since 4.0.0
     */
    private function render_cpt_tab() {
        $cpt_settings = get_option( 'bp_activity_filter_cpt_settings', array() );
        $post_types = $this->get_eligible_post_types();
        ?>
        <div class="settings-section">
            <h2><?php esc_html_e( 'Custom Post Type Activities', 'bp-activity-filter' ); ?></h2>
            <p>
                <?php esc_html_e( 'Enable automatic activity generation for custom post types when they are published. Only public custom post types with admin interface are available for selection.', 'bp-activity-filter' ); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable Post Types', 'bp-activity-filter' ); ?></th>
                        <td>
                            <?php if ( empty( $post_types ) ) : ?>
                                <div class="notice notice-info inline">
                                    <p>
                                        <strong><?php esc_html_e( 'No eligible custom post types found.', 'bp-activity-filter' ); ?></strong>
                                        <br>
                                        <?php esc_html_e( 'Custom post types must be public, have admin UI enabled, and support posts to appear here. Built-in WordPress post types (posts, pages) are not shown as they have their own activity systems.', 'bp-activity-filter' ); ?>
                                    </p>
                                </div>
                            <?php else : ?>
                                <?php foreach ( $post_types as $post_type => $post_type_obj ) : ?>
                                    <?php $this->render_cpt_setting_item( $post_type, $post_type_obj, $cpt_settings ); ?>
                                <?php endforeach; ?>
                                
                                <?php $this->render_cpt_global_settings( $cpt_settings ); ?>

                                <div class="notice notice-info inline" style="margin-top: 20px;">
                                    <p>
                                        <strong><?php esc_html_e( 'How it works:', 'bp-activity-filter' ); ?></strong>
                                        <?php esc_html_e( 'When a post of the selected custom post type is published, an activity entry will be automatically created showing the author, post type, and post title with a link. Existing posts will not generate activities - only new posts published after enabling this feature.', 'bp-activity-filter' ); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render individual CPT setting item.
     *
     * @since 4.0.0
     * @param string      $post_type     Post type slug.
     * @param WP_Post_Type $post_type_obj Post type object.
     * @param array       $cpt_settings  Current CPT settings.
     */
    private function render_cpt_setting_item( $post_type, $post_type_obj, $cpt_settings ) {
        $enabled = isset( $cpt_settings[ $post_type ]['enabled'] ) ? $cpt_settings[ $post_type ]['enabled'] : false;
        $label   = isset( $cpt_settings[ $post_type ]['label'] ) ? $cpt_settings[ $post_type ]['label'] : '';
        $post_count = wp_count_posts( $post_type );
        $total_posts = isset( $post_count->publish ) ? $post_count->publish : 0;
        ?>
        <div class="cpt-setting-item <?php echo $enabled ? '' : 'disabled'; ?>" data-post-type="<?php echo esc_attr( $post_type ); ?>">
            <div class="cpt-header">
                <label class="cpt-main-label">
                    <input type="checkbox" 
                           name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $post_type ); ?>][enabled]" 
                           value="1" <?php checked( $enabled ); ?>
                           class="cpt-enable-checkbox"
                           id="cpt_<?php echo esc_attr( $post_type ); ?>_enabled">
                    <strong><?php echo esc_html( $post_type_obj->label ); ?></strong>
                    <span class="cpt-meta">
                        (<?php echo esc_html( $post_type ); ?>) - 
                        <?php 
                        printf( 
                            /* translators: %d: number of posts */
                            _n( '%d post', '%d posts', $total_posts, 'bp-activity-filter' ), 
                            number_format_i18n( $total_posts )
                        ); 
                        ?>
                    </span>
                </label>
            </div>
            
            <?php if ( ! empty( $post_type_obj->description ) ) : ?>
                <div class="cpt-description">
                    <p class="description"><?php echo esc_html( $post_type_obj->description ); ?></p>
                </div>
            <?php endif; ?>

            <div class="cpt-settings" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
                <div class="cpt-label-setting">
                    <label for="cpt_<?php echo esc_attr( $post_type ); ?>_label">
                        <?php esc_html_e( 'Custom Activity Label (optional):', 'bp-activity-filter' ); ?>
                    </label>
                    <input type="text" 
                           id="cpt_<?php echo esc_attr( $post_type ); ?>_label"
                           name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $post_type ); ?>][label]" 
                           value="<?php echo esc_attr( $label ); ?>" 
                           placeholder="<?php echo esc_attr( strtolower( $post_type_obj->labels->singular_name ) ); ?>"
                           class="cpt-label-input"
                           <?php echo $enabled ? '' : 'disabled'; ?>>
                    <p class="description">
                        <?php esc_html_e( 'Leave empty to use the default post type name. This text will appear in activity entries like "John published a new [label]: Post Title"', 'bp-activity-filter' ); ?>
                    </p>
                </div>

                <?php $this->render_cpt_info( $post_type_obj ); ?>
                <?php $this->render_cpt_preview( $post_type_obj, $label ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render CPT information section.
     *
     * @since 4.0.0
     * @param WP_Post_Type $post_type_obj Post type object.
     */
    private function render_cpt_info( $post_type_obj ) {
        ?>
        <div class="cpt-capabilities">
            <strong><?php esc_html_e( 'Post Type Information:', 'bp-activity-filter' ); ?></strong>
            <ul class="cpt-info-list">
                <li>
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e( 'Public:', 'bp-activity-filter' ); ?> 
                    <code><?php echo $post_type_obj->public ? esc_html__( 'Yes', 'bp-activity-filter' ) : esc_html__( 'No', 'bp-activity-filter' ); ?></code>
                </li>
                <li>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Admin Interface:', 'bp-activity-filter' ); ?> 
                    <code><?php echo $post_type_obj->show_ui ? esc_html__( 'Enabled', 'bp-activity-filter' ) : esc_html__( 'Disabled', 'bp-activity-filter' ); ?></code>
                </li>
                <li>
                    <span class="dashicons dashicons-menu-alt"></span>
                    <?php esc_html_e( 'Menu Position:', 'bp-activity-filter' ); ?> 
                    <code><?php echo $post_type_obj->show_in_menu ? esc_html__( 'Shown', 'bp-activity-filter' ) : esc_html__( 'Hidden', 'bp-activity-filter' ); ?></code>
                </li>
                <?php if ( ! empty( $post_type_obj->capability_type ) ) : ?>
                    <li>
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e( 'Capability Type:', 'bp-activity-filter' ); ?> 
                        <code><?php echo esc_html( $post_type_obj->capability_type ); ?></code>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render CPT activity preview.
     *
     * @since 4.0.0
     * @param WP_Post_Type $post_type_obj Post type object.
     * @param string       $label         Custom label.
     */
    private function render_cpt_preview( $post_type_obj, $label ) {
        ?>
        <div class="cpt-preview">
            <strong><?php esc_html_e( 'Activity Preview:', 'bp-activity-filter' ); ?></strong>
            <div class="activity-preview-text">
                <?php
                $preview_label = ! empty( $label ) ? $label : strtolower( $post_type_obj->labels->singular_name );
                printf(
                    /* translators: 1: Author name, 2: Post type label, 3: Post title */
                    esc_html__( '%1$s published a new %2$s: %3$s', 'bp-activity-filter' ),
                    '<strong>' . esc_html__( 'John Doe', 'bp-activity-filter' ) . '</strong>',
                    '<em>' . esc_html( $preview_label ) . '</em>',
                    '<a href="#">' . esc_html__( 'Sample Post Title', 'bp-activity-filter' ) . '</a>'
                );
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render CPT global settings.
     *
     * @since 4.0.0
     * @param array $cpt_settings Current CPT settings.
     */
    private function render_cpt_global_settings( $cpt_settings ) {
        ?>
        <div class="cpt-global-settings">
            <h4><?php esc_html_e( 'Global Settings', 'bp-activity-filter' ); ?></h4>
            <label for="cpt_global_hide_sitewide">
                <input type="checkbox" 
                       id="cpt_global_hide_sitewide"
                       name="bp_activity_filter_cpt_settings[_global][hide_sitewide]" 
                       value="1" 
                       <?php checked( isset( $cpt_settings['_global']['hide_sitewide'] ) ? $cpt_settings['_global']['hide_sitewide'] : false ); ?>>
                <div>
                    <?php esc_html_e( 'Hide custom post type activities from site-wide activity stream', 'bp-activity-filter' ); ?>
                    <p class="description">
                        <?php esc_html_e( 'When enabled, custom post type activities will only appear in the author\'s profile activity stream, not in the main site-wide activity feed. This helps reduce clutter in the main activity stream while still giving authors credit on their profiles.', 'bp-activity-filter' ); ?>
                    </p>
                </div>
            </label>
        </div>
        <?php
    }

    /**
     * Wbcom dashboard page (fallback).
     *
     * @since 4.0.0
     */
    public function wbcom_dashboard_page() {
        if ( class_exists( 'Wbcom_Designs_Menu' ) ) {
            $menu = Wbcom_Designs_Menu::instance();
            $menu->dashboard_page();
        } else {
            ?>
            <div class="wrap wbcom-dashboard">
                <h1>
                    <span class="dashicons dashicons-star-filled" style="margin-right: 10px; color: #0073aa;"></span>
                    <?php esc_html_e( 'Wbcom Designs', 'bp-activity-filter' ); ?>
                </h1>
                
                <div class="wbcom-welcome-panel">
                    <h2><?php esc_html_e( 'Welcome to Wbcom Designs', 'bp-activity-filter' ); ?></h2>
                    <p class="about-description">
                        <?php esc_html_e( 'Your central hub for managing Wbcom Designs plugins. We create premium WordPress and BuddyPress solutions to enhance your community experience.', 'bp-activity-filter' ); ?>
                    </p>
                    
                    <div class="wbcom-welcome-panel-column-container">
                        <div class="wbcom-welcome-panel-column">
                            <h3><?php esc_html_e( 'Active Plugins', 'bp-activity-filter' ); ?></h3>
                            <ul class="wbcom-action-list">
                                <li>
                                    <a href="<?php echo esc_url( $this->get_admin_page_url() ); ?>" class="button button-primary">
                                        <span class="dashicons dashicons-filter"></span>
                                        <?php esc_html_e( 'Activity Filter Settings', 'bp-activity-filter' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="wbcom-welcome-panel-column">
                            <h3><?php esc_html_e( 'Quick Links', 'bp-activity-filter' ); ?></h3>
                            <ul class="wbcom-action-list">
                                <li><a href="https://wbcomdesigns.com/support/" target="_blank" class="button button-secondary"><?php esc_html_e( 'Get Support', 'bp-activity-filter' ); ?></a></li>
                                <li><a href="https://wbcomdesigns.com/downloads/" target="_blank" class="button button-secondary"><?php esc_html_e( 'Browse Plugins', 'bp-activity-filter' ); ?></a></li>
                                <li><a href="https://docs.wbcomdesigns.com/" target="_blank" class="button button-secondary"><?php esc_html_e( 'Documentation', 'bp-activity-filter' ); ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Get eligible post types for activity generation.
     *
     * @since 4.0.0
     * @return array Eligible post types.
     */
    private function get_eligible_post_types() {
        return BP_Activity_Filter_Helper::get_eligible_post_types();
    }

    /**
     * Get current admin tab.
     *
     * @since 4.0.0
     * @return string Current tab slug.
     */
    private function get_current_tab() {
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'default';
        
        $valid_tabs = array( 'default', 'hidden', 'cpt' );
        if ( ! in_array( $tab, $valid_tabs, true ) ) {
            $tab = 'default';
        }
        
        $this->current_tab = $tab;
        return $this->current_tab;
    }

    /**
     * Register plugin settings.
     *
     * @since 4.0.0
     */
    public function register_settings() {
        $settings = array(
            'bp_activity_filter_default' => array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_default_filter' ),
                'default'           => '0',
            ),
            'bp_activity_filter_profile_default' => array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_default_filter' ),
                'default'           => '-1',
            ),
            'bp_activity_filter_hidden' => array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_hidden_activities' ),
                'default'           => array(),
            ),
            'bp_activity_filter_cpt_settings' => array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_cpt_settings' ),
                'default'           => array(),
            ),
        );

        foreach ( $settings as $option => $args ) {
            register_setting( 'bp_activity_filter_settings', $option, $args );
        }
    }

    /**
     * Sanitize default filter values.
     *
     * @since 4.0.0
     * @param string $input Raw input value.
     * @return string Sanitized filter value.
     */
    public function sanitize_default_filter( $input ) {
        if ( empty( $input ) ) {
            return '0';
        }

        $input = sanitize_text_field( $input );
        $valid_actions = array_keys( BP_Activity_Filter_Helper::get_activity_actions() );
        $valid_actions[] = '0';
        $valid_actions[] = '-1';

        return in_array( $input, $valid_actions, true ) ? $input : '0';
    }

    /**
     * Sanitize hidden activities array.
     *
     * @since 4.0.0
     * @param mixed $input Raw input value.
     * @return array Sanitized array of activity types.
     */
    public function sanitize_hidden_activities( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();
        $valid_actions = array_keys( BP_Activity_Filter_Helper::get_activity_actions() );

        foreach ( $input as $activity_type ) {
            $activity_type = sanitize_text_field( $activity_type );
            if ( ! empty( $activity_type ) && in_array( $activity_type, $valid_actions, true ) ) {
                $sanitized[] = $activity_type;
            }
        }

        return array_unique( $sanitized );
    }

    /**
     * Sanitize CPT settings array.
     *
     * @since 4.0.0
     * @param mixed $input Raw input value.
     * @return array Sanitized CPT settings.
     */
    public function sanitize_cpt_settings( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();
        $valid_post_types = get_post_types( array( 'public' => true ), 'names' );

        foreach ( $input as $post_type => $settings ) {
            $post_type = sanitize_text_field( $post_type );
            
            if ( '_global' === $post_type ) {
                $sanitized[ $post_type ] = array(
                    'hide_sitewide' => ! empty( $settings['hide_sitewide'] ),
                );
            } elseif ( in_array( $post_type, $valid_post_types, true ) ) {
                $sanitized[ $post_type ] = array(
                    'enabled' => ! empty( $settings['enabled'] ),
                    'label'   => isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '',
                );
            }
        }

        return $sanitized;
    }

    /**
     * Save plugin settings from form submission.
     *
     * @since 4.0.0
     */
    private function save_settings() {
        if ( ! isset( $_POST['bp_activity_filter_nonce'] ) || ! wp_verify_nonce( $_POST['bp_activity_filter_nonce'], 'bp_activity_filter_save_settings' ) ) {
            add_settings_error(
                'bp_activity_filter_settings',
                'nonce_failed',
                esc_html__( 'Security check failed. Please try again.', 'bp-activity-filter' ),
                'error'
            );
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            add_settings_error(
                'bp_activity_filter_settings',
                'permission_denied',
                esc_html__( 'You do not have sufficient permissions to access this page.', 'bp-activity-filter' ),
                'error'
            );
            return;
        }

        $current_tab = isset( $_POST['current_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['current_tab'] ) ) : 'default';
        $updated = false;

        try {
            switch ( $current_tab ) {
                case 'hidden':
                    $updated = $this->save_hidden_activities();
                    break;
                    
                case 'cpt':
                    $updated = $this->save_cpt_settings();
                    break;
                    
                case 'default':
                default:
                    $updated = $this->save_default_filters();
                    break;
            }

            $this->clear_activity_filter_cookies();

            if ( $updated ) {
                add_settings_error(
                    'bp_activity_filter_settings',
                    'settings_updated',
                    esc_html__( 'Settings saved successfully.', 'bp-activity-filter' ),
                    'updated'
                );
            } else {
                add_settings_error(
                    'bp_activity_filter_settings',
                    'no_changes',
                    esc_html__( 'No changes were made to the settings.', 'bp-activity-filter' ),
                    'updated'
                );
            }

        } catch ( Exception $e ) {
            add_settings_error(
                'bp_activity_filter_settings',
                'save_error',
                sprintf(
                    /* translators: %s: Error message */
                    esc_html__( 'Error saving settings: %s', 'bp-activity-filter' ),
                    esc_html( $e->getMessage() )
                ),
                'error'
            );
        }
    }

    /**
     * Save default filters settings.
     *
     * @since 4.0.0
     * @return bool Whether any settings were updated.
     */
    private function save_default_filters() {
        $updated = false;

        if ( isset( $_POST['bp_activity_filter_default'] ) ) {
            $default_filter = $this->sanitize_default_filter( wp_unslash( $_POST['bp_activity_filter_default'] ) );
            $old_value = get_option( 'bp_activity_filter_default' );
            
            if ( $old_value !== $default_filter ) {
                update_option( 'bp_activity_filter_default', $default_filter );
                $updated = true;
            }
        }

        if ( isset( $_POST['bp_activity_filter_profile_default'] ) ) {
            $profile_default = $this->sanitize_default_filter( wp_unslash( $_POST['bp_activity_filter_profile_default'] ) );
            $old_value = get_option( 'bp_activity_filter_profile_default' );
            
            if ( $old_value !== $profile_default ) {
                update_option( 'bp_activity_filter_profile_default', $profile_default );
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Save hidden activities settings.
     *
     * @since 4.0.0
     * @return bool Whether any settings were updated.
     */
    private function save_hidden_activities() {
        $hidden = array();
        
        if ( isset( $_POST['bp_activity_filter_hidden'] ) && is_array( $_POST['bp_activity_filter_hidden'] ) ) {
            $hidden = $this->sanitize_hidden_activities( wp_unslash( $_POST['bp_activity_filter_hidden'] ) );
        }
        
        $old_hidden = get_option( 'bp_activity_filter_hidden', array() );
        $is_different = ( wp_json_encode( $old_hidden ) !== wp_json_encode( $hidden ) );
        
        if ( $is_different ) {
            return update_option( 'bp_activity_filter_hidden', $hidden );
        }
        
        return false;
    }

    /**
     * Save CPT settings.
     *
     * @since 4.0.0
     * @return bool Whether any settings were updated.
     */
    private function save_cpt_settings() {
        $old_cpt_settings = get_option( 'bp_activity_filter_cpt_settings', array() );
        $cpt_settings = array();

        if ( isset( $_POST['bp_activity_filter_cpt_settings'] ) && is_array( $_POST['bp_activity_filter_cpt_settings'] ) ) {
            $cpt_settings = $this->sanitize_cpt_settings( wp_unslash( $_POST['bp_activity_filter_cpt_settings'] ) );
        }
        
        $is_different = ( wp_json_encode( $old_cpt_settings ) !== wp_json_encode( $cpt_settings ) );
        
        if ( $is_different ) {
            return update_option( 'bp_activity_filter_cpt_settings', $cpt_settings );
        }
        
        return false;
    }

    /**
     * Clear activity filter cookies.
     *
     * @since 4.0.0
     */
    private function clear_activity_filter_cookies() {
        $cookies_to_clear = array(
            'bp-activity-filter',
            'bp_activity_filter_apply',
        );

        foreach ( $cookies_to_clear as $cookie ) {
            if ( isset( $_COOKIE[ $cookie ] ) ) {
                setcookie( $cookie, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
                unset( $_COOKIE[ $cookie ] );
            }
        }
    }

    /**
     * Handle activation redirect.
     *
     * @since 4.0.0
     */
    public function handle_activation_redirect() {
        if ( get_transient( 'bp_activity_filter_activation_redirect' ) ) {
            delete_transient( 'bp_activity_filter_activation_redirect' );
            if ( ! isset( $_GET['activate-multi'] ) && ! wp_doing_ajax() ) {
                wp_safe_redirect( $this->get_admin_page_url() );
                exit;
            }
        }
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 4.0.0
     * @param string $hook_suffix Current admin page hook suffix.
     */
    public function enqueue_admin_scripts( $hook_suffix ) {
        $valid_hooks = array(
            'settings_page_bp-activity-filter-settings',
            'wbcom-designs_page_wbcom-activity-filter',
            'toplevel_page_bp-activity-filter-standalone',
            'admin_page_wbcom-activity-filter',
        );

        if ( ! in_array( $hook_suffix, $valid_hooks, true ) ) {
            return;
        }

        wp_enqueue_style(
            'bp-activity-filter-admin',
            BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BP_ACTIVITY_FILTER_VERSION
        );

        wp_enqueue_script(
            'bp-activity-filter-admin',
            BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            BP_ACTIVITY_FILTER_VERSION,
            true
        );

        wp_localize_script(
            'bp-activity-filter-admin',
            'bpActivityFilterAdmin',
            array(
                'nonce'        => wp_create_nonce( 'bp_activity_filter_admin' ),
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'currentTab'   => $this->get_current_tab(),
                'strings'      => array(
                    'saving'           => esc_html__( 'Saving...', 'bp-activity-filter' ),
                    'saved'            => esc_html__( 'Settings saved!', 'bp-activity-filter' ),
                    'error'            => esc_html__( 'Error saving settings.', 'bp-activity-filter' ),
                    'confirmReset'     => esc_html__( 'Are you sure you want to reset all settings?', 'bp-activity-filter' ),
                    'selectAll'        => esc_html__( 'Select All', 'bp-activity-filter' ),
                    'deselectAll'      => esc_html__( 'Deselect All', 'bp-activity-filter' ),
                ),
            )
        );
    }

    /**
     * Add admin head styles for immediate styling.
     *
     * @since 4.0.0
     */
    public function add_admin_head_styles() {
        $screen = get_current_screen();
        if ( ! $screen || false === strpos( $screen->id, 'activity-filter' ) ) {
            return;
        }

        ?>
        <style>
        /* Immediate styling to prevent flash of unstyled content */
        .bp-activity-filter-admin {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .bp-activity-filter-admin.loaded {
            opacity: 1;
        }
        .nav-tab-wrapper {
            margin-bottom: 0 !important;
        }
        .tab-content-wrapper {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 0 0 4px 4px;
        }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var adminWrap = document.querySelector('.bp-activity-filter-admin');
            if (adminWrap) {
                adminWrap.classList.add('loaded');
            }
        });
        </script>
        <?php
    }

    /**
     * Display admin notices.
     *
     * @since 4.0.0
     */
    public function display_admin_notices() {
        // Show notices only on our admin pages
        $screen = get_current_screen();
        if ( ! $screen || false === strpos( $screen->id, 'activity-filter' ) ) {
            return;
        }

        // Additional admin notices can be added here
    }

    /**
     * Add help tabs to admin page.
     *
     * @since 4.0.0
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_overview',
                'title'   => esc_html__( 'Overview', 'bp-activity-filter' ),
                'content' => $this->get_help_tab_content_overview(),
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_default_filters',
                'title'   => esc_html__( 'Default Filters', 'bp-activity-filter' ),
                'content' => $this->get_help_tab_content_default_filters(),
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_hidden_activities',
                'title'   => esc_html__( 'Hidden Activities', 'bp-activity-filter' ),
                'content' => $this->get_help_tab_content_hidden_activities(),
            )
        );

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_custom_post_types',
                'title'   => esc_html__( 'Custom Post Types', 'bp-activity-filter' ),
                'content' => $this->get_help_tab_content_custom_post_types(),
            )
        );

        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__( 'For more information:', 'bp-activity-filter' ) . '</strong></p>' .
            '<p><a href="https://wbcomdesigns.com/support/" target="_blank">' . esc_html__( 'Support', 'bp-activity-filter' ) . '</a></p>' .
            '<p><a href="https://docs.wbcomdesigns.com/" target="_blank">' . esc_html__( 'Documentation', 'bp-activity-filter' ) . '</a></p>' .
            '<p><a href="https://wordpress.org/plugins/bp-activity-filter/" target="_blank">' . esc_html__( 'Plugin Page', 'bp-activity-filter' ) . '</a></p>'
        );
    }

    /**
     * Get help tab content for overview.
     *
     * @since 4.0.0
     * @return string Help content HTML.
     */
    private function get_help_tab_content_overview() {
        return '<h3>' . esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ) . '</h3>' .
               '<p>' . esc_html__( 'This plugin allows you to customize how BuddyPress activities are displayed and filtered on your site.', 'bp-activity-filter' ) . '</p>' .
               '<p>' . esc_html__( 'Use the tabs above to configure different aspects of activity filtering:', 'bp-activity-filter' ) . '</p>' .
               '<ul>' .
               '<li><strong>' . esc_html__( 'Default Filters:', 'bp-activity-filter' ) . '</strong> ' . esc_html__( 'Set what activity type is shown by default', 'bp-activity-filter' ) . '</li>' .
               '<li><strong>' . esc_html__( 'Hidden Activities:', 'bp-activity-filter' ) . '</strong> ' . esc_html__( 'Hide specific activity types completely', 'bp-activity-filter' ) . '</li>' .
               '<li><strong>' . esc_html__( 'Custom Post Types:', 'bp-activity-filter' ) . '</strong> ' . esc_html__( 'Enable activities for custom post types', 'bp-activity-filter' ) . '</li>' .
               '</ul>';
    }

    /**
     * Get help tab content for default filters.
     *
     * @since 4.0.0
     * @return string Help content HTML.
     */
    private function get_help_tab_content_default_filters() {
        return '<h3>' . esc_html__( 'Default Filters', 'bp-activity-filter' ) . '</h3>' .
               '<p>' . esc_html__( 'Default filters determine what type of activities are shown when users first visit activity streams.', 'bp-activity-filter' ) . '</p>' .
               '<h4>' . esc_html__( 'Site-wide Activity Default', 'bp-activity-filter' ) . '</h4>' .
               '<p>' . esc_html__( 'This setting applies to the main activity directory that all users see.', 'bp-activity-filter' ) . '</p>' .
               '<h4>' . esc_html__( 'Profile Activity Default', 'bp-activity-filter' ) . '</h4>' .
               '<p>' . esc_html__( 'This setting applies to individual user profile activity streams.', 'bp-activity-filter' ) . '</p>' .
               '<p><strong>' . esc_html__( 'Note:', 'bp-activity-filter' ) . '</strong> ' . esc_html__( 'Users can still change the filter using the dropdown, but these settings determine what they see initially.', 'bp-activity-filter' ) . '</p>';
    }

    /**
     * Get help tab content for hidden activities.
     *
     * @since 4.0.0
     * @return string Help content HTML.
     */
    private function get_help_tab_content_hidden_activities() {
        return '<h3>' . esc_html__( 'Hidden Activities', 'bp-activity-filter' ) . '</h3>' .
               '<p>' . esc_html__( 'Use this section to completely hide specific types of activities from all activity streams.', 'bp-activity-filter' ) . '</p>' .
               '<p>' . esc_html__( 'Hidden activities will not appear in:', 'bp-activity-filter' ) . '</p>' .
               '<ul>' .
               '<li>' . esc_html__( 'Activity streams (site-wide or profile)', 'bp-activity-filter' ) . '</li>' .
               '<li>' . esc_html__( 'Activity filter dropdown options', 'bp-activity-filter' ) . '</li>' .
               '<li>' . esc_html__( 'Activity feeds or notifications', 'bp-activity-filter' ) . '</li>' .
               '</ul>' .
               '<p><strong>' . esc_html__( 'Warning:', 'bp-activity-filter' ) . '</strong> ' . esc_html__( 'This affects all users on your site. Use this feature carefully as it cannot be overridden by individual users.', 'bp-activity-filter' ) . '</p>';
    }

    /**
     * Get help tab content for custom post types.
     *
     * @since 4.0.0
     * @return string Help content HTML.
     */
    private function get_help_tab_content_custom_post_types() {
        return '<h3>' . esc_html__( 'Custom Post Types', 'bp-activity-filter' ) . '</h3>' .
               '<p>' . esc_html__( 'Enable automatic activity generation when custom post types are published.', 'bp-activity-filter' ) . '</p>' .
               '<h4>' . esc_html__( 'Requirements', 'bp-activity-filter' ) . '</h4>' .
               '<p>' . esc_html__( 'Only custom post types that are public and have admin UI enabled will appear here.', 'bp-activity-filter' ) . '</p>' .
               '<h4>' . esc_html__( 'How it works', 'bp-activity-filter' ) . '</h4>' .
               '<p>' . esc_html__( 'When someone publishes a post of the enabled custom post type, an activity entry will be automatically created showing:', 'bp-activity-filter' ) . '</p>' .
               '<ul>' .
               '<li>' . esc_html__( 'The author name (linked to their profile)', 'bp-activity-filter' ) . '</li>' .
               '<li>' . esc_html__( 'The post type name or your custom label', 'bp-activity-filter' ) . '</li>' .
               '<li>' . esc_html__( 'The post title (linked to the post)', 'bp-activity-filter' ) . '</li>' .
               '</ul>' .
               '<p><strong>' . esc_html__( 'Note:', 'bp-activity-filter' ) . '</strong> ' . esc_html__( 'Only new posts published after enabling this feature will generate activities. Existing posts will not create activities.', 'bp-activity-filter' ) . '</p>';
    }

    /**
     * Handle AJAX test action.
     *
     * @since 4.0.0
     */
    public function handle_ajax_test() {
        check_ajax_referer( 'bp_activity_filter_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'bp-activity-filter' ) );
        }

        wp_send_json_success( array( 'message' => esc_html__( 'AJAX test successful!', 'bp-activity-filter' ) ) );
    }

    /**
     * Add plugin action links.
     *
     * @since 4.0.0
     * @param array $links Existing plugin action links.
     * @return array Modified plugin action links.
     */
    public function plugin_action_links( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $this->get_admin_page_url() ),
            esc_html__( 'Settings', 'bp-activity-filter' )
        );

        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Prevent cloning.
     *
     * @since 4.0.0
     */
    public function __clone() {
        _doing_it_wrong(
            __FUNCTION__,
            esc_html__( 'Cloning instances of this class is forbidden.', 'bp-activity-filter' ),
            '4.0.0'
        );
    }

    /**
     * Prevent unserializing.
     *
     * @since 4.0.0
     */
    public function __wakeup() {
        _doing_it_wrong(
            __FUNCTION__,
            esc_html__( 'Unserializing instances of this class is forbidden.', 'bp-activity-filter' ),
            '4.0.0'
        );
    }
}

// Initialize the enhanced admin class
if ( is_admin() ) {
    BP_Activity_Filter_Admin_Enhanced::instance();
}