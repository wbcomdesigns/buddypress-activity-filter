<?php
/**
 *
 * @link              https://wbcomdesigns.com/
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       BuddyPress Activity Filter
 * Plugin URI:        https://wbcomdesigns.com/downloads/buddypress-activity-filter/
 * Description:       Admin can set default and customized activities to be listed on front-end.
 * Version:           2.0.1
 * Author:            Wbcom Designs<admin@wbcomdesigns.com>
 * Author URI:        https://wbcomdesigns.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bp-activity-filter
 * Domain Path:       /languages
 */


if (!defined('ABSPATH')) {

    wp_die('Direct Access is not Allowed');

}
define( 'BP_ACTIVITY_FILTER_PLUGIN_BASENAME',  plugin_basename( __FILE__ ) );

define( 'BP_ACTIVITY_FILTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Defining class WbCom_BP_Activity_Filter is not exist
 */

if (!class_exists('WbCom_BP_Activity_Filter')) {



    class WbCom_BP_Activity_Filter {



        /**
         * Constructor
         */

        public function __construct() {
            global $bp;
            /**
             * Adding text domain
             */
            $this->bp_activity_filter_load_textdomain();

            /**
             * Adding setting link on plugin listing page
             */

            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'bp_activity_filter_plugin_actions'), 10, 2);


            require_once plugin_dir_path( __FILE__ ) . 'admin/wbcom/wbcom-admin-settings.php';
            /**
             * Including scripts files for admin setting
             */

            require_once plugin_dir_path(__FILE__) . 'admin/class-bp-activity-filter-admin-script-includer.php';



            /**
             * Including file for admin setting
             */

            require_once plugin_dir_path(__FILE__) . 'admin/class-bp-activity-filter-admin-setting.php';



            //require_once plugin_dir_path(__FILE__) . 'admin/bpaf-admin-options.php';

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

            require_once plugin_dir_path(__FILE__) . 'admin/class-bp-activity-filter-add-post-support.php';


            /**
             * Including file for dropdown option filter setting on front-end
             */

            require_once plugin_dir_path(__FILE__) . 'templates/class-bp-activity-filter-query.php';
        }

        

        //Load plugin textdomain.

        public function bp_activity_filter_load_textdomain() {

            $domain = "bp-activity-filter";

            $locale = apply_filters('plugin_locale', get_locale(), $domain);

            load_textdomain($domain, 'languages/' . $domain . '-' . $locale . '.pot');

            $var = load_plugin_textdomain($domain, false, plugin_basename(dirname(__FILE__)) . '/languages');

        }



        /**
         * @desc Adds the Settings link to the plugin activate/deactivate page
         */

        public function bp_activity_filter_plugin_actions($links, $file) {

            $settings_link = '<a href="' . admin_url("admin.php?page=bp_activity_filter_settings") . '">' . __('Settings', 'bp-activity-filter') . '</a>';

            array_unshift($links, $settings_link); // before other links

            return $links;

        }
    }
}

function bpfilter_check_config(){
    global $bp;
    
    $config = array(
        'blog_status'    => false, 
        'network_active' => false, 
        'network_status' => true 
    );
    if ( get_current_blog_id() == bp_get_root_blog_id() ) {
        $config['blog_status'] = true;
    }
    
    $network_plugins = get_site_option( 'active_sitewide_plugins', array() );

    // No Network plugins
    if ( empty( $network_plugins ) )

    // Looking for BuddyPress and bp-activity plugin
    $check[] = $bp->basename;
    $check[] = BP_ACTIVITY_FILTER_PLUGIN_BASENAME;

    // Are they active on the network ?
    $network_active = array_diff( $check, array_keys( $network_plugins ) );
    
    // If result is 1, your plugin is network activated
    // and not BuddyPress or vice & versa. Config is not ok
    if ( count( $network_active ) == 1 )
        $config['network_status'] = false;

    // We need to know if the plugin is network activated to choose the right
    // notice ( admin or network_admin ) to display the warning message.
    $config['network_active'] = isset( $network_plugins[ BP_ACTIVITY_FILTER_PLUGIN_BASENAME ] );

    // if BuddyPress config is different than bp-activity plugin
    if ( !$config['blog_status'] || !$config['network_status'] ) {

        $warnings = array();
        if ( !bp_core_do_network_admin() && !$config['blog_status'] ) {
            add_action( 'admin_notices', 'bpfilter_same_blog' );
            $warnings[] = esc_html__( 'BuddyPress Activity Filter requires to be activated on the blog where BuddyPress is activated.', 'bp-activity-filter' );
        }

        if ( bp_core_do_network_admin() && !$config['network_status'] ) {
            add_action( 'admin_notices', 'bpfilter_same_network_config' );
            $warnings[] = esc_html__( 'BuddyPress Activity Filter and BuddyPress need to share the same network configuration.', 'bp-activity-filter' );
        }

        if ( ! empty( $warnings ) ) :
            return false;
        endif;
    } 
    return true;
}


add_action( 'bp_include', 'bp_activity_filter_init' );
function bp_activity_filter_init(){
    if ( bpfilter_check_config() && class_exists('WbCom_BP_Activity_Filter')) {
        $GLOBALS['activity_filter'] = new WbCom_BP_Activity_Filter();
    }
}
function bpfilter_same_blog() {
    echo '<div class="error"><p>'
    . esc_html__( 'BuddyPress Activity Filter requires to be activated on the blog where BuddyPress is activated.', 'bp-activity-filter' )
    . '</p></div>';
}

function bpfilter_same_network_config() {
    echo '<div class="error"><p>'
    . esc_html__( 'BuddyPress Activity Filter and BuddyPress need to share the same network configuration.', 'bp-activity-filter' )
    . '</p></div>';
}

/**
 *  Check if buddypress activate.
 */
function bpfilter_requires_buddypress()
{

    if ( !class_exists( 'Buddypress' ) &&  ! defined( 'BP_VERSION' )  ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        //deactivate_plugins('buddypress-polls/buddypress-polls.php');
        add_action( 'admin_notices', 'bpfilter_required_plugin_admin_notice' );
        unset($_GET['activate']);
    }
}

add_action( 'admin_init', 'bpfilter_requires_buddypress' );
/**
 * Throw an Alert to tell the Admin why it didn't activate.
 *
 * @author wbcomdesigns
 * @since  2.0.2
 */
function bpfilter_required_plugin_admin_notice()
{

    $bpquotes_plugin          = esc_html__('BuddyPress Activity Filter', 'bp-activity-filter');
    $bp_plugin                = esc_html__('BuddyPress', 'bp-activity-filter');
    echo '<div class="error"><p>';
    echo sprintf(esc_html__('%1$s is ineffective now as it requires %2$s to be installed and active.', 'bp-activity-filter'), '<strong>' . esc_html($bpquotes_plugin) . '</strong>', '<strong>' . esc_html($bp_plugin) . '</strong>');
    echo '</p></div>';
    if (isset($_GET['activate']) ) {
        unset($_GET['activate']);
    }
}
