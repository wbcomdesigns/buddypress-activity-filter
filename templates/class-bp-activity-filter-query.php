<?php
/**
 * Defining class for Filtering activity stream
 */
if ( ! class_exists( 'WbCom_BP_Activity_Filter_Activity_Stream' ) ) {
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
		}

		public function bpaf_enqueue_scripts() {

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Bp_Add_Group_Types_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Bp_Add_Group_Types_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */
			global $bp;
			$defult_activity_stream = bp_get_option( 'bp-default-filter-name' );
			if ( empty( $defult_activity_stream ) ) {
				$defult_activity_stream = -1;
			}	

				wp_enqueue_script( 'bp-activity-filter-public', plugin_dir_url( __FILE__ ) . 'js/buddypress-activity-filter-public.js', array( 'jquery' ), time(), false );

				wp_localize_script(
					'bp-activity-filter-public',
					'bpaf_js_object',
					array(
						'default_filter' => $defult_activity_stream,
					)
				);

		}

		/**
		 * Modifying activity loop for default acitvity
		 *
		 * @param $retval
		 */
		public function filtering_activity_default( $query, $object ) {
			global $bp;
			$query_size = '';

			if ( 'activity' != $object ) {
				return $query;
			}
			

			if ( ! empty( $_POST['cookie'] ) )
				$_BP_COOKIE = wp_parse_args( str_replace( '; ', '&', urldecode( $_POST['cookie'] ) ) );
			else
				$_BP_COOKIE = &$_COOKIE;
			
			if( !empty( $query ) ) {
				$bp_query     = explode( '&', $query );
				$bp_query_arr = $bp_query;
				$page     = array_pop( $bp_query_arr );
				$qs       = explode( '=', $page );
				if( 'page' == $qs[0] ) {
					$size = $qs[1];
					$query_size = sizeof( $bp_query );
				}
			} else {
				$bp_query = array();
				$size = sizeof( $bp_query );
			}

			if( bp_is_group_activity() ) {
				$defult_activity_stream = -1;
				$page_actions = bp_activity_get_actions_for_context();
				if( !empty( $page_actions ) ) {
					$selected_activity_stream = bp_get_option( 'bp-default-filter-name' );
					foreach( $page_actions as $gakey => $gavalue ) {
						if( $selected_activity_stream == $gavalue['key'] ) {
							$defult_activity_stream = $selected_activity_stream;
						}
					}
				}
			} else {
				$defult_activity_stream = bp_get_option( 'bp-default-filter-name' );
				$page_actions           = bp_activity_get_actions_for_context( 'activity' );
			}

			$hidden_activity_stream = bp_get_option( 'bp-hidden-filters-name' );
			if ( ( $defult_activity_stream != -1 ) && ( 1 == $_BP_COOKIE['bpaf-default-filter'] ) ) {
				$query = wp_parse_args( $query, array() );

				$count  = 0;
				$action = '';
				if ( empty( $hidden_activity_stream ) ) {
					$hidden_activity_stream = array();
				}
				$admin_setting_object = new WbCom_BP_Activity_Filter_Admin_Setting();
				$labels               = $admin_setting_object->bpaf_get_labels();
				foreach ( $labels as $l_key => $l_value ) {
					if ( ! empty( $l_value ) ) {
						if ( in_array( $l_key, $hidden_activity_stream ) ) {

						} else {
							if ( $count == 0 ) {
								$action .= $l_key;
								$count++;
							} else {
								$action .= ',' . $l_key;
								$count++;
							}
						}
					}
				}
				if ( $defult_activity_stream != -1 ) {
					$query = 'action=' . $defult_activity_stream;
					if( !empty( $page ) ) {
						$query .= '&'.$page;
					}					
				} else {
					$query = 'action=' . $action;
				}
				
			} else if( $defult_activity_stream == -1 && ( 1 == $_BP_COOKIE['bpaf-default-filter'] ) || empty( $query ) || ( 1 == $query_size ) ) {
				$count  = 0;
				$action = '';
				$admin_setting_object = new WbCom_BP_Activity_Filter_Admin_Setting();
				$labels               = $admin_setting_object->bpaf_get_labels();
				if( !empty( $labels ) ) {
					foreach ( $labels as $l_key => $l_value ) {
						if ( ! empty( $l_value ) ) {
							if ( in_array( $l_key, $hidden_activity_stream ) ) {

							} else {
								if ( $count == 0 ) {
									$action .= $l_key;
									$count++;
								} else {
									$action .= ',' . $l_key;
									$count++;
								}
							}
						}
					}
				}
				$query = 'action=' . $action;
				if( !empty( $page ) ) {
					$query .= '&'.$page;
				}				
			}
					
			return $query;
		}
	}
}
if ( class_exists( 'WbCom_BP_Activity_Filter_Activity_Stream' ) ) {
	$filter_query_obj = new WbCom_BP_Activity_Filter_Activity_Stream();
}
