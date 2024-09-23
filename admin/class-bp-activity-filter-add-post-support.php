<?php
/**
 * Including CSS for admin setting.
 *
 * @package BuddyPress_Activity_Filter
 */

 if ( ! class_exists( 'WbCom_BP_Activity_Filter_Add_Post_Type_Support' ) ) {

    /**
     * Class for adding post type support to BuddyPress activity.
     *
     * @package BuddyPress_Activity_Filter
     */
    class WbCom_BP_Activity_Filter_Add_Post_Type_Support {

        /**
         * Constructor
         */
        public function __construct() {
            // Hook into post status transition to handle custom post type support.
            add_action( 'transition_post_status', array( $this, 'bpaf_customize_page_tracking_args' ), 999, 3 );
        }

        /**
         * Fire a callback only when my-custom-post-type posts are transitioned to 'publish'.
         *
         * @param string  $new_status New post status.
         * @param string  $old_status Old post status.
         * @param WP_Post $post       Post object.
         */
        public function bpaf_customize_page_tracking_args( $new_status, $old_status, $post ) {
            // Bail out if post is not being published.
            if ( 'publish' === $old_status || 'publish' !== $new_status ) {
                return;
            }

            $post_id      = $post->ID;
            $post_type    = get_post_type( $post_id );
            $filter_types = bp_get_option( 'bp-cpt-filters-settings' );

            // Bail out if post type is not set or is not being filtered.
            if ( ! isset( $post_type ) || empty( $filter_types ) || ! isset( $filter_types['bpaf_admin_settings'] ) ) {
                return;
            }

            $all_posts = $filter_types['bpaf_admin_settings'];

            if ( isset( $all_posts[ $post_type ] ) ) {
                $this->bpaf_handle_post_type( $post, $all_posts[ $post_type ] );
            }
        }

        /**
         * Handle the post type according to the specified filter type.
         *
         * @param WP_Post $post Post object.
         * @param array   $details Filter details for the post type.
         */
        protected function bpaf_handle_post_type( $post, $details ) {
            $post_type_object = get_post_type_object( $post->post_type );
            $filter_type      = $details['display_type'];
            
            // Ensure the post type label is in lowercase
            $post_type_label  = ! empty( $details['new_label'] ) 
                ? $details['new_label'] 
                : strtolower( $post_type_object->labels->singular_name );

            if ( 'groups' === $filter_type ) {
                $this->bpaf_handle_groups_filter( $post, $post_type_label );
            } elseif ( 'main_activity' === $filter_type || 'enable' === $filter_type ) {
                $this->bpaf_add_activity( $post, $post_type_label, 'activity' );
            }
        }


        /**
         * Handle group-related post types.
         *
         * @param WP_Post $post Post object.
         * @param string  $post_type_label The label for the post type.
         */
        protected function bpaf_handle_groups_filter( $post, $post_type_label ) {
            $group_ids = $this->bpaf_get_all_group_ids();

            if ( ! empty( $group_ids ) ) {
                foreach ( $group_ids as $group_id ) {
                    $group_permalink = $this->bpaf_get_group_permalink( $group_id );
                    $action = sprintf(
                        '%s added a new %s, %s in the group %s',
                        $this->bpaf_get_post_author_link( $post->post_author ),
                        $post_type_label,
                        $this->bpaf_get_post_title_link( $post->ID ),
                        $this->bpaf_get_group_link( $group_id )
                    );

                    $this->bpaf_add_activity( $post, $post_type_label, 'groups', $group_id, $action );
                }
            }
        }

        /**
         * Add an activity entry.
         *
         * @param WP_Post $post Post object.
         * @param string  $post_type_label The label for the post type.
         * @param string  $component The component where the activity is added.
         * @param int     $item_id The item ID related to the activity.
         * @param string  $action Custom action string.
         */
        protected function bpaf_add_activity( $post, $post_type_label, $component, $item_id = 0, $action = '' ) {
            if ( empty( $action ) ) {
                $action = sprintf(
                    '%s added a new %s, %s',
                    $this->bpaf_get_post_author_link( $post->post_author ),
                    $post_type_label,
                    $this->bpaf_get_post_title_link( $post->ID )
                );
            }

            $args = array(
                'action'            => $action,
                'content'           => $this->bpaf_get_post_link( $post->ID ),
                'component'         => $component,
                'type'              => 'new_blog_post',
                'primary_link'      => $this->bpaf_get_post_link( $post->ID ),
                'user_id'           => bp_loggedin_user_id(),
                'item_id'           => $item_id ?: $post->ID,
                'secondary_item_id' => false,
                'recorded_time'     => bp_core_current_time(),
                'hide_sitewide'     => false,
                'is_spam'           => false,
            );

            bp_activity_add( $args );
        }

        /**
         * Get all group IDs.
         *
         * @return array Array of group IDs.
         */
        protected function bpaf_get_all_group_ids() {
            $group_args = array(
                'order'   => 'DESC',
                'orderby' => 'date_created',
            );
            $groups = groups_get_groups( $group_args );

            return wp_list_pluck( $groups['groups'], 'id' );
        }

        /**
         * Get the permalink of a group.
         *
         * @param int $group_id Group ID.
         *
         * @return string Group permalink.
         */
        protected function bpaf_get_group_permalink( $group_id ) {
            return trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . groups_get_group( array( 'group_id' => $group_id ) )->slug . '/' );
        }

        /**
         * Get the link to a post author.
         *
         * @param int $author_id Author ID.
         *
         * @return string Author link.
         */
        protected function bpaf_get_post_author_link( $author_id ) {
            return sprintf(
                '<a href="%s">%s</a>',
                bp_get_loggedin_user_link(),
                get_the_author_meta( 'display_name', $author_id )
            );
        }

        /**
         * Get the title link for a post.
         *
         * @param int $post_id Post ID.
         *
         * @return string Post title link.
         */
        protected function bpaf_get_post_title_link( $post_id ) {
            return sprintf(
                '<a href="%s">%s</a>',
                get_the_permalink( $post_id ),
                get_the_title( $post_id )
            );
        }

        /**
         * Get the link for a post.
         *
         * @param int $post_id Post ID.
         *
         * @return string Post link.
         */
        protected function bpaf_get_post_link( $post_id ) {
            return get_the_permalink( $post_id );
        }

        /**
         * Get the link to a group.
         *
         * @param int $group_id Group ID.
         *
         * @return string Group link.
         */
        protected function bpaf_get_group_link( $group_id ) {
            return sprintf(
                '<a id="group-%s" class="new-group" href="%s">%s</a>',
                esc_attr( $group_id ),
                bp_get_group_permalink( groups_get_group( array( 'group_id' => $group_id ) ) ),
                groups_get_group( array( 'group_id' => $group_id ) )->name
            );
        }
    }
}

if ( class_exists( 'WbCom_BP_Activity_Filter_Add_Post_Type_Support' ) ) {
    new WbCom_BP_Activity_Filter_Add_Post_Type_Support();
}

