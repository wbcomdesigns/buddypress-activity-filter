<?php
/*
Plugin Name: BuddyPress  Activity Filter
Plugin URI: https://wbcomdesigns.com/plugins/buddypress-activity-filter/
Description: Admin can set default and customized activities to be listed on front-end
Version: 1.0.0
Text Domain: bp-activity-filter
Author: Wbcom Designs<admin@wbcomdesigns.com>
Author URI: http://www.wbcomdesigns.com/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
	wp_die('Direct Access is not Allowed');
}

/**
 *  Checking for buddypress whether it is active or not
 */
if (!in_array('buddypress/bp-loader.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	 $active_plugins =  apply_filters('active_plugins', get_option('active_plugins'));
	 $bp_filter_plugin = 'buddypress-activity-filter/buddypress-activity-filter.php';
	 $bp_filter_key = array_search($bp_filter_plugin,$active_plugins);
	 if(isset($bp_filter_key) && in_array($bp_filter_plugin, $active_plugins)) {
	 	unset($active_plugins[$bp_filter_key]);
	 	add_action('admin_notices', 'buddypress_not_active_notice');
	 	update_option('active_plugins', $active_plugins);
	 	if(isset($_GET['activate']))
	 		unset($_GET['activate']);
	 }
	 return;
}
/**
 * Defining class WbCom_BP_Activity_Filter is not exist
 */
else if (!class_exists('WbCom_BP_Activity_Filter')) {
	class WbCom_BP_Activity_Filter {		
		/**
		 * Constructor
		 */
		public function __construct() {						
			/**
			 * Adding setting link on plugin listing page
			 */
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'bp_activity_filter_plugin_actions'), 10, 2);
			
			/**
			 * Including scripts files for admin setting
			 */
			require_once plugin_dir_path(__FILE__) . 'admin/class-bp-activity-filter-admin-script-includer.php';
			
			/**
			 * Including file for admin setting
			 */
			require_once plugin_dir_path(__FILE__) . 'admin/class-bp-activity-filter-admin-setting.php';
			
			/**
			 * Including file for saving admin setting
			 */
			require_once plugin_dir_path(__FILE__) . 'admin/class-bp-activity-filter-admin-setting-save.php';
															
			/**
			 * Including file for dropdown option filter setting on front-end 
			 */
			require_once plugin_dir_path(__FILE__) . 'templates/class-bp-activity-filter-dropdown.php';
			
			/**
			 * Including file for dropdown option filter setting on front-end 
			 */
			require_once plugin_dir_path(__FILE__) . 'templates/class-bp-activity-filter-query.php';
			
		}
		
		/**
		 * @desc Adds the Settings link to the plugin activate/deactivate page
		 */
		public function bp_activity_filter_plugin_actions($links, $file) { //die('here');
			$settings_link = '<a href="' . admin_url("admin.php?page=bp-settings#bp_activity_filter") . '">' . __('Settings', 'buddypress') . '</a>';
			array_unshift($links, $settings_link); // before other links
			return $links;
		}
	}
}

if (class_exists('WbCom_BP_Activity_Filter')) {
	$GLOBALS['activity_filter'] = new WbCom_BP_Activity_Filter();
}
function buddypress_not_active_notice() { ?>
    <div class="error notice">
        <p><?php _e('To work BuddyPress  Activity Filter, Buddypress should be activated', 'buddypress'); ?></p>
    </div>
    <?php
}