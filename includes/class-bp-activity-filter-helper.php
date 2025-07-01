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

		// Apply professional labels.
		$labels = self::apply_professional_labels( $labels );

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
	 * Apply professional labels to activity actions.
	 *
	 * @since 4.0.0
	 *
	 * @param array $labels Current labels.
	 * @return array Modified labels.
	 */
	private static function apply_professional_labels( $labels ) {
		$professional_labels = array(
			'new_member'                            => __( 'New Member Registered', 'bp-activity-filter' ),
			'new_avatar'                            => __( 'Profile Picture Updated', 'bp-activity-filter' ),
			'new_cover_photo'                       => __( 'Cover Photo Updated', 'bp-activity-filter' ),
			'updated_profile'                       => __( 'Profile Updated', 'bp-activity-filter' ),
			'friendship_accepted,friendship_created' => __( 'Friendship Status Changed', 'bp-activity-filter' ),
			'friends_register_activity_action'     => __( 'Friendship Activity Registered', 'bp-activity-filter' ),
			'created_group'                         => __( 'New Group Created', 'bp-activity-filter' ),
			'joined_group'                          => __( 'Joined a Group', 'bp-activity-filter' ),
			'group_details_updated'                 => __( 'Group Details Updated', 'bp-activity-filter' ),
			'new_group_avatar'                      => __( 'Group Avatar Updated', 'bp-activity-filter' ),
			'new_group_cover_photo'                 => __( 'Group Cover Photo Updated', 'bp-activity-filter' ),
			'bbp_topic_create'                      => __( 'Forum Topic Created', 'bp-activity-filter' ),
			'bbp_reply_create'                      => __( 'Forum Reply Posted', 'bp-activity-filter' ),
			'activity_update'                       => __( 'Status Update Posted', 'bp-activity-filter' ),
			'activity_comment'                      => __( 'Activity Comment Posted', 'bp-activity-filter' ),
			'new_blog_post'                         => __( 'New Blog Post Published', 'bp-activity-filter' ),
			'new_blog_comment'                      => __( 'New Blog Comment Posted', 'bp-activity-filter' ),
		);

		foreach ( $labels as $key => $value ) {
			if ( isset( $professional_labels[ $key ] ) ) {
				$labels[ $key ] = $professional_labels[ $key ];
			}
		}

		return $labels;
	}

	/**
	 * Get activity actions for a specific context.
	 *
	 * @since 4.0.0
	 *
	 * @param string $context Context (activity, member, group).
	 * @return array Activity actions for context.
	 */
	public static function get_activity_actions_for_context( $context = 'activity' ) {
		if ( ! function_exists( 'bp_activity_get_actions_for_context' ) ) {
			return self::get_activity_actions();
		}

		$actions = bp_activity_get_actions_for_context( $context );
		$labels  = array();

		foreach ( $actions as $action ) {
			// Merge friendship actions.
			if ( in_array( $action['key'], array( 'friendship_accepted', 'friendship_created' ), true ) ) {
				$action['key'] = 'friendship_accepted,friendship_created';
			}

			if ( ! array_key_exists( $action['key'], $labels ) ) {
				$labels[ $action['key'] ] = $action['label'];
			}
		}

		return self::apply_professional_labels( $labels );
	}

	/**
	 * Check if an activity type is hidden.
	 *
	 * @since 4.0.0
	 *
	 * @param string $activity_type Activity type.
	 * @return bool
	 */
	public static function is_activity_type_hidden( $activity_type ) {
		$hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );
		return in_array( $activity_type, $hidden_activities, true );
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
	 * Check if current user can manage plugin settings.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Log debug message.
	 *
	 * @since 4.0.0
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level (error, warning, info, debug).
	 */
	public static function log( $message, $level = 'info' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		if ( function_exists( 'error_log' ) ) {
			error_log( sprintf( '[BP Activity Filter] [%s] %s', strtoupper( $level ), $message ) );
		}
	}

	/**
	 * Get current BuddyPress version.
	 *
	 * @since 4.0.0
	 * @return string|false
	 */
	public static function get_buddypress_version() {
		if ( ! function_exists( 'buddypress' ) ) {
			return false;
		}

		return buddypress()->version;
	}

	/**
	 * Check if BuddyPress meets minimum version requirement.
	 *
	 * @since 4.0.0
	 *
	 * @param string $min_version Minimum required version.
	 * @return bool
	 */
	public static function is_buddypress_version_compatible( $min_version = '5.0.0' ) {
		$bp_version = self::get_buddypress_version();
		
		if ( false === $bp_version ) {
			return false;
		}

		return version_compare( $bp_version, $min_version, '>=' );
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
	public static function is_post_type_eligible_for_activity( $post_type ) {
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

		// Should appear in menus
		if ( ! $post_type->show_in_menu && ! $post_type->show_in_nav_menus ) {
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

		// Check if current user can edit posts of this type
		if ( is_admin() && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get enabled CPT settings.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public static function get_enabled_cpt_settings() {
		$all_settings = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_cpt_settings', array() );
		$enabled_settings = array();

		foreach ( $all_settings as $post_type => $settings ) {
			if ( '_global' === $post_type ) {
				continue;
			}

			if ( isset( $settings['enabled'] ) && $settings['enabled'] ) {
				$enabled_settings[ $post_type ] = $settings;
			}
		}

		return $enabled_settings;
	}

	/**
	 * Check if a post type is enabled for activities.
	 *
	 * @since 4.0.0
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public static function is_post_type_enabled_for_activity( $post_type ) {
		$cpt_settings = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_cpt_settings', array() );
		
		return isset( $cpt_settings[ $post_type ]['enabled'] ) && $cpt_settings[ $post_type ]['enabled'];
	}

	/**
	 * Format activity action with proper HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param string $format Format string with placeholders.
	 * @param array  $args   Arguments for sprintf.
	 * @return string
	 */
	public static function format_activity_action( $format, $args = array() ) {
		return sprintf( $format, ...$args );
	}
}