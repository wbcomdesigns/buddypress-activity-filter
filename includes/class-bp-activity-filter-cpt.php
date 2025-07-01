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
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		add_action( 'transition_post_status', array( $this, 'handle_post_transition' ), 10, 3 );
	}

	/**
	 * Handle post status transition.
	 *
	 * @since 4.0.0
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function handle_post_transition( $new_status, $old_status, $post ) {
		// Only handle when publishing new posts.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Check if BuddyPress activity functions are available.
		if ( ! function_exists( 'bp_activity_add' ) ) {
			return;
		}

		$cpt_settings = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_cpt_settings', array() );
		$post_type    = get_post_type( $post );

		// Check if this post type is enabled for activity generation.
		if ( ! $this->is_post_type_enabled( $post_type, $cpt_settings ) ) {
			return;
		}

		$this->create_activity_for_post( $post, $cpt_settings[ $post_type ] );
	}

	/**
	 * Check if post type is enabled for activity generation.
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
	 * Create activity for post.
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

		// Create the activity.
		$activity_id = bp_activity_add( $activity_args );

		if ( $activity_id ) {
			// Add meta to identify this as a CPT activity
			bp_activity_update_meta( $activity_id, 'bp_activity_filter_cpt', $post->post_type );
			bp_activity_update_meta( $activity_id, 'bp_activity_filter_post_id', $post->ID );
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
}