<?php
/**
 * Helper functions and utilities.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class.
 *
 * @since 4.0.0
 */
class BP_Activity_Filter_Helper {

	/**
	 * Get all available activity actions.
	 *
	 * @since 4.0.0
	 * @return array Activity actions array.
	 */
	public static function get_activity_actions() {
		if ( ! function_exists( 'bp_activity_get_actions' ) ) {
			return array();
		}

		$actions = bp_activity_get_actions();
		$labels  = array();

		foreach ( $actions as $component => $component_actions ) {
			foreach ( $component_actions as $key => $action ) {
				// Merge friendship actions into one.
				if ( in_array( $key, array( 'friendship_accepted', 'friendship_created' ), true ) ) {
					$key = 'friendship_accepted,friendship_created';
				}

				if ( ! isset( $labels[ $key ] ) ) {
					$labels[ $key ] = $action['value'];
				}
			}
		}

		/**
		 * Filter the available activity actions.
		 *
		 * @since 4.0.0
		 *
		 * @param array $labels Activity action labels.
		 */
		return apply_filters( 'bp_activity_filter_activity_actions', $labels );
	}

	/**
	 * Get default filter for current context.
	 *
	 * @since 4.0.0
	 *
	 * @param string $context Context (sitewide, profile).
	 * @return string
	 */
	public static function get_default_filter( $context = 'sitewide' ) {
		$option_key = 'profile' === $context ? 'bp_activity_filter_profile_default' : 'bp_activity_filter_default';
		$default    = 'profile' === $context ? '-1' : '0';

		return BP_Activity_Filter_Migration::get_option_with_fallback( $option_key, $default );
	}

	/**
	 * Sanitize activity filter value.
	 *
	 * @since 4.0.0
	 *
	 * @param string $filter Filter value.
	 * @return string
	 */
	public static function sanitize_filter_value( $filter ) {
		if ( empty( $filter ) ) {
			return '0';
		}

		// Allow comma-separated values for multiple actions.
		$filter = sanitize_text_field( $filter );
		
		// Validate against known actions.
		$known_actions = array_keys( self::get_activity_actions() );
		$filter_parts  = explode( ',', $filter );
		$valid_parts   = array();

		foreach ( $filter_parts as $part ) {
			$part = trim( $part );
			if ( in_array( $part, $known_actions, true ) || in_array( $part, array( '0', '-1' ), true ) ) {
				$valid_parts[] = $part;
			}
		}

		return ! empty( $valid_parts ) ? implode( ',', $valid_parts ) : '0';
	}

	/**
	 * Get plugin version.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public static function get_plugin_version() {
		return defined( 'BP_ACTIVITY_FILTER_VERSION' ) ? BP_ACTIVITY_FILTER_VERSION : '4.0.0';
	}

	/**
	 * Get plugin directory path.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public static function get_plugin_dir() {
		return defined( 'BP_ACTIVITY_FILTER_PLUGIN_DIR' ) ? BP_ACTIVITY_FILTER_PLUGIN_DIR : plugin_dir_path( __DIR__ );
	}

	/**
	 * Get plugin directory URL.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public static function get_plugin_url() {
		return defined( 'BP_ACTIVITY_FILTER_PLUGIN_URL' ) ? BP_ACTIVITY_FILTER_PLUGIN_URL : plugin_dir_url( __DIR__ );
	}

	/**
	 * Get all public custom post types with admin UI.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public static function get_eligible_post_types() {
		$post_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
				'show_ui'  => true,
			),
			'objects'
		);

		$eligible_types = array();

		foreach ( $post_types as $post_type => $post_type_obj ) {
			if ( self::is_post_type_eligible_for_activity( $post_type_obj ) ) {
				$eligible_types[ $post_type ] = $post_type_obj;
			}
		}

		return $eligible_types;
	}

	/**
	 * Check if a post type is eligible for activity generation.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post_Type|string $post_type Post type object or name.
	 * @return bool
	 */
	private static function is_post_type_eligible_for_activity( $post_type ) {
		if ( is_string( $post_type ) ) {
			$post_type = get_post_type_object( $post_type );
		}

		if ( ! $post_type ) {
			return false;
		}

		// Must be public
		if ( ! $post_type->public ) {
			return false;
		}

		// Must have admin UI
		if ( ! $post_type->show_ui ) {
			return false;
		}

		// Should not be excluded from search
		if ( $post_type->exclude_from_search ) {
			return false;
		}

		// Must support title
		if ( ! post_type_supports( $post_type->name, 'title' ) ) {
			return false;
		}

		// Exclude specific post types
		$excluded_types = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset' );
		if ( in_array( $post_type->name, $excluded_types, true ) ) {
			return false;
		}

		return true;
	}
}