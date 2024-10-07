<?php

/**
 * Defining class if not exist for admin setting
 *
 *  @package BuddyPress_Activity_Filter
 */

if (!class_exists('WbCom_BP_Activity_Filter_Admin_Setting')) {
	/**
	 * Defining class if not exist for admin setting
	 *
	 *  @package BuddyPress_Activity_Filter
	 */
	class WbCom_BP_Activity_Filter_Admin_Setting
	{

		/**
		 * Constructor
		 */
		public function __construct()
		{
			add_action('admin_menu', array($this, 'bp_activity_filter_admin_menu'), 100);
			add_action('network_admin_menu', array($this, 'bp_activity_filter_admin_menu'), 100);

			add_action('wp_ajax_bp_activity_filter_save_display_settings', array($this, 'bp_activity_filter_save_display_settings'));
			add_action('wp_ajax_bp_activity_filter_save_hide_settings', array($this, 'bp_activity_filter_save_hide_settings'));
			add_action('wp_ajax_bp_activity_filter_save_cpt_settings', array($this, 'bp_activity_filter_save_cpt_settings'));

			add_action('in_admin_header', array($this, 'bp_activity_filter_hide_all_admin_notices_from_setting_page'));
		}


		/**
		 * BP Share activity filter
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_admin_menu()
		{
			if (empty($GLOBALS['admin_page_hooks']['wbcomplugins'])) {
				add_menu_page(esc_html__('WB Plugins', 'bp-activity-filter'), esc_html__('WB Plugins', 'bp-activity-filter'), 'manage_options', 'wbcomplugins', array($this, 'bp_activity_filter_section_settings'), 'dashicons-lightbulb', 59);
				add_submenu_page('wbcomplugins', esc_html__('General', 'bp-activity-filter'), esc_html__('General', 'bp-activity-filter'), 'manage_options', 'wbcomplugins');
			}
			add_submenu_page('wbcomplugins', esc_html__('Activity Filter', 'bp-activity-filter'), esc_html__('Activity Filter', 'bp-activity-filter'), 'manage_options', 'bp_activity_filter_settings', array($this, 'bp_activity_filter_section_settings'));
		}

		/**
		 * Settings page content
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_section_settings()
		{
			$tab = filter_input(INPUT_GET, 'tab') ? filter_input(INPUT_GET, 'tab') : 'bpaf_welcome';
?>
			<div class="wrap">
				<div class="wbcom-bb-plugins-offer-wrapper">
					<div id="wb_admin_logo">
						<a href="https://wbcomdesigns.com/downloads/buddypress-community-bundle/?utm_source=pluginoffernotice&utm_medium=community_banner" target="_blank">
							<img src="<?php echo esc_url(BP_ACTIVITY_FILTER_PLUGIN_URL) . 'admin/wbcom/assets/imgs/wbcom-offer-notice.png'; ?>">
						</a>
					</div>
				</div>
				<div class="wbcom-wrap">
					<div class="blpro-header">
						<div class="wbcom_admin_header-wrapper">
							<div id="wb_admin_plugin_name">
								<?php esc_html_e('BuddyPress Activity Filter', 'bp-activity-filter'); ?>
								<span><?php
										/* translators: %s: */
										printf(esc_html__('Version %s', 'bp-activity-filter'), esc_attr(BP_ACTIVITY_FILTER_PLUGIN_VERSION));
										?></span>
							</div>
							<?php echo do_shortcode('[wbcom_admin_setting_header]'); ?>
						</div>
					</div>
					<div id="bpaf_setting_error_settings_updated" class="updated settings-error notice is-dismissible" style="display:none;">
						<p><strong><?php esc_html_e('Settings saved.', 'bp-activity-filter'); ?></strong></p>
						<button type="button" class="notice-dismiss">
							<span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'bp-activity-filter'); ?></span>
						</button>
					</div>
					<div class="wbcom-admin-settings-page">
						<?php $this->bpaf_plugin_settings_tabs($tab); ?>
					</div>
				</div>
			</div>
		<?php
		}

		/**
		 * Get all labels.
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bpaf_get_labels()
		{
			$filter_actions = buddypress()->activity->actions;
			$actions        = array_keys(get_object_vars($filter_actions));
			$labels = array();

			foreach ($actions as $value) {
				foreach (get_object_vars($filter_actions->$value) as $val) {
					$labels[$val['key']] = !empty($val['label']) ? $val['label'] : $val['value'];
				}
			}

			$context = 'activity';
			if (bp_is_user()) {
				$context = (bp_is_active('groups') && bp_is_current_action(bp_get_groups_slug())) ? 'member_groups' : 'member';
			} elseif (bp_is_active('groups') && bp_is_group()) {
				$context = 'group';
			}

			$default_filters = array();
			foreach (bp_activity_get_actions() as $actions) {
				foreach ($actions as $action) {
					if (!in_array($context, (array) $action['context'])) {
						continue;
					}
					if (in_array($action['key'], array('friendship_accepted', 'friendship_created'))) {
						$action['key'] = 'friendship_accepted,friendship_created';
					}
					$default_filters[$action['key']] = $action['label'];
				}
			}

			foreach ($default_filters as $key => $value) {
				$labels[$key] = $labels[$key] ?? $value;
			}

			return array_reverse(array_unique(array_reverse($labels)));
		}


		/**
		 * Display tabs.
		 *
		 * @access public
		 * @since    1.0.0
		 *
		 * @param string $current Current Admin tab.
		 */
		public function bpaf_plugin_settings_tabs($current)
		{
			$bpaf_tabs = array(
				'bpaf_welcome'          => esc_html__('Welcome', 'bp-activity-filter'),
				'bpaf_display_activity' => esc_html__('Default Filter', 'bp-activity-filter'),
				'bpaf_hide_activity'    => esc_html__('Remove Activity', 'bp-activity-filter'),
				'bpaf_cpt_activity'     => esc_html__('CPT Activites', 'bp-activity-filter'),
			);

			$tab_html = '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html('Menu') . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';
			foreach ($bpaf_tabs as $bpaf_tab => $bpaf_name) {
				$class     = ($bpaf_tab == $current) ? 'nav-tab-active' : '';
				$tab_html .= '<li><a class="nav-tab ' . $class . '" id="' . esc_attr($bpaf_tab) . '" href="admin.php?page=bp_activity_filter_settings&tab=' . $bpaf_tab . '">' . $bpaf_name . '</a></li>';
			}

			$tab_html .= '</div></ul></div>';
			echo wp_kses_post($tab_html);
			$this->bpaf_include_admin_setting_tabs($current);
		}

		/**
		 * Display content according tabs
		 *
		 * @access public
		 * @since    1.0.0
		 * @param string $bpaf_tab Tabs.
		 */
		public function bpaf_include_admin_setting_tabs($bpaf_tab)
		{
			$bpaf_tab = filter_input(INPUT_GET, 'tab') ? filter_input(INPUT_GET, 'tab') : $bpaf_tab;

			switch ($bpaf_tab) {
				case 'bpaf_welcome':
					$this->bpaf_welcome_section();
					break;
				case 'bpaf_display_activity':
					$this->bpaf_display_activity_section();
					break;
				case 'bpaf_hide_activity':
					$this->bpaf_hide_activity_section();
					break;
				case 'bpaf_cpt_activity':
					$this->bpaf_cpt_activity_section();
					break;
				default:
					$this->bpaf_welcome_section();
					break;
			}
		}

		/**
		 * Display content of welcome tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bpaf_welcome_section()
		{
			if (file_exists(dirname(__FILE__) . '/bp-welcome-page.php')) {
				require_once dirname(__FILE__) . '/bp-welcome-page.php';
			}
		}

		/**
		 * Display content of Display Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bpaf_display_activity_section()
		{
			global $bp;
			$actions = (function_exists('bp_activity_get_actions_for_context')) ? bp_activity_get_actions_for_context('activity') : array();
			$labels  = array();
			foreach ($actions as $action) {
				// Friends activity collapses two filters into one.
				if (in_array($action['key'], array('friendship_accepted', 'friendship_created'))) {
					$action['key'] = 'friendship_accepted,friendship_created';
				}

				if (!array_key_exists($action['key'], $labels)) {
					$labels[$action['key']] = $action['label'];
				}
			}

			/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */
			$bp_default_activity_value = bp_get_option('bp-default-filter-name');
			$bp_hidden_filters_value   = bp_get_option('bp-hidden-filters-name');
			if (is_array($bp_hidden_filters_value) && in_array($bp_default_activity_value, $bp_hidden_filters_value)) {
				bp_update_option('bp-default-filter-name', '-1');
			}
			$bp_default_activity_value = bp_get_option('bp-default-filter-name');
			if (empty($bp_default_activity_value)) {
				$bp_default_activity_value = -1;
			}

		?>
			<div class="wbcom-tab-content">
				<div class="wbcom-wrapper-admin">
					<div class="wbcom-admin-title-section">
						<h3><?php esc_html_e('Default Filter Settings', 'bp-activity-filter'); ?></h3>
					</div>
					<div class="wbcom-welcome-head">
						<p class="description"><?php echo esc_html__('Configure default activity filters for site-wide and profile-specific activities.', 'bp-activity-filter'); ?></p>
					</div>
					<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
						<form method="post" novalidate="novalidate" id="bp_activity_filter_display_setting_form">
							<div class="filter-table form-table">
								<div class="wbcom-settings-section-wrap">
									<div class="wbcom-settings-section-options-heading wbcom-admin-title-section">
										<h3><?php esc_html_e('Site-Wide Activity Filter', 'bp-activity-filter'); ?></h3>
									</div>
									<div class="wbcom-settings-section-select">
										<p><label for="bp-default-filter-name"><?php esc_html_e('Select the default filter for site-wide activity display.', 'bp-activity-filter'); ?></label></p>
										<select id="bp-default-filter-name" name="bp-default-filter-name">
											<option value="0" <?php echo ($bp_default_activity_value == 0) ? 'selected="selected"' : ''; ?>><?php esc_html_e('Everything', 'bp-activity-filter'); ?></option>
											<?php
											foreach ($labels as $key => $value) :
												if (!empty($value)) {
													$disabled = '';
													if (!empty($bp_hidden_filters_value) && in_array($key, $bp_hidden_filters_value)) {
														$disabled = 'disabled="disabled"';
													}
													$selected = ($bp_default_activity_value == $key) ? 'selected="selected"' : '';
											?>
													<option value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($selected); ?> <?php echo esc_attr($disabled); ?>>
														<?php echo esc_html($value); ?>
													</option>
											<?php
												}
											endforeach;
											?>
										</select>
									</div>
								</div>
							</div>
							<div class="filter-table form-table">
								<?php
								/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */
								$bp_default_activity_value = bp_get_option('bp-default-profile-filter-name');
								$bp_hidden_filters_value   = bp_get_option('bp-hidden-filters-name');

								if (is_array($bp_hidden_filters_value) && in_array($bp_default_activity_value, $bp_hidden_filters_value)) {
									bp_update_option('bp-default-profile-filter-name', '-1');
								}
								$bp_default_activity_value = bp_get_option('bp-default-profile-filter-name');
								if (empty($bp_default_activity_value)) {
									$bp_default_activity_value = -1;
								}
								?>
								<div class="wbcom-settings-section-wrap">
									<div class="wbcom-settings-section-options-heading wbcom-admin-title-section">
										<h3><?php esc_html_e('Profile Activity Filter', 'bp-activity-filter'); ?></h3>
									</div>
									<div class="wbcom-settings-section-select">
										<p><label for="bp-default-profile-filter-name"><?php esc_html_e('Choose the default filter for profile-specific activity display.', 'bp-activity-filter'); ?></label></p>
										<select id="bp-default-profile-filter-name" name="bp-default-profile-filter-name">
											<option value="-1" <?php echo ($bp_default_activity_value == -1) ? 'selected="selected"' : ''; ?>><?php esc_html_e('Everything', 'bp-activity-filter'); ?></option>
											<?php
											unset($labels['new_member']);
											unset($labels['updated_profile']);
											foreach ($labels as $key => $value) :
												if (!empty($value)) {
													$disabled = '';
													if (!empty($bp_hidden_filters_value) && in_array($key, $bp_hidden_filters_value)) {
														$disabled = 'disabled="disabled"';
													}
													$selected = ($bp_default_activity_value == $key) ? 'selected="selected"' : '';
											?>
													<option value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($selected); ?> <?php echo esc_attr($disabled); ?>>
														<?php echo esc_html($value); ?>
													</option>
											<?php
												}
											endforeach;
											?>
										</select>
									</div>
								</div>
							</div>
							<div class="submit">
								<a id="bp_activity_filter_display_setting_form_submit" class="button button-primary"><?php esc_html_e('Save Settings', 'bp-activity-filter'); ?></a>
								<div class="spinner"></div>
							</div>
						</form>
					</div>
				</div>
			</div>
		<?php
		}

		/**
		 * Display content of Hide Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bpaf_hide_activity_section()
		{
			global $bp;

			// Fetch activity actions and initialize labels array.
			$activity_actions = function_exists('bp_activity_get_actions') ? bp_activity_get_actions() : array();
			$labels = array();

			// Process activity actions to populate labels.
			foreach ($activity_actions as $component => $actions) {
				foreach ($actions as $action_key => $action_values) {
					// Collapse friendship actions into one.
					if (in_array($action_key, array('friendship_accepted', 'friendship_created'))) {
						$action_key = 'friendship_accepted,friendship_created';
					}

					// Add unique action labels.
					if (!array_key_exists($action_key, $labels)) {
						$labels[$action_key] = $action_values['value'];
					}
				}
			}

			// Remove specific labels if present.
			foreach (['activity_update', 'activity_comment', 'new_blog_post', 'new_blog_comment'] as $unwanted_key) {
				if (array_key_exists($unwanted_key, $labels)) {
					unset($labels[$unwanted_key]);
				}
			}

			// Define an associative array with your new, professional labels.
			$professional_labels = [
				'new_member' => 'New Member Registered',
				'new_avatar' => 'Profile Picture Updated',
				'new_cover_photo' => 'Cover Photo Updated',
				'updated_profile' => 'Profile Updated',
				'friendship_accepted,friendship_created' => 'Friendship Status Changed',
				'friends_register_activity_action' => 'Friendship Activity Registered',
				'created_group' => 'New Group Created',
				'joined_group' => 'Joined a Group',
				'group_details_updated' => 'Group Details Updated',
				'new_group_avatar' => 'Group Avatar Updated',
				'new_group_cover_photo' => 'Group Cover Photo Updated',
				'bbp_topic_create' => 'Forum Topic Created',
				'bbp_reply_create' => 'Forum Reply Posted'
			];

			// Update the original $labels array with professional labels.
			foreach ($labels as $key => &$value) {
				if (array_key_exists($key, $professional_labels)) {
					$value = $professional_labels[$key];
				}
			}

			// Fetch hidden filters option.
			$bp_hidden_filters_value = bp_get_option('bp-hidden-filters-name');

			// Output form with checkboxes.
		?>
			<div class="wbcom-tab-content">
				<div class="wbcom-wrapper-admin">
					<div class="wbcom-admin-title-section">
						<h3><?php esc_html_e('Control Auto-Generated Activities', 'bp-activity-filter'); ?></h3>
					</div>
					<div class="wbcom-welcome-head">
						<p class="description"><?php echo esc_html__('Manage and customize the automatic generation of activities on your BuddyPress site. Easily enable or disable specific activities to tailor your community\'s experience. Checked options will be skipped in the activity newsfeed', 'bp-activity-filter'); ?></p>
					</div>
					<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
						<form method="post" novalidate="novalidate" id="bp_activity_filter_hide_setting_form">
							<div class="filter-table form-table">
								<div class="wbcom-settings-section-wrap">
									<?php foreach ($labels as $key => $value) :
										if (!empty($value)) {
											$checked = in_array($key, (array)$bp_hidden_filters_value) ? " checked='checked' " : '';
									?>
											<div class="wbcom-settings-section-remove-activity-setting">
												<input id="<?php echo esc_attr($key . '-checkbox'); ?>" name="bp-hidden-filters-name[]" type="checkbox" value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($checked); ?> />
												<label for="<?php echo esc_attr($key . '-checkbox'); ?>"><?php echo esc_html($value); ?></label>
											</div>
									<?php
										}
									endforeach; ?>
								</div>
							</div>
							<div class="submit">
								<a id="bp_activity_filter_hide_setting_form_submit" class="button button-primary"><?php esc_html_e('Save Settings', 'bp-activity-filter'); ?></a>
								<div class="spinner"></div>
							</div>
						</form>
					</div>
				</div>
			</div>
		<?php
		}

		/**
		 * Display content of Display Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bpaf_cpt_activity_section() {
			$cpt_filter_val = bp_get_option('bp-cpt-filters-settings');
		?>
			<div class="wbcom-tab-content">
				<div class="wbcom-wrapper-admin">
					<div class="wbcom-admin-title-section">
						<h3><?php echo esc_html__('BuddyPress Activity Integration', 'bp-activity-filter'); ?></h3>
					</div>
					<div class="wbcom-welcome-head">
						<p class="description"><?php echo esc_html__('Enable BuddyPress Activity Posting for selected Post Type', 'bp-activity-filter'); ?></p>
					</div>
					<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
						<form method="post" novalidate="novalidate" id="bp_activity_filter_cpt_setting_form">
		
							<table class="filter-table form-table">
								<thead>
									<th class="th-title"><?php echo esc_html__('Post Type', 'bp-activity-filter'); ?></th>
									<th class="th-title"><?php echo esc_html__('Enable/Disable', 'bp-activity-filter'); ?></th>
									<th class="th-title"><?php echo esc_html__('Name for activities', 'bp-activity-filter'); ?></th>
								</thead>
								<?php
								$args = array(
									'public'              => true,
									'_builtin'            => false,
									'exclude_from_search' => false,
								);
		
								$output   = 'objects'; // fetch objects instead of names
								$operator = 'and'; 
		
								$post_types = get_post_types($args, $output, $operator);
		
								echo '<tbody>';
		
								if (!empty($post_types) && is_array($post_types)) :
		
									foreach ($post_types as $post_type => $post_details) {
		
										$saved_settings = isset($cpt_filter_val['bpaf_admin_settings'][$post_type]) ? $cpt_filter_val['bpaf_admin_settings'][$post_type] : array();
		
										$display_type = isset($saved_settings['display_type']) ? $saved_settings['display_type'] : '';
										$value = isset($saved_settings['new_label']) ? $saved_settings['new_label'] : '';
		
								?>
		
										<tr>
											<td scope="row" data-title="Post Type">
												<label class="filter-description"><?php echo esc_html($post_details->label); ?></label>
											</td>
											<td class="filter-option" data-title="Enable/Disable">
												<input id="<?php echo esc_attr($post_type . '_radio'); ?>" 
													name="<?php echo esc_attr("bpaf_admin_settings[$post_type][display_type]"); ?>" 
													type="checkbox" 
													value="enable" 
													<?php checked($display_type, 'enable'); ?> 
												/>
											</td>
											<td class="filter-option" data-title="Name for activities">
												<input id="<?php echo esc_attr($post_type . '_text'); ?>" 
													placeholder='<?php echo esc_html(strtolower($post_details->labels->singular_name)); ?>' 
													name='<?php echo esc_attr("bpaf_admin_settings[$post_type][new_label]"); ?>' 
													type="text" 
													value="<?php echo esc_attr($value); ?>" 
												/>
											</td>
										</tr>
		
								<?php
									}
		
								else :
									echo '<div class="notice">';
									echo '<p class="description">' . esc_html__('Sorry, it seems you do not have any custom post type available to allow in the activity stream.', 'bp-activity-filter') . '</p>';
									echo '</div>';
		
								endif;
		
								?>
								</tbody>
							</table>
		
							<div class="submit">
								<a id="bp_activity_filter_cpt_setting_form_submit" class="button button-primary"><?php esc_html_e('Save Settings', 'bp-activity-filter'); ?></a>
								<div class="spinner"></div>
							</div>
						</form>
					</div>
				</div>
			</div>
		<?php
		}		

		/**
		 * Save content of Display Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_save_display_settings()
		{
			check_ajax_referer('bp_activity_filter_nonce', 'nonce', true);

			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Permission denied.', 'bp-activity-filter'));
			}

			$form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : ''; //phpcs:ignore
			parse_str($form_data, $setting_form_data);

			$bp_default_filter_name = sanitize_text_field($setting_form_data['bp-default-filter-name'] ?? '');
			$bp_default_profile_filter_name = sanitize_text_field($setting_form_data['bp-default-profile-filter-name'] ?? '');

			bp_update_option('bp-default-filter-name', $bp_default_filter_name);
			bp_update_option('bp-default-profile-filter-name', $bp_default_profile_filter_name);			
			// Delete the cookie for set the admin filter
			if ( isset( $_COOKIE['bpaf-default-filter'] ) ) {
				setcookie( 'bpaf-default-filter', '', time() - 3600, '/' );					
			}
				

			wp_send_json_success();
		}



		/**
		 * Save content of Hide Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_save_hide_settings()
		{
			check_ajax_referer('bp_activity_filter_nonce', 'nonce', true);

			if (!current_user_can('manage_options')) {
				wp_send_json_error('Permission denied.');
			}

			$form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : ''; //phpcs:ignore
			parse_str($form_data, $setting_form_data);

			$bp_hidden_filter_name = isset($setting_form_data['bp-hidden-filters-name']) ? array_map('sanitize_text_field', $setting_form_data['bp-hidden-filters-name']) : array();
			bp_update_option('bp-hidden-filters-name', $bp_hidden_filter_name);

			wp_send_json_success();
		}


		/**
		 * Save content of Custom post type Activity tab section
		 *
		 * @access public
		 * @since 1.0.0
		 */
		public function bp_activity_filter_save_cpt_settings() {
			check_ajax_referer('bp_activity_filter_nonce', 'nonce', true);

			if (!current_user_can('manage_options')) {
				wp_send_json_error('Permission denied.');
			}

			$form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : ''; //phpcs:ignore
			
			// Parse the serialized form data.
			parse_str($form_data, $cpt_settings_data);

			// Ensure that $cpt_settings_data['bpaf_admin_settings'] exists and is an array.
			if (isset($cpt_settings_data['bpaf_admin_settings']) && is_array($cpt_settings_data['bpaf_admin_settings'])) {
				// Sanitize nested array values.
				foreach ($cpt_settings_data['bpaf_admin_settings'] as $post_type => $settings) {
					$cpt_settings_data['bpaf_admin_settings'][$post_type]['display_type'] = isset($settings['display_type']) ? sanitize_text_field($settings['display_type']) : '';
					$cpt_settings_data['bpaf_admin_settings'][$post_type]['new_label'] = isset($settings['new_label']) ? sanitize_text_field($settings['new_label']) : '';
				}
			}

			// Update the option with sanitized data.
			bp_update_option('bp-cpt-filters-settings', $cpt_settings_data);

			wp_send_json_success();
		}



		/**
		 * Hide all notices from the setting page.
		 *
		 * @return void
		 */
		public function bp_activity_filter_hide_all_admin_notices_from_setting_page()
		{
			// Check if the 'page' parameter exists and is not empty
			$wbcom_setting_page = isset($_GET['page']) && !empty($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : null;

			// Only remove admin notices on specific pages
			if (!is_null($wbcom_setting_page) && in_array($wbcom_setting_page, array('wbcomplugins', 'wbcom-plugins-page', 'wbcom-support-page', 'bp_activity_filter_settings'), true)) {
				remove_all_actions('admin_notices');
				remove_all_actions('all_admin_notices');
			}
		}

	}
}


if (class_exists('WbCom_BP_Activity_Filter_Admin_Setting')) {
	$admin_setting_obj = new WbCom_BP_Activity_Filter_Admin_Setting();
}
