<?php
/**
 * Plugin Name: BuddyPress Activity Filter
 * Plugin URI: https://wordpress.org/plugins/bp-activity-filter/
 * Description: Filter and manage BuddyPress activity streams with default filters and custom post type support.
 * Version: 4.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bp-activity-filter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Network: true
 *
 * @package BuddyPress_Activity_Filter
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'BP_ACTIVITY_FILTER_VERSION' ) ) {
	define( 'BP_ACTIVITY_FILTER_VERSION', '4.0.0' );
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
	 * @var BP_Activity_Filter
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @since 4.0.0
	 * @return BP_Activity_Filter
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
			return;
		}

		// Check if BuddyBoss is active (incompatible).
		if ( $this->is_buddyboss_active() ) {
			add_action( 'admin_notices', array( $this, 'buddyboss_incompatible_notice' ) );
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
	 * Load plugin textdomain.
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
		// Include classes.
		require_once BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/class-bp-activity-filter-helper.php';
		require_once BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/class-bp-activity-filter-migration.php';
		require_once BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/class-bp-activity-filter-admin.php';
		require_once BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/class-bp-activity-filter-frontend.php';
		require_once BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/class-bp-activity-filter-cpt.php';
		
		// Include debug class if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			require_once BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/class-bp-activity-filter-debug.php';
		}
	}

	/**
	 * Initialize components.
	 *
	 * @since 4.0.0
	 */
	private function init_components() {
		// Initialize migration system first
		new BP_Activity_Filter_Migration();

		// Initialize admin.
		if ( is_admin() ) {
			BP_Activity_Filter_Admin::instance();
		}

		// Initialize frontend.
		BP_Activity_Filter_Frontend::instance();

		// Initialize CPT support.
		BP_Activity_Filter_CPT::instance();
	}

	/**
	 * Plugin activation.
	 *
	 * @since 4.0.0
	 */
	public function activate() {
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
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 4.0.0
	 */
	public function deactivate() {
		// Clean up transients.
		delete_transient( 'bp_activity_filter_activation_redirect' );
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 4.0.0
	 * @param array $links Plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=bp-activity-filter' ),
			esc_html__( 'Settings', 'bp-activity-filter' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Check if BuddyPress is active.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	private function is_buddypress_active() {
		return class_exists( 'BuddyPress' );
	}

	/**
	 * Check if BuddyBoss is active.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	private function is_buddyboss_active() {
		return function_exists( 'buddypress' ) && isset( buddypress()->buddyboss );
	}

	/**
	 * BuddyPress required notice.
	 *
	 * @since 4.0.0
	 */
	public function buddypress_required_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: BuddyPress plugin name */
					esc_html__( 'BuddyPress Activity Filter requires %s to be installed and active.', 'bp-activity-filter' ),
					'<strong>BuddyPress</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * BuddyBoss incompatible notice.
	 *
	 * @since 4.0.0
	 */
	public function buddyboss_incompatible_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'BuddyPress Activity Filter is not compatible with BuddyBoss due to similar built-in features.', 'bp-activity-filter' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 4.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 4.0.0
	 */
	private function __wakeup() {}
}

/**
 * Get the main plugin instance.
 *
 * @since 4.0.0
 * @return BP_Activity_Filter
 */
function bp_activity_filter() {
	return BP_Activity_Filter::instance();
}

// Initialize the plugin.
bp_activity_filter();