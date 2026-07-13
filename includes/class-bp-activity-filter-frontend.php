<?php
/**
 * Frontend functionality for BuddyPress Activity Filter - FIXED VERSION
 *
 * This version works WITH BuddyPress instead of against it
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class - MINIMAL INTERFERENCE APPROACH
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
	 * Setup frontend hooks - Server-side approach
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		// Server-side default filter - runs BEFORE activities are queried.
		add_filter( 'bp_after_has_activities_parse_args', array( $this, 'apply_default_filter_server_side' ), 10, 1 );

		// Exclude hidden types from the stream itself. Blocking creation only stops new
		// items, so activities recorded before a type was hidden would still be listed.
		// This must be a WHERE condition, not a filter_query arg: BP_Activity_Activity::get()
		// only honours filter_query when no scope is set, so scoped streams (just-me,
		// friends, groups, mentions, favorites) would ignore it.
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'exclude_hidden_from_query' ), 10, 2 );

		// Also filter AJAX requests.
		add_filter( 'bp_ajax_querystring', array( $this, 'apply_default_filter_ajax' ), 10, 2 );

		// Set initial cookie and dropdown state (minimal JS just for UI sync).
		add_action( 'wp_footer', array( $this, 'sync_dropdown_with_default' ), 999 );

		// Remove hidden activities from dropdown (but don't interfere with filtering).
		// Filter the options array, not the built HTML: Nouveau hooks
		// bp_get_activity_show_filters at the same priority and returns the original
		// $filters array, which would discard any edit made to the HTML output.
		add_filter( 'bp_get_activity_show_filters_options', array( $this, 'remove_hidden_from_dropdown' ), 10, 2 );

		// Prevent hidden activities from being created (at source).
		// Use very early priority to catch before other plugins.
		// This is the single enforcement point: BP_Activity_Activity::save() aborts on
		// an empty component/type, so it covers every hidden type regardless of which
		// component records it.
		add_action( 'bp_activity_before_save', array( $this, 'maybe_prevent_activity_save' ), 1 );
	}

	/**
	 * Apply default filter server-side.
	 *
	 * @since 4.0.0
	 * @param array $args Activity query arguments.
	 * @return array Modified arguments.
	 */
	public function apply_default_filter_server_side( $args ) {
		// Skip if already filtered or if specific type is requested.
		if ( ! empty( $args['filter_query'] ) || ! empty( $args['action'] ) || ! empty( $args['type'] ) ) {
			return $args;
		}

		// Skip if this is an AJAX request with existing filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST['filter'] ) ) {
			return $args;
		}

		// Check user preference first (from cookie).
		if ( isset( $_COOKIE['bp-activity-filter'] ) && '0' !== $_COOKIE['bp-activity-filter'] && '-1' !== $_COOKIE['bp-activity-filter'] ) {
			$args['action'] = sanitize_text_field( wp_unslash( $_COOKIE['bp-activity-filter'] ) );

			return $args;
		}

		// No user preference, apply admin default.
		$default_filter = $this->get_default_filter();
		if ( $default_filter && '0' !== $default_filter && '-1' !== $default_filter ) {
			$args['action'] = $default_filter;
		}

		return $args;
	}

	/**
	 * Exclude hidden activity types from activity stream queries.
	 *
	 * Preventing hidden types from being saved only stops new items. Activities
	 * recorded before an admin hid that type are already in the database and would
	 * still show in the stream, so they have to be excluded at query time too.
	 *
	 * BuddyPress ANDs every condition returned here and applies them after the
	 * scope/filter_query branch, so this covers the directory, scoped streams
	 * (just-me, friends, groups, mentions, favorites), AJAX refreshes, and the
	 * pagination count, which is built from the same WHERE clause.
	 *
	 * The site admin's own Activity screen is left alone so hidden items remain
	 * moderatable in the backend.
	 *
	 * @since 4.0.0
	 * @param array $where_conditions Current WHERE conditions.
	 * @param array $args             Parsed activity query arguments.
	 * @return array WHERE conditions with hidden types excluded.
	 */
	public function exclude_hidden_from_query( $where_conditions, $args = array() ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $where_conditions;
		}

		$hidden_activities = $this->get_hidden_activities();

		if ( empty( $hidden_activities ) ) {
			return $where_conditions;
		}

		$not_in = "'" . implode( "', '", array_map( 'esc_sql', $hidden_activities ) ) . "'";

		$where_conditions['bp_activity_filter_hidden'] = "a.type NOT IN ({$not_in})";

		return $where_conditions;
	}

	/**
	 * Apply filter to AJAX requests.
	 *
	 * @since 4.0.0
	 * @param string $query_string The query string.
	 * @param string $object       The object type.
	 * @return string Modified query string.
	 */
	public function apply_default_filter_ajax( $query_string, $object ) {
		if ( 'activity' !== $object ) {
			return $query_string;
		}

		// Parse existing query string.
		wp_parse_str( $query_string, $args );

		// If action/type already set, don't override.
		if ( ! empty( $args['action'] ) || ! empty( $args['type'] ) ) {
			return $query_string;
		}

		// Check user preference from cookie.
		if ( isset( $_COOKIE['bp-activity-filter'] ) && '0' !== $_COOKIE['bp-activity-filter'] && '-1' !== $_COOKIE['bp-activity-filter'] ) {
			$args['action'] = sanitize_text_field( wp_unslash( $_COOKIE['bp-activity-filter'] ) );
			return http_build_query( $args );
		}

		// No user preference, use admin default.
		$default_filter = $this->get_default_filter();
		if ( $default_filter && '0' !== $default_filter && '-1' !== $default_filter ) {
			$args['action'] = $default_filter;
			return http_build_query( $args );
		}

		return $query_string;
	}

	/**
	 * Sync dropdown with server-side default (minimal JS just for UI)
	 *
	 * @since 4.0.0
	 */
	public function sync_dropdown_with_default() {
		// Only on activity pages.
		if ( ! function_exists( 'bp_is_activity_directory' ) || ! bp_is_activity_directory() ) {
			if ( ! function_exists( 'bp_is_user_activity' ) || ! bp_is_user_activity() ) {
				return;
			}
		}

		// Determine which filter to use.
		$filter_to_apply = '';

		// Check if user has existing preference.
		if ( isset( $_COOKIE['bp-activity-filter'] ) && '' !== $_COOKIE['bp-activity-filter'] ) {
			$filter_to_apply = sanitize_text_field( wp_unslash( $_COOKIE['bp-activity-filter'] ) );
		} else {
			// No preference, use admin default.
			$filter_to_apply = $this->get_default_filter();
		}

		// Only proceed if we have a filter to apply.
		if ( ! $filter_to_apply || '0' === $filter_to_apply || '-1' === $filter_to_apply ) {
			return;
		}

		?>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			// Wait a moment for BuddyPress to initialize.
			setTimeout(function() {
				var dropdown = document.getElementById('activity-filter-by');
				if (!dropdown) {
					return;
				}

				var filterValue = '<?php echo esc_js( $filter_to_apply ); ?>';

				/*
				 * The member's own filter choice wins over the admin default.
				 *
				 * BuddyPress Nouveau remembers it in sessionStorage under "bp-activity"
				 * and re-applies it to the stream over AJAX. If we overwrote the dropdown
				 * with the admin default here, the control would claim one filter while
				 * the stream below it showed another. Mirror what BuddyPress actually
				 * applied instead.
				 */
				try {
					var bpState = JSON.parse(window.sessionStorage.getItem('bp-activity') || 'null');
					if (bpState && bpState.filter) {
						filterValue = bpState.filter;
					}
				} catch (e) {}

				// BuddyPress renders some options under a combined key, e.g. friendships
				// are listed as "friendship_accepted,friendship_created". Assigning the
				// single type to dropdown.value would not match any option and would
				// leave the dropdown blank, so match on the combined parts as well.
				var selected = Array.prototype.filter.call(dropdown.options, function(option) {
					return option.value === filterValue || option.value.split(',').indexOf(filterValue) !== -1;
				})[0];

				if (!selected) {
					return;
				}

				// Only reflect the value in the dropdown. Do NOT write the
				// bp-activity-filter cookie here.
				//
				// That cookie is the *member's own* choice, and it outranks the admin
				// default. Writing the default into it turned the admin's setting into a
				// permanent per-visitor preference: once a visitor had loaded the page,
				// their cookie pinned the old value and the site owner could never change
				// the default for them again. BuddyPress sets this cookie itself when the
				// member actually picks a filter, which is the only time it should be set.
				dropdown.value = selected.value;
			}, 50);
		});
		</script>
		<?php
	}

	/**
	 * Remove hidden activity types from the filter dropdown options.
	 *
	 * Runs on the options array before the dropdown HTML is built, so both the
	 * Legacy template pack (which renders the HTML) and Nouveau (which re-reads
	 * the raw array) see the same reduced set.
	 *
	 * @since 4.0.0
	 * @param array  $filters Activity filter options, keyed by option value.
	 * @param string $context The current context.
	 * @return array Filter options without the hidden activity types.
	 */
	public function remove_hidden_from_dropdown( $filters, $context = '' ) {
		$hidden_activities = $this->get_hidden_activities();

		if ( empty( $hidden_activities ) || ! is_array( $filters ) ) {
			return $filters;
		}

		// BuddyPress records one "friendship_created" activity for both friendship
		// hooks and collapses the pair into a single "friendship_accepted,friendship_created"
		// option, so hiding one side must account for the other.
		if ( in_array( 'friendship_created', $hidden_activities, true ) ) {
			$hidden_activities[] = 'friendship_accepted';
		}

		foreach ( array_keys( $filters ) as $option_value ) {
			$types = array_map( 'trim', explode( ',', (string) $option_value ) );

			// Drop the option only when every type it would show is hidden.
			if ( ! array_diff( $types, $hidden_activities ) ) {
				unset( $filters[ $option_value ] );
			}
		}

		return $filters;
	}

	/**
	 * Prevent hidden activities from being saved (at the source).
	 *
	 * @since 4.0.0
	 * @param BP_Activity_Activity $activity The activity object.
	 */
	public function maybe_prevent_activity_save( $activity ) {
		if ( ! isset( $activity->type ) ) {
			return;
		}

		$hidden_activities = $this->get_hidden_activities();

		// If this activity type is hidden, prevent it from being saved.
		if ( ! empty( $hidden_activities ) && in_array( $activity->type, $hidden_activities, true ) ) {
			// Store original type for error message.
			$original_type = $activity->type;

			// Multiple strategies to prevent save.
			// 1. Set type to empty string (BuddyPress checks for empty type).
			$activity->type = '';

			// 2. Also clear component to ensure save fails.
			$activity->component = '';

			// 3. Add an error if the activity object supports it.
			if ( isset( $activity->errors ) && is_wp_error( $activity->errors ) ) {
				$activity->errors->add(
					'bp_activity_type_disabled',
					/* translators: %s: The activity type that has been disabled. */
					sprintf( __( 'Activity type "%s" has been disabled by administrator.', 'bp-activity-filter' ), $original_type )
				);
			}
		}
	}

	/**
	 * Get default filter based on current context
	 *
	 * @since 4.0.0
	 * @return string Default filter value
	 */
	private function get_default_filter() {
		$context = $this->get_filter_context();

		if ( 'profile' === $context ) {
			$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );
		} else {
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
		return apply_filters( 'bp_activity_filter_default', $default_filter, $context );
	}

	/**
	 * Get current filter context
	 *
	 * @since 4.0.0
	 * @return string Context (profile, sitewide)
	 */
	private function get_filter_context() {
		if ( function_exists( 'bp_is_user_activity' ) && bp_is_user_activity() && 'just-me' === bp_current_action() ) {
			return 'profile';
		}
		return 'sitewide';
	}

	/**
	 * Get hidden activities list
	 *
	 * @since 4.0.0
	 * @return array List of hidden activity types
	 */
	private function get_hidden_activities() {
		// Get the option directly and handle serialization.
		$hidden_activities = get_option( 'bp_activity_filter_hidden', array() );

		// Ensure we have an array (handle serialized data).
		if ( ! is_array( $hidden_activities ) ) {
			$hidden_activities = maybe_unserialize( $hidden_activities );
			if ( ! is_array( $hidden_activities ) ) {
				$hidden_activities = array();
			}
		}

		// Core activities that should never be hidden.
		$core_protected_activities = array(
			'activity_update',
			'activity_comment',
		);

		// Remove any core activities from hidden list (safety protection).
		$hidden_activities = array_diff( $hidden_activities, $core_protected_activities );

		return $hidden_activities;
	}

	/**
	 * Prevent cloning
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing
	 */
	public function __wakeup() {}
}