<?php
/**
 * Defining class for Filtering activity stream.
 *
 * @package BuddyPress_Activity_Filter
 */

if ( ! class_exists( 'WbCom_BP_Activity_Filter_Activity_Stream' ) ) {
	/**
	 * Defining class for Filtering activity stream.
	 *
	 * @package BuddyPress_Activity_Filter
	 */
	class WbCom_BP_Activity_Filter_Activity_Stream {
		/**
		 * Constructor
		 */
		public function __construct() {
			/**
			 * Filtering activity stream
			 */
			add_filter( 'bp_ajax_querystring', array( $this, 'filtering_activity_default' ), 999, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'bpaf_enqueue_scripts' ) );
			add_action( 'bp_activity_before_save', array( $this, 'bpaf_activity_do_not_save' ), 5, 1 );
			add_action( 'friends_friendship_accepted', array( $this, 'bpaf_bp_friends_friendship_accepted_activity' ), 5, 4 );

			add_action( 'bp_template_redirect', array( $this, 'bpaf_bp_set_default_activity_filter' ) );
		}

		/**
		 * Registers the script if $src provided (does NOT overwrite), and enqueues it.
		 *
		 * @return void
		 */
		public function bpaf_enqueue_scripts() {
			global $bp;

			// Determine the default activity stream based on whether it's user or sitewide activity
			if ( bp_is_user_activity() && bp_current_action() === 'just-me' ) {
				// Only set the filter for the "just-me" tab in the profile activity
				$default_activity_stream = bp_get_option( 'bp-default-profile-filter-name' );
			} elseif ( ! bp_is_user_activity() ) {
				// Set the filter for sitewide activity
				$default_activity_stream = bp_get_option( 'bp-default-filter-name' );
			} else {
				// For other tabs (like friends, groups, etc.), no default filter
				$default_activity_stream = 0;
			}

			// Enqueue necessary scripts
			wp_enqueue_script( 'wp-embed' );
			wp_enqueue_script( 'bp-activity-filter-public', plugin_dir_url( __FILE__ ) . 'js/buddypress-activity-filter-public.js', array( 'jquery' ), time(), false );

			// Pass the default filter and current action to JavaScript
			wp_localize_script(
				'bp-activity-filter-public',
				'bpaf_js_object',
				array(
					'default_filter' => $default_activity_stream, // Default filter from backend (just-me or sitewide)
					'current_action' => bp_current_action(),      // Current action (just-me, friends, groups, etc.)
				)
			);
		}

		/**
		 * Modifying activity loop for default activity.
		 *
		 * @param  string $query  Current query string.
		 * @param  string $object Current template component.
		 */
		public function filtering_activity_default( $query, $object ) {
			global $bp;

			// Check if it's a single activity view, and return the original query if true
			if ( bp_is_single_activity() ) {
				return $query;
			}

			// Ensure this is for the activity component, otherwise return the original query
			if ( 'activity' !== $object ) {
				return $query;
			}

			// Parse the query
			$query = wp_parse_args( $query, array() );

			// Skip if this is for mentions, friends, favorites, or groups tabs
			if ( bp_is_activity_directory() && isset( $query['scope'] ) && in_array( $query['scope'], array( 'mentions', 'friends', 'favorites', 'groups' ) ) ) {
				return build_query( $query );
			} elseif ( bp_is_user_activity() && in_array( bp_current_action(), array( 'mentions', 'favorites', 'friends', 'groups' ) ) ) {
				return build_query( $query );
			}

			// Check if hashtags plugin is active and return if true to avoid conflicts
			$active_plugins = get_option( 'active_plugins' );
			if ( in_array( 'buddypress-hashtag/buddypress-hashtags.php', $active_plugins ) ) {
				return $query;
			}

			// Retrieve cookie or parse post data for default filter logic
			$bpaf_filter_nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( wp_verify_nonce( $bpaf_filter_nonce, '_wpnonce_activity_filter' ) ) {
				return true;
			}

			// Parse cookie or POST data to determine default filters
			if ( ! empty( $_POST['cookie'] ) ) {
				$_BP_COOKIE = wp_parse_args( str_replace( '; ', '&', urldecode( sanitize_text_field( wp_unslash( $_POST['cookie'] ) ) ) ) );
			} else {
				$_BP_COOKIE = &$_COOKIE;
			}

			// Handle pagination dynamically (get page number from POST or query)
			if ( ! empty( $query ) && is_array( $query ) ) {
				if ( isset( $_POST['page'] ) && is_numeric( $_POST['page'] ) ) {
					$page = intval( $_POST['page'] );
				} elseif ( isset( $query['page'] ) && is_numeric( $query['page'] ) ) {
					$page = intval( $query['page'] );
				} else {
					$page = 1; // Default to page 1 if no page parameter is found
				}

				// Add the 'page' parameter to the query string
				$query['page'] = $page;
			} else {
				$page = 1;
				$query = array( 'page' => $page ); // Default page 1 when query is empty
			}

			// Initialize bp_query if not already set
			$bp_query = isset( $bp_query ) ? $bp_query : array();
			$size     = count( $bp_query );

			// Set default activity stream based on group, user, or sitewide activity
			if ( bp_is_group_activity() ) {
				$default_activity_stream = -1;
			} elseif ( bp_is_user_activity() && bp_current_action() === 'just-me' ) {
				$default_activity_stream = bp_get_option( 'bp-default-profile-filter-name' );
			} else {
				$default_activity_stream = bp_get_option( 'bp-default-filter-name' );
				$page_actions            = bp_activity_get_actions_for_context( 'activity' );
			}

			// Handle hidden activity streams (custom settings)
			$hidden_activity_stream = bp_get_option( 'bp-hidden-filters-name' );
			$activity_hidden        = ! empty( $hidden_activity_stream ) ? $hidden_activity_stream : array();

			// Apply default filter if cookie is set or return custom action query
			if ( isset( $_BP_COOKIE['bpaf-default-filter'] ) && $default_activity_stream != -1 && 1 == $_BP_COOKIE['bpaf-default-filter'] ) {
				$count  = 0;
				$action = '';

				// Fetch labels for custom activity filter
				$admin_setting_object = new WbCom_BP_Activity_Filter_Admin_Setting();
				$labels               = $admin_setting_object->bpaf_get_labels();

				// Build action query from custom activity filter labels
				foreach ( $labels as $l_key => $l_value ) {
					if ( ! empty( $l_value ) && ! in_array( $l_key, $activity_hidden ) ) {
						$action .= $count === 0 ? $l_key : ',' . $l_key;
						$count++;
					}
				}

				// Set the query based on default activity stream or custom filter
				if ( $default_activity_stream != -1 ) {
					$query = 'action=' . $default_activity_stream;
					if ( isset( $_POST['scope'] ) && $_POST['scope'] !== '' ) {
						$query .= '&scope=' . sanitize_text_field( wp_unslash( $_POST['scope'] ) );
					}
					if ( ! empty( $page ) ) {
						$query .= '&page=' . $page;
					}
				} else {
					$query = 'action=' . $action;
				}
			} elseif ( $default_activity_stream == -1 && isset( $_BP_COOKIE['bpaf-default-filter'] ) && 1 == $_BP_COOKIE['bpaf-default-filter'] || empty( $query ) || 1 == $query_size ) {
				$count  = 0;
				$action = '';

				// Fetch labels for this case
				$admin_setting_object = new WbCom_BP_Activity_Filter_Admin_Setting();
				$labels               = $admin_setting_object->bpaf_get_labels();

				// Build action query from custom activity filter labels
				foreach ( $labels as $l_key => $l_value ) {
					if ( ! empty( $l_value ) && ! in_array( $l_key, $activity_hidden ) ) {
						$action .= $count === 0 ? $l_key : ',' . $l_key;
						$count++;
					}
				}

				// Set action and pagination in query
				$query = 'action=' . $action;
				if ( isset( $_POST['scope'] ) && $_POST['scope'] !== '' ) {
					$query .= '&scope=' . sanitize_text_field( wp_unslash( $_POST['scope'] ) );
				}
				if ( ! empty( $page ) ) {
					$query .= '&page=' . $page;
				}
			}

			return $query;
		}



		/**
		 * Restrict to save activity.
		 *
		 * @param object $activity_object Activity Object.
		 */
		public function bpaf_activity_do_not_save( $activity_object ) {
			$hidden_activity_stream = bp_get_option( 'bp-hidden-filters-name' );
			if ( ! empty( $hidden_activity_stream ) && is_array( $hidden_activity_stream ) ) {
				if ( in_array( $activity_object->type, $hidden_activity_stream ) ) {
					$activity_object->type = false;
				}
			}
		}

		/**
		 * Restrict to create friendship activity.
		 *
		 * @param int    $friendship_id ID of the pending friendship object.
		 * @param int    $initiator_user_id ID of the friendship initiator.
		 * @param int    $friend_user_id ID of the user requested friendship with.
		 * @param object $friendship BuddyPress Friendship Object.
		 */
		public function bpaf_bp_friends_friendship_accepted_activity( $friendship_id, $initiator_user_id, $friend_user_id, $friendship = false ) {
			$hidden_activity_stream = bp_get_option( 'bp-hidden-filters-name' );
			if ( ! empty( $hidden_activity_stream ) && is_array( $hidden_activity_stream ) ) {
				if ( in_array( 'friendship_accepted,friendship_created', $hidden_activity_stream ) ) {
					remove_action( 'friends_friendship_accepted', 'bp_friends_friendship_accepted_activity', 10, 4 );
				}
			}
		}

		/**
		 * Fires inside the 'bp_template_redirect' function.
		 *
		 * @since BuddyPress 1.6.0
		 */
		public function bpaf_bp_set_default_activity_filter() {
			// If the filter is already set, do not do anything.
			if ( isset( $_COOKIE['bp-activity-filter'] ) ) {
				return;
			}

			// Skip filtering for specific tabs (mentions, favorites, friends, groups)
			if ( bp_is_single_activity() || bp_is_current_action( 'mentions' ) || bp_is_current_action( 'favorites' ) || bp_is_current_action( 'friends' ) || bp_is_current_action( 'groups' ) ) {
				return;
			}

			// Check if it's activity directory or "just-me" tab in the profile
			if ( ! bp_is_activity_directory() && ! bp_is_user_activity() ) {
				return;
			}

			// Apply filter only to "just-me" or sitewide activity
			if ( bp_is_user_activity() ) {
				$filter = bp_get_option( 'bp-default-profile-filter-name' );
			} elseif ( bp_is_activity_directory() ) {
				$filter = bp_get_option( 'bp-default-filter-name' );
			}

			// Set the filter cookie
			$expires = time() + 1800;
			setcookie( 'bp-activity-filter', $filter, $expires, '/' );
			$_COOKIE['bp-activity-filter'] = $filter;
		}



	}
}
if ( class_exists( 'WbCom_BP_Activity_Filter_Activity_Stream' ) ) {
	$filter_query_obj = new WbCom_BP_Activity_Filter_Activity_Stream();
}
