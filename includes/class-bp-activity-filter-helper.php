<?php
/**
 * Helper functions and utilities.
 *
 * @package BuddyPress_Activity_Filter
 * @since 3.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class.
 *
 * @since 3.1.0
 */
class BP_Activity_Filter_Helper {

	/**
	 * Get all available activity actions.
	 *
	 * @since 3.1.0
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
				// Skip friendship_accepted as it doesn't create an actual activity.
				// BuddyPress uses friendship_accepted hook but creates friendship_created activity type.
				if ( 'friendship_accepted' === $key ) {
					continue; // Skip this as it's not a real activity type.
				}

				// Skip friends_register_activity_action - it's just a registration helper, not a real activity type.
				// Only friendship_created activities are actually created in the database.
				if ( 'friends_register_activity_action' === $key ) {
					continue; // Skip this registration artifact.
				}

				// Update label for friendship_created to be clearer.
				if ( 'friendship_created' === $key ) {
					$action['value'] = __( 'New friendships', 'buddypress-activity-filter' );
				}

				if ( ! isset( $labels[ $key ] ) ) {
					$labels[ $key ] = $action['value'];
				}
			}
		}

		/**
		 * Filter the available activity actions.
		 *
		 * @since 3.1.0
		 *
		 * @param array $labels Activity action labels.
		 */
		return apply_filters( 'bp_activity_filter_activity_actions', $labels );
	}
}
