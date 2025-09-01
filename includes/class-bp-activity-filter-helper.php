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
				// Skip friendship_accepted as it doesn't create an actual activity
				// BuddyPress uses friendship_accepted hook but creates friendship_created activity type
				if ( $key === 'friendship_accepted' ) {
					continue; // Skip this as it's not a real activity type
				}
				
				// Skip friends_register_activity_action - it's just a registration helper, not a real activity type
				// Only friendship_created activities are actually created in the database
				if ( $key === 'friends_register_activity_action' ) {
					continue; // Skip this registration artifact
				}

				// Update label for friendship_created to be clearer
				if ( $key === 'friendship_created' ) {
					$action['value'] = __( 'New friendships', 'bp-activity-filter' );
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
		
		// Handle legacy merged friendship key
		if ( strpos( $filter, 'friendship_accepted,friendship_created' ) !== false ) {
			$filter = str_replace( 'friendship_accepted,friendship_created', 'friendship_created', $filter );
		}
		
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
	 * Get all public custom post types eligible for activity generation.
	 * CONSERVATIVE APPROACH: Only exclude obvious conflicts, include everything else.
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

		/**
		 * Filter eligible post types before returning.
		 *
		 * @since 4.0.0
		 *
		 * @param array $eligible_types Eligible post types.
		 */
		return apply_filters( 'bp_activity_filter_eligible_post_types', $eligible_types );
	}

	/**
	 * CONSERVATIVE: Check if a post type is eligible for activity generation.
	 * Only exclude OBVIOUS conflicts - err on the side of inclusion.
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

		// Basic requirements
		if ( ! $post_type->public || ! $post_type->show_ui ) {
			return false;
		}

		// Must support title
		if ( ! post_type_supports( $post_type->name, 'title' ) ) {
			return false;
		}

		// Exclude WordPress built-in types that are clearly not for content
		$excluded_builtin_types = array( 
			'attachment', 
			'revision', 
			'nav_menu_item', 
			'custom_css', 
			'customize_changeset',
			'oembed_cache',
			'user_request',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation'
		);
		
		if ( in_array( $post_type->name, $excluded_builtin_types, true ) ) {
			return false;
		}
		
		// Exclude known UI/template post types that shouldn't generate activities
		$excluded_ui_types = array(
			'elementor_library',    // Elementor templates
			'e-floating-buttons',   // Elementor floating UI elements
			'elementor_font',       // Elementor fonts
			'elementor_icons',      // Elementor icons
			'elementor_snippet',    // Elementor code snippets
			'e-landing-page'        // Elementor landing pages
		);
		
		if ( in_array( $post_type->name, $excluded_ui_types, true ) ) {
			return false;
		}

		// ONLY exclude if there are CLEAR indicators of conflict
		// 1. 'create_posts' => 'do_not_allow' capability (strong indicator)
		if ( isset( $post_type->capabilities['create_posts'] ) && 
			 'do_not_allow' === $post_type->capabilities['create_posts'] ) {
			return false;
		}

		// 2. Known problematic post types with confirmed conflicts
		if ( self::is_known_conflicting_post_type( $post_type->name ) ) {
			return false;
		}

		// INCLUDE everything else - let the runtime checks handle edge cases
		return true;
	}

	/**
	 * CONSERVATIVE: Only check for CONFIRMED conflicts with specific plugins.
	 * This is much more conservative than before.
	 *
	 * @since 4.0.0
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	private static function is_known_conflicting_post_type( $post_type ) {
		// Only include post types we KNOW have conflicts and we KNOW the plugin is active
		$confirmed_conflicts = array(
			// BuddyPress Member Reviews - only if plugin is active
			'review' => array(
				'plugin_check' => function() {
					return class_exists( 'BP_Member_Reviews' ) || 
						   class_exists( 'BUPR_Admin' ) || 
						   function_exists( 'bp_member_reviews_init' ) ||
						   defined( 'BUPR_PLUGIN_VERSION' );
				}
			),
			// bbPress - only if bbPress is active
			'forum' => array(
				'plugin_check' => function() {
					return class_exists( 'bbPress' ) || function_exists( 'bbpress' );
				}
			),
			'topic' => array(
				'plugin_check' => function() {
					return class_exists( 'bbPress' ) || function_exists( 'bbpress' );
				}
			),
			'reply' => array(
				'plugin_check' => function() {
					return class_exists( 'bbPress' ) || function_exists( 'bbpress' );
				}
			),
		);

		// Only exclude if we have a confirmed conflict AND the plugin is active
		if ( isset( $confirmed_conflicts[ $post_type ] ) ) {
			$conflict = $confirmed_conflicts[ $post_type ];
			if ( isset( $conflict['plugin_check'] ) && is_callable( $conflict['plugin_check'] ) ) {
				return call_user_func( $conflict['plugin_check'] );
			}
		}

		return false;
	}

	/**
	 * Get conflict reasons for excluded post types.
	 * SIMPLIFIED: Only show actual exclusions.
	 *
	 * @since 4.0.0
	 *
	 * @return array Array of post types and their conflict reasons.
	 */
	public static function get_excluded_post_types_with_reasons() {
		$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
		$excluded = array();

		foreach ( $post_types as $post_type => $post_type_obj ) {
			if ( ! self::is_post_type_eligible_for_activity( $post_type_obj ) ) {
				$reason = self::get_exclusion_reason( $post_type_obj );
				$excluded[ $post_type ] = array(
					'label' => $post_type_obj->label,
					'reason' => $reason
				);
			}
		}

		return $excluded;
	}

	/**
	 * Get the specific reason why a post type is excluded.
	 * SIMPLIFIED: Only the actual reasons we check for.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post_Type $post_type_obj Post type object.
	 * @return string
	 */
	private static function get_exclusion_reason( $post_type_obj ) {
		$post_type = $post_type_obj->name;

		if ( ! $post_type_obj->public ) {
			return 'not_public';
		}

		if ( ! $post_type_obj->show_ui ) {
			return 'no_admin_ui';
		}

		if ( ! post_type_supports( $post_type, 'title' ) ) {
			return 'no_title_support';
		}
		
		// Check if it's a UI/template type
		$excluded_ui_types = array(
			'elementor_library',
			'e-floating-buttons',
			'elementor_font',
			'elementor_icons',
			'elementor_snippet',
			'e-landing-page'
		);
		
		if ( in_array( $post_type, $excluded_ui_types, true ) ) {
			return 'ui_template_type';
		}

		if ( isset( $post_type_obj->capabilities['create_posts'] ) && 
			 'do_not_allow' === $post_type_obj->capabilities['create_posts'] ) {
			return 'capability_restriction';
		}

		if ( self::is_known_conflicting_post_type( $post_type ) ) {
			return 'known_plugin_conflict';
		}

		return 'unknown';
	}
}