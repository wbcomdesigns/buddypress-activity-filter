<?php
/**
 * Including CSS  for addmin setting
 *
 * * @package BuddyPress_Activity_Filter
 */

if ( ! class_exists( 'WbCom_BP_Activity_Filter_Script_Includer' ) ) {
	/**
	 * Including CSS  for addmin setting
	 *
	 * * @package BuddyPress_Activity_Filter
	 */
	class WbCom_BP_Activity_Filter_Script_Includer {

		/**
		 * Constructor
		 */
		public function __construct() {
			/**
			 * Adding style for admin settings
			 */
			add_action( 'admin_enqueue_scripts', array( &$this, 'include_admin_css_function' ) );
		}

		/**
		 * Adding css files
		 */
		public function include_admin_css_function() {
			// phpcs:ignore
			if ( isset( $_GET['page'] ) && 'bp_activity_filter_settings' === $_GET['page'] ) {
				wp_register_style('custom_wp_admin_css', plugins_url('css/bp-activity-filter.css', __FILE__), array(), BP_ACTIVITY_FILTER_PLUGIN_VERSION);

				wp_enqueue_style( 'custom_wp_admin_css' );
				wp_enqueue_script('custom_wp_admin_js', plugin_dir_url(__FILE__) . 'js/bp-activity-filter.js', array('jquery'), BP_ACTIVITY_FILTER_PLUGIN_VERSION, true);
				wp_localize_script(
					'custom_wp_admin_js',
					'wbcom_bpaf_admin',
					array(
						'ajax_url'    => admin_url( 'admin-ajax.php' ),
						'admin_nonce' => wp_create_nonce( 'bp_activity_filter_nonce' ),
					)
				);
			}

		}
	}

}

if ( class_exists( 'WbCom_BP_Activity_Filter_Script_Includer' ) ) {
	$script_includer = new WbCom_BP_Activity_Filter_Script_Includer();
}
