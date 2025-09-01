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
		// Server-side default filter - runs BEFORE activities are queried
		add_filter( 'bp_after_has_activities_parse_args', array( $this, 'apply_default_filter_server_side' ), 10, 1 );
		
		// Also filter AJAX requests
		add_filter( 'bp_ajax_querystring', array( $this, 'apply_default_filter_ajax' ), 10, 2 );
		
		// Set initial cookie and dropdown state (minimal JS just for UI sync)
		add_action( 'wp_footer', array( $this, 'sync_dropdown_with_default' ), 999 );
		
		// Remove hidden activities from dropdown (but don't interfere with filtering)
		add_filter( 'bp_get_activity_show_filters', array( $this, 'remove_hidden_from_dropdown' ), 10, 3 );
		
		// Prevent hidden activities from being created (at source)
		// Use very early priority to catch before other plugins
		add_action( 'bp_activity_before_save', array( $this, 'maybe_prevent_activity_save' ), 1 );
		
		// Prevent friendship activities specifically by removing the action hooks
		// Use bp_init which runs after BuddyPress has loaded all its components
		add_action( 'bp_init', array( $this, 'remove_hidden_activity_hooks' ), 999 );
	}

	/**
	 * Set initial default filter ONCE on page load via JavaScript
	 * This is the key fix - let BuddyPress handle everything else
	 *
	 * @since 4.0.0
	 * @deprecated 4.1.0 Replaced with server-side filtering
	 */
	public function set_initial_default_filter_legacy() {
		// Only on activity pages
		if ( ! $this->is_activity_page() ) {
			return;
		}

		// Don't set if user already has a preference
		if ( isset( $_COOKIE['bp-activity-filter'] ) ) {
			return;
		}

		// Get default filter based on context
		$default_filter = $this->get_default_filter();
		
		// Only set if we have a meaningful default
		if ( ! $default_filter || '0' === $default_filter || '-1' === $default_filter ) {
			return;
		}

		?>
		<script type="text/javascript">
		(function() {
			// Wait for DOM to be ready
			document.addEventListener('DOMContentLoaded', function() {
				// ONLY set the dropdown value and cookie - let BuddyPress handle the rest
				var dropdown = document.getElementById('activity-filter-by');
				if (dropdown && !getCookie('bp-activity-filter')) {
					// Set dropdown value
					dropdown.value = '<?php echo esc_js( $default_filter ); ?>';
					
					// Set cookie so BuddyPress knows the preference
					setCookie('bp-activity-filter', '<?php echo esc_js( $default_filter ); ?>', 30);
					
					// Trigger change event to let BuddyPress handle the filtering
					if (dropdown.dispatchEvent) {
						var event = new Event('change', { bubbles: true });
						dropdown.dispatchEvent(event);
					} else {
						// IE fallback
						var event = document.createEvent('Event');
						event.initEvent('change', true, true);
						dropdown.dispatchEvent(event);
					}
				}
			});
			
			// Helper functions
			function getCookie(name) {
				var nameEQ = name + "=";
				var ca = document.cookie.split(';');
				for(var i = 0; i < ca.length; i++) {
					var c = ca[i];
					while (c.charAt(0) == ' ') c = c.substring(1, c.length);
					if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
				}
				return null;
			}
			
			function setCookie(name, value, days) {
				var expires = "";
				if (days) {
					var date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					expires = "; expires=" + date.toUTCString();
				}
				document.cookie = name + "=" + value + expires + "; path=/; SameSite=Lax";
			}
		})();
		</script>
		<?php
	}

	/**
	 * Apply default filter server-side
	 *
	 * @since 4.0.0
	 * @param array $args Activity query arguments
	 * @return array Modified arguments
	 */
	public function apply_default_filter_server_side( $args ) {
		// Skip if already filtered or if specific type is requested
		if ( ! empty( $args['filter_query'] ) || ! empty( $args['action'] ) || ! empty( $args['type'] ) ) {
			return $args;
		}

		// Skip if this is an AJAX request with existing filter
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST['filter'] ) ) {
			return $args;
		}

		// Check user preference first (from cookie)
		if ( isset( $_COOKIE['bp-activity-filter'] ) && $_COOKIE['bp-activity-filter'] !== '0' && $_COOKIE['bp-activity-filter'] !== '-1' ) {
			$user_filter = sanitize_text_field( $_COOKIE['bp-activity-filter'] );
			// BuddyPress uses 'type' for filtering, not 'action'
			$args['type'] = $user_filter;
			// Also handle comma-separated values
			if ( strpos( $user_filter, ',' ) !== false ) {
				$args['type'] = explode( ',', $user_filter );
			}
			return $args;
		}

		// No user preference, apply admin default
		$default_filter = $this->get_default_filter();
		if ( $default_filter && $default_filter !== '0' && $default_filter !== '-1' ) {
			// BuddyPress uses 'type' for filtering, not 'action'
			$args['type'] = $default_filter;
			// Also handle comma-separated values
			if ( strpos( $default_filter, ',' ) !== false ) {
				$args['type'] = explode( ',', $default_filter );
			}
		}

		return $args;
	}

	/**
	 * Apply filter to AJAX requests
	 *
	 * @since 4.0.0
	 * @param string $query_string The query string
	 * @param string $object The object type
	 * @return string Modified query string
	 */
	public function apply_default_filter_ajax( $query_string, $object ) {
		if ( 'activity' !== $object ) {
			return $query_string;
		}

		// Parse existing query string
		wp_parse_str( $query_string, $args );

		// If action/type already set, don't override
		if ( ! empty( $args['action'] ) || ! empty( $args['type'] ) ) {
			return $query_string;
		}

		// Check user preference from cookie
		if ( isset( $_COOKIE['bp-activity-filter'] ) && $_COOKIE['bp-activity-filter'] !== '0' && $_COOKIE['bp-activity-filter'] !== '-1' ) {
			$args['type'] = sanitize_text_field( $_COOKIE['bp-activity-filter'] );
			return http_build_query( $args );
		}

		// No user preference, use admin default
		$default_filter = $this->get_default_filter();
		if ( $default_filter && $default_filter !== '0' && $default_filter !== '-1' ) {
			$args['type'] = $default_filter;
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
		// Only on activity pages
		if ( ! function_exists( 'bp_is_activity_directory' ) || ! bp_is_activity_directory() ) {
			if ( ! function_exists( 'bp_is_user_activity' ) || ! bp_is_user_activity() ) {
				return;
			}
		}

		// Determine which filter to use
		$filter_to_apply = '';
		
		// Check if user has existing preference
		if ( isset( $_COOKIE['bp-activity-filter'] ) && $_COOKIE['bp-activity-filter'] !== '' ) {
			$filter_to_apply = sanitize_text_field( $_COOKIE['bp-activity-filter'] );
		} else {
			// No preference, use admin default
			$filter_to_apply = $this->get_default_filter();
		}
		
		// Only proceed if we have a filter to apply
		if ( ! $filter_to_apply || '0' === $filter_to_apply || '-1' === $filter_to_apply ) {
			return;
		}

		?>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			// Wait a moment for BuddyPress to initialize
			setTimeout(function() {
				// Sync the dropdown to match the current filter (from cookie or default)
				var dropdown = document.getElementById('activity-filter-by');
				if (dropdown) {
					var filterValue = '<?php echo esc_js( $filter_to_apply ); ?>';
					
					// Set dropdown to match
					dropdown.value = filterValue;
					
					// Ensure cookie is set (in case it wasn't)
					if (!document.cookie.match(/bp-activity-filter=/)) {
						document.cookie = 'bp-activity-filter=' + filterValue + '; path=/; max-age=' + (30*24*60*60);
					}
					
					// If dropdown value doesn't stick, force it again
					if (dropdown.value !== filterValue) {
						setTimeout(function() {
							dropdown.value = filterValue;
						}, 100);
					}
				}
			}, 50);
		});
		</script>
		<?php
	}

	/**
	 * Remove hidden activities from dropdown options (but don't interfere with filtering logic)
	 *
	 * @since 4.0.0
	 */
	public function remove_hidden_from_dropdown( $output, $filters, $context ) {
		$hidden_activities = $this->get_hidden_activities();
		
		if ( empty( $hidden_activities ) ) {
			return $output;
		}

		// Handle Nouveau theme (array format)
		if ( is_array( $output ) && isset( $output['filters'] ) ) {
			foreach ( $hidden_activities as $hidden_key ) {
				unset( $output['filters'][ $hidden_key ] );
			}
			return $output;
		}

		// Handle legacy theme (HTML string format)
		if ( is_string( $output ) && ! empty( $output ) ) {
			foreach ( $hidden_activities as $hidden_key ) {
				$pattern = '/<option[^>]*value=["\']' . preg_quote( $hidden_key, '/' ) . '["\'][^>]*>.*?<\/option>/i';
				$output = preg_replace( $pattern, '', $output );
			}
		}

		return $output;
	}

	/**
	 * Prevent hidden activities from being saved (at the source)
	 *
	 * @since 4.0.0
	 */
	public function maybe_prevent_activity_save( $activity ) {
		if ( ! isset( $activity->type ) ) {
			return;
		}

		$hidden_activities = $this->get_hidden_activities();

		// If this activity type is hidden, prevent it from being saved
		if ( ! empty( $hidden_activities ) && in_array( $activity->type, $hidden_activities, true ) ) {
			// Store original type for error message
			$original_type = $activity->type;
			
			// Multiple strategies to prevent save:
			// 1. Set type to empty string (BuddyPress checks for empty type)
			$activity->type = '';
			
			// 2. Also clear component to ensure save fails
			$activity->component = '';
			
			// 3. Add an error if the activity object supports it
			if ( isset( $activity->errors ) && is_wp_error( $activity->errors ) ) {
				$activity->errors->add( 
					'bp_activity_type_disabled', 
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
		// Get the option directly and handle serialization
		$hidden_activities = get_option( 'bp_activity_filter_hidden', array() );
		
		// Ensure we have an array (handle serialized data)
		if ( ! is_array( $hidden_activities ) ) {
			$hidden_activities = maybe_unserialize( $hidden_activities );
			if ( ! is_array( $hidden_activities ) ) {
				$hidden_activities = array();
			}
		}
		
		// Core activities that should never be hidden
		$core_protected_activities = array(
			'activity_update',
			'activity_comment'
		);
		
		// Remove any core activities from hidden list (safety protection)
		$hidden_activities = array_diff( $hidden_activities, $core_protected_activities );
		
		return $hidden_activities;
	}

	/**
	 * Check if current page is an activity page
	 *
	 * @since 4.0.0
	 * @return bool True if on activity page
	 */
	private function is_activity_page() {
		if ( ! function_exists( 'bp_is_activity_component' ) ) {
			return false;
		}

		return bp_is_activity_component() || bp_is_user_activity();
	}

	/**
	 * Remove hooks for hidden activity types to prevent them from being created
	 *
	 * @since 4.0.0
	 */
	public function remove_hidden_activity_hooks() {
		$hidden_activities = $this->get_hidden_activities();
		
		if ( empty( $hidden_activities ) ) {
			return;
		}
		
		// Map activity types to their creation hooks
		$activity_hooks = array(
			'friendship_created' => array(
				'hook' => 'friends_friendship_requested',
				'function' => 'bp_friends_friendship_requested_activity',
				'priority' => 10
			),
			'friendship_accepted' => array(
				'hook' => 'friends_friendship_accepted', 
				'function' => 'bp_friends_friendship_accepted_activity',
				'priority' => 10
			),
			'new_member' => array(
				'hook' => 'bp_core_activated_user',
				'function' => 'bp_activity_new_member_activity',
				'priority' => 10
			),
			'updated_profile' => array(
				'hook' => 'xprofile_updated_profile',
				'function' => 'bp_xprofile_updated_profile_activity',
				'priority' => 10
			)
		);
		
		// Remove hooks for hidden activity types
		foreach ( $hidden_activities as $activity_type ) {
			if ( isset( $activity_hooks[ $activity_type ] ) ) {
				$hook_info = $activity_hooks[ $activity_type ];
				remove_action( 
					$hook_info['hook'], 
					$hook_info['function'], 
					$hook_info['priority'] 
				);
			}
		}
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