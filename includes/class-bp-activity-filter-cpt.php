<?php
/**
 * Custom Post Type support.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Post Type support class.
 *
 * @since 4.0.0
 */
class BP_Activity_Filter_CPT {

	/**
	 * Class instance.
	 *
	 * @since 4.0.0
	 * @var BP_Activity_Filter_CPT
	 */
	private static $instance = null;

	/**
	 * Cache for excluded post types and their reasons.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	private $excluded_post_types = array();

	/**
	 * Debug mode flag.
	 *
	 * @since 4.0.0
	 * @var bool
	 */
	private $debug_mode = false;

	/**
	 * Get class instance.
	 *
	 * @since 4.0.0
	 * @return BP_Activity_Filter_CPT
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
		$this->debug_mode = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$this->setup_hooks();
		$this->cache_excluded_post_types();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		// Use high priority to run after other plugins
		add_action( 'transition_post_status', array( $this, 'handle_post_transition' ), 999, 3 );
		
		// Admin notices for conflicts
		add_action( 'admin_notices', array( $this, 'show_conflict_notices' ) );
	}

	/**
	 * Cache excluded post types on initialization.
	 *
	 * @since 4.0.0
	 */
	private function cache_excluded_post_types() {
		$this->excluded_post_types = BP_Activity_Filter_Helper::get_excluded_post_types_with_reasons();
		
		if ( $this->debug_mode && ! empty( $this->excluded_post_types ) ) {
			error_log( 'BP Activity Filter: Excluded CPTs: ' . wp_json_encode( $this->excluded_post_types ) );
		}
	}

	/**
	 * CONSERVATIVE: Handle post status transition with minimal conflict checking.
	 * Only block obvious conflicts, allow everything else through.
	 *
	 * @since 4.0.0
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function handle_post_transition( $new_status, $old_status, $post ) {
		// Only handle publishing of new posts
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Ensure BuddyPress is available
		if ( ! function_exists( 'bp_activity_add' ) ) {
			return;
		}

		$post_type = get_post_type( $post );

		// Skip built-in WordPress post types
		if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		// CONSERVATIVE: Only skip if in our CONFIRMED exclusion cache
		if ( isset( $this->excluded_post_types[ $post_type ] ) ) {
			if ( $this->debug_mode ) {
				error_log( sprintf( 
					'BP Activity Filter: Skipping %s (ID: %d) - Reason: %s', 
					$post_type, 
					$post->ID, 
					$this->excluded_post_types[ $post_type ]['reason'] 
				) );
			}
			return;
		}

		// Check plugin settings - if not enabled, skip
		$cpt_settings = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_cpt_settings', array() );

		if ( ! $this->is_post_type_enabled( $post_type, $cpt_settings ) ) {
			// Not enabled in settings - this is normal, just return
			return;
		}

		// MINIMAL runtime conflict check - only check for immediate duplicates
		if ( $this->activity_already_exists( $post ) ) {
			if ( $this->debug_mode ) {
				error_log( sprintf( 
					'BP Activity Filter: Activity already exists for %s (ID: %d)', 
					$post_type, 
					$post->ID 
				) );
			}
			return;
		}

		// Create activity - let it proceed unless there's a clear problem
		$this->create_activity_for_post( $post, $cpt_settings[ $post_type ] );
	}

	/**
	 * Check if post type is enabled in settings.
	 *
	 * @since 4.0.0
	 *
	 * @param string $post_type    Post type.
	 * @param array  $cpt_settings CPT settings.
	 * @return bool
	 */
	private function is_post_type_enabled( $post_type, $cpt_settings ) {
		return isset( $cpt_settings[ $post_type ]['enabled'] ) && $cpt_settings[ $post_type ]['enabled'];
	}

	/**
	 * SIMPLIFIED: Check if activity already exists for this post.
	 * Focus on preventing immediate duplicates only.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	private function activity_already_exists( $post ) {
		if ( ! function_exists( 'bp_activity_get' ) ) {
			return false;
		}

		// Check for our own plugin's activities first
		$our_activities = bp_activity_get( array(
			'meta_query' => array(
				array(
					'key' => 'bp_activity_filter_post_id',
					'value' => $post->ID,
					'compare' => '='
				)
			),
			'per_page' => 1,
		) );

		if ( ! empty( $our_activities['activities'] ) ) {
			return true;
		}

		// Check if post already has our activity meta
		$our_activity_id = get_post_meta( $post->ID, '_bp_activity_filter_activity_id', true );
		if ( ! empty( $our_activity_id ) ) {
			return true;
		}

		// Simple check for any recent activity with this post ID
		$recent_activities = bp_activity_get( array(
			'filter' => array(
				'item_id' => $post->ID,
			),
			'per_page' => 1,
		) );

		if ( ! empty( $recent_activities['activities'] ) ) {
			// Found activity with this post ID - might be from another plugin
			// Check if it's very recent (last 60 seconds) - likely from same post publish
			foreach ( $recent_activities['activities'] as $activity ) {
				$activity_time = strtotime( $activity->date_recorded );
				$current_time = time();
				
				if ( ( $current_time - $activity_time ) < 60 ) {
					// Very recent activity found - likely duplicate
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Create activity for post with minimal conflict prevention.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $post     Post object.
	 * @param array   $settings Post type settings.
	 */
	private function create_activity_for_post( $post, $settings ) {
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj ) {
			return;
		}

		// One final duplicate check before creation
		if ( $this->activity_already_exists( $post ) ) {
			return;
		}

		$label = $this->get_activity_label( $settings, $post_type_obj );
		$action = $this->build_activity_action( $post, $label );
		$content = $this->get_activity_content( $post );

		// Check global settings for sitewide visibility
		$global_settings = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_cpt_settings', array() );
		$hide_sitewide = isset( $global_settings['_global']['hide_sitewide'] ) ? $global_settings['_global']['hide_sitewide'] : false;

		$activity_args = array(
			'action'            => $action,
			'content'           => $content,
			'component'         => 'activity',
			'type'              => 'new_blog_post',
			'primary_link'      => get_permalink( $post->ID ),
			'user_id'           => $post->post_author,
			'item_id'           => $post->ID,
			'secondary_item_id' => $post->ID,
			'recorded_time'     => bp_core_current_time(),
			'hide_sitewide'     => $hide_sitewide,
			'is_spam'           => false,
		);

		/**
		 * Filter the activity arguments before creating the activity.
		 *
		 * @since 4.0.0
		 *
		 * @param array   $activity_args Activity arguments.
		 * @param WP_Post $post          Post object.
		 * @param array   $settings      Post type settings.
		 */
		$activity_args = apply_filters( 'bp_activity_filter_cpt_activity_args', $activity_args, $post, $settings );

		// Create the activity
		$activity_id = bp_activity_add( $activity_args );

		if ( $activity_id ) {
			// Add comprehensive meta for tracking
			bp_activity_update_meta( $activity_id, 'bp_activity_filter_cpt', $post->post_type );
			bp_activity_update_meta( $activity_id, 'bp_activity_filter_post_id', $post->ID );
			bp_activity_update_meta( $activity_id, 'bp_activity_filter_created_time', time() );
			bp_activity_update_meta( $activity_id, 'bp_activity_filter_version', BP_Activity_Filter_Helper::get_plugin_version() );

			// Also add post meta for reverse lookup and duplicate prevention
			update_post_meta( $post->ID, '_bp_activity_filter_activity_id', $activity_id );
			update_post_meta( $post->ID, '_bp_activity_filter_recorded', time() );

			if ( $this->debug_mode ) {
				error_log( sprintf( 
					'BP Activity Filter: Created activity %d for %s (ID: %d)', 
					$activity_id, 
					$post->post_type, 
					$post->ID 
				) );
			}
		}

		/**
		 * Fires after a CPT activity is created.
		 *
		 * @since 4.0.0
		 *
		 * @param int     $activity_id Activity ID.
		 * @param WP_Post $post        Post object.
		 * @param array   $settings    Post type settings.
		 */
		do_action( 'bp_activity_filter_cpt_activity_created', $activity_id, $post, $settings );
	}

	/**
	 * SIMPLIFIED: Show admin notices only for actual exclusions.
	 *
	 * @since 4.0.0
	 */
	public function show_conflict_notices() {
		$screen = get_current_screen();
		
		// Only show on our settings page
		if ( ! $screen || strpos( $screen->id, 'wbcom-activity-filter' ) === false ) {
			return;
		}

		// Only show notice if there are actual exclusions
		if ( ! empty( $this->excluded_post_types ) ) {
			$this->render_simplified_notice();
		}
	}

	/**
	 * Render simplified exclusion notice.
	 *
	 * @since 4.0.0
	 */
	private function render_simplified_notice() {
		$reason_messages = array(
			'capability_restriction' => __( 'Has create_posts capability set to "do_not_allow" (managed by another plugin)', 'bp-activity-filter' ),
			'known_plugin_conflict' => __( 'Managed by a known plugin (e.g., BP Member Reviews, bbPress)', 'bp-activity-filter' ),
			'not_public' => __( 'Not a public post type', 'bp-activity-filter' ),
			'no_admin_ui' => __( 'No admin interface', 'bp-activity-filter' ),
			'no_title_support' => __( 'Does not support titles', 'bp-activity-filter' ),
		);

		?>
		<div class="notice notice-info">
			<p><strong><?php _e( 'Custom Post Type Exclusions', 'bp-activity-filter' ); ?></strong></p>
			<p><?php _e( 'The following post types are excluded from activity generation to prevent conflicts:', 'bp-activity-filter' ); ?></p>
			
			<ul style="margin-left: 20px;">
				<?php foreach ( $this->excluded_post_types as $post_type => $data ) : ?>
					<li>
						<strong><?php echo esc_html( $data['label'] ); ?></strong> 
						(<?php echo esc_html( $post_type ); ?>) - 
						<?php echo esc_html( $reason_messages[ $data['reason'] ] ?? 'Excluded for compatibility' ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			
			<p style="margin-top: 15px;">
				<em><?php _e( 'This is normal behavior and helps prevent duplicate activities. All other eligible custom post types are available in the settings below.', 'bp-activity-filter' ); ?></em>
			</p>
		</div>
		<?php
	}

	/**
	 * Get activity label for post type.
	 *
	 * @since 4.0.0
	 *
	 * @param array           $settings      Post type settings.
	 * @param WP_Post_Type    $post_type_obj Post type object.
	 * @return string
	 */
	private function get_activity_label( $settings, $post_type_obj ) {
		$label = '';

		if ( ! empty( $settings['label'] ) ) {
			$label = $settings['label'];
		} else {
			$label = strtolower( $post_type_obj->labels->singular_name );
		}

		/**
		 * Filter the activity label for CPT.
		 *
		 * @since 4.0.0
		 *
		 * @param string          $label         Activity label.
		 * @param array           $settings      Post type settings.
		 * @param WP_Post_Type    $post_type_obj Post type object.
		 */
		return apply_filters( 'bp_activity_filter_cpt_activity_label', $label, $settings, $post_type_obj );
	}

	/**
	 * Build activity action string.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $post  Post object.
	 * @param string  $label Activity label.
	 * @return string
	 */
	private function build_activity_action( $post, $label ) {
		$author_link = $this->get_author_link( $post->post_author );
		$post_link   = $this->get_post_link( $post );

		$action = sprintf(
			/* translators: 1: Author link, 2: Post type label, 3: Post link */
			__( '%1$s published a new %2$s: %3$s', 'bp-activity-filter' ),
			$author_link,
			$label,
			$post_link
		);

		/**
		 * Filter the activity action string for CPT.
		 *
		 * @since 4.0.0
		 *
		 * @param string  $action Activity action.
		 * @param WP_Post $post   Post object.
		 * @param string  $label  Activity label.
		 */
		return apply_filters( 'bp_activity_filter_cpt_activity_action', $action, $post, $label );
	}

	/**
	 * Get activity content.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private function get_activity_content( $post ) {
		$content = '';

		// Try to get excerpt first.
		if ( has_excerpt( $post ) ) {
			$content = get_the_excerpt( $post );
		} elseif ( ! empty( $post->post_content ) ) {
			// Fallback to trimmed content.
			$content = wp_trim_words( $post->post_content, 55 );
		}

		/**
		 * Filter the activity content for CPT.
		 *
		 * @since 4.0.0
		 *
		 * @param string  $content Activity content.
		 * @param WP_Post $post    Post object.
		 */
		return apply_filters( 'bp_activity_filter_cpt_activity_content', $content, $post );
	}

	/**
	 * Get author link HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param int $author_id Author user ID.
	 * @return string
	 */
	private function get_author_link( $author_id ) {
		if ( ! function_exists( 'bp_core_get_user_domain' ) ) {
			return get_the_author_meta( 'display_name', $author_id );
		}

		$author_name = get_the_author_meta( 'display_name', $author_id );
		$author_url  = bp_core_get_user_domain( $author_id );

		if ( empty( $author_url ) ) {
			return $author_name;
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $author_url ),
			esc_html( $author_name )
		);
	}

	/**
	 * Get post link HTML.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private function get_post_link( $post ) {
		$post_title = get_the_title( $post );
		$post_url   = get_permalink( $post->ID );

		if ( empty( $post_url ) ) {
			return $post_title;
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $post_url ),
			esc_html( $post_title )
		);
	}

	/**
	 * Get excluded post types for external access.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function get_excluded_post_types() {
		return $this->excluded_post_types;
	}
}