<?php
/**
 * Defining class if not exist for admin setting
 */

if (!class_exists('WbCom_BP_Activity_Filter_Admin_Setting')) {

	class WbCom_BP_Activity_Filter_Admin_Setting {
		/**
		 * Constructor
		 */
		public function __construct() {
			/**
			 * You need to hook bp_register_admin_settings to register your settings
			 */
			add_action('admin_menu', array(&$this, 'bp_activity_filter_admin_menu'), 100);
		}

		public function bp_activity_filter_admin_menu() {
			add_submenu_page( 'bp-activity', __('BP Activity Filter Settings', 'bp-activity-filter' ), __(' BP Activity Filter Settings ', 'bp-activity-filter' ), 'manage_options', 'bp_activity_filter_settings', array( $this, 'bp_activity_filter_section_settings') );
		}

		/**
		 * Your setting main function
		 */
		public function bp_activity_filter_section_settings() {

			/*Argument to pass in callback*/
			$filter_actions = buddypress() -> activity -> actions;

			$actions = array();

			foreach (get_object_vars($filter_actions) as $property => $value)
		  		$actions[] = $property;

			$labels = array();

			foreach ($actions as $key => $value) {

				foreach (get_object_vars($filter_actions -> $value) as $prop => $val) {

					if (!empty($val['label']))
						$labels [$val['key']] = $val ['label'];

					else $labels [$val['key']] = $val ['value'];
				}
			}

			// On member pages, default to 'member', unless this is a user's Groups activity.
			$context = '';

			if (bp_is_user()) {

				if (bp_is_active('groups') && bp_is_current_action(bp_get_groups_slug())) {

					$context = 'member_groups';

				} else {
					$context = 'member';
				}

			// On individual group pages, default to 'group'.
			} elseif (bp_is_active('groups') && bp_is_group()) {
				$context = 'group';

			// 'activity' everywhere else.
			} else {
				$context = 'activity';
			}

			$default_filters = array();

			// Walk through the registered actions, and prepare an the select box options.

			foreach (bp_activity_get_actions() as $actions) {

				foreach ($actions as $action) {

					if (!in_array($context, (array) $action['context'])) {
						continue;
					}

					// Friends activity collapses two filters into one.
					if (in_array($action['key'], array('friendship_accepted', 'friendship_created'))) {
						$action['key'] = 'friendship_accepted,friendship_created';
					}

					$default_filters[$action['key']] = $action['label'];
				}
			}
			foreach ($default_filters as $key => $value) {

				if (!array_key_exists($key, $labels))
					$labels[$key] = $value;
			}

			$labels = array_reverse(array_unique(array_reverse($labels)));
			$labels = array_reverse($labels);

			$this->bp_activity_filter_admin_settings_page( $labels );
		}


		public function bp_activity_filter_admin_settings_page( $labels ) { ?>
			<div id="wpbody-content" aria-label="Main content" tabindex="0">
			<div class="wrap">
				<h1><?php _e('BuddyPress Activity Filter Settings', 'bp-activity-filter' ); ?></h1>
				<form method="post" novalidate="novalidate" id="bp_activity_filter_setting_form" >
					<?php $this->bp_activity_filter_section_callback();
					$this->bp_activity_filter_filed_1_callback($labels);
					$this->bp_activity_filter_filed_2_callback($labels);
					?>
					<p class="submit">
						<a id="bp_activity_filter_setting_form_submit" class="button-primary">Save Settings</a>
					</p>
				</form>
		<?php }

	/**
		 * This is the display function for your section's description
		 */
		public function bp_activity_filter_section_callback() { ?>
		    <p id="bp_activity_filter" class="description">
		    	<?php _e('You can set here which type of activity will be shown on front-end by default on launching activity page and also can set which filter(s) to appear in dropdown list.', 'bp-activity-filter'); ?>
		    </p>
		    <?php
		}

		/**
		 * This is the display function for your field
		 */
		public function bp_activity_filter_filed_1_callback($labels) {
			global $bp;
			?>
			<table class="filter-table form-table" >
			<?php
			/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */

			$bp_default_activity_value = bp_get_option( 'bp-default-filter-name' );
			$bp_hidden_filters_value = bp_get_option( 'bp-hidden-filters-name' );

			if  ( is_array($bp_hidden_filters_value) && in_array( $bp_default_activity_value, $bp_hidden_filters_value) )
				bp_update_option( 'bp-default-filter-name', '-1' );

			$bp_default_activity_value = bp_get_option( 'bp-default-filter-name' );

			if(empty($bp_default_activity_value))
				$bp_default_activity_value=-1; ?>
			<th scope="row"><label class="filter-description" ><?php _e( 'Select activity you want to list on activity page by default.', 'bp-activity-filter' ); ?></label></th>
		    <td>
		    	<table>
			    	<tr>
				    	<td class="filter-option">
				    		<input id="bp-activity-filter-everything-radio" name="bp-default-filter-name" type="radio" value="-1"  <?php  echo ($bp_default_activity_value == -1) ? "checked=checked": " ";?>/>
							<label for="bp-default-filter-name"><?php _e( "Everything", 'bp-activity-filter' ); ?></label>
						</td>
					</tr>
			    <?php 	foreach ( $labels as $key => $value ) :
							if ( !empty($value) ) { ?>
							<tr>
								<td class="filter-option">
						    		<input id="<?php echo $key."_radio";?>" name="bp-default-filter-name" type="radio" value="<?php echo $key;?>" <?php  echo ($bp_default_activity_value == $key) ? "checked=checked ": " "; ?>  />
						    		<label for="<?php echo $key;?>"><?php _e( $value, 'bp-activity-filter' ); ?></label>
						    	</td>
					    	</tr>
						    <?php }
		   			 	endforeach;	 ?>
   			 	</table>
		 	</td>
		   	<?php
		}

		/**
		 * This is the display function for your field
		 */
		public function bp_activity_filter_filed_2_callback( $labels ) { ?>

			<?php
			/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */

			$bp_hidden_filters_value = bp_get_option( 'bp-hidden-filters-name' );  ?>
		    <tr>
		    	<th scope="row"><label class="filter-description" ><?php _e( 'Select activity/activities you want to hide from dropdown list on activity front page.', 'bp-activity-filter' ); ?></label></th>
		    	<td>
		    		<table>
		    			<tr>
					    	<td class="filter-option">
					    		<input id="bp-activity-filter-everything-checkbox" name="bp-hidden-filters-name[]" type="checkbox" value="-1"  disabled="disabled" />
								<label for="bp-hidden-filters-name"><?php _e( 'Everything', 'bp-activity-filter' ); ?></label>
							</td>
						</tr>
		    		<?php foreach ( $labels as $key => $value  ) :
						if ( !empty( $value)) { ?>
							<tr>
								<td class="filter-option">
						    		<input id="<?php echo $key."-checkbox"?>" name="bp-hidden-filters-name[]" type="checkbox" value="<?php echo $key;?>" <?php  echo ( (!empty($bp_hidden_filters_value) && is_array( $bp_hidden_filters_value )) && in_array($key, $bp_hidden_filters_value)) ? "checked" : " "; ?> />
						    		<label for="bp-hidden-filters-name"><?php _e( $value, 'bp-activity-filter' ); ?></label>
						    	</td>
					    	</tr>
			    		<?php }
					endforeach; ?>
					</table>
				</td>
			</tr>
		</table>

		<?php
		}

	}
}

if (class_exists('WbCom_BP_Activity_Filter_Admin_Setting')) {
	$admin_setting_obj = new WbCom_BP_Activity_Filter_Admin_Setting();
}
