<?php
/**
 * Admin class for BP Activity Filter
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
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Render settings page content.
     *
     * @since 4.0.0
     */
    public function render_settings_page() {
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

            <?php settings_errors( 'bp_activity_filter_settings' ); ?>

            <div class="wbcom-tab-wrapper">
                <nav class="wbcom-nav-tab-wrapper nav-tab-wrapper" role="tablist">
                    <?php $this->render_admin_tabs( $current_tab ); ?>
                </nav>

                <div class="wbcom-tab-content">
                <form method="post" action="" novalidate="novalidate">
                    <?php 
                    wp_nonce_field( 'bp_activity_filter_save_settings', 'bp_activity_filter_nonce' );
                    ?>
                    <input type="hidden" name="current_tab" value="<?php echo esc_attr( $current_tab ); ?>" />
                    <input type="hidden" name="bp_activity_filter_submit" value="1" />

                    <?php $this->render_tab_content( $current_tab ); ?>

                    <!-- Replace the submit section in your admin template -->
                    <?php if ( $current_tab !== 'faq' ) : ?>
                    <div class="submit" style="padding: 1.5em;">
                        <?php submit_button( 
                            esc_html__( 'Save Settings', 'bp-activity-filter' ), 
                            'primary', 
                            'submit', 
                            false, 
                            array( 
                                'id' => 'bp-activity-filter-submit',
                                'style' => 'margin-left: 0;' 
                            ) 
                        ); ?>
                    </div>
                    <?php endif; ?>
                </form>
                </div><!-- .wbcom-tab-content -->
            </div><!-- .wbcom-tab-wrapper -->
        </div>
        
        <style>
        /* Plugin-specific styles only - tab styles handled by shared CSS */
        .settings-section {
            padding: 20px;
        }
        
        .settings-section h2 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .settings-section p {
            margin-bottom: 20px;
            color: #666;
        }
        
        .form-table {
            margin-top: 0;
        }
        
        .form-table th {
            width: 220px;
            padding: 20px 10px 20px 0;
            vertical-align: top;
        }
        
        .form-table td {
            padding: 20px 10px;
        }
        
        /* Checkbox and label alignment */
        .form-table td label {
            display: block;
            margin-bottom: 8px;
        }
        
        .form-table td input[type="checkbox"] {
            margin-right: 8px;
        }
        
        /* CPT table specific styles */
        .bp-activity-filter-admin .cpt-settings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .bp-activity-filter-admin .cpt-settings-table th,
        .bp-activity-filter-admin .cpt-settings-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .bp-activity-filter-admin .cpt-settings-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .wbcom-version {
            font-size: 14px;
            color: #666;
            background: #f0f0f1;
            padding: 2px 8px;
            border-radius: 12px;
        }
        </style>
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
            'faq'     => array(
                'title' => esc_html__( 'FAQ', 'bp-activity-filter' ),
                'icon'  => 'dashicons-editor-help',
            ),
        );

        $base_url = admin_url( 'admin.php?page=wbcom-activity-filter' );

        foreach ( $tabs as $tab_key => $tab_data ) {
            $url = add_query_arg( array( 'tab' => $tab_key ), $base_url );
            $active_class = $current_tab === $tab_key ? 'nav-tab-active' : '';
            
            printf(
                '<a href="%s" class="wbcom-nav-tab nav-tab %s" role="tab" aria-selected="%s" data-tab="%s">
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
            case 'faq':
                $this->render_faq_tab();
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
     * Render hidden activities tab content with CORRECTED logic.
     *
     * @since 4.0.0
     */
    private function render_hidden_activities_tab() {
        $hidden_activities = get_option( 'bp_activity_filter_hidden', array() );
        $activity_actions = BP_Activity_Filter_Helper::get_activity_actions();
        
        // Define core activities that should never be hidden
        $core_readonly_activities = array(
            'activity_update' => __( 'Posted a status update', 'bp-activity-filter' ),
            'activity_comment' => __( 'Replied to a status update', 'bp-activity-filter' ),
        );
        
        ?>
        <div class="settings-section">
            <h2><?php esc_html_e( 'Hidden Activity Types', 'bp-activity-filter' ); ?></h2>
            <p><?php esc_html_e( 'Select activity types to completely hide from all activity streams. Hidden activities will not appear in the activity feed or filter dropdown options.', 'bp-activity-filter' ); ?></p>

            <h3 style="margin-top: 20px; margin-bottom: 15px;"><?php esc_html_e( 'Activity Types to Hide', 'bp-activity-filter' ); ?></h3>
            <div class="activity-selection-area">
                            <?php if ( empty( $activity_actions ) ) : ?>
                                <div class="notice notice-warning inline">
                                    <p><?php esc_html_e( 'No activity types available. Make sure BuddyPress is properly installed and activated.', 'bp-activity-filter' ); ?></p>
                                </div>
                            <?php else : ?>
                                
                                <!-- Core Read-only Activities -->
                                <div style="margin-bottom: 25px; padding: 15px; background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; border-left: 4px solid #0073aa;">
                                    <h4 style="margin: 0 0 10px 0; color: #0073aa; display: flex; align-items: center;">
                                        <span class="dashicons dashicons-shield-alt" style="margin-right: 8px;"></span>
                                        <?php esc_html_e( 'Core Activities (Always Available)', 'bp-activity-filter' ); ?>
                                    </h4>
                                    <p style="margin: 0 0 15px 0; color: #646970; font-size: 13px;">
                                        <?php esc_html_e( 'These essential activity types cannot be hidden as they are required for basic BuddyPress functionality.', 'bp-activity-filter' ); ?>
                                    </p>
                                    
                                    <?php foreach ( $core_readonly_activities as $key => $label ) : ?>
                                        <div style="display: block; margin-bottom: 8px; padding: 10px 12px; background: #e8f5e8; border: 1px solid #4caf50; border-radius: 4px;">
                                            <label style="display: flex; align-items: center; cursor: not-allowed; margin: 0;">
                                                <span class="dashicons dashicons-yes-alt" style="color: #4caf50; margin-right: 12px;"></span>
                                                <span style="flex: 1; font-weight: 500; color: #2e7d32;"><?php echo esc_html( $label ); ?> - Always Visible</span>
                                                <code style="font-size: 11px; background: #c8e6c9; color: #2e7d32; padding: 2px 6px; border-radius: 10px;"><?php echo esc_html( $key ); ?></code>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Other Activities -->
                                <div>
                                    <h4 style="margin: 0 0 15px 0; color: #23282d; display: flex; align-items: center;">
                                        <span class="dashicons dashicons-admin-settings" style="margin-right: 8px;"></span>
                                        <?php esc_html_e( 'Other Activity Types', 'bp-activity-filter' ); ?>
                                    </h4>
                                    <p style="margin: 0 0 15px 0; color: #646970; font-size: 13px;">
                                        <?php esc_html_e( 'Check the boxes below to HIDE these activity types from your site.', 'bp-activity-filter' ); ?>
                                    </p>
                                    
                                    <?php foreach ( $activity_actions as $key => $label ) : ?>
                                        <?php 
                                        // Skip core readonly activities
                                        if ( isset( $core_readonly_activities[ $key ] ) ) {
                                            continue;
                                        }
                                        
                                        $is_checked = in_array( $key, $hidden_activities, true );
                                        $checkbox_id = 'bp_hidden_' . sanitize_html_class( $key );
                                        
                                        // CORRECTED LOGIC: Checked = Hidden (red), Unchecked = Visible (green)
                                        if ( $is_checked ) {
                                            // HIDDEN - Red styling
                                            $bg_color = '#ffeaea';
                                            $border_color = '#f44336';
                                            $icon = 'dashicons-hidden';
                                            $icon_color = '#f44336';
                                            $status_text = 'Hidden';
                                            $text_color = '#c62828';
                                        } else {
                                            // VISIBLE - Green styling  
                                            $bg_color = '#e8f5e8';
                                            $border_color = '#4caf50';
                                            $icon = 'dashicons-visibility';
                                            $icon_color = '#4caf50';
                                            $status_text = 'Visible';
                                            $text_color = '#2e7d32';
                                        }
                                        ?>
                                        <div data-activity-state="<?php echo $is_checked ? 'hidden' : 'visible'; ?>" style="display: block; margin-bottom: 8px; padding: 10px 12px; background: <?php echo $bg_color; ?>; border: 1px solid <?php echo $border_color; ?>; border-radius: 4px; transition: all 0.3s ease;">
                                            <label for="<?php echo esc_attr( $checkbox_id ); ?>" style="display: flex; align-items: center; cursor: pointer; margin: 0;">
                                                <input type="checkbox" 
                                                    id="<?php echo esc_attr( $checkbox_id ); ?>"
                                                    name="bp_activity_filter_hidden[]" 
                                                    value="<?php echo esc_attr( $key ); ?>" 
                                                    <?php checked( $is_checked ); ?>
                                                    style="margin-right: 8px;">
                                                <span class="dashicons <?php echo $icon; ?>" style="color: <?php echo $icon_color; ?>; margin-right: 8px;"></span>
                                                <span style="flex: 1; font-weight: 500; color: <?php echo $text_color; ?>;"><?php echo esc_html( $label ); ?></span>
                                                <span style="font-size: 12px; font-weight: 600; color: <?php echo $icon_color; ?>; margin-right: 10px;"><?php echo $status_text; ?></span>
                                                <code style="font-size: 11px; background: rgba(0,0,0,0.1); color: #666; padding: 2px 6px; border-radius: 10px;"><?php echo esc_html( $key ); ?></code>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="notice notice-info inline" style="margin-top: 20px;">
                                    <p>
                                        <strong><?php esc_html_e( 'How it works:', 'bp-activity-filter' ); ?></strong>
                                    </p>
                                    <ul style="margin: 10px 0 0 20px;">
                                        <li><span class="dashicons dashicons-visibility" style="color: #4caf50;"></span> <strong style="color: #2e7d32;">Visible</strong> - Activity appears in streams and filter options</li>
                                        <li><span class="dashicons dashicons-hidden" style="color: #f44336;"></span> <strong style="color: #c62828;">Hidden</strong> - Activity is completely removed from site</li>
                                        <li><span class="dashicons dashicons-shield-alt" style="color: #0073aa;"></span> <strong style="color: #0073aa;">Core</strong> - Cannot be hidden (essential for BuddyPress)</li>
                                    </ul>
                                </div>
                            <?php endif; ?>
            </div>
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

            <h3 style="margin-top: 20px; margin-bottom: 15px;"><?php esc_html_e( 'Enable Post Types', 'bp-activity-filter' ); ?></h3>
            <div class="cpt-selection-area">
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
            </div>
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
        <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <div style="margin-bottom: 10px;">
                <label style="display: flex; align-items: center; font-weight: 600;">
                    <input type="checkbox" 
                           name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $post_type ); ?>][enabled]" 
                           value="1" <?php checked( $enabled ); ?>
                           style="margin-right: 8px;">
                    <?php echo esc_html( $post_type_obj->label ); ?>
                    <span style="font-size: 12px; color: #666; margin-left: 8px;">
                        (<?php echo esc_html( $post_type ); ?>) - 
                        <?php 
                        printf( 
                            _n( '%d post', '%d posts', $total_posts, 'bp-activity-filter' ), 
                            number_format_i18n( $total_posts )
                        ); 
                        ?>
                    </span>
                </label>
            </div>
            
            <?php if ( ! empty( $post_type_obj->description ) ) : ?>
                <p style="margin: 8px 0; color: #666; font-size: 13px;"><?php echo esc_html( $post_type_obj->description ); ?></p>
            <?php endif; ?>

            <div style="margin-top: 10px;">
                <label for="cpt_<?php echo esc_attr( $post_type ); ?>_label" style="display: block; margin-bottom: 5px; font-weight: 500;">
                    <?php esc_html_e( 'Custom Activity Label (optional):', 'bp-activity-filter' ); ?>
                </label>
                <input type="text" 
                       id="cpt_<?php echo esc_attr( $post_type ); ?>_label"
                       name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $post_type ); ?>][label]" 
                       value="<?php echo esc_attr( $label ); ?>" 
                       placeholder="<?php echo esc_attr( strtolower( $post_type_obj->labels->singular_name ) ); ?>"
                       style="width: 100%; max-width: 400px; padding: 6px;">
                <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">
                    <?php esc_html_e( 'Leave empty to use the default post type name. This text will appear in activity entries like "John published a new [label]: Post Title"', 'bp-activity-filter' ); ?>
                </p>
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
        <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
            <h4 style="margin: 0 0 10px 0;"><?php esc_html_e( 'Global Settings', 'bp-activity-filter' ); ?></h4>
            <label style="display: flex; align-items: flex-start; gap: 8px;">
                <input type="checkbox" 
                       name="bp_activity_filter_cpt_settings[_global][hide_sitewide]" 
                       value="1" 
                       <?php checked( isset( $cpt_settings['_global']['hide_sitewide'] ) ? $cpt_settings['_global']['hide_sitewide'] : false ); ?>
                       style="margin-top: 2px;">
                <div>
                    <?php esc_html_e( 'Hide custom post type activities from site-wide activity stream', 'bp-activity-filter' ); ?>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">
                        <?php esc_html_e( 'When enabled, custom post type activities will only appear in the author\'s profile activity stream, not in the main site-wide activity feed. This helps reduce clutter in the main activity stream while still giving authors credit on their profiles.', 'bp-activity-filter' ); ?>
                    </p>
                </div>
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
     * Get current admin tab.
     *
     * @since 4.0.0
     * @return string Current tab slug.
     */
    private function get_current_tab() {
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'default';
        
        $valid_tabs = array( 'default', 'hidden', 'cpt', 'faq' );
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
     * Sanitize hidden activities array with core activity protection.
     *
     * @since 4.0.0
     * @param mixed $input Raw input value.
     * @return array Sanitized array of activity types (excluding core activities).
     */
    public function sanitize_hidden_activities( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();
        $valid_actions = array_keys( BP_Activity_Filter_Helper::get_activity_actions() );
        
        // Define core activities that should NEVER be hidden
        $core_protected_activities = array(
            'activity_update',
            'activity_comment'
        );

        foreach ( $input as $activity_type ) {
            $activity_type = sanitize_text_field( $activity_type );
            
            // Skip empty values
            if ( empty( $activity_type ) ) {
                continue;
            }
            
            // Skip core protected activities
            if ( in_array( $activity_type, $core_protected_activities, true ) ) {
                continue;
            }
            
            // Only include valid activity types
            if ( in_array( $activity_type, $valid_actions, true ) ) {
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

        // Enqueue modern shared tab styles - Use centralized version from WBCom Essential if available
        if ( defined( 'WBCOM_ESSENTIAL_URL' ) && file_exists( WP_PLUGIN_DIR . '/wbcom-essential/includes/shared-admin/wbcom-shared-tabs.css' ) ) {
            wp_enqueue_style(
                'wbcom-shared-tabs',
                WBCOM_ESSENTIAL_URL . 'includes/shared-admin/wbcom-shared-tabs.css',
                array(),
                defined( 'WBCOM_ESSENTIAL_VERSION' ) ? WBCOM_ESSENTIAL_VERSION : BP_ACTIVITY_FILTER_VERSION,
                'all'
            );
        } else {
            // Fallback to local copy
            wp_enqueue_style(
                'wbcom-shared-tabs',
                BP_ACTIVITY_FILTER_PLUGIN_URL . 'includes/shared-admin/wbcom-shared-tabs.css',
                array(),
                BP_ACTIVITY_FILTER_VERSION,
                'all'
            );
        }

        wp_localize_script(
            'jquery',
            'bpActivityFilterAdmin',
            array(
                'nonce'        => wp_create_nonce( 'bp_activity_filter_admin' ),
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'currentTab'   => $this->get_current_tab(),
            )
        );
    }

    /**
     * Render FAQ tab content.
     *
     * @since 4.0.0
     */
    private function render_faq_tab() {
        ?>
        <div class="bp-activity-filter-faq">
            <h2><?php esc_html_e( 'Frequently Asked Questions', 'bp-activity-filter' ); ?></h2>
            
            <div class="faq-item">
                <h3><?php esc_html_e( 'What is BuddyPress Activity Filter?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'BuddyPress Activity Filter enhances the default BuddyPress activity stream by providing advanced filtering options. It allows users to filter activities by type, set default filters, and manage which activity types are visible.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'How do I set a default filter for all activity streams?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'Navigate to the Default Filters tab and select your preferred default filter from the dropdown. This filter will be applied automatically when users visit the activity stream.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'Can I set different default filters for profile pages?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'Yes! In the Default Filters tab, you can set a separate default filter specifically for user profile activity streams. This allows for more contextual filtering based on where activities are viewed.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'How do I hide specific activity types from users?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'Use the Hidden Activities tab to select which activity types should be hidden from the filter dropdown. Hidden activities will not appear as filter options for users, though the activities themselves may still be visible in the stream.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'What are Custom Post Type activities?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'When Custom Post Types are published on your site, BuddyPress can create activities for them. The Custom Post Types tab lets you control which CPT activities appear in the filter dropdown and how they are labeled.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'Can I rename activity type labels in the filter dropdown?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'Yes! In the Custom Post Types tab, you can customize the display labels for each post type activity. This helps make filter options more user-friendly and relevant to your community.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'Why are some activities not showing up in the filter?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'Check the Hidden Activities tab to ensure the activity type is not hidden. Also, some activity types may not have a filter option if they are system activities or if they have been disabled by other plugins.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'Is this plugin compatible with BuddyBoss Platform?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'BuddyPress Activity Filter is designed specifically for BuddyPress. BuddyBoss Platform includes its own built-in activity filtering features, so this plugin is not needed and may not be fully compatible with BuddyBoss.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'How do filters affect activity stream performance?', 'bp-activity-filter' ); ?></h3>
                <p><?php esc_html_e( 'The plugin is optimized for performance and uses BuddyPress core filtering mechanisms. Filters are applied at the database query level, ensuring efficient loading even with large activity streams.', 'bp-activity-filter' ); ?></p>
            </div>

            <div class="faq-item">
                <h3><?php esc_html_e( 'Where can I get support or report issues?', 'bp-activity-filter' ); ?></h3>
                <p>
                    <?php 
                    printf(
                        esc_html__( 'For support, please visit our %1$ssupport forum%2$s or check the %3$splugin documentation%4$s. You can also report issues on the plugin\'s WordPress.org support page.', 'bp-activity-filter' ),
                        '<a href="https://wbcomdesigns.com/support/" target="_blank">',
                        '</a>',
                        '<a href="https://docs.wbcomdesigns.com/doc_category/bp-activity-filter/" target="_blank">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <style>
        .bp-activity-filter-faq {
            max-width: 800px;
        }
        
        .bp-activity-filter-faq .faq-item {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .bp-activity-filter-faq .faq-item h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #1e293b;
            font-size: 16px;
        }
        
        .bp-activity-filter-faq .faq-item p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .bp-activity-filter-faq .faq-item a {
            color: #2271b1;
            text-decoration: none;
        }
        
        .bp-activity-filter-faq .faq-item a:hover {
            text-decoration: underline;
        }
        </style>
        <?php
    }
}