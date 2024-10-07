<?php
/**
 *
 * @link              https://wbcomdesigns.com/
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Wbcom Designs - BuddyPress Activity Filter
 * Plugin URI:        https://wbcomdesigns.com/downloads/buddypress-activity-filter/
 * Description:       It will help set the default filter option with BuddyPress Activity, & also allow disabling selected activity types.
 * Version:           3.0.1
 * Author:            Wbcom Designs<admin@wbcomdesigns.com>
 * Author URI:        https://wbcomdesigns.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bp-activity-filter
 * Domain Path:       /languages
 *
 * @package BuddyPress_Activity_Filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Direct Access is not Allowed' );
}
define( 'BP_ACTIVITY_FILTER_PLUGIN_VERSION', '3.0.1' );
define( 'BP_ACTIVITY_FILTER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'BP_ACTIVITY_FILTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 *  Checking for buddypress whether it is active or not
 *
 * @author wbcomdesigns
 * @since  3.0.1
 */
function check_required_plugin_is_activated() {
	if ( ! class_exists( 'Buddypress' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'bp_activity_filter_required_plugin_admin_notice' );
		if ( null !== filter_input( INPUT_GET, 'activate' ) ) {
			$activate = filter_input( INPUT_GET, 'activate' );
			unset( $activate );
		}
	} elseif ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'bp_activity_filter_required_plugin_admin_notice' );
		if ( null !== filter_input( INPUT_GET, 'activate' ) ) {
			$activate = filter_input( INPUT_GET, 'activate' );
			unset( $activate );
		}
	}
}
add_action( 'admin_init', 'check_required_plugin_is_activated' );

/**
 * Throw an Alert to tell the Admin why it didn't activate.
 *
 * @author wbcomdesigns
 * @since  3.0.1
 */
function bp_activity_filter_required_plugin_admin_notice() {
	$plugin    = esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' );
	$bp_plugin = esc_html__( 'BuddyPress', 'bp-activity-filter' );
	echo '<div class="error"><p>';
	/* translators: %1$s: BuddyPress Activity Filter ;  %2$s: BuddyPress*/
	echo sprintf( esc_html__( '%1$s is ineffective now as it requires %2$s to be installed and active. It is not compatible with BuddyBoss due to similar features.', 'bp-activity-filter' ), '<strong>' . esc_html( $plugin ) . '</strong>', '<strong>' . esc_html( $bp_plugin ) . '</strong>' );
	echo '</p></div>';
	if ( null !== filter_input( INPUT_GET, 'activate' ) ) {
		$activate = filter_input( INPUT_GET, 'activate' );
		unset( $activate );
	}
}

/**
 * Defining class WbCom_BP_Activity_Filter is not exist
 */
if ( ! class_exists( 'WbCom_BP_Activity_Filter' ) ) {
	class WbCom_BP_Activity_Filter {
		/**
		 * Constructor
		 */
		public function __construct() {
			global $bp;

			// Add text domain.
			$this->bp_activity_filter_load_textdomain();

			// Add settings link on plugin listing page.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'bp_activity_filter_plugin_actions' ), 10, 2 );

			// Include admin settings, scripts, and other files.
			require_once plugin_dir_path( __FILE__ ) . 'admin/wbcom/wbcom-admin-settings.php';
			require_once plugin_dir_path( __FILE__ ) . 'admin/class-bp-activity-filter-admin-script-includer.php';
			require_once plugin_dir_path( __FILE__ ) . 'admin/class-bp-activity-filter-admin-setting.php';
			require_once plugin_dir_path( __FILE__ ) . 'admin/class-bp-activity-filter-admin-setting-save.php';
			require_once plugin_dir_path( __FILE__ ) . 'admin/class-bp-activity-filter-feedback.php';
			require_once plugin_dir_path( __FILE__ ) . 'templates/class-bp-activity-filter-dropdown.php';
			require_once plugin_dir_path( __FILE__ ) . 'admin/class-bp-activity-filter-add-post-support.php';
			require_once plugin_dir_path( __FILE__ ) . 'templates/class-bp-activity-filter-query.php';
		}

		/**
		 * Load plugin textdomain.
		 */
		public function bp_activity_filter_load_textdomain() {
			$domain = 'bp-activity-filter';
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			load_textdomain( $domain, 'languages/' . $domain . '-' . $locale . '.pot' );
			load_plugin_textdomain( $domain, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Adds the Settings link to the plugin activate/deactivate page.
		 *
		 * @param string $links Action Links.
		 * @param string $file Path to the plugin file relative to the plugins directory.
		 */
		public function bp_activity_filter_plugin_actions( $links, $file ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=bp_activity_filter_settings' ) . '">' . __( 'Settings', 'bp-activity-filter' ) . '</a>';
			array_unshift( $links, $settings_link ); // Add settings link before others.
			return $links;
		}
	}
}

/**
 * Check Configuration.
 */
function bpfilter_check_config() {
	global $bp;

	$config = array(
		'blog_status'    => false,
		'network_active' => false,
		'network_status' => true,
	);

	if ( get_current_blog_id() == bp_get_root_blog_id() ) {
		$config['blog_status'] = true;
	}

	$network_plugins = get_site_option( 'active_sitewide_plugins', array() );

	// No Network plugins.
	if ( empty( $network_plugins ) ) {
		$check[] = $bp->basename;
	}
	$check[] = BP_ACTIVITY_FILTER_PLUGIN_BASENAME;

	// Are they active on the network?
	$network_active = array_diff( $check, array_keys( $network_plugins ) );

	// If plugin is network activated but not BuddyPress, config is not ok.
	if ( count( $network_active ) == 1 ) {
		$config['network_status'] = false;
	}

	// Check if network-activated.
	$config['network_active'] = isset( $network_plugins[ BP_ACTIVITY_FILTER_PLUGIN_BASENAME ] );

	// If BuddyPress config is different than this plugin's config.
	if ( ! $config['blog_status'] || ! $config['network_status'] ) {
		if ( ! bp_core_do_network_admin() && ! $config['blog_status'] ) {
			add_action( 'admin_notices', 'bpfilter_same_blog' );
		}
		if ( bp_core_do_network_admin() && ! $config['network_status'] ) {
			add_action( 'admin_notices', 'bpfilter_same_network_config' );
		}
		return false;
	}
	return true;
}

/**
 * Fires inside the 'bp_include' function, where plugins should include files.
 *
 * @since 1.2.5
 */
add_action( 'bp_include', 'bp_activity_filter_init' );
function bp_activity_filter_init() {
	if ( bpfilter_check_config() && class_exists( 'WbCom_BP_Activity_Filter' ) ) {
		$GLOBALS['activity_filter'] = new WbCom_BP_Activity_Filter();
	}
}

/**
 * Show error if BuddyPress Activity Filter is not activated on the same blog as BuddyPress.
 */
function bpfilter_same_blog() {
	echo '<div class="error"><p>' . esc_html__( 'BuddyPress Activity Filter requires to be activated on the blog where BuddyPress is activated.', 'bp-activity-filter' ) . '</p></div>';
}

/**
 * Show error if BuddyPress and this plugin do not share the same network configuration.
 */
function bpfilter_same_network_config() {
	echo '<div class="error"><p>' . esc_html__( 'BuddyPress Activity Filter and BuddyPress need to share the same network configuration.', 'bp-activity-filter' ) . '</p></div>';
}

/**
 * Redirect to plugin settings page after activation.
 *
 * @since  1.0.0
 *
 * @param string $plugin Path to the plugin file relative to the plugins directory.
 */
function bpfilter_activation_redirect_settings( $plugin ) {
	$active_plugins = get_option( 'active_plugins' );
	if ( plugin_basename( __FILE__ ) === $plugin && class_exists( 'Buddypress' ) && ! in_array( 'buddyboss-platform/bp-loader.php', $active_plugins ) ) {
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'activate' && isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] == $plugin ) { //phpcs:ignore
			wp_safe_redirect( admin_url( 'admin.php?page=bp_activity_filter_settings' ) );
			exit;
		}
	}
	if ( $plugin == $_REQUEST['plugin'] && class_exists( 'Buddypress' ) ) { //phpcs:ignore
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'activate-plugin' && isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] == $plugin ) { //phpcs:ignore
			set_transient( '_bpfilter_is_new_install', true, 30 );
		}
	}
}
add_action( 'activated_plugin', 'bpfilter_activation_redirect_settings' );

/**
 * Bpfilter_do_activation_redirect
 */
function bpfilter_do_activation_redirect() {
	if ( get_transient( '_bpfilter_is_new_install' ) ) {
		delete_transient( '_bpfilter_is_new_install' );
		wp_safe_redirect( admin_url( 'admin.php?page=bp_activity_filter_settings' ) );
	}
}
add_action( 'admin_init', 'bpfilter_do_activation_redirect' );
