<?php
/**
 * Including CSS and JS for addmin setting 
 */

if (!class_exists('WbCom_BP_Activity_Filter_Script_Includer')) {
	class WbCom_BP_Activity_Filter_Script_Includer {
		/**
		 * Constructor
		 */
		public function __construct() {
			/**
			 * Adding style and scripts for admin settings
			 */
			add_action('admin_enqueue_scripts', array(&$this,'include_admin_css_js_function'));
			
			/**
			 * Adding style and scripts for front end
			 */
			add_action('wp_enqueue_scripts', array(&$this,'include_front_css_js_function'));
			
		}
		
		/**
		 * Adding css and js files
		 */
		public function include_admin_css_js_function () {
			// Register the script 
			wp_register_script( 'custom_admin_script', plugins_url('js/bp-activity-filter.js', __FILE__) , false , '1.0.0');
    		
			wp_enqueue_script('custom_admin_script');
    		
			wp_register_style( 'custom_wp_admin_css', plugins_url('css/bp-activity-filter.css', __FILE__), false, '1.0.0' );
    		
			wp_enqueue_style( 'custom_wp_admin_css' );
        	
		}
		
		/**
		 * Adding css and js files for front end
		 */
		public function include_front_css_js_function () {
			// Register the script 
			
			wp_register_script( 'min_js_front_script', plugins_url('templates/js/jquery-1.12.0.min.js', plugin_dir_path(__FILE__)) , false , '1.0.0');
    		
			wp_enqueue_script('min_js_front_script');
			
			wp_register_script( 'custom_front_script', plugins_url('templates/js/bp-activity-filter-front.js', plugin_dir_path(__FILE__)) , false , '1.0.0');
    		
			wp_enqueue_script('custom_front_script');
   
		}
		
	}
}
if (class_exists('WbCom_BP_Activity_Filter_Script_Includer')) {
	$script_includer = new WbCom_BP_Activity_Filter_Script_Includer();
}