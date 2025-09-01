<?php
/**
 * Plugin Name: BuddyPress Activity Filter
 * Plugin URI: https://wordpress.org/plugins/bp-activity-filter/
 * Description: Filter and manage BuddyPress activity streams with default filters and custom post type support.
 * Version: 3.2.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bp-activity-filter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 8.0
 * Requires Plugins: buddypress
 * Network: true
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'BP_ACTIVITY_FILTER_VERSION' ) ) {
	define( 'BP_ACTIVITY_FILTER_VERSION', '3.2.0' );
}

if ( ! defined( 'BP_ACTIVITY_FILTER_PLUGIN_DIR' ) ) {
	define( 'BP_ACTIVITY_FILTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BP_ACTIVITY_FILTER_PLUGIN_URL' ) ) {
	define( 'BP_ACTIVITY_FILTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BP_ACTIVITY_FILTER_BASENAME' ) ) {
	define( 'BP_ACTIVITY_FILTER_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Main plugin class
 *
 * @since 4.0.0
 */
final class BP_Activity_Filter {

	/**
	 * Plugin instance.
	 *
	 * @since 4.0.0
	 * @var BP_Activity_Filter|null Single instance of the plugin class.
	 */
	private static $instance = null;

	/**
	 * Minimum required BuddyPress version.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private $min_bp_version = '12.0.0';

	/**
	 * Wbcom integration instance.
	 *
	 * @since 4.0.0
	 * @var BP_Activity_Filter_Wbcom_Integration|null
	 */
	private $wbcom_integration = null;

	/**
	 * Get plugin instance.
	 *
	 * @since 4.0.0
	 * @return BP_Activity_Filter The single instance of the plugin.
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
	 * Setup plugin hooks.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// NEW: Simple integration with shared system
		if ( is_admin() ) {
			add_action( 'init', array( $this, 'init_wbcom_integration' ), 1 );
		}

		// Activation/Deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . BP_ACTIVITY_FILTER_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'network_admin_plugin_action_links_' . BP_ACTIVITY_FILTER_BASENAME, array( $this, 'plugin_action_links' ) );
		
	}

	/**
	 * NEW: Initialize Wbcom integration with enhanced auto-detection
	 *
	 * @since 4.0.0
	 */
	public function init_wbcom_integration() {
		// First check if wbcom_integrate_plugin is already available (from wbcom-essential or another plugin)
		if ( function_exists( 'wbcom_integrate_plugin' ) ) {
			// Use the existing integration function
			wbcom_integrate_plugin( __FILE__, array(
				'name'         => esc_html__( 'BP Activity Filter', 'bp-activity-filter' ),
				'menu_title'   => esc_html__( 'BP Activity Filter', 'bp-activity-filter' ),
				'slug'         => 'activity-filter',
				'priority'     => 10,
				'icon'         => 'dashicons-filter',
				'callback'     => array( $this, 'render_admin_page' ),
				'settings_url' => admin_url( 'admin.php?page=wbcom-activity-filter' ),
			) );
			return;
		}
		
		// Otherwise, load our own integration helper
		$helper_file = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/shared-admin/wbcom-easy-setup.php';
		
		if ( file_exists( $helper_file ) && ! function_exists( 'wbcom_integrate_plugin' ) ) {
			require_once $helper_file;
			
			// One-line integration - auto-detects everything!
			if ( function_exists( 'wbcom_integrate_plugin' ) ) {
				wbcom_integrate_plugin( __FILE__ );
			}
		} else {
			// Fallback to original method if helper not found
			$this->init_wbcom_integration_fallback();
		}
	}

	/**
	 * Fallback integration method (original code)
	 *
	 * @since 4.0.0
	 */
	private function init_wbcom_integration_fallback() {
		// Load Wbcom shared system FIRST
		$shared_loader_path = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/shared-admin/class-wbcom-shared-loader.php';
		
		if ( file_exists( $shared_loader_path ) ) {
			require_once $shared_loader_path;
			
			// Register plugin with shared system
			if ( class_exists( 'Wbcom_Shared_Loader' ) ) {
				Wbcom_Shared_Loader::register_plugin( array(
					'slug'         => 'bp-activity-filter',
					'name'         => 'BP Activity Filter',
					'version'      => BP_ACTIVITY_FILTER_VERSION,
					'settings_url' => admin_url( 'admin.php?page=wbcom-bp-activity-filter' ),
					'icon'         => 'dashicons-filter',
					'priority'     => 5,
					'description'  => 'Filter and manage BuddyPress activity streams with default filters and custom post type support.',
					'status'       => 'active',
					'has_premium'  => false,
					'docs_url'     => 'https://docs.wbcomdesigns.com/bp-activity-filter/',
					'support_url'  => 'https://wbcomdesigns.com/support/',
				) );
			}
		}
		
		// Load integration class for assets only
		$integration_file = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/class-wbcom-integration.php';
		
		if ( file_exists( $integration_file ) ) {
			require_once $integration_file;
			
			if ( class_exists( 'BP_Activity_Filter_Wbcom_Integration' ) ) {
				$this->wbcom_integration = new BP_Activity_Filter_Wbcom_Integration();
			}
		}
	}

	/**
	 * Render admin page - Called by Wbcom shared system.
	 *
	 * @since 4.0.0
	 */
	public function render_admin_page() {
		if ( class_exists( 'BP_Activity_Filter_Admin' ) ) {
			$admin = BP_Activity_Filter_Admin::instance();
			$admin->render_settings_page();
		} else {
			echo '<div class="wrap"><h1>BuddyPress Activity Filter</h1><p>Admin class not loaded.</p></div>';
		}
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 4.0.0
	 */
	public function init() {
		// Check if BuddyPress is active.
		if ( ! $this->is_buddypress_active() ) {
			add_action( 'admin_notices', array( $this, 'buddypress_required_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'buddypress_required_notice' ) );
			return;
		}

		// Check BuddyPress version compatibility.
		if ( ! $this->is_buddypress_version_compatible() ) {
			add_action( 'admin_notices', array( $this, 'buddypress_version_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'buddypress_version_notice' ) );
			return;
		}

		// Check if BuddyBoss is active (incompatible).
		if ( $this->is_buddyboss_active() ) {
			add_action( 'admin_notices', array( $this, 'buddyboss_incompatible_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'buddyboss_incompatible_notice' ) );
			return;
		}

		// Include required files.
		$this->includes();

		// Initialize components.
		$this->init_components();

		/**
		 * Fires after BuddyPress Activity Filter is fully initialized.
		 *
		 * @since 4.0.0
		 */
		do_action( 'bp_activity_filter_init' );
	}

	/**
	 * Load plugin textdomain for internationalization.
	 *
	 * @since 4.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'bp-activity-filter',
			false,
			dirname( BP_ACTIVITY_FILTER_BASENAME ) . '/languages'
		);
	}

	/**
	 * Include required files.
	 *
	 * @since 4.0.0
	 */
	private function includes() {
		$include_files = array(
			'includes/class-bp-activity-filter-helper.php',
			'includes/class-bp-activity-filter-migration.php',
			'includes/class-bp-activity-filter-admin.php',
			'includes/class-bp-activity-filter-frontend.php',
			'includes/class-bp-activity-filter-cpt.php',
		);

		foreach ( $include_files as $file ) {
			$file_path = BP_ACTIVITY_FILTER_PLUGIN_DIR . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			} else {
				wp_die(
					sprintf(
						/* translators: %s: file path */
						esc_html__( 'BuddyPress Activity Filter: Required file missing: %s', 'bp-activity-filter' ),
						esc_html( $file )
					)
				);
			}
		}
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 4.0.0
	 */
	private function init_components() {
		// Initialize migration system first.
		if ( class_exists( 'BP_Activity_Filter_Migration' ) ) {
			new BP_Activity_Filter_Migration();
		}

		// Initialize admin interface.
		if ( is_admin() && class_exists( 'BP_Activity_Filter_Admin' ) ) {
			BP_Activity_Filter_Admin::instance();
		}

		// Initialize frontend functionality.
		if ( class_exists( 'BP_Activity_Filter_Frontend' ) ) {
			BP_Activity_Filter_Frontend::instance();
		}

		// Initialize CPT support.
		if ( class_exists( 'BP_Activity_Filter_CPT' ) ) {
			BP_Activity_Filter_CPT::instance();
		}
	}

	/**
	 * Plugin activation callback.
	 *
	 * @since 4.0.0
	 */
	public function activate() {
		// Check for minimum requirements during activation.
		if ( ! $this->meets_requirements() ) {
			deactivate_plugins( BP_ACTIVITY_FILTER_BASENAME );
			wp_die(
				esc_html__( 'BuddyPress Activity Filter requires BuddyPress to be installed and active.', 'bp-activity-filter' ),
				esc_html__( 'Plugin Activation Error', 'bp-activity-filter' ),
				array( 'back_link' => true )
			);
		}

		// Set default options.
		$default_options = array(
			'bp_activity_filter_default'         => '0',
			'bp_activity_filter_profile_default' => '-1',
			'bp_activity_filter_hidden'          => array(),
			'bp_activity_filter_cpt_settings'    => array(),
		);

		foreach ( $default_options as $option => $value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $value );
			}
		}

		// Set activation redirect flag.
		set_transient( 'bp_activity_filter_activation_redirect', true, 30 );

		// Flush rewrite rules if needed.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @since 4.0.0
	 */
	public function deactivate() {
		// Clean up transients.
		delete_transient( 'bp_activity_filter_activation_redirect' );
		
		// Clear any object cache.
		wp_cache_flush();
		
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Add plugin action links in the plugins list.
	 *
	 * @since 4.0.0
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wbcom-activity-filter' ) ),
			esc_html__( 'Settings', 'bp-activity-filter' )
		);

		// Check if Wbcom dashboard exists
		$dashboard_url = admin_url( 'admin.php?page=wbcom-designs' );
		$dashboard_link = sprintf(
			'<a href="%s" style="color: #0073aa; font-weight: 600;">%s</a>',
			esc_url( $dashboard_url ),
			esc_html__( 'Dashboard', 'bp-activity-filter' )
		);

		array_unshift( $links, $settings_link, $dashboard_link );
		return $links;
	}

	// All existing methods remain unchanged...
	private function meets_requirements() {
		return $this->is_buddypress_active() && $this->is_buddypress_version_compatible();
	}

	private function is_buddypress_active() {
		return class_exists( 'BuddyPress' ) && function_exists( 'buddypress' );
	}

	private function is_buddypress_version_compatible() {
		if ( ! $this->is_buddypress_active() ) {
			return false;
		}

		$bp_version = buddypress()->version;
		return version_compare( $bp_version, $this->min_bp_version, '>=' );
	}

	private function is_buddyboss_active() {
		return function_exists( 'buddypress' ) && isset( buddypress()->buddyboss );
	}

	public function buddypress_required_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: BuddyPress plugin link */
					esc_html__( 'This plugin requires %s to be installed and active.', 'bp-activity-filter' ),
					'<a href="' . esc_url( admin_url( 'plugin-install.php?s=buddypress&tab=search&type=term' ) ) . '"><strong>BuddyPress</strong></a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	public function buddypress_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: required version, 2: current version */
					esc_html__( 'This plugin requires BuddyPress version %1$s or higher. You are running version %2$s.', 'bp-activity-filter' ),
					esc_html( $this->min_bp_version ),
					esc_html( buddypress()->version )
				);
				?>
			</p>
		</div>
		<?php
	}

	public function buddyboss_incompatible_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'This plugin is not compatible with BuddyBoss due to similar built-in features. Please use BuddyBoss\'s native activity filtering instead.', 'bp-activity-filter' ); ?>
			</p>
		</div>
		<?php
	}

	public function get_version() {
		return BP_ACTIVITY_FILTER_VERSION;
	}

	public function get_plugin_dir() {
		return BP_ACTIVITY_FILTER_PLUGIN_DIR;
	}

	public function get_plugin_url() {
		return BP_ACTIVITY_FILTER_PLUGIN_URL;
	}

	public function get_wbcom_integration() {
		return $this->wbcom_integration;
	}

	public function is_wbcom_integration_active() {
		return ! is_null( $this->wbcom_integration );
	}

	public function handle_activation_redirect() {
		if ( get_transient( 'bp_activity_filter_activation_redirect' ) ) {
			delete_transient( 'bp_activity_filter_activation_redirect' );
			
			if ( ! isset( $_GET['activate-multi'] ) && ! wp_doing_ajax() ) {
				// Redirect to Wbcom dashboard
				$redirect_url = admin_url( 'admin.php?page=wbcom-designs' );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	public function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Cloning instances of this class is forbidden.', 'bp-activity-filter' ),
			'4.0.0'
		);
	}

	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Unserializing instances of this class is forbidden.', 'bp-activity-filter' ),
			'4.0.0'
		);
	}
}

/**
 * Get the main plugin instance.
 *
 * @since 4.0.0
 * @return BP_Activity_Filter The single instance of the plugin.
 */
function bp_activity_filter() {
	return BP_Activity_Filter::instance();
}

// Initialize the plugin.
bp_activity_filter();

// Handle activation redirect after plugin is fully loaded.
if ( is_admin() ) {
	add_action( 'admin_init', array( bp_activity_filter(), 'handle_activation_redirect' ) );
}

// Add filter early to customize submenu label
add_filter( 'wbcom_submenu_label', 'bp_activity_filter_customize_submenu_label', 10, 3 );

/**
 * Customize submenu label for BP Activity Filter
 *
 * @since 4.0.0
 * @param string $label Current menu label
 * @param string $slug Plugin slug
 * @param array $plugin Plugin data
 * @return string Modified menu label
 */
function bp_activity_filter_customize_submenu_label( $label, $slug, $plugin ) {
	// Change menu label for this plugin
	if ( $slug === 'bp-activity-filter' || $slug === 'activity-filter' || $slug === 'buddypress-activity-filter' ) {
		return esc_html__( 'BP Activity Filter', 'bp-activity-filter' );
	}
	
	return $label;
}
