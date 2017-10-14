<?php
/**
 * Including CSS  for addmin setting
 */

if (!class_exists('WbCom_BP_Activity_Filter_Add_Post_Type_Support')) {
	class WbCom_BP_Activity_Filter_Add_Post_Type_Support {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'bp_init',  array(&$this,'bpaf_customize_page_tracking_args'));
		}

		public function bpaf_customize_page_tracking_args() {
			global $bp;
			$cpt_filter_setting = bp_get_option('bp-cpt-filters-settings');
			$all_posts = $cpt_filter_setting['bpaf_admin_settings'];
			foreach( $all_posts as $post_type=>$details ) {
				$post_details = get_post_type_object( $post_type );
				add_post_type_support( $post_type , 'buddypress-activity' );
			    bp_activity_set_post_type_tracking_args( $post_type , array(
			        'component_id'             => buddypress()->activity->id,
			        'action_id'                => "new_$post_type",
			        'bp_activity_admin_filter' => __( "Published a new $post_type", BPAF_TEXT_DOMAIN ),
			        'bp_activity_front_filter' => __( $post_type , BPAF_TEXT_DOMAIN ),
			        'contexts'                 => array( 'activity','member', 'groups', 'member-groups'),
			        'activity_comment'         => true,
			        'bp_activity_new_post'     => __( '%1$s posted a new <a href="%2$s">'.$post_type.'</a>', BPAF_TEXT_DOMAIN ),
			        'bp_activity_new_post_ms'  => __( '%1$s posted a new <a href="%2$s">'.$post_type.'</a>, on the site %3$s', BPAF_TEXT_DOMAIN ),
			        'position'                 => 100,
			    ) );
			}

		}

	}
}
if (class_exists('WbCom_BP_Activity_Filter_Add_Post_Type_Support')) {
	$support_includer = new WbCom_BP_Activity_Filter_Add_Post_Type_Support();
}