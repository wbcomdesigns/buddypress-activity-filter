<?php
/**
 * Frontend functionality.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class.
 *
 * @since 4.0.0
 */
class BP_Activity_Filter_Frontend {

	/**
	 * Class instance.
	 *
	 * @since 4.0.0
	 * @var BP_Activity_Filter_Frontend
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 4.0.0
	 * @return BP_Activity_Filter_Frontend
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
	 * Setup hooks.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'bp_get_activity_show_filters', array( $this, 'filter_activity_dropdown' ), 11, 3 );
		add_filter( 'bp_ajax_querystring', array( $this, 'filter_activity_query' ), 999, 2 );
		add_action( 'bp_activity_before_save', array( $this, 'maybe_prevent_activity_save' ), 5 );
		add_action( 'friends_friendship_accepted', array( $this, 'maybe_prevent_friendship_activity' ), 5, 4 );
		add_action( 'bp_template_redirect', array( $this, 'set_default_activity_filter' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 4.0.0
	 */
	public function enqueue_scripts() {
		// Only enqueue on BuddyPress activity pages.
		if ( ! bp_is_activity_component() ) {
			return;
		}

		// Determine default filter based on context.
		$default_filter = $this->get_default_filter();

		wp_enqueue_script(
			'bp-activity-filter-frontend',
			BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			BP_ACTIVITY_FILTER_VERSION,
			true
		);

		wp_localize_script(
			'bp-activity-filter-frontend',
			'bpActivityFilter',
			array(
				'defaultFilter'  => $default_filter,
				'currentAction'  => bp_current_action(),
				'isUserActivity' => bp_is_user_activity(),
				'isActivityDir'  => bp_is_activity_directory(),
			)
		);
	}

	/**
	 * Get default filter based on context.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	private function get_default_filter() {
		$default_filter = '0';

		if ( bp_is_user_activity() && 'just-me' === bp_current_action() ) {
			$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );
		} elseif ( ! bp_is_user_activity() ) {
			$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_default', '0' );
		}

		/**
		 * Filter the default activity filter.
		 *
		 * @since 4.0.0
		 *
		 * @param string $default_filter Default filter value.
		 */
		return apply_filters( 'bp_activity_filter_default', $default_filter );
	}

	/**
	 * Filter activity dropdown options.
	 *
	 * @since 4.0.0
	 *
	 * @param string $output  Current output.
	 * @param array  $filters Available filters.
	 * @param string $context Filter context.
	 * @return string|array Modified output.
	 */
	public function filter_activity_dropdown( $output, $filters, $context ) {
		$hidden_filters = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );
		$default_filter = $this->get_default_filter();

		// Remove hidden filters.
		if ( ! empty( $hidden_filters ) && is_array( $filters ) ) {
			foreach ( $filters as $key => $value ) {
				if ( in_array( $key, $hidden_filters, true ) ) {
					unset( $filters[ $key ] );
				}
			}
		}

		/**
		 * Filter the available activity filters.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $filters Available filters.
		 * @param string $context Filter context.
		 */
		$filters = apply_filters( 'bp_activity_filter_available_filters', $filters, $context );

		// Build output for legacy themes.
		$output = '';
		if ( ! empty( $filters ) ) {
			foreach ( $filters as $value => $filter ) {
				$selected = ( $value === $default_filter ) ? ' selected="selected"' : '';
				$output  .= '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $filter ) . '</option>' . "\n";
			}
		}

		// Handle theme compatibility.
		$theme_package = bp_get_option( '_bp_theme_package_id' );
		if ( 'nouveau' === $theme_package && ! class_exists( 'Youzify' ) ) {
			return array(
				'filters' => $filters,
				'context' => $context,
			);
		}

		return $output;
	}

	/**
	 * Filter activity query string.
	 *
	 * @since 4.0.0
	 *
	 * @param string $query  Current query string.
	 * @param string $object Query object type.
	 * @return string Modified query string.
	 */
	public function filter_activity_query( $query, $object ) {
		// Only filter activity queries.
		if ( 'activity' !== $object ) {
			return $query;
		}

		// Skip single activity views.
		if ( bp_is_single_activity() ) {
			return $query;
		}

		// Skip for specific scopes.
		if ( $this->should_skip_filtering() ) {
			return $query;
		}

		// Parse query arguments.
		$query_args = wp_parse_args( $query );

		// Handle pagination.
		$query_args['page'] = $this->get_page_number( $query_args );

		// Apply default filter if cookie is set.
		if ( $this->should_apply_default_filter() ) {
			$default_filter = $this->get_default_filter();

			if ( $default_filter && '0' !== $default_filter && '-1' !== $default_filter ) {
				$query_args['action'] = $default_filter;
			} else {
				// Apply hidden activities filter.
				$query_args = $this->apply_hidden_activities_filter( $query_args );
			}
		} else {
			// Apply hidden activities filter.
			$query_args = $this->apply_hidden_activities_filter( $query_args );
		}

		/**
		 * Filter the activity query arguments.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $query_args Query arguments.
		 * @param string $query      Original query string.
		 * @param string $object     Query object type.
		 */
		$query_args = apply_filters( 'bp_activity_filter_query_args', $query_args, $query, $object );

		return build_query( $query_args );
	}

	/**
	 * Check if filtering should be skipped.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	private function should_skip_filtering() {
		$skip_scopes = array( 'mentions', 'friends', 'favorites', 'groups' );

		// Check for directory scopes.
		if ( bp_is_activity_directory() && isset( $_POST['scope'] ) && in_array( $_POST['scope'], $skip_scopes, true ) ) {
			return true;
		}

		// Check for user activity scopes.
		if ( bp_is_user_activity() && in_array( bp_current_action(), $skip_scopes, true ) ) {
			return true;
		}

		// Check for hashtags plugin compatibility.
		if ( $this->is_hashtags_plugin_active() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if hashtags plugin is active.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	private function is_hashtags_plugin_active() {
		$active_plugins = get_option( 'active_plugins', array() );
		return in_array( 'buddypress-hashtag/buddypress-hashtags.php', $active_plugins, true );
	}

	/**
	 * Get page number from query or POST data.
	 *
	 * @since 4.0.0
	 *
	 * @param array $query_args Query arguments.
	 * @return int Page number.
	 */
	private function get_page_number( $query_args ) {
		if ( isset( $_POST['page'] ) && is_numeric( $_POST['page'] ) ) {
			return absint( $_POST['page'] );
		}

		if ( isset( $query_args['page'] ) && is_numeric( $query_args['page'] ) ) {
			return absint( $query_args['page'] );
		}

		return 1;
	}

	/**
	 * Check if default filter should be applied.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	private function should_apply_default_filter() {
		return ! empty( $_COOKIE['bp_activity_filter_apply'] );
	}

	/**
	 * Apply hidden activities filter.
	 *
	 * @since 4.0.0
	 *
	 * @param array $query_args Query arguments.
	 * @return array Modified query arguments.
	 */
	private function apply_hidden_activities_filter( $query_args ) {
		$hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );

		if ( empty( $hidden_activities ) ) {
			return $query_args;
		}

		// Get all available activity actions.
		$all_actions = BP_Activity_Filter_Helper::get_activity_actions();
		$allowed_actions = array();

		foreach ( $all_actions as $action_key => $action_label ) {
			if ( ! in_array( $action_key, $hidden_activities, true ) ) {
				$allowed_actions[] = $action_key;
			}
		}

		if ( ! empty( $allowed_actions ) ) {
			$query_args['action'] = implode( ',', $allowed_actions );
		}

		return $query_args;
	}

	/**
	 * Maybe prevent activity from being saved.
	 *
	 * @since 4.0.0
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 */
	public function maybe_prevent_activity_save( $activity ) {
		$hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );

		if ( ! empty( $hidden_activities ) && in_array( $activity->type, $hidden_activities, true ) ) {
			$activity->type = false;
		}
	}

	/**
	 * Maybe prevent friendship activity.
	 *
	 * @since 4.0.0
	 *
	 * @param int    $friendship_id      Friendship ID.
	 * @param int    $initiator_user_id  Initiator user ID.
	 * @param int    $friend_user_id     Friend user ID.
	 * @param object $friendship         Friendship object.
	 */
	public function maybe_prevent_friendship_activity( $friendship_id, $initiator_user_id, $friend_user_id, $friendship = false ) {
		$hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );

		if ( ! empty( $hidden_activities ) && in_array( 'friendship_accepted,friendship_created', $hidden_activities, true ) ) {
			remove_action( 'friends_friendship_accepted', 'bp_friends_friendship_accepted_activity', 10, 4 );
		}
	}

	/**
	 * Set default activity filter.
	 *
	 * @since 4.0.0
	 */
	public function set_default_activity_filter() {
		// Skip if filter already set or on specific pages.
		if ( isset( $_COOKIE['bp-activity-filter'] ) || bp_is_single_activity() ) {
			return;
		}

		// Skip for specific actions.
		$skip_actions = array( 'mentions', 'favorites', 'friends', 'groups' );
		if ( in_array( bp_current_action(), $skip_actions, true ) ) {
			return;
		}

		// Only apply on activity pages.
		if ( ! bp_is_activity_directory() && ! bp_is_user_activity() ) {
			return;
		}

		// Get filter based on context.
		$filter = $this->get_default_filter();

		// Set cookies.
		$expire_time = time() + HOUR_IN_SECONDS;
		setcookie( 'bp-activity-filter', $filter, $expire_time, '/' );
		setcookie( 'bp_activity_filter_apply', '1', $expire_time, '/' );

		// Set global cookies for immediate use.
		$_COOKIE['bp-activity-filter'] = $filter;
		$_COOKIE['bp_activity_filter_apply'] = '1';
	}
}