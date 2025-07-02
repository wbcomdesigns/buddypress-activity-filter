<?php
/**
 * Improved Admin Menu Integration for BP Activity Filter
 *
 * Enhanced integration with the Wbcom Designs unified menu system.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BP Activity Filter Admin Class
 */
class BP_Activity_Filter_Admin {

    /**
     * Class instance.
     *
     * @since 4.0.0
     * @var BP_Activity_Filter_Admin|null Singleton instance.
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
     * Get class instance.
     *
     * @since 4.0.0
     * @return BP_Activity_Filter_Admin Singleton instance.
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
        add_action( 'admin_init', array( $this, 'handle_debug_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
        add_action( 'wp_ajax_bp_activity_filter_test_action', array( $this, 'handle_ajax_test' ) );
    }

    /**
     * Handle debug actions from URL parameters
     *
     * @since 4.0.0
     */
    public function handle_debug_actions() {
        if ( isset( $_GET['force_bp_migration'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'force_migration' ) ) {
            // Handle migration logic here
            $this->log_admin_action( 'debug', 'Force migration triggered', 'info' );
        }
    }

    /**
     * Add admin menu page using improved unified Wbcom Designs menu.
     *
     * @since 4.0.0
     * @return string|false Page hook string on success, false on failure.
     */
    public function add_admin_menu() {
        $page_hook = false;
        $menu_creation_method = 'none';

        try {
            // Primary method: Use unified menu system
            $page_hook = $this->try_unified_menu();
            if ( $page_hook ) {
                $menu_creation_method = 'unified_menu';
            }

            // Fallback methods in order of preference
            if ( ! $page_hook ) {
                $page_hook = $this->try_existing_wbcom_menu();
                if ( $page_hook ) {
                    $menu_creation_method = 'existing_menu';
                }
            }

            if ( ! $page_hook ) {
                $page_hook = $this->create_emergency_menu();
                if ( $page_hook ) {
                    $menu_creation_method = 'emergency_menu';
                }
            }

            if ( ! $page_hook ) {
                $page_hook = $this->add_to_settings_menu();
                if ( $page_hook ) {
                    $menu_creation_method = 'settings_fallback';
                }
            }

            // Last resort: standalone menu
            if ( ! $page_hook ) {
                $page_hook = $this->create_standalone_menu();
                if ( $page_hook ) {
                    $menu_creation_method = 'standalone_menu';
                }
            }

        } catch ( Exception $e ) {
            $this->log_admin_action( 'menu_creation', 'Exception: ' . $e->getMessage(), 'error' );
            $page_hook = $this->add_to_settings_menu();
            $menu_creation_method = 'exception_fallback';
        }

        // Store creation info for debugging
        $this->store_menu_info( $menu_creation_method, $page_hook );

        // Add help tabs if successful
        if ( $page_hook ) {
            add_action( "load-{$page_hook}", array( $this, 'add_help_tab' ) );
            add_action( "load-{$page_hook}", array( $this, 'add_advanced_help_tabs' ) );
        }

        return $page_hook;
    }

    /**
     * Try to use the unified menu system.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function try_unified_menu() {
        if ( ! class_exists( 'Wbcom_Designs_Menu' ) ) {
            return false;
        }

        $wbcom_menu = Wbcom_Designs_Menu::instance();
        $menu_status = $wbcom_menu->get_menu_status();

        if ( ! ( $menu_status['menu_exists'] || $menu_status['menu_created'] ) ) {
            return false;
        }

        $page_hook = $wbcom_menu->add_submenu(
            'activity-filter',
            esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'wbcom-activity-filter',
            array( $this, 'admin_page' )
        );

        if ( $page_hook ) {
            $this->log_admin_action( 'menu_creation', 'Successfully created menu using unified system', 'info' );
        }

        return $page_hook;
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

        $page_hook = add_submenu_page(
            'wbcom-designs',
            esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'wbcom-activity-filter',
            array( $this, 'admin_page' )
        );

        if ( $page_hook ) {
            $this->log_admin_action( 'menu_creation', 'Added to existing Wbcom menu', 'info' );
        }

        return $page_hook;
    }

    /**
     * Create emergency Wbcom menu.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function create_emergency_menu() {
        $main_menu_hook = add_menu_page(
            esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
            esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
            'manage_options',
            'wbcom-designs',
            array( $this, 'emergency_dashboard_page' ),
            $this->get_menu_icon(),
            58.5
        );

        if ( ! $main_menu_hook ) {
            return false;
        }

        $page_hook = add_submenu_page(
            'wbcom-designs',
            esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'wbcom-activity-filter',
            array( $this, 'admin_page' )
        );

        if ( $page_hook ) {
            $this->log_admin_action( 'menu_creation', 'Created emergency menu system', 'warning' );
        }

        return $page_hook;
    }

    /**
     * Add to WordPress settings menu.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function add_to_settings_menu() {
        $page_hook = add_options_page(
            esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'bp-activity-filter-settings',
            array( $this, 'admin_page' )
        );

        if ( $page_hook ) {
            $this->log_admin_action( 'menu_creation', 'Used WordPress settings menu fallback', 'warning' );
        }

        return $page_hook;
    }

    /**
     * Create standalone menu as last resort.
     *
     * @since 4.0.0
     * @return string|false Page hook on success, false on failure.
     */
    private function create_standalone_menu() {
        $page_hook = add_menu_page(
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            esc_html__( 'Activity Filter', 'bp-activity-filter' ),
            'manage_options',
            'bp-activity-filter-standalone',
            array( $this, 'admin_page' ),
            'dashicons-filter',
            30
        );

        if ( $page_hook ) {
            $this->log_admin_action( 'menu_creation', 'Created standalone menu as last resort', 'error' );
        }

        return $page_hook;
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
     * Store menu creation information for debugging.
     *
     * @since 4.0.0
     * @param string $method Menu creation method used.
     * @param string|false $page_hook Page hook string or false.
     */
    private function store_menu_info( $method, $page_hook ) {
        update_option( 'bp_activity_filter_menu_method', array(
            'method'      => $method,
            'page_hook'   => $page_hook ? $page_hook : false,
            'timestamp'   => current_time( 'mysql' ),
            'wp_version'  => get_bloginfo( 'version' ),
            'php_version' => PHP_VERSION,
        ) );
    }

    /**
     * Handle activation redirect and special admin actions.
     *
     * @since 4.0.0
     */
    public function handle_activation_redirect() {
        // Handle force migration request.
        if ( isset( $_GET['force_bp_migration'] ) && current_user_can( 'manage_options' ) ) {
            check_admin_referer( 'force_migration' );
            $this->handle_force_migration();
        }

        // Handle emergency hidden activities fix.
        if ( isset( $_GET['emergency_hidden_fix'] ) && current_user_can( 'manage_options' ) ) {
            check_admin_referer( 'emergency_fix' );
            $this->handle_emergency_fix();
        }

        // Handle activation redirect.
        if ( get_transient( 'bp_activity_filter_activation_redirect' ) ) {
            delete_transient( 'bp_activity_filter_activation_redirect' );
            if ( ! isset( $_GET['activate-multi'] ) && ! wp_doing_ajax() ) {
                wp_safe_redirect( 
                    add_query_arg( 
                        array( 'page' => 'wbcom-activity-filter' ), 
                        admin_url( 'admin.php' ) 
                    ) 
                );
                exit;
            }
        }
    }

    /**
     * Handle force migration request.
     *
     * @since 4.0.0
     */
    private function handle_force_migration() {
        // Reset migration flags to force re-migration.
        delete_option( 'bp_activity_filter_db_version' );
        delete_option( 'bp_activity_filter_migration_complete' );
        
        // Trigger migration.
        if ( class_exists( 'BP_Activity_Filter_Migration' ) ) {
            $migration = new BP_Activity_Filter_Migration();
            $migration->maybe_migrate();
        }
        
        // Redirect to remove the parameter.
        $redirect_url = remove_query_arg( 'force_bp_migration' );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Handle emergency hidden activities fix.
     *
     * @since 4.0.0
     */
    private function handle_emergency_fix() {
        // Get the test values from URL or use defaults.
        $test_values = array( 'activity_update', 'activity_comment', 'new_member' );
        if ( isset( $_GET['values'] ) ) {
            $test_values = explode( ',', sanitize_text_field( wp_unslash( $_GET['values'] ) ) );
            $test_values = array_map( 'trim', $test_values );
        }
        
        // Force save the hidden activities.
        $result = update_option( 'bp_activity_filter_hidden', $test_values );
        
        // Redirect with result.
        $redirect_url = remove_query_arg( array( 'emergency_hidden_fix', 'values' ) );
        $redirect_url = add_query_arg( 'hidden_fix_result', $result ? 'success' : 'failed', $redirect_url );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Register plugin settings.
     *
     * @since 4.0.0
     */
    public function register_settings() {
        // Register settings with enhanced validation.
        register_setting(
            'bp_activity_filter_settings',
            'bp_activity_filter_default',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_default_filter' ),
                'default'           => '0',
                'show_in_rest'      => false,
            )
        );

        register_setting(
            'bp_activity_filter_settings',
            'bp_activity_filter_profile_default',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_default_filter' ),
                'default'           => '-1',
                'show_in_rest'      => false,
            )
        );

        register_setting(
            'bp_activity_filter_settings',
            'bp_activity_filter_hidden',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_hidden_activities' ),
                'default'           => array(),
                'show_in_rest'      => false,
            )
        );

        register_setting(
            'bp_activity_filter_settings',
            'bp_activity_filter_cpt_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_cpt_settings' ),
                'default'           => array(),
                'show_in_rest'      => false,
            )
        );
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
        
        // Validate against known activity actions.
        $valid_actions = array_keys( BP_Activity_Filter_Helper::get_activity_actions() );
        $valid_actions[] = '0';  // Everything.
        $valid_actions[] = '-1'; // Profile default.

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
            
            // Allow _global settings and valid post types.
            if ( '_global' === $post_type || in_array( $post_type, $valid_post_types, true ) ) {
                if ( '_global' === $post_type ) {
                    $sanitized[ $post_type ] = array(
                        'hide_sitewide' => ! empty( $settings['hide_sitewide'] ),
                    );
                } else {
                    $sanitized[ $post_type ] = array(
                        'enabled' => ! empty( $settings['enabled'] ),
                        'label'   => isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '',
                    );
                }
            }
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 4.0.0
     * @param string $hook_suffix Current admin page hook suffix.
     */
    public function enqueue_admin_scripts( $hook_suffix ) {
        // Check multiple possible hook suffixes since we have fallbacks
        $valid_hooks = array(
            'settings_page_bp-activity-filter',
            'wbcom-designs_page_wbcom-activity-filter',
            'toplevel_page_bp-activity-filter-emergency'
        );

        $should_enqueue = false;
        foreach ( $valid_hooks as $valid_hook ) {
            if ( $hook_suffix === $valid_hook ) {
                $should_enqueue = true;
                break;
            }
        }

        if ( ! $should_enqueue ) {
            return;
        }

        // Enqueue admin stylesheet.
        wp_enqueue_style(
            'bp-activity-filter-admin',
            BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BP_ACTIVITY_FILTER_VERSION
        );

        // Enqueue admin JavaScript.
        wp_enqueue_script(
            'bp-activity-filter-admin',
            BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            BP_ACTIVITY_FILTER_VERSION,
            true
        );

        // Localize script with admin data.
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
     * Get current admin tab.
     *
     * @since 4.0.0
     * @return string Current tab slug.
     */
    private function get_current_tab() {
        // Get tab from URL parameter
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'default';
        
        // Validate tab
        $valid_tabs = array( 'default', 'hidden', 'cpt' );
        if ( ! in_array( $tab, $valid_tabs, true ) ) {
            $tab = 'default';
        }
        
        $this->current_tab = $tab;
        return $this->current_tab;
    }

    /**
     * Display admin notices.
     *
     * @since 4.0.0
     */
    public function display_admin_notices() {
        // Show hidden fix result.
        if ( isset( $_GET['hidden_fix_result'] ) ) {
            $result = sanitize_text_field( wp_unslash( $_GET['hidden_fix_result'] ) );
            $class = 'success' === $result ? 'notice-success' : 'notice-error';
            $message = 'success' === $result 
                ? esc_html__( 'Emergency hidden activities fix applied successfully!', 'bp-activity-filter' )
                : esc_html__( 'Emergency fix failed. Check permissions.', 'bp-activity-filter' );
            
            printf(
                '<div class="notice %s is-dismissible"><p>%s</p></div>',
                esc_attr( $class ),
                esc_html( $message )
            );
        }
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

        wp_send_json_success( array( 'message' => esc_html__( 'Test successful!', 'bp-activity-filter' ) ) );
    }

    /**
     * Display admin page content.
     *
     * @since 4.0.0
     */
    public function admin_page() {
        $current_tab = $this->get_current_tab();
        
        // Handle form submission.
        if ( isset( $_POST['bp_activity_filter_submit'] ) && '1' === $_POST['bp_activity_filter_submit'] ) {
            $this->save_settings();
        }

        ?>
        <div class="wrap bp-activity-filter-admin">
            <h1><?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?></h1>

            <?php settings_errors( 'bp_activity_filter_settings' ); ?>

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

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render admin navigation tabs.
     *
     * @since 4.0.0
     * @param string $current_tab Current active tab.
     */
    private function render_admin_tabs( $current_tab ) {
        $tabs = array(
            'default' => esc_html__( 'Default Filters', 'bp-activity-filter' ),
            'hidden'  => esc_html__( 'Hidden Activities', 'bp-activity-filter' ),
            'cpt'     => esc_html__( 'Custom Post Types', 'bp-activity-filter' ),
        );

        // Get current page URL properly
        $current_url = add_query_arg( array(), admin_url( 'admin.php' ) );
        $base_url = add_query_arg( array( 'page' => 'wbcom-activity-filter' ), $current_url );

        foreach ( $tabs as $tab_key => $tab_label ) {
            $url = add_query_arg( array( 'tab' => $tab_key ), $base_url );
            $active_class = $current_tab === $tab_key ? 'nav-tab-active' : '';
            
            printf(
                '<a href="%s" class="nav-tab %s" role="tab" aria-selected="%s">%s</a>',
                esc_url( $url ),
                esc_attr( $active_class ),
                $current_tab === $tab_key ? 'true' : 'false',
                esc_html( $tab_label )
            );
        }
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
        $default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_default', '0' );
        $profile_default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );
        $activity_actions = BP_Activity_Filter_Helper::get_activity_actions();

        // Check if this is a fresh install.
        $migration_status = get_option( 'bp_activity_filter_migration_complete', false );
        $is_fresh_install = is_array( $migration_status ) && 'fresh_install' === $migration_status['status'];
        ?>
        <div class="settings-section">
            <h2><?php esc_html_e( 'Default Activity Filters', 'bp-activity-filter' ); ?></h2>
            <p><?php esc_html_e( 'Set the default activity filter for different contexts.', 'bp-activity-filter' ); ?></p>

            <?php if ( $is_fresh_install ) : ?>
                <div class="notice notice-info inline">
                    <p>
                        <strong><?php esc_html_e( 'Welcome!', 'bp-activity-filter' ); ?></strong>
                        <?php esc_html_e( 'This is a fresh installation. Default settings have been applied automatically.', 'bp-activity-filter' ); ?>
                    </p>
                </div>
            <?php endif; ?>

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
                                <?php esc_html_e( 'Default filter for the main activity stream.', 'bp-activity-filter' ); ?>
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
                                <?php esc_html_e( 'Default filter for user profile activity streams.', 'bp-activity-filter' ); ?>
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
        $hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );
        $activity_actions = BP_Activity_Filter_Helper::get_activity_actions();
        ?>
        <div class="settings-section">
            <h2><?php esc_html_e( 'Hidden Activity Types', 'bp-activity-filter' ); ?></h2>
            <p><?php esc_html_e( 'Select activity types to hide from the activity stream.', 'bp-activity-filter' ); ?></p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Hide Activity Types', 'bp-activity-filter' ); ?></th>
                        <td>
                            <?php if ( empty( $activity_actions ) ) : ?>
                                <p class="description">
                                    <?php esc_html_e( 'No activity types available. Make sure BuddyPress is properly installed.', 'bp-activity-filter' ); ?>
                                </p>
                            <?php else : ?>
                                <fieldset id="bp-hidden-activities-fieldset">
                                    <legend class="screen-reader-text">
                                        <?php esc_html_e( 'Select activity types to hide', 'bp-activity-filter' ); ?>
                                    </legend>
                                    <?php foreach ( $activity_actions as $key => $label ) : ?>
                                        <?php
                                        $is_checked = in_array( $key, $hidden_activities, true );
                                        $checkbox_id = 'bp_hidden_' . sanitize_html_class( $key );
                                        ?>
                                        <label for="<?php echo esc_attr( $checkbox_id ); ?>" class="bp-activity-checkbox-label">
                                            <input type="checkbox" 
                                                   id="<?php echo esc_attr( $checkbox_id ); ?>"
                                                   name="bp_activity_filter_hidden[]" 
                                                   value="<?php echo esc_attr( $key ); ?>" 
                                                   <?php checked( $is_checked ); ?>
                                                   class="bp-activity-checkbox">
                                            <span class="checkbox-label-text"><?php echo esc_html( $label ); ?></span>
                                            <code class="activity-key"><?php echo esc_html( $key ); ?></code>
                                        </label><br>
                                    <?php endforeach; ?>
                                </fieldset>
                                
                                <div class="hidden-activities-actions">
                                    <button type="button" id="select-all-hidden" class="button button-secondary">
                                        <?php esc_html_e( 'Select All', 'bp-activity-filter' ); ?>
                                    </button>
                                    <button type="button" id="deselect-all-hidden" class="button button-secondary">
                                        <?php esc_html_e( 'Deselect All', 'bp-activity-filter' ); ?>
                                    </button>
                                </div>

                                <p class="description">
                                    <strong><?php esc_html_e( 'Note:', 'bp-activity-filter' ); ?></strong>
                                    <?php esc_html_e( 'Checked activity types will be hidden from the activity stream and will not appear in the filter dropdown.', 'bp-activity-filter' ); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#select-all-hidden').on('click', function(e) {
                e.preventDefault();
                $('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', true);
            });
            
            $('#deselect-all-hidden').on('click', function(e) {
                e.preventDefault();
                $('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', false);
            });
        });
        </script>
        <?php
    }

    /**
     * Render custom post types tab content.
     *
     * @since 4.0.0
     */
    private function render_cpt_tab() {
        $cpt_settings = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_cpt_settings', array() );
        $post_types = $this->get_eligible_post_types();
        ?>
        <div class="settings-section">
            <h2><?php esc_html_e( 'Custom Post Type Activities', 'bp-activity-filter' ); ?></h2>
            <p>
                <?php esc_html_e( 'Enable activity generation for custom post types when they are published. Only public post types with admin interface are shown.', 'bp-activity-filter' ); ?>
            </p>

            <?php if ( empty( $post_types ) ) : ?>
                <div class="notice notice-info inline">
                    <p>
                        <?php esc_html_e( 'No eligible custom post types found.', 'bp-activity-filter' ); ?>
                        <br>
                        <small>
                            <?php esc_html_e( 'Custom post types must be public, have admin UI enabled, and support posts to appear here.', 'bp-activity-filter' ); ?>
                        </small>
                    </p>
                </div>
            <?php else : ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Enable Post Types', 'bp-activity-filter' ); ?></th>
                            <td>
                                <?php foreach ( $post_types as $post_type => $post_type_obj ) : ?>
                                    <?php $this->render_cpt_setting_item( $post_type, $post_type_obj, $cpt_settings ); ?>
                                <?php endforeach; ?>
                                
                                <?php $this->render_cpt_global_settings( $cpt_settings ); ?>

                                <p class="description">
                                    <strong><?php esc_html_e( 'Note:', 'bp-activity-filter' ); ?></strong>
                                    <?php esc_html_e( 'Activities are created automatically when posts are published. Existing posts will not generate activities.', 'bp-activity-filter' ); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
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
        <div class="cpt-setting-item" data-post-type="<?php echo esc_attr( $post_type ); ?>">
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
                            $total_posts 
                        ); 
                        ?>
                    </span>
                </label>
            </div>
            
            <div class="cpt-description">
                <?php if ( ! empty( $post_type_obj->description ) ) : ?>
                    <p class="description"><?php echo esc_html( $post_type_obj->description ); ?></p>
                <?php endif; ?>
            </div>

            <div class="cpt-settings">
                <label class="cpt-label-setting" for="cpt_<?php echo esc_attr( $post_type ); ?>_label">
                    <?php esc_html_e( 'Activity Label:', 'bp-activity-filter' ); ?>
                    <input type="text" 
                           id="cpt_<?php echo esc_attr( $post_type ); ?>_label"
                           name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $post_type ); ?>][label]" 
                           value="<?php echo esc_attr( $label ); ?>" 
                           placeholder="<?php echo esc_attr( strtolower( $post_type_obj->labels->singular_name ) ); ?>"
                           class="cpt-label-input">
                    <br>
                    <small class="description">
                        <?php esc_html_e( 'Leave empty to use default label. This text will appear in activity entries.', 'bp-activity-filter' ); ?>
                    </small>
                </label>

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
            <strong><?php esc_html_e( 'Post Type Info:', 'bp-activity-filter' ); ?></strong>
            <ul class="cpt-info-list">
                <li>
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e( 'Public:', 'bp-activity-filter' ); ?> 
                    <code><?php echo $post_type_obj->public ? esc_html__( 'Yes', 'bp-activity-filter' ) : esc_html__( 'No', 'bp-activity-filter' ); ?></code>
                </li>
                <li>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Admin UI:', 'bp-activity-filter' ); ?> 
                    <code><?php echo $post_type_obj->show_ui ? esc_html__( 'Yes', 'bp-activity-filter' ) : esc_html__( 'No', 'bp-activity-filter' ); ?></code>
                </li>
                <li>
                    <span class="dashicons dashicons-menu-alt"></span>
                    <?php esc_html_e( 'Menu Position:', 'bp-activity-filter' ); ?> 
                    <code><?php echo $post_type_obj->show_in_menu ? esc_html__( 'Shown', 'bp-activity-filter' ) : esc_html__( 'Hidden', 'bp-activity-filter' ); ?></code>
                </li>
                <?php if ( ! empty( $post_type_obj->capability_type ) ) : ?>
                    <li>
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e( 'Capability:', 'bp-activity-filter' ); ?> 
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
                <?php esc_html_e( 'Hide CPT activities from site-wide activity stream', 'bp-activity-filter' ); ?>
                <br>
                <small class="description">
                    <?php esc_html_e( 'When enabled, custom post type activities will only appear in author profiles, not in the main activity feed.', 'bp-activity-filter' ); ?>
                </small>
            </label>
        </div>
        <?php
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
     * Save plugin settings from form submission.
     *
     * @since 4.0.0
     */
    private function save_settings() {
        // Verify nonce.
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
            // Process settings based on current tab.
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

            // Clear activity filter cookies.
            $this->clear_activity_filter_cookies();

            // Show success message.
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

            /**
             * Fires after settings are saved.
             *
             * @since 4.0.0
             * @param string $current_tab The current tab being saved.
             * @param bool   $updated     Whether settings were actually updated.
             */
            do_action( 'bp_activity_filter_settings_saved', $current_tab, $updated );

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

        // Save default filter.
        if ( isset( $_POST['bp_activity_filter_default'] ) ) {
            $default_filter = $this->sanitize_default_filter( wp_unslash( $_POST['bp_activity_filter_default'] ) );
            $old_value = get_option( 'bp_activity_filter_default' );
            
            if ( $old_value !== $default_filter ) {
                update_option( 'bp_activity_filter_default', $default_filter );
                $updated = true;
            }
        }

        // Save profile default filter.
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
        
        // Get old value to check if there's actually a change.
        $old_hidden = get_option( 'bp_activity_filter_hidden', array() );
        
        // Check if values are different.
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
        
        // Check if settings actually changed.
        $is_different = ( wp_json_encode( $old_cpt_settings ) !== wp_json_encode( $cpt_settings ) );
        
        if ( $is_different ) {
            return update_option( 'bp_activity_filter_cpt_settings', $cpt_settings );
        }
        
        // Ensure CPT settings option exists.
        if ( false === get_option( 'bp_activity_filter_cpt_settings' ) ) {
            add_option( 'bp_activity_filter_cpt_settings', array() );
            return true;
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
     * Emergency dashboard page with enhanced functionality.
     *
     * @since 4.0.0
     */
    public function emergency_dashboard_page() {
        $menu_info = get_option( 'bp_activity_filter_menu_method', array() );
        $system_info = $this->get_system_info();
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php esc_html_e( 'Wbcom Designs Dashboard', 'bp-activity-filter' ); ?>
                <span class="emergency-badge"><?php esc_html_e( 'Emergency Mode', 'bp-activity-filter' ); ?></span>
            </h1>
            
            <?php $this->render_success_notice(); ?>
            <?php $this->render_dashboard_content( $menu_info, $system_info ); ?>
            <?php $this->render_emergency_mode_info(); ?>
        </div>

        <style>
            .emergency-badge {
                font-size: 14px;
                font-weight: normal;
                color: #666;
                background: #f0f0f1;
                padding: 2px 8px;
                border-radius: 12px;
                margin-left: 10px;
            }
            .dashboard-container {
                display: flex;
                gap: 20px;
                margin-top: 20px;
            }
            .dashboard-main {
                flex: 1;
            }
            .dashboard-sidebar {
                width: 300px;
            }
            .status-badge {
                background: #d1e7dd;
                color: #0f5132;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            .quick-actions {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 10px;
            }
            .quick-action-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 10px;
            }
            .quick-action-btn .dashicons {
                margin-right: 5px;
            }
        </style>
        <?php
    }

    /**
     * Render success notice.
     *
     * @since 4.0.0
     */
    private function render_success_notice() {
        ?>
        <div class="notice notice-success">
            <p>
                <strong><?php esc_html_e( 'Menu System Active!', 'bp-activity-filter' ); ?></strong> 
                <?php esc_html_e( 'The Wbcom Designs menu system is working. This dashboard provides access to plugin settings and system information.', 'bp-activity-filter' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render main dashboard content.
     *
     * @since 4.0.0
     * @param array $menu_info Menu creation information.
     * @param array $system_info System information.
     */
    private function render_dashboard_content( $menu_info, $system_info ) {
        ?>
        <div class="dashboard-container">
            <div class="dashboard-main">
                <?php $this->render_available_plugins(); ?>
                <?php $this->render_quick_actions(); ?>
            </div>
            <div class="dashboard-sidebar">
                <?php $this->render_system_info( $menu_info, $system_info ); ?>
                <?php $this->render_recent_activity( $menu_info ); ?>
                <?php $this->render_debug_tools( $menu_info ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render available plugins section.
     *
     * @since 4.0.0
     */
    private function render_available_plugins() {
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Available Plugins', 'bp-activity-filter' ); ?></h2>
            </div>
            <div class="inside">
                <ul style="margin: 0; list-style: none;">
                    <li style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f1;">
                        <span class="dashicons dashicons-filter" style="color: #0073aa; margin-right: 10px;"></span>
                        <div style="flex: 1;">
                            <strong>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-activity-filter' ) ); ?>">
                                    <?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?>
                                </a>
                            </strong>
                            <br>
                            <small style="color: #666;">
                                <?php esc_html_e( 'Manage activity filters and custom post types', 'bp-activity-filter' ); ?>
                            </small>
                        </div>
                        <span class="status-badge">
                            <?php esc_html_e( 'ACTIVE', 'bp-activity-filter' ); ?>
                        </span>
                    </li>
                </ul>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
                    <p style="margin: 0; color: #666;">
                        <em><?php esc_html_e( 'Other Wbcom plugins will appear here when installed.', 'bp-activity-filter' ); ?></em>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render quick actions section.
     *
     * @since 4.0.0
     */
    private function render_quick_actions() {
        $actions = array(
            array(
                'url'   => admin_url( 'admin.php?page=wbcom-activity-filter' ),
                'icon'  => 'admin-settings',
                'label' => __( 'Plugin Settings', 'bp-activity-filter' ),
                'class' => 'button-primary'
            ),
            array(
                'url'   => 'https://wbcomdesigns.com/support/',
                'icon'  => 'sos',
                'label' => __( 'Get Support', 'bp-activity-filter' ),
                'class' => 'button-secondary',
                'target' => '_blank'
            ),
            array(
                'url'   => 'https://wbcomdesigns.com/downloads/',
                'icon'  => 'download',
                'label' => __( 'Browse Plugins', 'bp-activity-filter' ),
                'class' => 'button-secondary',
                'target' => '_blank'
            ),
            array(
                'url'   => 'https://docs.wbcomdesigns.com/',
                'icon'  => 'book',
                'label' => __( 'Documentation', 'bp-activity-filter' ),
                'class' => 'button-secondary',
                'target' => '_blank'
            )
        );
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Quick Actions', 'bp-activity-filter' ); ?></h2>
            </div>
            <div class="inside">
                <div class="quick-actions">
                    <?php foreach ( $actions as $action ) : ?>
                        <a href="<?php echo esc_url( $action['url'] ); ?>" 
                           class="button <?php echo esc_attr( $action['class'] ); ?> quick-action-btn"
                           <?php echo isset( $action['target'] ) ? 'target="' . esc_attr( $action['target'] ) . '"' : ''; ?>>
                            <span class="dashicons dashicons-<?php echo esc_attr( $action['icon'] ); ?>"></span>
                            <?php echo esc_html( $action['label'] ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render system information section.
     *
     * @since 4.0.0
     * @param array $menu_info Menu creation information.
     * @param array $system_info System information.
     */
    private function render_system_info( $menu_info, $system_info ) {
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'System Information', 'bp-activity-filter' ); ?></h2>
            </div>
            <div class="inside">
                <table style="width: 100%;">
                    <tr>
                        <td><strong><?php esc_html_e( 'Plugin Version:', 'bp-activity-filter' ); ?></strong></td>
                        <td><?php echo esc_html( $this->get_plugin_version() ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Menu Method:', 'bp-activity-filter' ); ?></strong></td>
                        <td>
                            <span style="background: #fff3cd; color: #664d03; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                <?php echo esc_html( ucwords( str_replace( '_', ' ', $menu_info['method'] ?? 'Unknown' ) ) ); ?>
                            </span>
                        </td>
                    </tr>
                    <?php foreach ( $this->get_system_info_rows( $system_info ) as $row ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $row['label'] ); ?></strong></td>
                            <td><?php echo wp_kses_post( $row['value'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Get system information rows for display.
     *
     * @since 4.0.0
     * @param array $system_info System information.
     * @return array System info rows.
     */
    private function get_system_info_rows( $system_info ) {
        return array(
            array(
                'label' => __( 'WordPress:', 'bp-activity-filter' ),
                'value' => esc_html( $system_info['wp_version'] )
            ),
            array(
                'label' => __( 'BuddyPress:', 'bp-activity-filter' ),
                'value' => $system_info['bp_active'] 
                    ? '<span style="color: #00a32a;">' . esc_html( $system_info['bp_version'] ) . '</span>'
                    : '<span style="color: #d63638;">' . esc_html__( 'Not Active', 'bp-activity-filter' ) . '</span>'
            ),
            array(
                'label' => __( 'PHP Version:', 'bp-activity-filter' ),
                'value' => esc_html( $system_info['php_version'] )
            ),
            array(
                'label' => __( 'Debug Mode:', 'bp-activity-filter' ),
                'value' => $system_info['debug_mode']
                    ? '<span style="color: #d63638;">' . esc_html__( 'Enabled', 'bp-activity-filter' ) . '</span>'
                    : '<span style="color: #00a32a;">' . esc_html__( 'Disabled', 'bp-activity-filter' ) . '</span>'
            )
        );
    }

    /**
     * Render recent activity section.
     *
     * @since 4.0.0
     * @param array $menu_info Menu creation information.
     */
    private function render_recent_activity( $menu_info ) {
        $activities = array(
            array(
                'action' => __( 'Menu system initialized', 'bp-activity-filter' ),
                'time'   => $menu_info['timestamp'] ?? current_time( 'mysql' ),
                'status' => 'success'
            ),
            array(
                'action' => __( 'Plugin activated', 'bp-activity-filter' ),
                'time'   => current_time( 'mysql' ),
                'status' => 'success'
            ),
        );
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Recent Activity', 'bp-activity-filter' ); ?></h2>
            </div>
            <div class="inside">
                <ul style="margin: 0; list-style: none;">
                    <?php foreach ( $activities as $activity ) : ?>
                        <li style="display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f1;">
                            <span class="dashicons dashicons-yes-alt" style="color: #00a32a; margin-right: 8px; font-size: 16px;"></span>
                            <div style="flex: 1;">
                                <div style="font-weight: 500;"><?php echo esc_html( $activity['action'] ); ?></div>
                                <small style="color: #666;">
                                    <?php echo esc_html( human_time_diff( strtotime( $activity['time'] ), current_time( 'timestamp' ) ) . ' ago' ); ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render debug tools section.
     *
     * @since 4.0.0
     * @param array $menu_info Menu creation information.
     */
    private function render_debug_tools( $menu_info ) {
        ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Debug Tools', 'bp-activity-filter' ); ?></h2>
            </div>
            <div class="inside">
                <p style="margin-top: 0;"><?php esc_html_e( 'Advanced troubleshooting tools for developers.', 'bp-activity-filter' ); ?></p>
                
                <div style="margin: 10px 0;">
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wbcom-designs&force_bp_migration=1' ), 'force_migration' ) ); ?>" 
                       class="button button-small">
                        <?php esc_html_e( 'Force Migration', 'bp-activity-filter' ); ?>
                    </a>
                </div>
                
                <details style="margin: 10px 0;">
                    <summary style="cursor: pointer; font-weight: 500;"><?php esc_html_e( 'Menu Debug Info', 'bp-activity-filter' ); ?></summary>
                    <pre style="background: #f0f0f1; padding: 10px; font-size: 11px; overflow-x: auto; margin: 5px 0;">
<?php echo esc_html( wp_json_encode( $menu_info, JSON_PRETTY_PRINT ) ); ?>
                    </pre>
                </details>
            </div>
        </div>
        <?php
    }

    /**
     * Render emergency mode information.
     *
     * @since 4.0.0
     */
    private function render_emergency_mode_info() {
        ?>
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0;"><?php esc_html_e( 'About Emergency Mode', 'bp-activity-filter' ); ?></h3>
            <p style="margin: 0;">
                <?php esc_html_e( 'This emergency dashboard ensures you always have access to your Wbcom Designs plugins, even if the main menu system encounters issues. All plugin functionality remains fully available.', 'bp-activity-filter' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Get system information for dashboard.
     *
     * @since 4.0.0
     * @return array System information.
     */
    private function get_system_info() {
        return array(
            'wp_version'         => get_bloginfo( 'version' ),
            'php_version'        => PHP_VERSION,
            'bp_active'          => function_exists( 'buddypress' ),
            'bp_version'         => function_exists( 'buddypress' ) ? buddypress()->version : 'N/A',
            'debug_mode'         => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'multisite'          => is_multisite(),
            'memory_limit'       => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
        );
    }

    /**
     * Get plugin version.
     *
     * @since 4.0.0
     * @return string Plugin version.
     */
    private function get_plugin_version() {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_data = get_plugin_data( __FILE__ );
        return $plugin_data['Version'] ?? '4.0.0';
    }

    /**
     * Get plugin information for dashboard.
     *
     * @since 4.0.0
     * @return array Plugin information.
     */
    private function get_plugin_info() {
        return array(
            'version' => $this->get_plugin_version(),
            'name'    => 'BuddyPress Activity Filter',
            'slug'    => 'bp-activity-filter',
        );
    }

    /**
     * Log admin actions for debugging.
     *
     * @since 4.0.0
     * @param string $action Action being performed.
     * @param string $message Log message.
     * @param string $level Log level (info, warning, error).
     */
    public function log_admin_action( $action, $message, $level = 'info' ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( '[BP Activity Filter] %s: %s', strtoupper( $level ), $message ) );
        }

        // Store in options for dashboard display
        $logs = get_option( 'bp_activity_filter_logs', array() );
        $logs[] = array(
            'action'    => $action,
            'message'   => $message,
            'level'     => $level,
            'timestamp' => current_time( 'mysql' ),
        );

        // Keep only last 50 log entries
        if ( count( $logs ) > 50 ) {
            $logs = array_slice( $logs, -50 );
        }

        update_option( 'bp_activity_filter_logs', $logs );
    }

    /**
     * Add basic help tab.
     *
     * @since 4.0.0
     */
    public function add_help_tab() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $screen->add_help_tab(
            array(
                'id'      => 'bp_activity_filter_overview',
                'title'   => esc_html__( 'Overview', 'bp-activity-filter' ),
                'content' => '<h3>' . esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ) . '</h3>' .
                           '<p>' . esc_html__( 'This plugin allows you to filter BuddyPress activities and customize the activity stream display.', 'bp-activity-filter' ) . '</p>' .
                           '<p>' . esc_html__( 'Use the settings on this page to configure how activity filtering works on your site.', 'bp-activity-filter' ) . '</p>',
            )
        );

        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__( 'For more information:', 'bp-activity-filter' ) . '</strong></p>' .
            '<p><a href="https://wbcomdesigns.com/support/" target="_blank">' . esc_html__( 'Support', 'bp-activity-filter' ) . '</a></p>' .
            '<p><a href="https://docs.wbcomdesigns.com/" target="_blank">' . esc_html__( 'Documentation', 'bp-activity-filter' ) . '</a></p>'
        );
    }

    /**
     * Add advanced help tabs with more detailed information.
     *
     * @since 4.0.0
     */
    public function add_advanced_help_tabs() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $help_tabs = array(
            array(
                'id'      => 'bp_activity_filter_troubleshooting',
                'title'   => __( 'Troubleshooting', 'bp-activity-filter' ),
                'content' => $this->get_troubleshooting_help_content(),
            ),
            array(
                'id'      => 'bp_activity_filter_requirements',
                'title'   => __( 'System Requirements', 'bp-activity-filter' ),
                'content' => $this->get_requirements_help_content(),
            ),
            array(
                'id'      => 'bp_activity_filter_developer',
                'title'   => __( 'Developer Info', 'bp-activity-filter' ),
                'content' => $this->get_developer_help_content(),
            ),
        );

        foreach ( $help_tabs as $tab ) {
            $screen->add_help_tab( $tab );
        }
    }

    /**
     * Get troubleshooting help content.
     *
     * @since 4.0.0
     * @return string Help content HTML.
     */
    private function get_troubleshooting_help_content() {
        $content = '<h3>' . esc_html__( 'Common Issues', 'bp-activity-filter' ) . '</h3>';
        
        $issues = array(
            __( 'Menu Not Appearing', 'bp-activity-filter' ) => array(
                __( 'Ensure you have "manage_options" capability', 'bp-activity-filter' ),
                __( 'Check if other plugins are conflicting', 'bp-activity-filter' ),
                __( 'Try deactivating and reactivating the plugin', 'bp-activity-filter' ),
            ),
            __( 'Settings Not Saving', 'bp-activity-filter' ) => array(
                __( 'Verify PHP memory limit (recommended: 256MB+)', 'bp-activity-filter' ),
                __( 'Check file permissions on wp-content directory', 'bp-activity-filter' ),
                __( 'Disable any caching plugins temporarily', 'bp-activity-filter' ),
            ),
            __( 'Activity Filters Not Working', 'bp-activity-filter' ) => array(
                __( 'Ensure BuddyPress is active and up to date', 'bp-activity-filter' ),
                __( 'Clear browser cookies and cache', 'bp-activity-filter' ),
                __( 'Check for JavaScript errors in browser console', 'bp-activity-filter' ),
            ),
        );

        foreach ( $issues as $issue_title => $solutions ) {
            $content .= '<h4>' . esc_html( $issue_title ) . '</h4><ul>';
            foreach ( $solutions as $solution ) {
                $content .= '<li>' . esc_html( $solution ) . '</li>';
            }
            $content .= '</ul>';
        }

        return $content;
    }

    /**
     * Get system requirements help content.
     *
     * @since 4.0.0
     * @return string Help content HTML.
     */
    private function get_requirements_help_content() {
        $current_system = $this->get_system_info();
        
        $requirements = array(
            array(
                'component' => 'WordPress',
                'required'  => '5.0+',
                'current'   => $current_system['wp_version'],
                'status'    => version_compare( $current_system['wp_version'], '5.0', '>=' )
            ),
            array(
                'component' => 'BuddyPress',
                'required'  => '5.0+',
                'current'   => $current_system['bp_version'],
                'status'    => $current_system['bp_active'] && version_compare( $current_system['bp_version'], '5.0', '>=' )
            ),
            array(
                'component' => 'PHP',
                'required'  => '7.4+',
                'current'   => $current_system['php_version'],
                'status'    => version_compare( $current_system['php_version'], '7.4', '>=' )
            ),
            array(
                'component' => 'Memory Limit',
                'required'  => '128MB+',
                'current'   => $current_system['memory_limit'],
                'status'    => intval( $current_system['memory_limit'] ) >= 128
            ),
        );

        $content = '<h3>' . esc_html__( 'Minimum Requirements', 'bp-activity-filter' ) . '</h3>';
        $content .= '<table class="widefat striped">';
        $content .= '<thead><tr>';
        $content .= '<th>' . esc_html__( 'Component', 'bp-activity-filter' ) . '</th>';
        $content .= '<th>' . esc_html__( 'Required', 'bp-activity-filter' ) . '</th>';
        $content .= '<th>' . esc_html__( 'Current', 'bp-activity-filter' ) . '</th>';
        $content .= '<th>' . esc_html__( 'Status', 'bp-activity-filter' ) . '</th>';
        $content .= '</tr></thead><tbody>';

        foreach ( $requirements as $req ) {
            $content .= '<tr>';
            $content .= '<td>' . esc_html( $req['component'] ) . '</td>';
            $content .= '<td>' . esc_html( $req['required'] ) . '</td>';
            $content .= '<td>' . esc_html( $req['current'] ) . '</td>';
            $content .= '<td>' . ( $req['status'] ? '✓' : '✗' ) . '</td>';
            $content .= '</tr>';
        }

        $content .= '</tbody></table>';
        
        $recommendations = array(
            __( 'PHP 8.0 or higher for better performance', 'bp-activity-filter' ),
            __( '256MB+ PHP memory limit', 'bp-activity-filter' ),
            __( 'Modern browser with JavaScript enabled', 'bp-activity-filter' ),
            __( 'HTTPS for secure cookie handling', 'bp-activity-filter' ),
        );

        $content .= '<h3>' . esc_html__( 'Recommended', 'bp-activity-filter' ) . '</h3><ul>';
        foreach ( $recommendations as $recommendation ) {
            $content .= '<li>' . esc_html( $recommendation ) . '</li>';
        }
        $content .= '</ul>';

        return $content;
    }

    /**
     * Get developer help content.
     *
     * @since 4.0.0
     * @return string Help content HTML.
     */
    private function get_developer_help_content() {
        $hooks = array(
            __( 'Action Hooks', 'bp-activity-filter' ) => array(
                'bp_activity_filter_init' => 'Plugin initialization',
                'bp_activity_filter_settings_saved' => 'After settings save',
                'bp_activity_filter_cpt_activity_created' => 'When CPT activity is created',
            ),
            __( 'Filter Hooks', 'bp-activity-filter' ) => array(
                'bp_activity_filter_default' => 'Modify default filter value',
                'bp_activity_filter_available_filters' => 'Customize available filters',
                'bp_activity_filter_query_args' => 'Modify activity query arguments',
            ),
        );

        $content = '<h3>' . esc_html__( 'Developer Hooks', 'bp-activity-filter' ) . '</h3>';
        
        foreach ( $hooks as $section_title => $section_hooks ) {
            $content .= '<h4>' . esc_html( $section_title ) . '</h4><ul>';
            foreach ( $section_hooks as $hook => $description ) {
                $content .= '<li><code>' . esc_html( $hook ) . '</code> - ' . esc_html( $description ) . '</li>';
            }
            $content .= '</ul>';
        }

        $content .= '<h3>' . esc_html__( 'Menu System API', 'bp-activity-filter' ) . '</h3>';
        $content .= '<p>' . esc_html__( 'Access the menu system:', 'bp-activity-filter' ) . '</p>';
        $content .= '<pre><code>$menu = Wbcom_Designs_Menu::instance();
$status = $menu->get_menu_status();
$submenus = $menu->get_submenus();</code></pre>';

        $content .= '<h3>' . esc_html__( 'Debug Information', 'bp-activity-filter' ) . '</h3>';
        $content .= '<p>' . esc_html__( 'Enable WP_DEBUG to see detailed error messages and logging.', 'bp-activity-filter' ) . '</p>';

        return $content;
    }

    /**
     * Add plugin action links in the plugins list.
     *
     * @since 4.0.0
     * @param array $links Existing plugin action links.
     * @return array Modified plugin action links.
     */
    public function plugin_action_links( $links ) {
        // Determine the correct URL based on menu type
        $settings_url = admin_url( 'admin.php?page=wbcom-activity-filter' );
        if ( ! class_exists( 'Wbcom_Designs_Menu' ) ) {
            $settings_url = admin_url( 'options-general.php?page=bp-activity-filter' );
        }

        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $settings_url ),
            esc_html__( 'Settings', 'bp-activity-filter' )
        );

        array_unshift( $links, $settings_link );
        return $links;
    }



    /**
     * Check if current user can manage plugin settings.
     *
     * @since 4.0.0
     * @return bool True if user can manage settings.
     */
    public function current_user_can_manage() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Get menu status for Wbcom menu integration.
     *
     * @since 4.0.0
     * @return array Menu status information.
     */
    public function get_menu_status() {
        return array(
            'menu_exists' => $this->wbcom_menu_exists(),
            'menu_created' => true,
            'unified_menu_available' => class_exists( 'Wbcom_Designs_Menu' ),
            'fallback_used' => ! class_exists( 'Wbcom_Designs_Menu' ),
        );
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