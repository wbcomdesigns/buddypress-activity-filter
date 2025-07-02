<?php
/**
 * Admin functionality for BuddyPress Activity Filter.
 *
 * Handles all administrative interface functionality including settings pages,
 * option management, and admin-specific features.
 *
 * @package BuddyPress_Activity_Filter
 * @subpackage Admin
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class for managing plugin settings and administrative interface.
 *
 * Provides the administrative interface for configuring activity filters,
 * managing hidden activities, and setting up custom post type integration.
 *
 * @since 4.0.0
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
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'BP_Activity_Filter_Admin::__construct() called' );
		}
		
		$this->setup_hooks();
	}

	/**
	 * Setup admin hooks and filters.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'BP_Activity_Filter_Admin::setup_hooks() called' );
		}

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 ); // Much later priority to ensure Wbcom menu is created first
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'wp_ajax_bp_activity_filter_test_action', array( $this, 'handle_ajax_test' ) );

		// Debug: Hook into admin_menu to log when it fires
		add_action( 'admin_menu', function() {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'admin_menu hook fired - BP_Activity_Filter_Admin is active' );
			}
		}, 1 );
	}

	/**
	 * Add admin menu page using unified Wbcom Designs menu.
	 *
	 * @since 4.0.0
	 */
	public function add_admin_menu() {
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '=== BP_Activity_Filter_Admin::add_admin_menu() STARTED ===' );
			error_log( 'Current user ID: ' . get_current_user_id() );
			error_log( 'Current user can manage options: ' . ( current_user_can( 'manage_options' ) ? 'YES' : 'NO' ) );
			error_log( 'Is admin: ' . ( is_admin() ? 'YES' : 'NO' ) );
			error_log( 'Wbcom_Designs_Menu class exists: ' . ( class_exists( 'Wbcom_Designs_Menu' ) ? 'YES' : 'NO' ) );
		}

		$page_hook = false;
		$using_unified_menu = false;

		// EMERGENCY FIX: Create main Wbcom menu if it doesn't exist
		global $menu;
		$main_menu_exists = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && $item[2] === 'wbcom-designs' ) {
					$main_menu_exists = true;
					break;
				}
			}
		}

		if ( ! $main_menu_exists ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'EMERGENCY: Creating main Wbcom menu because it does not exist' );
			}
			
			$main_menu_hook = add_menu_page(
				esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
				esc_html__( 'Wbcom Designs', 'bp-activity-filter' ),
				'manage_options',
				'wbcom-designs',
				array( $this, 'emergency_dashboard_page' ),
				'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwIDJMMTMuMDkgOC4yNkwyMCA5TDE0IDEyTDE1IDIwTDEwIDE3TDUgMjBMNiAxMkwwIDlMNi45MSA4LjI2TDEwIDJaIiBmaWxsPSIjYTdhYWFkIi8+Cjwvc3ZnPgo=',
				58.5
			);
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'EMERGENCY: Main menu creation result: ' . ( $main_menu_hook ? $main_menu_hook : 'FAILED' ) );
			}
		}

		// Try to use unified menu system
		if ( class_exists( 'Wbcom_Designs_Menu' ) ) {
			try {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Attempting to create Wbcom_Designs_Menu instance...' );
				}

				// Get the unified menu instance
				$wbcom_menu = Wbcom_Designs_Menu::instance();

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Wbcom_Designs_Menu instance created successfully' );
					error_log( 'Attempting to add submenu...' );
				}

				// Add our submenu to the Wbcom Designs menu
				$page_hook = $wbcom_menu->add_submenu(
					'activity-filter', // Plugin key (matches priority array)
					esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ), // Page title
					esc_html__( 'Activity Filter', 'bp-activity-filter' ), // Menu title
					'manage_options', // Capability
					'wbcom-activity-filter', // Menu slug (updated to avoid conflicts)
					array( $this, 'admin_page' ) // Callback function
				);

				if ( $page_hook ) {
					$using_unified_menu = true;
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'SUCCESS: Unified submenu added. Hook: ' . $page_hook );
					}
				} else {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'WARNING: Unified submenu returned FALSE' );
					}
				}

			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'ERROR: Exception creating unified menu: ' . $e->getMessage() );
				}
			} catch ( Error $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'FATAL ERROR: Fatal error creating unified menu: ' . $e->getMessage() );
				}
			}
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Wbcom_Designs_Menu class not found, will use fallback' );
			}
		}

		// If unified menu didn't work, add submenu directly to emergency main menu
		if ( ! $page_hook && $main_menu_exists ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Adding submenu directly to emergency main menu...' );
			}

			$page_hook = add_submenu_page(
				'wbcom-designs',
				esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
				esc_html__( 'Activity Filter', 'bp-activity-filter' ),
				'manage_options',
				'wbcom-activity-filter',
				array( $this, 'admin_page' )
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Direct submenu added. Hook: ' . ( $page_hook ? $page_hook : 'FALSE' ) );
			}
		}

		// Fallback: If unified menu fails, create individual menu
		if ( ! $page_hook ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Using fallback menu creation under Settings...' );
			}

			$page_hook = add_options_page(
				esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
				esc_html__( 'Activity Filter', 'bp-activity-filter' ),
				'manage_options',
				'bp-activity-filter',
				array( $this, 'admin_page' )
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Fallback menu added. Hook: ' . ( $page_hook ? $page_hook : 'FALSE' ) );
			}
		}

		// Emergency fallback: Create a top-level menu if all else fails
		if ( ! $page_hook ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'EMERGENCY: Creating top-level menu as last resort...' );
			}

			$page_hook = add_menu_page(
				esc_html__( 'Activity Filter', 'bp-activity-filter' ),
				esc_html__( 'Activity Filter', 'bp-activity-filter' ),
				'manage_options',
				'bp-activity-filter-emergency',
				array( $this, 'admin_page' ),
				'dashicons-filter',
				30
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Emergency menu added. Hook: ' . ( $page_hook ? $page_hook : 'STILL FAILED!' ) );
			}
		}

		// Add help tab on our admin page
		if ( $page_hook ) {
			add_action( "load-{$page_hook}", array( $this, 'add_help_tab' ) );
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Help tab hook added for: ' . $page_hook );
				error_log( 'Menu type used: ' . ( $using_unified_menu ? 'UNIFIED' : 'FALLBACK' ) );
			}
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'CRITICAL ERROR: No page hook created - menu will not appear!' );
				error_log( 'Dumping global $menu variable...' );
				global $menu;
				if ( is_array( $menu ) ) {
					foreach ( $menu as $index => $menu_item ) {
						error_log( "Menu item {$index}: " . print_r( $menu_item, true ) );
					}
				} else {
					error_log( 'Global $menu is not an array: ' . gettype( $menu ) );
				}
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '=== BP_Activity_Filter_Admin::add_admin_menu() FINISHED ===' );
		}
	}

	/**
	 * Emergency dashboard page for main menu.
	 *
	 * @since 4.0.0
	 */
	public function emergency_dashboard_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Wbcom Designs', 'bp-activity-filter' ); ?></h1>
			
			<div class="notice notice-success">
				<p><strong><?php esc_html_e( 'Success!', 'bp-activity-filter' ); ?></strong> 
				<?php esc_html_e( 'The Wbcom Designs menu is now working. This is an emergency fallback dashboard.', 'bp-activity-filter' ); ?></p>
			</div>

			<h2><?php esc_html_e( 'Available Plugins', 'bp-activity-filter' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wbcom-activity-filter' ) ); ?>">
					<?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?>
				</a></li>
			</ul>

			<h2><?php esc_html_e( 'Debug Information', 'bp-activity-filter' ); ?></h2>
			<ul>
				<li><strong><?php esc_html_e( 'Plugin Version:', 'bp-activity-filter' ); ?></strong> <?php echo esc_html( BP_ACTIVITY_FILTER_VERSION ); ?></li>
				<li><strong><?php esc_html_e( 'Menu Type:', 'bp-activity-filter' ); ?></strong> Emergency Fallback</li>
				<li><strong><?php esc_html_e( 'Time:', 'bp-activity-filter' ); ?></strong> <?php echo esc_html( current_time( 'mysql' ) ); ?></li>
			</ul>
		</div>
		<?php
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
	 * Add help tab to admin page.
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
				'id'      => 'bp_activity_filter_help',
				'title'   => esc_html__( 'Activity Filter Help', 'bp-activity-filter' ),
				'content' => $this->get_help_content(),
			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'bp-activity-filter' ) . '</strong></p>' .
			'<p><a href="https://wbcomdesigns.com/" target="_blank">' . esc_html__( 'Wbcom Designs', 'bp-activity-filter' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/support/plugin/bp-activity-filter/" target="_blank">' . esc_html__( 'Support Forums', 'bp-activity-filter' ) . '</a></p>'
		);
	}

	/**
	 * Get help content for admin page.
	 *
	 * @since 4.0.0
	 * @return string Help content HTML.
	 */
	private function get_help_content() {
		return '<h3>' . esc_html__( 'Default Filters', 'bp-activity-filter' ) . '</h3>' .
			'<p>' . esc_html__( 'Set the default activity filter for site-wide and profile activity streams.', 'bp-activity-filter' ) . '</p>' .
			'<h3>' . esc_html__( 'Hidden Activities', 'bp-activity-filter' ) . '</h3>' .
			'<p>' . esc_html__( 'Select activity types to completely hide from all activity streams.', 'bp-activity-filter' ) . '</p>' .
			'<h3>' . esc_html__( 'Custom Post Types', 'bp-activity-filter' ) . '</h3>' .
			'<p>' . esc_html__( 'Enable activity generation for custom post types when posts are published.', 'bp-activity-filter' ) . '</p>';
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

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Admin scripts check - Hook: {$hook_suffix}, Should enqueue: " . ( $should_enqueue ? 'YES' : 'NO' ) );
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
		if ( ! empty( $this->current_tab ) ) {
			return $this->current_tab;
		}

		$this->current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'default';
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

		// Debug notice for menu troubleshooting
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) {
			$current_screen = get_current_screen();
			if ( $current_screen && strpos( $current_screen->id, 'activity-filter' ) !== false ) {
				echo '<div class="notice notice-info"><p>';
				echo '<strong>Debug Info:</strong> ';
				echo 'Screen ID: ' . esc_html( $current_screen->id ) . ' | ';
				echo 'Hook Suffix: ' . esc_html( $current_screen->base ) . ' | ';
				echo 'Unified Menu: ' . ( class_exists( 'Wbcom_Designs_Menu' ) ? 'Available' : 'Missing' );
				echo '</p></div>';
			}
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

		// Debug info at top if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && current_user_can( 'manage_options' ) ) {
			?>
			<div class="notice notice-info">
				<h4>Debug Information</h4>
				<ul>
					<li><strong>Plugin Version:</strong> <?php echo esc_html( BP_ACTIVITY_FILTER_VERSION ); ?></li>
					<li><strong>PHP Version:</strong> <?php echo esc_html( PHP_VERSION ); ?></li>
					<li><strong>WordPress Version:</strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></li>
					<li><strong>Current User:</strong> <?php echo esc_html( wp_get_current_user()->display_name ); ?> (ID: <?php echo esc_html( get_current_user_id() ); ?>)</li>
					<li><strong>Menu Location:</strong> <?php echo class_exists( 'Wbcom_Designs_Menu' ) ? 'Unified Wbcom Menu' : 'Settings Submenu'; ?></li>
					<li><strong>Screen ID:</strong> <?php echo esc_html( get_current_screen()->id ); ?></li>
				</ul>
			</div>
			<?php
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

		// Determine current page URL
		$base_url = '';
		if ( class_exists( 'Wbcom_Designs_Menu' ) ) {
			$base_url = admin_url( 'admin.php?page=wbcom-activity-filter' );
		} else {
			$base_url = admin_url( 'options-general.php?page=bp-activity-filter' );
		}

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
	 * Get plugin information for display.
	 *
	 * @since 4.0.0
	 * @return array Plugin information.
	 */
	public function get_plugin_info() {
		return array(
			'version'     => BP_ACTIVITY_FILTER_VERSION,
			'plugin_dir'  => BP_ACTIVITY_FILTER_PLUGIN_DIR,
			'plugin_url'  => BP_ACTIVITY_FILTER_PLUGIN_URL,
			'text_domain' => 'bp-activity-filter',
		);
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
	 * Validate and sanitize form input data.
	 *
	 * @since 4.0.0
	 * @param mixed  $input     Raw input data.
	 * @param string $data_type Expected data type.
	 * @return mixed Sanitized data.
	 */
	private function validate_input( $input, $data_type = 'text' ) {
		switch ( $data_type ) {
			case 'array':
				return is_array( $input ) ? $input : array();
			case 'checkbox':
				return ! empty( $input );
			case 'email':
				return sanitize_email( $input );
			case 'url':
				return esc_url_raw( $input );
			case 'int':
				return absint( $input );
			case 'text':
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Log admin actions for debugging.
	 *
	 * @since 4.0.0
	 * @param string $action  The action being performed.
	 * @param string $message Log message.
	 * @param string $level   Log level (info, warning, error).
	 */
	private function log_admin_action( $action, $message, $level = 'info' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$log_message = sprintf(
				'[BP Activity Filter Admin] [%s] %s: %s',
				strtoupper( $level ),
				$action,
				$message
			);
			error_log( $log_message );
		}
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