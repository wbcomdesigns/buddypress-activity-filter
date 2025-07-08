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
	 * Setup frontend hooks - MINIMAL APPROACH
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		// ONLY set default filter on initial page load - don't interfere with AJAX
		add_action( 'wp_head', array( $this, 'set_initial_default_filter' ), 5 );
		
		// Remove hidden activities from dropdown (but don't interfere with filtering)
		add_filter( 'bp_get_activity_show_filters', array( $this, 'remove_hidden_from_dropdown' ), 10, 3 );
		
		// Prevent hidden activities from being created (at source)
		add_action( 'bp_activity_before_save', array( $this, 'maybe_prevent_activity_save' ), 5 );
	}

	/**
	 * Set initial default filter ONCE on page load via JavaScript
	 * This is the key fix - let BuddyPress handle everything else
	 *
	 * @since 4.0.0
	 */
	public function set_initial_default_filter() {
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
			$activity->type = false; // This prevents the activity from being saved
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
		$hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );
		
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
	 * Prevent cloning
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing
	 */
	public function __wakeup() {}
}