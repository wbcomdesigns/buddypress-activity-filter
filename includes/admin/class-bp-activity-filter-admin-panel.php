<?php
/**
 * Card-panel admin: menu, settings registration, enqueue, page rendering.
 *
 * Replaces the INTERMEDIATE Wbcom shared-dashboard wrapper
 * (includes/shared-admin/ + includes/class-wbcom-integration.php).
 * Follows the wp-plugin-development skill Part 6 card-panel pattern and
 * references/wbcom-wrapper-migration.md (Parts 5, 6, 7.1, 15).
 *
 * The legacy admin class (class-bp-activity-filter-admin.php) is retained
 * for its battle-tested sanitizer callbacks only; this class owns the
 * menu, settings registration, enqueue, and page rendering.
 *
 * @package BuddyPress_Activity_Filter
 * @since 3.2.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Activity_Filter_Admin_Panel
 *
 * @since 3.2.1
 */
class BP_Activity_Filter_Admin_Panel {

	/**
	 * Menu slug. Kept identical to the legacy slug so existing bookmarks
	 * and the settings/Dashboard action links keep resolving.
	 *
	 * @since 3.2.1
	 * @var string
	 */
	const MENU_SLUG = 'wbcom-activity-filter';

	/**
	 * Settings API option group. Preserved byte-for-byte so the save
	 * contract and every sanitize callback stay identical to pre-3.2.1.
	 *
	 * @since 3.2.1
	 * @var string
	 */
	const OPTION_GROUP = 'bp_activity_filter_settings';

	/**
	 * Class instance.
	 *
	 * @since 3.2.1
	 * @var BP_Activity_Filter_Admin_Panel|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 3.2.1
	 * @return BP_Activity_Filter_Admin_Panel
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sidebar tabs rendered inside the one admin page. Single source of
	 * truth for the nav. Tab slugs preserved from the legacy UI
	 * (default | hidden | cpt | faq) so ?tab= deep-links keep working.
	 *
	 * @since 3.2.1
	 * @return array<string, array{label:string, icon:string, group:string}>
	 */
	public static function get_tabs() {
		$tabs = array(
			'default' => array(
				'label' => __( 'Default Filters', 'bp-activity-filter' ),
				'icon'  => 'dashicons-admin-settings',
				'group' => 'settings',
			),
			'hidden'  => array(
				'label' => __( 'Hidden Activities', 'bp-activity-filter' ),
				'icon'  => 'dashicons-hidden',
				'group' => 'settings',
			),
			'cpt'     => array(
				'label' => __( 'Custom Post Types', 'bp-activity-filter' ),
				'icon'  => 'dashicons-admin-post',
				'group' => 'settings',
			),
			'faq'     => array(
				'label' => __( 'FAQ', 'bp-activity-filter' ),
				'icon'  => 'dashicons-editor-help',
				'group' => 'resources',
			),
			'discover' => array(
				'label' => __( 'Discover', 'bp-activity-filter' ),
				'icon'  => 'dashicons-lightbulb',
				'group' => 'resources',
			),
		);
		return apply_filters( 'bp_activity_filter_admin_tabs', $tabs );
	}

	/**
	 * Bootstrap hooks.
	 *
	 * @since 3.2.1
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		// Priority 999 runs after every other plugin has registered its
		// admin_menu entries. Used to reclaim the hub's landing render
		// when a legacy wbcom-wrapper plugin was the first to register
		// the wbcomplugins top-level menu (mixed-install support).
		add_action( 'admin_menu', array( $this, 'takeover_hub_landing' ), 999 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'in_admin_header', array( $this, 'suppress_foreign_notices' ), 1 );
	}

	/**
	 * Attach as a single submenu under the shared WB Plugins hub.
	 *
	 * @since 3.2.1
	 */
	public function add_menu() {
		$cap = 'manage_options';

		// First Wbcom plugin to load creates the shared WB Plugins hub.
		// Whichever plugin wins the race provides the hub's landing
		// dashboard (a card grid of every Wbcom plugin attached to the
		// hub). Peer plugins are auto-discovered via
		// $GLOBALS['submenu']['wbcomplugins'].
		if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
			add_menu_page(
				esc_html__( 'WB Plugins', 'bp-activity-filter' ),
				esc_html__( 'WB Plugins', 'bp-activity-filter' ),
				$cap,
				'wbcomplugins',
				array( $this, 'render_hub' ),
				'dashicons-lightbulb',
				59
			);
		}

		add_submenu_page(
			'wbcomplugins',
			esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
			esc_html__( 'BP Activity Filter', 'bp-activity-filter' ),
			$cap,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Force the shared hub's landing page to render our clean card-panel
	 * dashboard, overriding whatever a legacy wbcom-wrapper plugin may
	 * have registered. See references/wbcom-wrapper-migration.md Part 15.
	 *
	 * @since 3.2.1
	 */
	public function takeover_hub_landing() {
		global $admin_page_hooks;
		if ( empty( $admin_page_hooks['wbcomplugins'] ) ) {
			return;
		}
		remove_all_actions( 'toplevel_page_wbcomplugins' );
		add_action( 'toplevel_page_wbcomplugins', array( $this, 'render_hub' ) );
	}

	/**
	 * Register the four settings options under the preserved option group.
	 *
	 * Each option keeps its existing key, type, default, and sanitize
	 * callback (delegated to the retained legacy admin class). Settings
	 * persist via the WordPress Settings API (options.php) as the
	 * card-panel form posts to options.php.
	 *
	 * @since 3.2.1
	 */
	public function register_settings() {
		$legacy = $this->legacy_admin();

		register_setting(
			self::OPTION_GROUP,
			'bp_activity_filter_default',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_default' ),
				'default'           => '0',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'bp_activity_filter_profile_default',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_profile_default' ),
				'default'           => '-1',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'bp_activity_filter_hidden',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_hidden' ),
				'default'           => array(),
			)
		);
		register_setting(
			self::OPTION_GROUP,
			'bp_activity_filter_cpt_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_cpt' ),
				'default'           => array(),
			)
		);

		unset( $legacy );
	}

	/**
	 * Get the retained legacy admin class instance for sanitizer reuse.
	 *
	 * @since 3.2.1
	 * @return BP_Activity_Filter_Admin|null
	 */
	private function legacy_admin() {
		if ( class_exists( 'BP_Activity_Filter_Admin' ) ) {
			return BP_Activity_Filter_Admin::instance();
		}
		return null;
	}

	/*
	 * ── Sentinel-guarded sanitize callbacks (Playbook 7.1) ─────────────
	 *
	 * The card-panel splits the option group across separate settings
	 * tabs. The WordPress Settings API (options.php) calls
	 * update_option() for EVERY option registered to the group on save,
	 * passing null for any option the submitted tab did not render. A
	 * naive sanitizer would coerce those nulls to defaults and wipe the
	 * other tabs' saved values (data loss).
	 *
	 * Fix: each settings tab view emits a hidden sentinel
	 * `bp_activity_filter_rendered_options[]` listing the option keys it
	 * owns. Each sanitize callback checks the sentinel; if its own key
	 * was NOT rendered by the submitting tab, it returns the currently
	 * stored value unchanged instead of sanitizing a null into a default.
	 */

	/**
	 * Whether the submitting tab actually rendered the given option key.
	 *
	 * Reads the per-tab sentinel field from the raw POST. The sentinel is
	 * a list of option keys the rendered tab owns; only those keys should
	 * be overwritten on this save.
	 *
	 * @since 3.2.1
	 * @param string $option_key Option key to test.
	 * @return bool True when the tab rendered (and therefore owns) the key.
	 */
	private function tab_rendered_option( $option_key ) {
		// Settings API save is nonce-verified by options.php itself
		// (check_admin_referer) before any sanitize callback runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['bp_activity_filter_rendered_options'] ) || ! is_array( $_POST['bp_activity_filter_rendered_options'] ) ) {
			// No sentinel present (e.g. programmatic update_option or REST
			// settings write). Treat the key as owned so the value still
			// sanitizes through normally.
			return true;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$rendered = array_map( 'sanitize_key', wp_unslash( $_POST['bp_activity_filter_rendered_options'] ) );
		return in_array( $option_key, $rendered, true );
	}

	/**
	 * Sanitize the site-wide default filter.
	 *
	 * @since 3.2.1
	 * @param mixed $input Raw value (null when its tab did not render).
	 * @return string Sanitized value, or the stored value if not owned.
	 */
	public function sanitize_default( $input ) {
		if ( ! $this->tab_rendered_option( 'bp_activity_filter_default' ) ) {
			return get_option( 'bp_activity_filter_default', '0' );
		}
		$legacy = $this->legacy_admin();
		if ( $legacy ) {
			return $legacy->sanitize_default_filter( is_string( $input ) ? $input : '0' );
		}
		return is_string( $input ) ? sanitize_text_field( $input ) : '0';
	}

	/**
	 * Sanitize the profile default filter.
	 *
	 * @since 3.2.1
	 * @param mixed $input Raw value (null when its tab did not render).
	 * @return string Sanitized value, or the stored value if not owned.
	 */
	public function sanitize_profile_default( $input ) {
		if ( ! $this->tab_rendered_option( 'bp_activity_filter_profile_default' ) ) {
			return get_option( 'bp_activity_filter_profile_default', '-1' );
		}
		$legacy = $this->legacy_admin();
		if ( $legacy ) {
			return $legacy->sanitize_default_filter( is_string( $input ) ? $input : '-1' );
		}
		return is_string( $input ) ? sanitize_text_field( $input ) : '-1';
	}

	/**
	 * Sanitize the hidden activities array.
	 *
	 * @since 3.2.1
	 * @param mixed $input Raw value (null when its tab did not render).
	 * @return array Sanitized value, or the stored value if not owned.
	 */
	public function sanitize_hidden( $input ) {
		if ( ! $this->tab_rendered_option( 'bp_activity_filter_hidden' ) ) {
			$stored = get_option( 'bp_activity_filter_hidden', array() );
			return is_array( $stored ) ? $stored : array();
		}
		$legacy = $this->legacy_admin();
		if ( $legacy ) {
			return $legacy->sanitize_hidden_activities( is_array( $input ) ? $input : array() );
		}
		return is_array( $input ) ? array_map( 'sanitize_text_field', $input ) : array();
	}

	/**
	 * Sanitize the per-CPT settings array.
	 *
	 * @since 3.2.1
	 * @param mixed $input Raw value (null when its tab did not render).
	 * @return array Sanitized value, or the stored value if not owned.
	 */
	public function sanitize_cpt( $input ) {
		if ( ! $this->tab_rendered_option( 'bp_activity_filter_cpt_settings' ) ) {
			$stored = get_option( 'bp_activity_filter_cpt_settings', array() );
			return is_array( $stored ) ? $stored : array();
		}
		$legacy = $this->legacy_admin();
		if ( $legacy ) {
			return $legacy->sanitize_cpt_settings( is_array( $input ) ? $input : array() );
		}
		return is_array( $input ) ? $input : array();
	}

	/**
	 * Enqueue admin assets only on our screen + the shared hub landing.
	 *
	 * @since 3.2.1
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		unset( $hook_suffix );
		$screen = get_current_screen();
		if ( ! $screen || ! $this->is_our_screen( $screen ) ) {
			return;
		}

		$version = defined( 'BP_ACTIVITY_FILTER_VERSION' ) ? BP_ACTIVITY_FILTER_VERSION : '1.0.0';

		wp_enqueue_style(
			'bp-activity-filter-admin',
			BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'bp-activity-filter-admin',
			BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$version,
			true
		);

		wp_localize_script(
			'bp-activity-filter-admin',
			'bpActivityFilterAdmin',
			array(
				'i18n' => array(
					'visible'         => __( 'Visible', 'bp-activity-filter' ),
					'hidden'          => __( 'Hidden', 'bp-activity-filter' ),
					'confirmContinue' => __( 'Continue', 'bp-activity-filter' ),
					'confirmCancel'   => __( 'Cancel', 'bp-activity-filter' ),
				),
			)
		);
	}

	/**
	 * Suppress 3rd-party admin notices on our screen.
	 *
	 * @since 3.2.1
	 */
	public function suppress_foreign_notices() {
		$screen = get_current_screen();
		if ( ! $screen || ! $this->is_our_screen( $screen ) ) {
			return;
		}
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * True if the current screen is the plugin's admin page OR the shared
	 * hub landing (our callback owns the hub render too).
	 *
	 * @since 3.2.1
	 * @param WP_Screen $screen Current screen.
	 * @return bool
	 */
	private function is_our_screen( $screen ) {
		if ( empty( $screen->id ) ) {
			return false;
		}
		return (bool) preg_match( '/_page_' . preg_quote( self::MENU_SLUG, '/' ) . '$/', $screen->id )
			|| 'toplevel_page_wbcomplugins' === $screen->id;
	}

	/**
	 * Render the shared WB Plugins hub landing page.
	 *
	 * @since 3.2.1
	 */
	public function render_hub() {
		$view = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/admin/views/hub.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	/**
	 * Render the single admin page. Routes to the active tab.
	 *
	 * @since 3.2.1
	 */
	public function render_page() {
		$bpaf_tabs = self::get_tabs();
		$tab_slugs = array_keys( $bpaf_tabs );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $tab_slugs[0];
		if ( ! isset( $bpaf_tabs[ $active ] ) ) {
			$active = $tab_slugs[0];
		}

		$page_url            = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		$view_map            = array(
			'default' => 'settings-default',
			'hidden'  => 'settings-hidden',
			'cpt'     => 'settings-cpt',
			'faq'     => 'faq',
			'discover' => 'discover',
		);
		$view                = isset( $view_map[ $active ] ) ? $view_map[ $active ] : 'settings-default';
		$in_settings_group   = isset( $bpaf_tabs[ $active ]['group'] ) && 'settings' === $bpaf_tabs[ $active ]['group'];
		$settings_form_group = self::OPTION_GROUP;

		$view_path = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/admin/views/' . $view . '.php';
		$shell     = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'includes/admin/views/shell.php';

		include $shell;
	}
}
