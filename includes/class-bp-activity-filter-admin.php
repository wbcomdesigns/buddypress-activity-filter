<?php
/**
 * Legacy admin service class for BP Activity Filter.
 *
 * Post-migration (v3.2.1) this class no longer renders the admin UI or
 * registers the menu — BP_Activity_Filter_Admin_Panel owns the card-panel
 * page, menu, enqueue, and settings registration. This class is retained
 * solely for its battle-tested sanitizer callbacks, which the new panel's
 * register_setting() calls delegate to so the option-save contract stays
 * byte-identical to the pre-3.2.1 form.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Activity Filter Admin Class (sanitizers only).
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
	 * No hooks: settings registration + UI moved to
	 * BP_Activity_Filter_Admin_Panel in v3.2.1. Instantiated only so the
	 * sanitizer callbacks below remain reachable as a service.
	 *
	 * @since 4.0.0
	 */
	private function __construct() {}

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

		$input           = sanitize_text_field( $input );
		$valid_actions   = array_keys( BP_Activity_Filter_Helper::get_activity_actions() );
		$valid_actions[] = '0';
		$valid_actions[] = '-1';

		return in_array( $input, $valid_actions, true ) ? $input : '0';
	}

	/**
	 * Sanitize hidden activities array with core activity protection.
	 *
	 * @since 4.0.0
	 * @param mixed $input Raw input value.
	 * @return array Sanitized array of activity types (excluding core activities).
	 */
	public function sanitize_hidden_activities( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized     = array();
		$valid_actions = array_keys( BP_Activity_Filter_Helper::get_activity_actions() );

		// Define core activities that should NEVER be hidden.
		$core_protected_activities = array(
			'activity_update',
			'activity_comment',
		);

		foreach ( $input as $activity_type ) {
			$activity_type = sanitize_text_field( $activity_type );

			// Skip empty values.
			if ( empty( $activity_type ) ) {
				continue;
			}

			// Skip core protected activities.
			if ( in_array( $activity_type, $core_protected_activities, true ) ) {
				continue;
			}

			// Only include valid activity types.
			if ( in_array( $activity_type, $valid_actions, true ) ) {
				$sanitized[] = $activity_type;
			}
		}

		return array_values( array_unique( $sanitized ) );
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

		$sanitized        = array();
		$valid_post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ( $input as $post_type => $settings ) {
			$post_type = sanitize_text_field( $post_type );

			if ( '_global' === $post_type ) {
				$sanitized[ $post_type ] = array(
					'hide_sitewide' => ! empty( $settings['hide_sitewide'] ),
				);
			} elseif ( in_array( $post_type, $valid_post_types, true ) ) {
				$sanitized[ $post_type ] = array(
					'enabled' => ! empty( $settings['enabled'] ),
					'label'   => isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '',
				);
			}
		}

		return $sanitized;
	}
}
