<?php
/**
 * Frontend functionality for BuddyPress Activity Filter.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class for managing activity filtering and display.
 *
 * @since 4.0.0
 */
class BP_Activity_Filter_Frontend {

	/**
	 * Class instance.
	 *
	 * @since 4.0.0
	 * @var BP_Activity_Filter_Frontend|null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Cache for activity actions to avoid repeated database calls.
	 *
	 * @since 4.0.0
	 * @var array|null Cached activity actions.
	 */
	private $activity_actions_cache = null;

	/**
	 * Cache for default filter values.
	 *
	 * @since 4.0.0
	 * @var array Cached default filters.
	 */
	private $default_filters_cache = array();

	/**
	 * Get class instance.
	 *
	 * @since 4.0.0
	 * @return BP_Activity_Filter_Frontend Singleton instance.
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
	 * Setup frontend hooks and filters.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		// Core frontend hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'bp_template_redirect', array( $this, 'set_default_activity_filter' ) );

		// Activity filtering hooks.
		add_filter( 'bp_get_activity_show_filters', array( $this, 'filter_activity_dropdown' ), 11, 3 );
		add_filter( 'bp_ajax_querystring', array( $this, 'filter_activity_query' ), 999, 2 );

		// Activity prevention hooks.
		add_action( 'bp_activity_before_save', array( $this, 'maybe_prevent_activity_save' ), 5 );
		add_action( 'friends_friendship_accepted', array( $this, 'maybe_prevent_friendship_activity' ), 5, 4 );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 4.0.0
	 */
	public function enqueue_scripts() {
		// Only enqueue on BuddyPress activity pages.
		if ( ! $this->is_activity_page() ) {
			return;
		}

		// Determine default filter based on context.
		$default_filter = $this->get_default_filter();

		// Enqueue frontend script if it exists.
		$script_path = BP_ACTIVITY_FILTER_PLUGIN_DIR . 'assets/js/frontend.js';
		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				'bp-activity-filter-frontend',
				BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				BP_ACTIVITY_FILTER_VERSION,
				true
			);

			// Localize script with frontend data.
			wp_localize_script(
				'bp-activity-filter-frontend',
				'bpActivityFilter',
				array(
					'defaultFilter'       => $default_filter,
					'currentAction'       => bp_current_action(),
					'isUserActivity'      => bp_is_user_activity(),
					'isActivityDir'       => bp_is_activity_directory(),
					'isSingleActivity'    => bp_is_single_activity(),
					'hiddenActivities'    => $this->get_hidden_activities(),
					'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
					'nonce'               => wp_create_nonce( 'bp_activity_filter_frontend' ),
					'cookiePath'          => COOKIEPATH,
					'cookieDomain'        => COOKIE_DOMAIN,
					'strings'             => array(
						'everything'      => esc_html__( 'Everything', 'bp-activity-filter' ),
						'loading'         => esc_html__( 'Loading...', 'bp-activity-filter' ),
						'error'           => esc_html__( 'Error loading activities.', 'bp-activity-filter' ),
						'noActivities'    => esc_html__( 'No activities found.', 'bp-activity-filter' ),
					),
				)
			);
		}
	}

	/**
	 * Check if current page is an activity page.
	 *
	 * @since 4.0.0
	 * @return bool True if on activity page.
	 */
	private function is_activity_page() {
		if ( ! function_exists( 'bp_is_activity_component' ) ) {
			return false;
		}

		return bp_is_activity_component() || bp_is_user_activity();
	}

	/**
	 * Get default filter based on current context.
	 *
	 * @since 4.0.0
	 * @return string Default filter value.
	 */
	private function get_default_filter() {
		$context = $this->get_filter_context();
		
		// Return cached value if available.
		if ( isset( $this->default_filters_cache[ $context ] ) ) {
			return $this->default_filters_cache[ $context ];
		}

		$default_filter = '0';

		if ( 'profile' === $context ) {
			$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );
		} elseif ( 'sitewide' === $context ) {
			$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_default', '0' );
		}

		/**
		 * Filter the default activity filter value.
		 *
		 * @since 4.0.0
		 *
		 * @param string $default_filter Default filter value.
		 * @param string $context        Filter context (profile, sitewide).
		 */
		$default_filter = apply_filters( 'bp_activity_filter_default', $default_filter, $context );

		// Cache the result.
		$this->default_filters_cache[ $context ] = $default_filter;

		return $default_filter;
	}

	/**
	 * Get current filter context.
	 *
	 * @since 4.0.0
	 * @return string Context (profile, sitewide).
	 */
	private function get_filter_context() {
		if ( function_exists( 'bp_is_user_activity' ) && bp_is_user_activity() && 'just-me' === bp_current_action() ) {
			return 'profile';
		}

		return 'sitewide';
	}

	/**
	 * Get hidden activities list with core activity protection.
	 *
	 * @since 4.0.0
	 * @return array List of hidden activity types (excluding core activities).
	 */
	private function get_hidden_activities() {
		$hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );
		
		// Define core activities that should NEVER be hidden, even if somehow saved in settings
		$core_protected_activities = array(
			'activity_update',
			'activity_comment'
		);
		
		// Remove any core activities from hidden list (safety protection)
		$hidden_activities = array_diff( $hidden_activities, $core_protected_activities );
		
		/**
		 * Filter hidden activities after core protection.
		 *
		 * @since 4.0.0
		 *
		 * @param array $hidden_activities Filtered hidden activities.
		 * @param array $core_protected_activities Core activities that cannot be hidden.
		 */
		return apply_filters( 'bp_activity_filter_hidden_activities', $hidden_activities, $core_protected_activities );
	}

	/**
	 * Filter activity dropdown options.
	 *
	 * @since 4.0.0
	 *
	 * @param string|array $output  Current output.
	 * @param array        $filters Available filters.
	 * @param string       $context Filter context.
	 * @return string|array Modified output.
	 */
	public function filter_activity_dropdown( $output, $filters, $context ) {
		$hidden_filters = $this->get_hidden_activities();
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
		 * Filter the available activity filters before display.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $filters Available filters after processing.
		 * @param string $context Filter context.
		 * @param array  $hidden_filters List of hidden filters.
		 */
		$filters = apply_filters( 'bp_activity_filter_available_filters', $filters, $context, $hidden_filters );

		// Handle theme compatibility.
		if ( $this->is_nouveau_theme() ) {
			return array(
				'filters'        => $filters,
				'context'        => $context,
				'default_filter' => $default_filter,
			);
		}

		// Build output for legacy themes.
		return $this->build_legacy_dropdown_output( $filters, $default_filter );
	}

	/**
	 * Check if using BuddyPress Nouveau theme.
	 *
	 * @since 4.0.0
	 * @return bool True if using Nouveau theme.
	 */
	private function is_nouveau_theme() {
		if ( ! function_exists( 'bp_get_option' ) ) {
			return false;
		}

		$theme_package = bp_get_option( '_bp_theme_package_id' );
		return 'nouveau' === $theme_package && ! class_exists( 'Youzify' );
	}

	/**
	 * Build dropdown output for legacy themes.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $filters       Available filters.
	 * @param string $default_filter Default filter value.
	 * @return string HTML dropdown options.
	 */
	private function build_legacy_dropdown_output( $filters, $default_filter ) {
		$output = '';
		
		if ( ! empty( $filters ) && is_array( $filters ) ) {
			foreach ( $filters as $value => $filter ) {
				$selected = ( $value === $default_filter ) ? ' selected="selected"' : '';
				$output  .= sprintf(
					'<option value="%s"%s>%s</option>%s',
					esc_attr( $value ),
					$selected,
					esc_html( $filter ),
					"\n"
				);
			}
		}

		return $output;
	}

	/**
	 * Filter activity query string for AJAX requests.
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
		if ( function_exists( 'bp_is_single_activity' ) && bp_is_single_activity() ) {
			return $query;
		}

		// Skip for specific scopes that should not be filtered.
		if ( $this->should_skip_filtering() ) {
			return $query;
		}

		// Parse and modify query arguments.
		$query_args = $this->parse_query_string( $query );
		$query_args = $this->apply_activity_filters( $query_args );

		/**
		 * Filter the activity query arguments before building final query.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $query_args Processed query arguments.
		 * @param string $query      Original query string.
		 * @param string $object     Query object type.
		 */
		$query_args = apply_filters( 'bp_activity_filter_query_args', $query_args, $query, $object );

		return $this->build_query_string( $query_args );
	}

	/**
	 * Parse query string into arguments array.
	 *
	 * @since 4.0.0
	 *
	 * @param string $query Query string.
	 * @return array Parsed query arguments.
	 */
	private function parse_query_string( $query ) {
		$query_args = wp_parse_args( $query );

		// Handle pagination.
		$query_args['page'] = $this->get_page_number( $query_args );

		// Ensure numeric values are properly typed.
		if ( isset( $query_args['per_page'] ) ) {
			$query_args['per_page'] = absint( $query_args['per_page'] );
		}

		return $query_args;
	}

	/**
	 * Apply activity filters to query arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array $query_args Query arguments.
	 * @return array Modified query arguments.
	 */
	private function apply_activity_filters( $query_args ) {
		// Apply default filter if cookie is set.
		if ( $this->should_apply_default_filter() ) {
			$query_args = $this->apply_default_filter( $query_args );
		} else {
			// Apply hidden activities filter.
			$query_args = $this->apply_hidden_activities_filter( $query_args );
		}

		return $query_args;
	}

	/**
	 * Build query string from arguments array.
	 *
	 * @since 4.0.0
	 *
	 * @param array $query_args Query arguments.
	 * @return string Built query string.
	 */
	private function build_query_string( $query_args ) {
		// Remove empty values.
		$query_args = array_filter( $query_args, function( $value ) {
			return '' !== $value && null !== $value;
		});

		return build_query( $query_args );
	}

	/**
	 * Check if filtering should be skipped for current request.
	 *
	 * @since 4.0.0
	 * @return bool True if filtering should be skipped.
	 */
	private function should_skip_filtering() {
		$skip_scopes = array( 'mentions', 'friends', 'favorites', 'groups' );

		// Check for directory scopes.
		if ( function_exists( 'bp_is_activity_directory' ) && bp_is_activity_directory() ) {
			$scope = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : '';
			if ( in_array( $scope, $skip_scopes, true ) ) {
				return true;
			}
		}

		// Check for user activity scopes.
		if ( function_exists( 'bp_is_user_activity' ) && bp_is_user_activity() ) {
			$current_action = function_exists( 'bp_current_action' ) ? bp_current_action() : '';
			if ( in_array( $current_action, $skip_scopes, true ) ) {
				return true;
			}
		}

		/**
		 * Filter whether activity filtering should be skipped.
		 *
		 * @since 4.0.0
		 *
		 * @param bool $skip_filtering Whether to skip filtering.
		 */
		return apply_filters( 'bp_activity_filter_skip_filtering', false );
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
	 * @return bool True if default filter should be applied.
	 */
	private function should_apply_default_filter() {
		return ! empty( $_COOKIE['bp_activity_filter_apply'] );
	}

	/**
	 * Apply default filter to query arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array $query_args Query arguments.
	 * @return array Modified query arguments.
	 */
	private function apply_default_filter( $query_args ) {
		$default_filter = $this->get_default_filter();

		if ( $default_filter && '0' !== $default_filter && '-1' !== $default_filter ) {
			$query_args['action'] = $default_filter;
		} else {
			// Apply hidden activities filter.
			$query_args = $this->apply_hidden_activities_filter( $query_args );
		}

		return $query_args;
	}

	/**
	 * Apply hidden activities filter to query arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array $query_args Query arguments.
	 * @return array Modified query arguments.
	 */
	private function apply_hidden_activities_filter( $query_args ) {
		$hidden_activities = $this->get_hidden_activities();

		if ( empty( $hidden_activities ) ) {
			return $query_args;
		}

		// Get all available activity actions.
		$all_actions = $this->get_all_activity_actions();
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
	 * Get all available activity actions with caching.
	 *
	 * @since 4.0.0
	 * @return array All activity actions.
	 */
	private function get_all_activity_actions() {
		if ( null === $this->activity_actions_cache ) {
			$this->activity_actions_cache = BP_Activity_Filter_Helper::get_activity_actions();
		}

		return $this->activity_actions_cache;
	}

	/**
	 * Maybe prevent activity from being saved based on hidden settings.
	 *
	 * @since 4.0.0
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 */
	public function maybe_prevent_activity_save( $activity ) {
		if ( ! isset( $activity->type ) ) {
			return;
		}

		$hidden_activities = $this->get_hidden_activities();

		if ( ! empty( $hidden_activities ) && in_array( $activity->type, $hidden_activities, true ) ) {
			$activity->type = false;
		}
	}

	/**
	 * Maybe prevent friendship activity based on hidden settings.
	 *
	 * @since 4.0.0
	 *
	 * @param int    $friendship_id      Friendship ID.
	 * @param int    $initiator_user_id  Initiator user ID.
	 * @param int    $friend_user_id     Friend user ID.
	 * @param object $friendship         Friendship object.
	 */
	public function maybe_prevent_friendship_activity( $friendship_id, $initiator_user_id, $friend_user_id, $friendship = false ) {
		$hidden_activities = $this->get_hidden_activities();

		if ( ! empty( $hidden_activities ) && in_array( 'friendship_accepted,friendship_created', $hidden_activities, true ) ) {
			if ( function_exists( 'remove_action' ) ) {
				remove_action( 'friends_friendship_accepted', 'bp_friends_friendship_accepted_activity', 10, 4 );
			}
		}
	}

	/**
	 * Set default activity filter on page load.
	 *
	 * @since 4.0.0
	 */
	public function set_default_activity_filter() {
		// Skip if not on activity pages.
		if ( ! $this->is_activity_page() ) {
			return;
		}

		// Skip if filter already set or on specific pages.
		if ( isset( $_COOKIE['bp-activity-filter'] ) ) {
			return;
		}

		if ( function_exists( 'bp_is_single_activity' ) && bp_is_single_activity() ) {
			return;
		}

		// Skip for specific actions.
		$skip_actions = array( 'mentions', 'favorites', 'friends', 'groups' );
		$current_action = function_exists( 'bp_current_action' ) ? bp_current_action() : '';
		
		if ( in_array( $current_action, $skip_actions, true ) ) {
			return;
		}

		// Get filter based on context.
		$filter = $this->get_default_filter();

		// Set cookies with proper security.
		$this->set_filter_cookies( $filter );
	}

	/**
	 * Set filter cookies with proper configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param string $filter Filter value to set.
	 */
	private function set_filter_cookies( $filter ) {
		$expire_time = time() + HOUR_IN_SECONDS;
		$secure = is_ssl();
		$httponly = true;

		// Set cookies.
		setcookie( 'bp-activity-filter', $filter, $expire_time, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly );
		setcookie( 'bp_activity_filter_apply', '1', $expire_time, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly );

		// Set global cookies for immediate use.
		$_COOKIE['bp-activity-filter'] = $filter;
		$_COOKIE['bp_activity_filter_apply'] = '1';
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 4.0.0
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 4.0.0
	 */
	public function __wakeup() {}
}