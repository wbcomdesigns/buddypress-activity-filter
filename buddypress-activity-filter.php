<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Main plugin file.
/**
 * Plugin Name: BuddyPress Activity Filter
 * Plugin URI: https://wordpress.org/plugins/bp-activity-filter/
 * Description: Filter and manage BuddyPress activity streams with default filters and custom post type support.
 * Version: 3.2.1
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bp-activity-filter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * Requires Plugins: buddypress
 * Network: true
 *
 * @package BuddyPress_Activity_Filter
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed

// Define plugin constants.
if ( ! defined( 'BP_ACTIVITY_FILTER_VERSION' ) ) {
	define( 'BP_ACTIVITY_FILTER_VERSION', '3.2.1' );
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
 * Main plugin class.
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

		// Activation/Deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . BP_ACTIVITY_FILTER_BASENAME, array( $this, 'plugin_action_links' ) );
		add_filter( 'network_admin_plugin_action_links_' . BP_ACTIVITY_FILTER_BASENAME, array( $this, 'plugin_action_links' ) );
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
		// Translations are automatically loaded by WordPress since 4.6.
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
			'includes/admin/class-bp-activity-filter-admin-panel.php',
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

		// Initialize admin interface (card-panel wrapper).
		if ( is_admin() && class_exists( 'BP_Activity_Filter_Admin_Panel' ) ) {
			BP_Activity_Filter_Admin_Panel::instance()->register();
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

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Check if all plugin requirements are met.
	 *
	 * @since 4.0.0
	 * @return bool True if requirements are met, false otherwise.
	 */
	private function meets_requirements() {
		return $this->is_buddypress_active() && $this->is_buddypress_version_compatible();
	}

	/**
	 * Check if BuddyPress is active.
	 *
	 * @since 4.0.0
	 * @return bool True if BuddyPress is active, false otherwise.
	 */
	private function is_buddypress_active() {
		return class_exists( 'BuddyPress' ) && function_exists( 'buddypress' );
	}

	/**
	 * Check if BuddyPress version is compatible.
	 *
	 * @since 4.0.0
	 * @return bool True if BuddyPress version meets minimum requirement, false otherwise.
	 */
	private function is_buddypress_version_compatible() {
		if ( ! $this->is_buddypress_active() ) {
			return false;
		}

		$bp_version = buddypress()->version;
		return version_compare( $bp_version, $this->min_bp_version, '>=' );
	}

	/**
	 * Check if BuddyBoss is active.
	 *
	 * @since 4.0.0
	 * @return bool True if BuddyBoss is active, false otherwise.
	 */
	private function is_buddyboss_active() {
		return function_exists( 'buddypress' ) && isset( buddypress()->buddyboss );
	}

	/**
	 * Display admin notice when BuddyPress is not active.
	 *
	 * @since 4.0.0
	 */
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

	/**
	 * Display admin notice when BuddyPress version is incompatible.
	 *
	 * @since 4.0.0
	 */
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

	/**
	 * Display admin notice when BuddyBoss is active.
	 *
	 * @since 4.0.0
	 */
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

	/**
	 * Get the plugin version.
	 *
	 * @since 4.0.0
	 * @return string Plugin version string.
	 */
	public function get_version() {
		return BP_ACTIVITY_FILTER_VERSION;
	}

	/**
	 * Get the plugin directory path.
	 *
	 * @since 4.0.0
	 * @return string Plugin directory path.
	 */
	public function get_plugin_dir() {
		return BP_ACTIVITY_FILTER_PLUGIN_DIR;
	}

	/**
	 * Get the plugin URL.
	 *
	 * @since 4.0.0
	 * @return string Plugin URL.
	 */
	public function get_plugin_url() {
		return BP_ACTIVITY_FILTER_PLUGIN_URL;
	}

	/**
	 * Handle plugin activation redirect.
	 *
	 * @since 4.0.0
	 */
	public function handle_activation_redirect() {
		if ( get_transient( 'bp_activity_filter_activation_redirect' ) ) {
			delete_transient( 'bp_activity_filter_activation_redirect' );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['activate-multi'] ) && ! wp_doing_ajax() ) {
				// Redirect to the plugin settings page.
				$redirect_url = admin_url( 'admin.php?page=wbcom-activity-filter' );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * Prevent cloning of the singleton instance.
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
	 * Prevent unserializing of the singleton instance.
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
