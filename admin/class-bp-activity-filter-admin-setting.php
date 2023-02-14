<?php
/**
 * Defining class if not exist for admin setting
 *
 *  @package BuddyPress_Activity_Filter
 */

if ( ! class_exists( 'WbCom_BP_Activity_Filter_Admin_Setting' ) ) {
	/**
	 * Defining class if not exist for admin setting
	 *
	 *  @package BuddyPress_Activity_Filter
	 */
	class WbCom_BP_Activity_Filter_Admin_Setting {

		/**
		 * Constructor
		 */
		public function __construct() {

			/**
			 * You need to hook bp_register_admin_settings to register your settings
			 */
			add_action( 'admin_menu', array( &$this, 'bp_activity_filter_admin_menu' ), 100 );
			add_action( 'network_admin_menu', array( &$this, 'bp_activity_filter_admin_menu' ), 100 );

			add_action( 'wp_ajax_bp_activity_filter_save_display_settings', array( $this, 'bp_activity_filter_save_display_settings' ) );

			add_action( 'wp_ajax_bp_activity_filter_save_hide_settings', array( $this, 'bp_activity_filter_save_hide_settings' ) );

			add_action( 'wp_ajax_bp_activity_filter_save_cpt_settings', array( $this, 'bp_activity_filter_save_cpt_settings' ) );
			add_action( 'in_admin_header', array( $this, 'bp_activity_filter_hide_all_admin_notices_from_setting_page' ) );

		}

		/**
		 * BP Share activity filter
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_admin_menu() {
			if ( is_network_admin() ) {
				$admin_url = 'network/admin.php?page=bp_activity_filter_settings';
			} else {
				$admin_url = 'admin.php?page=bp_activity_filter_settings';
			}

			if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
				add_menu_page( esc_html__( 'WB Plugins', 'bp-activity-filter' ), esc_html__( 'WB Plugins', 'bp-activity-filter' ), 'manage_options', 'wbcomplugins', array( $this, 'bp_activity_filter_section_settings' ), 'dashicons-lightbulb', 59 );
				add_submenu_page( 'wbcomplugins', esc_html__( 'General', 'bp-activity-filter' ), esc_html__( 'General', 'bp-activity-filter' ), 'manage_options', 'wbcomplugins' );
			}
			add_submenu_page( 'wbcomplugins', esc_html__( 'BP Activity Filter', 'bp-activity-filter' ), esc_html__( 'BP Activity Filter', 'bp-activity-filter' ), 'manage_options', 'bp_activity_filter_settings', array( $this, 'bp_activity_filter_section_settings' ) );
		}

		/**
		 * Settings page content
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_section_settings() {
			$tab = filter_input( INPUT_GET, 'tab' ) ? filter_input( INPUT_GET, 'tab' ) : 'bpaf_welcome';
			?>
			<div class="wrap">
				<div class="wbcom-bb-plugins-offer-wrapper">
					<div id="wb_admin_logo">
						<a href="https://wbcomdesigns.com/downloads/buddypress-community-bundle/" target="_blank">
							<img src="<?php echo esc_url( BP_ACTIVITY_FILTER_PLUGIN_URL ) . 'admin/wbcom/assets/imgs/wbcom-offer-notice.png'; ?>">
						</a>
					</div>
				</div>
				<div class="wbcom-wrap">
					<div class="blpro-header">
						<div class="wbcom_admin_header-wrapper">
							<div id="wb_admin_plugin_name">
								<?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?>
								<span><?php printf( __( 'Version %s', 'bp-activity-filter' ), BP_ACTIVITY_FILTER_PLUGIN_VERSION ); ?></span>
							</div>
							<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
						</div>
					</div>
					<div id="bpaf_setting_error_settings_updated" class="updated settings-error notice is-dismissible" style="display:none;">
						<p><strong><?php esc_html_e( 'Settings saved.', 'bp-activity-filter' ); ?></strong></p>
						<button type="button" class="notice-dismiss">
							<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'bp-activity-filter' ); ?></span>
						</button>
					</div>
					<div class="wbcom-admin-settings-page">
						<?php $this->bpaf_plugin_settings_tabs( $tab ); ?>
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
		public function bpaf_get_labels() {
			/* Argument to pass in callback */
			$filter_actions = buddypress()->activity->actions;
			$actions        = array();
			foreach ( get_object_vars( $filter_actions ) as $property => $value ) {
				$actions[] = $property;
			}
			$labels = array();
			foreach ( $actions as $key => $value ) {
				foreach ( get_object_vars( $filter_actions->$value ) as $prop => $val ) {
					if ( ! empty( $val['label'] ) ) {
						$labels [ $val['key'] ] = $val ['label'];
					} else {
						$labels [ $val['key'] ] = $val ['value'];
					}
				}
			}

			// On member pages, default to 'member', unless this is a user's Groups activity.

			$context = '';
			if ( bp_is_user() ) {
				if ( bp_is_active( 'groups' ) && bp_is_current_action( bp_get_groups_slug() ) ) {
					$context = 'member_groups';
				} else {
					$context = 'member';
				}

				// On individual group pages, default to 'group'.
			} elseif ( bp_is_active( 'groups' ) && bp_is_group() ) {
				$context = 'group';
				// 'activity' everywhere else.
			} else {
				$context = 'activity';
			}

			$default_filters = array();
			// Walk through the registered actions, and prepare an the select box options.

			foreach ( bp_activity_get_actions() as $actions ) {
				foreach ( $actions as $action ) {
					if ( ! in_array( $context, (array) $action['context'] ) ) {
						continue;
					}

					// Friends activity collapses two filters into one.

					if ( in_array( $action['key'], array( 'friendship_accepted', 'friendship_created' ) ) ) {
						$action['key'] = 'friendship_accepted,friendship_created';
					}
					$default_filters[ $action['key'] ] = $action['label'];
				}
			}

			foreach ( $default_filters as $key => $value ) {
				if ( ! array_key_exists( $key, $labels ) ) {
					$labels[ $key ] = $value;
				}
			}

			$labels = array_reverse( array_unique( array_reverse( $labels ) ) );
			$labels = array_reverse( $labels );
			return $labels;
		}

		/**
		 * Display tabs.
		 *
		 * @access public
		 * @since    1.0.0
		 *
		 * @param string $current Current Admin tab.
		 */
		public function bpaf_plugin_settings_tabs( $current ) {
			$bpaf_tabs = array(
				'bpaf_welcome'          => esc_html__( 'Welcome', 'bp-activity-filter' ),
				'bpaf_display_activity' => esc_html__( 'Default Filter', 'bp-activity-filter' ),
				'bpaf_hide_activity'    => esc_html__( 'Remove Activity', 'bp-activity-filter' ),
				'bpaf_cpt_activity'     => esc_html__( 'CPT Activites', 'bp-activity-filter' ),
			);

			$tab_html = '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html( 'Menu' ) . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';
			foreach ( $bpaf_tabs as $bpaf_tab => $bpaf_name ) {
				$class     = ( $bpaf_tab == $current ) ? 'nav-tab-active' : '';
				$tab_html .= '<li><a class="nav-tab ' . $class . '" id="' . esc_attr( $bpaf_tab ) . '" href="admin.php?page=bp_activity_filter_settings&tab=' . $bpaf_tab . '">' . $bpaf_name . '</a></li>';
			}

			$tab_html .= '</div></ul></div>';
			echo wp_kses_post( $tab_html );
			$this->bpaf_include_admin_setting_tabs( $current );
		}

		/**
		 * Display content according tabs
		 *
		 * @access public
		 * @since    1.0.0
		 * @param string $bpaf_tab Tabs.
		 */
		public function bpaf_include_admin_setting_tabs( $bpaf_tab ) {
			$bpaf_tab = filter_input( INPUT_GET, 'tab' ) ? filter_input( INPUT_GET, 'tab' ) : $bpaf_tab;

			switch ( $bpaf_tab ) {
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
		public function bpaf_welcome_section() {
			if ( file_exists( dirname( __FILE__ ) . '/bp-welcome-page.php' ) ) {
				require_once dirname( __FILE__ ) . '/bp-welcome-page.php';
			}
		}

		/**
		 * Display content of Display Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bpaf_display_activity_section() {
			global $bp;
			$actions = ( function_exists( 'bp_activity_get_actions_for_context' ) ) ? bp_activity_get_actions_for_context( 'activity' ) : array();
			$labels  = array();
			foreach ( $actions as $action ) {
				// Friends activity collapses two filters into one.
				if ( in_array( $action['key'], array( 'friendship_accepted', 'friendship_created' ) ) ) {
					$action['key'] = 'friendship_accepted,friendship_created';
				}

				if ( ! array_key_exists( $action['key'], $labels ) ) {
					$labels[ $action['key'] ] = $action['label'];
				}
			}

			/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */
			$bp_default_activity_value = bp_get_option( 'bp-default-filter-name' );
			$bp_hidden_filters_value   = bp_get_option( 'bp-hidden-filters-name' );
			if ( is_array( $bp_hidden_filters_value ) && in_array( $bp_default_activity_value, $bp_hidden_filters_value ) ) {
				bp_update_option( 'bp-default-filter-name', '-1' );
			}
			$bp_default_activity_value = bp_get_option( 'bp-default-filter-name' );
			if ( empty( $bp_default_activity_value ) ) {
				$bp_default_activity_value = -1;
			}

			?>
			<div class="wbcom-tab-content">
				<div class="wbcom-wrapper-admin">
					<div class="wbcom-admin-title-section">
						<h3><?php esc_html_e( 'Default Filter Settings', 'bp-activity-filter' ); ?></h3>
					</div>
					<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
						<form method="post" novalidate="novalidate" id="bp_activity_filter_display_setting_form">												
							<div class="filter-table form-table">
								<div class="wbcom-settings-section-wrap">
									<div class="wbcom-settings-section-options-heading wbcom-admin-title-section">
										<h3><?php esc_html_e( 'Apply Default Filter on Activity Page', 'bp-activity-filter' ); ?></h3>
									</div>	
									<div class="wbcom-settings-section-radio">										
										<input id="bp-activity-filter-everything-radio" name="bp-default-filter-name" type="radio" value="-1"  <?php echo ( $bp_default_activity_value == -1 ) ? 'checked=checked' : ' '; ?>/>
										<label><?php esc_html_e( 'Everything', 'bp-activity-filter' ); ?></label>
									</div>								
								<?php
								foreach ( $labels as $key => $value ) :
									if ( ! empty( $value ) ) {
										$hide_active = '';
										if ( ! empty( $bp_hidden_filters_value ) ) {
											if ( in_array( $key, $bp_hidden_filters_value ) ) {
												$hide_active = " disabled = 'disabled' ";
											}
										}

										$checked = '';
										if ( ( $bp_default_activity_value == $key ) ) {
											$checked = " checked='checked' ";
										}
										?>
										<div class="wbcom-settings-section-radio">
											<input id="<?php echo esc_attr( $key . '_radio' ); ?>" name="bp-default-filter-name" type="radio" value="<?php echo esc_attr( $key ); ?>"
												<?php
												echo esc_html( $checked );
												echo esc_html( $hide_active );
												?>
											/>
											<label>
												<?php esc_html_e( $value, 'bp-activity-filter' ); ?>
											</label>
										</div>
										<?php
									}
								endforeach;
								?>
							</div>

							<div class="filter-table form-table" >
							<?php
							/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */
							$bp_default_activity_value = bp_get_option( 'bp-default-profile-filter-name' );
							$bp_hidden_filters_value   = bp_get_option( 'bp-hidden-filters-name' );

							if ( is_array( $bp_hidden_filters_value ) && in_array( $bp_default_activity_value, $bp_hidden_filters_value ) ) {
								bp_update_option( 'bp-default-profile-filter-name', '-1' );
							}
							$bp_default_activity_value = bp_get_option( 'bp-default-profile-filter-name' );
							if ( empty( $bp_default_activity_value ) ) {
								$bp_default_activity_value = -1;
							}
							?>
								<div class="wbcom-settings-section-wrap">
									<div class="wbcom-settings-section-options-heading wbcom-admin-title-section">
										<h3><?php esc_html_e( 'Apply Default Filter on Profile Activity Page', 'bp-activity-filter' ); ?></h3>									
									</div>
									<div class="wbcom-settings-section-radio">
										<input id="bp-activity-filter-everything-radio" name="bp-default-profile-filter-name" type="radio" value="-1"  <?php echo ( $bp_default_activity_value == -1 ) ? 'checked=checked' : ' '; ?>/>
										<label><?php esc_html_e( 'Everything', 'bp-activity-filter' ); ?></label>
									</div>
								<?php
								unset( $labels['new_member'] );
								unset( $labels['updated_profile'] );
								foreach ( $labels as $key => $value ) :
									if ( ! empty( $value ) ) {
										$hide_active = '';
										if ( ! empty( $bp_hidden_filters_value ) ) {
											if ( in_array( $key, $bp_hidden_filters_value ) ) {
												$hide_active = " disabled = 'disabled' ";
											}
										}
										$checked = '';
										if ( ( $bp_default_activity_value == $key ) ) {
											$checked = " checked='checked' ";
										}
										?>
										<div class="wbcom-settings-section-radio">
											<input id="<?php echo esc_attr( $key . '_profile_radio' ); ?>" name="bp-default-profile-filter-name" type="radio" value="<?php echo esc_attr( $key ); ?>"
												<?php
												echo esc_html( $checked );
												echo esc_html( $hide_active );
												?>
											/>				
											<label>
												<?php echo esc_html( $value ); ?>
											</label>							
										</div>
										<?php
									}
								endforeach;
								?>
							</div>
							<div class="submit">
								<a id="bp_activity_filter_display_setting_form_submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'bp-activity-filter' ); ?></a>
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
		public function bpaf_hide_activity_section() {

			global $bp;

			$activity_actions = ( function_exists( 'bp_activity_get_actions' ) ) ? bp_activity_get_actions() : array();
			$labels           = array();
			foreach ( $activity_actions as $component => $actions ) {
				foreach ( $actions as $action_key => $action_values ) {
					// Friends activity collapses two filters into one.
					if ( in_array( $action_key, array( 'friendship_accepted', 'friendship_created' ) ) ) {
						$action_key = 'friendship_accepted,friendship_created';
					}

					if ( ! array_key_exists( $action_key, $labels ) ) {
						$labels[ $action_key ] = $action_values['value'];
					}

					if ( array_key_exists( 'activity_update', $labels ) || array_key_exists( 'activity_comment', $labels ) ) {
						unset( $labels['activity_update'] );
						unset( $labels['activity_comment'] );

					}
				}
			}

			/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */
			$bp_hidden_filters_value = bp_get_option( 'bp-hidden-filters-name' );

			?>
			<div class="wbcom-tab-content">
				<div class="wbcom-wrapper-admin">
					<div class="wbcom-admin-title-section">
						<h3><?php esc_html_e( 'Remove Activity Settings', 'bp-activity-filter' ); ?></h3>
					</div>
					<div class="wbcom-welcome-head">
						<p class="description"><?php echo esc_html__( 'Any checked activity type will not be recorded as a new activity. ', 'bp-activity-filter' ); ?></p>
					</div>
					<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
						<form method="post" novalidate="novalidate" id="bp_activity_filter_hide_setting_form" >
							<div class="filter-table form-table" >
								<div class="wbcom-settings-section-wrap">
								<?php
								foreach ( $labels as $key => $value ) :
									if ( ! empty( $value ) ) {
										$checked = '';
										if ( ( ( ! empty( $bp_hidden_filters_value ) && is_array( $bp_hidden_filters_value ) ) && in_array( $key, $bp_hidden_filters_value ) ) ) {
											$checked = " checked='checked' ";
										}
										?>
										
											<div class="wbcom-settings-section-remove-activity-setting">
												<input id="<?php echo esc_attr( $key . '-checkbox' ); ?>" name="bp-hidden-filters-name[]" type="checkbox" value="<?php echo esc_attr( $key ); ?>"<?php echo esc_html( $checked ); ?> />
												<label><?php echo esc_html( $value ); ?></label>											
											</div>
										<?php
									}
								endforeach;
								?>
							</div>
							</div>
							<div class="submit">
								<a id="bp_activity_filter_hide_setting_form_submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'bp-activity-filter' ); ?></a>
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

			$cpt_filter_val = bp_get_option( 'bp-cpt-filters-settings' );
			?>
			<div class="wbcom-tab-content">
				<div class="wbcom-wrapper-admin">
					<div class="wbcom-admin-title-section">
						<h3>
							<?php echo esc_html__( 'Enable Post Type Activites', 'bp-activity-filter' ); ?>
						</h3>
					</div>
					<div class="wbcom-admin-option-wrap wbcom-admin-option-wrap-view">
						<form method="post" novalidate="novalidate" id="bp_activity_filter_cpt_setting_form" >

							<table class="filter-table form-table" >
								<thead>
								<th class="th-title"><?php echo esc_html__( 'Post Type', 'bp-activity-filter' ); ?></th>
								<th class="th-title"><?php echo esc_html__( 'Enable/Disable', 'bp-activity-filter' ); ?></th>
								<th class="th-title"><?php echo esc_html__( 'Name for activities', 'bp-activity-filter' ); ?></th>
								</thead>
						<?php
						$args = array(
							'public'              => true,
							'_builtin'            => false,
							'exclude_from_search' => false,
						);

						$output   = 'names'; // names or objects, note names is the default.
						$operator = 'and'; // 'and' or 'or'

						$post_types = get_post_types( $args, $output, $operator );

						echo '<tbody>';

						if ( ! empty( $post_types ) && is_array( $post_types ) ) :

							foreach ( $post_types as $post_type ) {

								$post_details = get_post_type_object( $post_type );

								if ( ! empty( $cpt_filter_val ) ) {
									$saved_settings = ( isset( $cpt_filter_val['bpaf_admin_settings'][ $post_type ] ) ) ? $cpt_filter_val['bpaf_admin_settings'][ $post_type ] : array();
								}

								if ( ! empty( $saved_settings ) && array_key_exists( 'display_type', $saved_settings ) ) {
									$display_type = $saved_settings['display_type'];
								} else {
									$display_type = '';
								}

								if ( ! empty( $saved_settings ) && array_key_exists( 'group', $saved_settings ) ) {

									$group = $saved_settings['group'];
								} else {

									$group = '';
								}

								if ( isset( $saved_settings['new_label'] ) ) {
									$value = $saved_settings['new_label'];
								} else {
									$value = '';
								}
								?>

									<tr>

										<td scope="row" data-title="Post Type"><label class="filter-description" ><?php echo esc_html( $post_details->label ); ?></label></td>
										<td class="filter-option" data-title="Enable/Disable">
											<input id="<?php echo esc_attr( $post_type . '_radio' ); ?>" name="<?php echo esc_attr( "bpaf_admin_settings[$post_type][display_type]" ); ?>" type="checkbox" value="enable" <?php checked( $display_type, 'enable' ); ?> />
										</td>
										<td class="filter-option" data-title="Upload Lable">
											<input id="<?php echo esc_attr( $post_type . '_text' ); ?>" placeholder='<?php echo esc_attr( "$post_type" );?> <?php esc_html_e( 'published', 'bp-activity-filter' ); ?>' name='<?php echo esc_attr( "bpaf_admin_settings[$post_type][new_label]" ); ?>' type="text" value="<?php echo esc_attr( $value ); ?>" />
										</td>
									</tr>

								<?php
							}

						else :
							echo '<div class="notice">';
							echo '<p class="description">' . esc_html__( 'Sorry, it seems you do not have any custom post type available to allow in the activity stream.', 'bp-activity-filter' ) . '</p>';
							echo '</div>';

						endif;

						?>
								</tbody>
							</table>

							<div class="submit">
								<a id="bp_activity_filter_cpt_setting_form_submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'bp-activity-filter' ); ?></a>
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
		public function bp_activity_filter_save_display_settings() {
			// Check for nonce security.
			$admin_nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $admin_nonce, 'bp_activity_filter_nonce' ) ) {
				die( 'Busted!' );
			}
			$form_data = isset( $_POST['form_data'] ) ? sanitize_text_field( wp_unslash( $_POST['form_data'] ) ) : '';
			parse_str( $form_data, $setting_form_data );

			$form_details = filter_var_array( $setting_form_data, FILTER_SANITIZE_STRING );

			$bp_default_filter_name = $form_details['bp-default-filter-name'];

			$bp_default_profile_filter_name = $form_details['bp-default-profile-filter-name'];

			bp_update_option( 'bp-default-filter-name', $bp_default_filter_name );

			bp_update_option( 'bp-default-profile-filter-name', $bp_default_profile_filter_name );

			wp_die();
		}

		/**
		 * Save content of Hide Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_save_hide_settings() {
			$admin_nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $admin_nonce, 'bp_activity_filter_nonce' ) ) {
				die( 'Busted!' );
			}
			$form_data = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			parse_str( $form_data, $setting_form_data );

			$form_details = filter_var_array( $setting_form_data, FILTER_SANITIZE_STRING );

			$bp_hidden_filter_name = ( isset( $form_details['bp-hidden-filters-name'] ) ) ? $form_details['bp-hidden-filters-name'] : array();
			bp_update_option( 'bp-hidden-filters-name', $bp_hidden_filter_name );

			wp_die();
		}

		/**
		 * Save content of Custom post type Activity tab section
		 *
		 * @access public
		 * @since    1.0.0
		 */
		public function bp_activity_filter_save_cpt_settings() {
			$admin_nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $admin_nonce, 'bp_activity_filter_nonce' ) ) {
				die( 'Busted!' );
			}
			$form_data = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			parse_str( $form_data, $cpt_settings_data );

			$cpt_settings_details = filter_var_array( $cpt_settings_data, FILTER_SANITIZE_STRING );
			bp_update_option( 'bp-cpt-filters-settings', $cpt_settings_details );

			wp_die();
		}

		/**
		 * Hide all notices from the setting page.
		 *
		 * @return void
		 */
		public function bp_activity_filter_hide_all_admin_notices_from_setting_page() {
			$wbcom_pages_array  = array( 'wbcomplugins', 'wbcom-plugins-page', 'wbcom-support-page', 'bp_activity_filter_settings' );
			$wbcom_setting_page = filter_input( INPUT_GET, 'page' ) ? filter_input( INPUT_GET, 'page' ) : '';

			if ( in_array( $wbcom_setting_page, $wbcom_pages_array, true ) ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}

		}

	}

}



if ( class_exists( 'WbCom_BP_Activity_Filter_Admin_Setting' ) ) {
	$admin_setting_obj = new WbCom_BP_Activity_Filter_Admin_Setting();
}
