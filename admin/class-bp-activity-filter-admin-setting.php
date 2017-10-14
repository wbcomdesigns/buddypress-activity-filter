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
			add_action( 'admin_menu', array(&$this, 'bp_activity_filter_admin_menu'), 100);
			add_action( 'wp_ajax_bp_activity_filter_save_display_settings', array($this, 'bp_activity_filter_save_display_settings') );
			add_action( 'wp_ajax_nopriv_bp_activity_filter_save_display_settings', array($this, 'bp_activity_filter_save_display_settings' ) );
			add_action( 'wp_ajax_bp_activity_filter_save_hide_settings', array($this, 'bp_activity_filter_save_hide_settings') );
			add_action( 'wp_ajax_nopriv_bp_activity_filter_save_hide_settings', array($this, 'bp_activity_filter_save_hide_settings' ) );
			add_action( 'wp_ajax_bp_activity_filter_save_cpt_settings', array($this, 'bp_activity_filter_save_cpt_settings') );
			add_action( 'wp_ajax_nopriv_bp_activity_filter_save_cpt_settings', array($this, 'bp_activity_filter_save_cpt_settings' ) );
		}

		public function bp_activity_filter_admin_menu() {
			add_submenu_page( 'bp-activity', __('BP Activity Filter Settings', BPAF_TEXT_DOMAIN ), __(' BP Activity Filter Settings ', BPAF_TEXT_DOMAIN ), 'manage_options', 'bp_activity_filter_settings', array( $this, 'bp_activity_filter_section_settings') );
		}

		/**
		 * Your setting main function
		 */
		public function bp_activity_filter_section_settings() {
			$tab = isset($_GET['tab']) ? $_GET['tab'] : 'bpaf_display_activity';
		?>
		<div id="wpbody-content" class="bpaf-setting-page" aria-label="Main content" tabindex="0">
			<div class="wrap">
				<div class="bpaf-header">
					<div class="bpaf-extra-actions">
						<button type="button" class="button button-secondary" onclick="window.open('https://wbcomdesigns.com/contact/', '_blank');"><i class="fa fa-envelope" aria-hidden="true"></i> <?php _e( 'Email Support', BPAF_TEXT_DOMAIN )?></button>
						<button type="button" class="button button-secondary" onclick="window.open('https://wbcomdesigns.com/helpdesk/article-categories/buddypress-activity-filter/', '_blank');"><i class="fa fa-file" aria-hidden="true"></i> <?php _e( 'User Manual', BPAF_TEXT_DOMAIN )?></button>
						<button type="button" class="button button-secondary" onclick="window.open('https://wordpress.org/support/plugin/bp-activity-filter/reviews/', '_blank');"><i class="fa fa-star" aria-hidden="true"></i> <?php _e( 'Rate Us on WordPress.org', BPAF_TEXT_DOMAIN )?></button>
					</div>
				</div>
				<h1><?php _e('BuddyPress Activity Filter Settings', BPAF_TEXT_DOMAIN ); ?></h1>
			    <div id="bpaf_setting_error_settings_updated" class="updated settings-error notice is-dismissible">
					<p><strong><?php _e('Settings saved.', BPAF_TEXT_DOMAIN ); ?></strong></p>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"><?php _e('Dismiss this notice.', BPAF_TEXT_DOMAIN ); ?></span>
					</button>
				</div>
				<?php $this->bpaf_plugin_settings_tabs($tab);?>
		<?php
		}

		public function bpaf_get_labels() {
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
			return $labels;
		}



		public function bpaf_plugin_settings_tabs( $current ) {
			$bpaf_tabs = array(
				'bpaf_display_activity' => __('Display Activity', BPAF_TEXT_DOMAIN),
				'bpaf_hide_activity' => __('Hide Activity', BPAF_TEXT_DOMAIN),
				'bpaf_cpt_activity' => __('Post Type Activity', BPAF_TEXT_DOMAIN),
				'bpaf_faq' => __('FAQ', BPAF_TEXT_DOMAIN)
			);

			$tab_html =  '<h2 class="nav-tab-wrapper">';
			foreach( $bpaf_tabs as $bpaf_tab => $bpaf_name ){
				$class = ($bpaf_tab == $current) ? 'nav-tab-active' : '';
				$tab_html .=  '<a class="nav-tab '.$class.'" href="admin.php?page=bp_activity_filter_settings&tab=' . $bpaf_tab . '">' . $bpaf_name . '</a>';
			}
			$tab_html .= '</h2>';
			echo $tab_html;
			$this->bpaf_include_admin_setting_tabs($current);
		}

		function bpaf_include_admin_setting_tabs($bpaf_tab)
		{
		    $bpaf_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $bpaf_tab;
		    switch($bpaf_tab){
		        case 'bpaf_display_activity': 	$this->bpaf_display_activity_section();
		            							break;
		        case 'bpaf_hide_activity'   :	$this->bpaf_hide_activity_section();
		            							break;
		        case 'bpaf_cpt_activity'   :	$this->bpaf_cpt_activity_section();
		            							break;
		        case 'bpaf_faq'             :	$this->bpaf_faq_section();
		               							break;
		        default                     :  	$this->bpaf_display_activity_section();
		            							break;
		    }
		}

		public function bpaf_display_activity_section() {
			$labels = $this->bpaf_get_labels();
			?>
			<form method="post" novalidate="novalidate" id="bp_activity_filter_display_setting_form" >
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
					<th scope="row"><label class="filter-description" ><?php _e( 'Select activity you want to list on activity page by default.', BPAF_TEXT_DOMAIN ); ?></label></th>
				    <td>
				    	<table>
					    	<tr>
						    	<td class="filter-option">
						    		<input id="bp-activity-filter-everything-radio" name="bp-default-filter-name" type="radio" value="-1"  <?php  echo ($bp_default_activity_value == -1) ? "checked=checked": " ";?>/>
									<label for="bp-default-filter-name"><?php _e( "Everything", BPAF_TEXT_DOMAIN ); ?></label>
								</td>
							</tr>
					    <?php 	foreach ( $labels as $key => $value ) :
									if ( !empty($value) ) { ?>
									<tr>
										<td class="filter-option">
								    		<input id="<?php echo $key."_radio";?>" name="bp-default-filter-name" type="radio" value="<?php echo $key;?>" <?php  echo ($bp_default_activity_value == $key) ? "checked=checked ": " "; ?>  />
								    		<label for="<?php echo $key;?>"><?php _e( $value, BPAF_TEXT_DOMAIN ); ?></label>
								    	</td>
							    	</tr>
								    <?php }
				   			 	endforeach;	 ?>
		   			 	</table>
				 	</td>
			 	</table>
		 		<div class="submit">
					<a id="bp_activity_filter_display_setting_form_submit" class="button-primary"><?php _e('Save Settings', BPAF_TEXT_DOMAIN ); ?></a>
					<div class="spinner"></div>
				</div>
		 	</form>
		   	<?php
		}

		/**
		 * This is the display function for your field
		 */
		public function bpaf_hide_activity_section() {
			global $bp;
			$labels = $this->bpaf_get_labels();
			/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */

			$bp_hidden_filters_value = bp_get_option( 'bp-hidden-filters-name' );  ?>

			<form method="post" novalidate="novalidate" id="bp_activity_filter_hide_setting_form" >
				<table class="filter-table form-table" >
				    <tr>
				    	<th scope="row"><label class="filter-description" ><?php _e( 'Select activity/activities you want to hide from dropdown list on activity front page.', BPAF_TEXT_DOMAIN ); ?></label></th>
				    	<td>
				    		<table>
				    			<tr>
							    	<td class="filter-option">
							    		<input id="bp-activity-filter-everything-checkbox" name="bp-hidden-filters-name[]" type="checkbox" value="-1"  disabled="disabled" />
										<label for="bp-hidden-filters-name"><?php _e( 'Everything', BPAF_TEXT_DOMAIN ); ?></label>
									</td>
								</tr>
				    		<?php foreach ( $labels as $key => $value  ) :
								if ( !empty( $value)) { ?>
									<tr>
										<td class="filter-option">
								    		<input id="<?php echo $key."-checkbox"?>" name="bp-hidden-filters-name[]" type="checkbox" value="<?php echo $key;?>" <?php  echo ( (!empty($bp_hidden_filters_value) && is_array( $bp_hidden_filters_value )) && in_array($key, $bp_hidden_filters_value)) ? "checked" : " "; ?> />
								    		<label for="bp-hidden-filters-name"><?php _e( $value, BPAF_TEXT_DOMAIN ); ?></label>
								    	</td>
							    	</tr>
					    		<?php }
							endforeach; ?>
							</table>
						</td>
					</tr>
				</table>
				<div class="submit">
					<a id="bp_activity_filter_hide_setting_form_submit" class="button-primary"><?php _e('Save Settings', BPAF_TEXT_DOMAIN ); ?></a>
					<div class="spinner"></div>
				</div>
			</form>
		<?php
		}

		public function bpaf_faq_section() { ?>
			<div class="bpaf-admin-settings-block">
			    <div id="bpaf-settings-tbl">
			        <div class="bpaf-admin-row">
			            <div>
			                <button class="bpaf-accordion">What plugin does this plugin require?</button>
			                <div class="panel">
			                    <p>As the name of the plugin justifies, this plugin helps the site members to <strong>checkin, during their post updates</strong>, this plugin requires <strong>BuddyPress</strong> plugin to be installed and active.</p>
			                    <p>You'll also get an admin notice and the plugin will become ineffective if the required plugin will not be there.</p>
			                </div>
			            </div>
			        </div>

			        <div class="bpaf-admin-row">
			            <div>
			                <button class="bpaf-accordion">How to go for any custom development?</button>
			                <div class="panel">
			                    <p>If you need additional help you can contact us for <a href="https://wbcomdesigns.com/contact/" target="_blank" title="Custom Development by Wbcom Designs">Custom Development</a>.</p>
			                </div>
			            </div>
			        </div>
			    </div>
			</div>
		<?php }

		public function bp_activity_filter_save_display_settings() {
			parse_str( $_POST['form_data'], $setting_form_data );
			$form_details = filter_var_array( $setting_form_data, FILTER_SANITIZE_STRING );
			$bp_default_filter_name = $form_details['bp-default-filter-name'];
			bp_update_option( 'bp-default-filter-name',  $bp_default_filter_name );
			exit;
		}

		public function bp_activity_filter_save_hide_settings() {
			parse_str( $_POST['form_data'], $setting_form_data );
			$form_details = filter_var_array( $setting_form_data, FILTER_SANITIZE_STRING );
			$bp_hidden_filter_name = $form_details['bp-hidden-filters-name'];
			bp_update_option( 'bp-hidden-filters-name',  $bp_hidden_filter_name );
			exit;
		}

		public function bp_activity_filter_save_cpt_settings() {
			parse_str( $_POST['form_data'], $cpt_settings_data );
			$cpt_settings_details = filter_var_array( $cpt_settings_data, FILTER_SANITIZE_STRING );
			bp_update_option( 'bp-cpt-filters-settings',  $cpt_settings_details );
			exit;
		}

		public function bpaf_cpt_activity_section() {
			$cpt_filter_val = bp_get_option( 'bp-cpt-filters-settings');
			?>
			<form method="post" novalidate="novalidate" id="bp_activity_filter_cpt_setting_form" >
				<table class="filter-table form-table" >
				<?php
				 	$args = array(
				       'public'   => true,
				       '_builtin' => false,
				    );

				    $output = 'names'; // names or objects, note names is the default
				    $operator = 'and'; // 'and' or 'or'

				    $post_types = get_post_types( $args, $output, $operator );
				    foreach ( $post_types  as $post_type ) {
				    	$post_details = get_post_type_object( $post_type );
				    	$saved_settings = $cpt_filter_val['bpaf_admin_settings'][$post_type];
				    	if( array_key_exists('display_type', $saved_settings) ) {
				    		$display_type = $saved_settings['display_type'];
				    	} else {
				    		$display_type = '';
				    	}
				    	if( array_key_exists('group', $saved_settings)) {
				    		$group = $saved_settings['group'];
				    	} else {
				    		$group = '';
				    	}
				    ?>
				    <tr>
				    	<th scope="row"><label class="filter-description" ><?php echo $post_details->label; ?></label></th>
					    <td>
					    	<table>
					    		<tr>
							    	<td class="filter-option">
										<label ><?php _e( 'Rename in Activity Stream', BPAF_TEXT_DOMAIN ); ?></label>
									</td>
								</tr>
								<tr>
									<td class="filter-option">
										<input id="<?php echo $post_type."_text";?>" name='<?php echo "bpaf_admin_settings[$post_type][new_label]"; ?>' type="text" value="<?php if( isset( $saved_settings['new_label'] ) ) { echo $saved_settings['new_label']; } ?>" />

									</td>
								</tr>
								<tr>
									<td class="filter-option">
										<input id="<?php echo $post_type."_radio";?>" name="<?php echo "bpaf_admin_settings[$post_type][display_type]"; ?>" type="radio" value="not_display" <?php checked( $display_type, 'not_display' ); ?> />
										<label for="bpaf_admin_settings_display_type-$post_type"><?php _e( 'Do not display', BPAF_TEXT_DOMAIN ); ?></label>
									</td>
								</tr>
								<tr>
									<td class="filter-option">
										<input id="<?php echo $post_type."_radio";?>" class="bp-default-filter-name" name="<?php echo "bpaf_admin_settings[$post_type][display_type]"; ?>" type="radio" value="main_activity" <?php checked( $display_type, 'main_activity' ); ?>  />
										<label for="bpaf_admin_settings_display_type-$post_type"><?php _e( 'Display in main Activity Stream', BPAF_TEXT_DOMAIN ); ?></label>
									</td>
								</tr>
								<?php if ( bp_is_active( 'groups' ) ) { ?>
								<tr>
									<td class="filter-option">
										<input id="<?php echo $post_type."_radio";?>" class="bpaf-group-filter" name="<?php echo "bpaf_admin_settings[$post_type][display_type]"; ?>" type="radio" value="groups" <?php checked( $display_type, 'groups' ); ?>  />
										<label for='<?php "bpaf_admin_settings_display_type-$post_type"; ?>'><?php _e( 'Display in Groups', BPAF_TEXT_DOMAIN ); ?></label>
									</td>
								</tr>
								<tr>
									<td class="filter-option">
										<table class="bpaf-group-list" >
										<?php
											$group_args = array(
												'order' => 'DESC',
												'orderby' => 'date_created'
											);
						                    $allgroups  = groups_get_groups( $group_args );
						                    foreach ($allgroups['groups'] as $key => $value) {
						                    	if( is_array( $group )) {
						                    		if( in_array( $value->name, $group ) ) {
						                    			$checked_val = $value->name;
						                    		}
						                    		else {
						                    			$checked_val = '';
						                    		}
						                    	} else {
						                    		$checked_val = '';
						                    	}
				                    		?>
											<tr>
												<td class="filter-option">
													<input id="<?php echo $post_type."_checkbox";?>" name="<?php echo "bpaf_admin_settings[$post_type][group][]"; ?>" type="checkbox" value="<?php echo $value->name; ?>" <?php checked( $checked_val, $value->name ); ?> />
													<label for="bp-hidden-filters-name"><?php echo $value->name; ?></label>
												</td>
											</tr>
										<?php } ?>
										</table>
									</td>
								</tr>
								<?php } ?>
							</table>
						</td>
			       	</tr>
				    <?php }	?>
	    		</table>
				<div class="submit">
					<a id="bp_activity_filter_cpt_setting_form_submit" class="button-primary"><?php _e('Save Settings', BPAF_TEXT_DOMAIN ); ?></a>
					<div class="spinner"></div>
				</div>
    		</form>
		<?php exit; }

	}
}

if (class_exists('WbCom_BP_Activity_Filter_Admin_Setting')) {
	$admin_setting_obj = new WbCom_BP_Activity_Filter_Admin_Setting();
}
