<?php
/**
 * Admin functionality.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 *
 * @since 4.0.0
 */
class BP_Activity_Filter_Admin {

	/**
	 * Class instance.
	 *
	 * @since 4.0.0
	 * @var BP_Activity_Filter_Admin
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 4.0.0
	 * @return BP_Activity_Filter_Admin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 */
	private function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Handle activation redirect.
	 *
	 * @since 4.0.0
	 */
	public function handle_activation_redirect() {
		// Handle force migration request
		if ( isset( $_GET['force_bp_migration'] ) && current_user_can( 'manage_options' ) ) {
			// Reset migration flags to force re-migration
			delete_option( 'bp_activity_filter_db_version' );
			delete_option( 'bp_activity_filter_migration_complete' );
			
			// Trigger migration
			$migration = new BP_Activity_Filter_Migration();
			$migration->maybe_migrate();
			
			// Redirect to remove the parameter
			$redirect_url = remove_query_arg( 'force_bp_migration' );
			wp_redirect( $redirect_url );
			exit;
		}

		// Handle emergency hidden activities fix
		if ( isset( $_GET['emergency_hidden_fix'] ) && current_user_can( 'manage_options' ) ) {
			// Get the test values from URL or use defaults
			$test_values = array( 'activity_update', 'activity_comment', 'new_member' );
			if ( isset( $_GET['values'] ) ) {
				$test_values = explode( ',', sanitize_text_field( $_GET['values'] ) );
				$test_values = array_map( 'trim', $test_values );
			}
			
			// Force save the hidden activities
			$result = update_option( 'bp_activity_filter_hidden', $test_values );
			
			// Redirect with result
			$redirect_url = remove_query_arg( array( 'emergency_hidden_fix', 'values' ) );
			$redirect_url = add_query_arg( 'hidden_fix_result', $result ? 'success' : 'failed', $redirect_url );
			wp_redirect( $redirect_url );
			exit;
		}

		// Show hidden fix result
		if ( isset( $_GET['hidden_fix_result'] ) ) {
			$result = sanitize_text_field( $_GET['hidden_fix_result'] );
			add_action( 'admin_notices', function() use ( $result ) {
				$class = 'success' === $result ? 'notice-success' : 'notice-error';
				$message = 'success' === $result 
					? 'Emergency hidden activities fix applied successfully!' 
					: 'Emergency fix failed. Check permissions.';
				echo '<div class="notice ' . $class . '"><p>' . esc_html( $message ) . '</p></div>';
			});
		}

		if ( get_transient( 'bp_activity_filter_activation_redirect' ) ) {
			delete_transient( 'bp_activity_filter_activation_redirect' );
			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'options-general.php?page=bp-activity-filter' ) );
				exit;
			}
		}
	}

	/**
	 * Add admin menu.
	 *
	 * @since 4.0.0
	 */
	public function add_admin_menu() {
		add_options_page(
			esc_html__( 'BuddyPress Activity Filter', 'bp-activity-filter' ),
			esc_html__( 'Activity Filter', 'bp-activity-filter' ),
			'manage_options',
			'bp-activity-filter',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 4.0.0
	 */
	public function register_settings() {
		register_setting(
			'bp_activity_filter_settings',
			'bp_activity_filter_default',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '0',
			)
		);

		register_setting(
			'bp_activity_filter_settings',
			'bp_activity_filter_profile_default',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '-1',
			)
		);

		register_setting(
			'bp_activity_filter_settings',
			'bp_activity_filter_hidden',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_hidden_activities' ),
				'default'           => array(),
			)
		);

		register_setting(
			'bp_activity_filter_settings',
			'bp_activity_filter_cpt_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_cpt_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize hidden activities.
	 *
	 * @since 4.0.0
	 * @param array $input Input array.
	 * @return array Sanitized array.
	 */
	public function sanitize_hidden_activities( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $input );
	}

	/**
	 * Sanitize CPT settings.
	 *
	 * @since 4.0.0
	 * @param array $input Input array.
	 * @return array Sanitized array.
	 */
	public function sanitize_cpt_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $input as $post_type => $settings ) {
			$sanitized[ sanitize_text_field( $post_type ) ] = array(
				'enabled' => isset( $settings['enabled'] ) ? (bool) $settings['enabled'] : false,
				'label'   => isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '',
			);
		}

		return $sanitized;
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 4.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_bp-activity-filter' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'bp-activity-filter-admin',
			BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			BP_ACTIVITY_FILTER_VERSION
		);

		wp_enqueue_script(
			'bp-activity-filter-admin',
			BP_ACTIVITY_FILTER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			BP_ACTIVITY_FILTER_VERSION,
			true
		);

		wp_localize_script(
			'bp-activity-filter-admin',
			'bpActivityFilterAdmin',
			array(
				'nonce' => wp_create_nonce( 'bp_activity_filter_admin' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Admin page content.
	 *
	 * @since 4.0.0
	 */
	public function admin_page() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'default';
		
		// Handle form submission
		if ( isset( $_POST['bp_activity_filter_submit'] ) && '1' === $_POST['bp_activity_filter_submit'] ) {
			$this->save_settings();
		}
		?>
		<div class="wrap bp-activity-filter-admin">
			<h1><?php esc_html_e( 'BuddyPress Activity Filter', 'bp-activity-filter' ); ?></h1>

			<?php settings_errors( 'bp_activity_filter_settings' ); ?>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=bp-activity-filter&tab=default' ) ); ?>" 
				   class="nav-tab <?php echo 'default' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Default Filters', 'bp-activity-filter' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=bp-activity-filter&tab=hidden' ) ); ?>" 
				   class="nav-tab <?php echo 'hidden' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Hidden Activities', 'bp-activity-filter' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=bp-activity-filter&tab=cpt' ) ); ?>" 
				   class="nav-tab <?php echo 'cpt' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Custom Post Types', 'bp-activity-filter' ); ?>
				</a>
			</nav>

			<form method="post" action="">
				<?php wp_nonce_field( 'bp_activity_filter_save_settings', 'bp_activity_filter_nonce' ); ?>
				<input type="hidden" name="current_tab" value="<?php echo esc_attr( $current_tab ); ?>" />
				<input type="hidden" name="bp_activity_filter_submit" value="1" />

				<?php
				switch ( $current_tab ) {
					case 'hidden':
						$this->render_hidden_activities_tab();
						break;
					case 'cpt':
						$this->render_cpt_tab();
						break;
					default:
						$this->render_default_filters_tab();
						break;
				}
				?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render default filters tab.
	 *
	 * @since 4.0.0
	 */
	private function render_default_filters_tab() {
		$default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_default', '0' );
		$profile_default_filter = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_profile_default', '-1' );
		$activity_actions = BP_Activity_Filter_Helper::get_activity_actions();

		// Check if this is a fresh install
		$migration_status = get_option( 'bp_activity_filter_migration_complete', false );
		$is_fresh_install = is_array( $migration_status ) && 'fresh_install' === $migration_status['status'];
		?>
		<h2><?php esc_html_e( 'Default Activity Filters', 'bp-activity-filter' ); ?></h2>
		<p><?php esc_html_e( 'Set the default activity filter for different contexts.', 'bp-activity-filter' ); ?></p>

		<?php if ( $is_fresh_install ) : ?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Welcome!', 'bp-activity-filter' ); ?></strong>
					<?php esc_html_e( 'This is a fresh installation. Default settings have been applied automatically.', 'bp-activity-filter' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="bp_activity_filter_default">
							<?php esc_html_e( 'Site-wide Activity Default', 'bp-activity-filter' ); ?>
						</label>
					</th>
					<td>
						<select name="bp_activity_filter_default" id="bp_activity_filter_default">
							<option value="0" <?php selected( $default_filter, '0' ); ?>>
								<?php esc_html_e( 'Everything', 'bp-activity-filter' ); ?>
							</option>
							<?php foreach ( $activity_actions as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default_filter, $key ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Default filter for the main activity stream.', 'bp-activity-filter' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="bp_activity_filter_profile_default">
							<?php esc_html_e( 'Profile Activity Default', 'bp-activity-filter' ); ?>
						</label>
					</th>
					<td>
						<select name="bp_activity_filter_profile_default" id="bp_activity_filter_profile_default">
							<option value="-1" <?php selected( $profile_default_filter, '-1' ); ?>>
								<?php esc_html_e( 'Everything', 'bp-activity-filter' ); ?>
							</option>
							<?php foreach ( $activity_actions as $key => $label ) : ?>
								<?php if ( ! in_array( $key, array( 'new_member', 'updated_profile' ), true ) ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile_default_filter, $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Default filter for user profile activity streams.', 'bp-activity-filter' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render hidden activities tab.
	 *
	 * @since 4.0.0
	 */
	private function render_hidden_activities_tab() {
		$hidden_activities = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_hidden', array() );
		$activity_actions = BP_Activity_Filter_Helper::get_activity_actions();
		?>
		<h2><?php esc_html_e( 'Hidden Activity Types', 'bp-activity-filter' ); ?></h2>
		<p><?php esc_html_e( 'Select activity types to hide from the activity stream.', 'bp-activity-filter' ); ?></p>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Hide Activity Types', 'bp-activity-filter' ); ?></th>
					<td>
						<?php if ( empty( $activity_actions ) ) : ?>
							<p class="description"><?php esc_html_e( 'No activity types available. Make sure BuddyPress is properly installed.', 'bp-activity-filter' ); ?></p>
						<?php else : ?>
							<fieldset id="bp-hidden-activities-fieldset">
								<legend class="screen-reader-text"><?php esc_html_e( 'Select activity types to hide', 'bp-activity-filter' ); ?></legend>
								<?php foreach ( $activity_actions as $key => $label ) : ?>
									<?php
									$is_checked = in_array( $key, $hidden_activities, true );
									$checkbox_id = 'bp_hidden_' . sanitize_html_class( $key );
									?>
									<label for="<?php echo esc_attr( $checkbox_id ); ?>" class="bp-activity-checkbox-label">
										<input type="checkbox" 
											   id="<?php echo esc_attr( $checkbox_id ); ?>"
											   name="bp_activity_filter_hidden[]" 
											   value="<?php echo esc_attr( $key ); ?>" 
											   <?php checked( $is_checked ); ?>
											   class="bp-activity-checkbox">
										<span class="checkbox-label-text"><?php echo esc_html( $label ); ?></span>
										<code class="activity-key"><?php echo esc_html( $key ); ?></code>
									</label><br>
								<?php endforeach; ?>
							</fieldset>
							
							<div class="hidden-activities-actions" style="margin-top: 15px;">
								<button type="button" id="select-all-hidden" class="button button-secondary">
									<?php esc_html_e( 'Select All', 'bp-activity-filter' ); ?>
								</button>
								<button type="button" id="deselect-all-hidden" class="button button-secondary">
									<?php esc_html_e( 'Deselect All', 'bp-activity-filter' ); ?>
								</button>
							</div>

							<p class="description">
								<strong><?php esc_html_e( 'Note:', 'bp-activity-filter' ); ?></strong>
								<?php esc_html_e( 'Checked activity types will be hidden from the activity stream and will not appear in the filter dropdown.', 'bp-activity-filter' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#select-all-hidden').on('click', function() {
				$('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', true);
			});
			
			$('#deselect-all-hidden').on('click', function() {
				$('#bp-hidden-activities-fieldset input[type="checkbox"]').prop('checked', false);
			});
		});
		</script>
		<?php
	}

	/**
	 * Render CPT tab.
	 *
	 * @since 4.0.0
	 */
	private function render_cpt_tab() {
		$cpt_settings = BP_Activity_Filter_Migration::get_option_with_fallback( 'bp_activity_filter_cpt_settings', array() );
		$post_types = $this->get_eligible_post_types();
		?>
		<h2><?php esc_html_e( 'Custom Post Type Activities', 'bp-activity-filter' ); ?></h2>
		<p><?php esc_html_e( 'Enable activity generation for custom post types when they are published. Only public post types with admin interface are shown.', 'bp-activity-filter' ); ?></p>

		<?php if ( empty( $post_types ) ) : ?>
			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'No eligible custom post types found.', 'bp-activity-filter' ); ?>
					<br>
					<small>
						<?php esc_html_e( 'Custom post types must be public, have admin UI enabled, and support posts to appear here.', 'bp-activity-filter' ); ?>
					</small>
				</p>
			</div>
		<?php else : ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Post Types', 'bp-activity-filter' ); ?></th>
						<td>
							<?php foreach ( $post_types as $post_type => $post_type_obj ) : ?>
								<?php
								$enabled = isset( $cpt_settings[ $post_type ]['enabled'] ) ? $cpt_settings[ $post_type ]['enabled'] : false;
								$label   = isset( $cpt_settings[ $post_type ]['label'] ) ? $cpt_settings[ $post_type ]['label'] : '';
								$post_count = wp_count_posts( $post_type );
								$total_posts = isset( $post_count->publish ) ? $post_count->publish : 0;
								?>
								<div class="cpt-setting-item" data-post-type="<?php echo esc_attr( $post_type ); ?>">
									<div class="cpt-header">
										<label class="cpt-main-label">
											<input type="checkbox" 
												   name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $post_type ); ?>][enabled]" 
												   value="1" <?php checked( $enabled ); ?>
												   class="cpt-enable-checkbox">
											<strong><?php echo esc_html( $post_type_obj->label ); ?></strong>
											<span class="cpt-meta">
												(<?php echo esc_html( $post_type ); ?>) - 
												<?php 
												printf( 
													/* translators: %d: number of posts */
													_n( '%d post', '%d posts', $total_posts, 'bp-activity-filter' ), 
													$total_posts 
												); 
												?>
											</span>
										</label>
									</div>
									
									<div class="cpt-description">
										<?php if ( ! empty( $post_type_obj->description ) ) : ?>
											<p class="description"><?php echo esc_html( $post_type_obj->description ); ?></p>
										<?php endif; ?>
									</div>

									<div class="cpt-settings">
										<label class="cpt-label-setting">
											<?php esc_html_e( 'Activity Label:', 'bp-activity-filter' ); ?>
											<input type="text" 
												   name="bp_activity_filter_cpt_settings[<?php echo esc_attr( $post_type ); ?>][label]" 
												   value="<?php echo esc_attr( $label ); ?>" 
												   placeholder="<?php echo esc_attr( strtolower( $post_type_obj->labels->singular_name ) ); ?>"
												   class="cpt-label-input">
											<br>
											<small class="description">
												<?php esc_html_e( 'Leave empty to use default label. This text will appear in activity entries.', 'bp-activity-filter' ); ?>
											</small>
										</label>

										<div class="cpt-capabilities">
											<strong><?php esc_html_e( 'Post Type Info:', 'bp-activity-filter' ); ?></strong>
											<ul class="cpt-info-list">
												<li>
													<span class="dashicons dashicons-visibility"></span>
													<?php esc_html_e( 'Public:', 'bp-activity-filter' ); ?> 
													<code><?php echo $post_type_obj->public ? esc_html__( 'Yes', 'bp-activity-filter' ) : esc_html__( 'No', 'bp-activity-filter' ); ?></code>
												</li>
												<li>
													<span class="dashicons dashicons-admin-settings"></span>
													<?php esc_html_e( 'Admin UI:', 'bp-activity-filter' ); ?> 
													<code><?php echo $post_type_obj->show_ui ? esc_html__( 'Yes', 'bp-activity-filter' ) : esc_html__( 'No', 'bp-activity-filter' ); ?></code>
												</li>
												<li>
													<span class="dashicons dashicons-menu-alt"></span>
													<?php esc_html_e( 'Menu Position:', 'bp-activity-filter' ); ?> 
													<code><?php echo $post_type_obj->show_in_menu ? esc_html__( 'Shown', 'bp-activity-filter' ) : esc_html__( 'Hidden', 'bp-activity-filter' ); ?></code>
												</li>
												<?php if ( ! empty( $post_type_obj->capability_type ) ) : ?>
													<li>
														<span class="dashicons dashicons-admin-users"></span>
														<?php esc_html_e( 'Capability:', 'bp-activity-filter' ); ?> 
														<code><?php echo esc_html( $post_type_obj->capability_type ); ?></code>
													</li>
												<?php endif; ?>
											</ul>
										</div>

										<div class="cpt-preview">
											<strong><?php esc_html_e( 'Activity Preview:', 'bp-activity-filter' ); ?></strong>
											<div class="activity-preview-text">
												<?php
												$preview_label = ! empty( $label ) ? $label : strtolower( $post_type_obj->labels->singular_name );
												printf(
													/* translators: 1: Author name, 2: Post type label, 3: Post title */
													esc_html__( '%1$s published a new %2$s: %3$s', 'bp-activity-filter' ),
													'<strong>' . esc_html__( 'John Doe', 'bp-activity-filter' ) . '</strong>',
													'<em>' . esc_html( $preview_label ) . '</em>',
													'<a href="#">' . esc_html__( 'Sample Post Title', 'bp-activity-filter' ) . '</a>'
												);
												?>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
							
							<div class="cpt-global-settings">
								<h4><?php esc_html_e( 'Global Settings', 'bp-activity-filter' ); ?></h4>
								<label>
									<input type="checkbox" 
										   name="bp_activity_filter_cpt_settings[_global][hide_sitewide]" 
										   value="1" 
										   <?php checked( isset( $cpt_settings['_global']['hide_sitewide'] ) ? $cpt_settings['_global']['hide_sitewide'] : false ); ?>>
									<?php esc_html_e( 'Hide CPT activities from site-wide activity stream', 'bp-activity-filter' ); ?>
									<br>
									<small class="description">
										<?php esc_html_e( 'When enabled, custom post type activities will only appear in author profiles, not in the main activity feed.', 'bp-activity-filter' ); ?>
									</small>
								</label>
							</div>

							<p class="description">
								<strong><?php esc_html_e( 'Note:', 'bp-activity-filter' ); ?></strong>
								<?php esc_html_e( 'Activities are created automatically when posts are published. Existing posts will not generate activities.', 'bp-activity-filter' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Get eligible post types for activity generation.
	 *
	 * @since 4.0.0
	 * @return array Eligible post types.
	 */
	private function get_eligible_post_types() {
		$post_types = get_post_types(
			array(
				'public'   => true,        // Must be public
				'_builtin' => false,       // Exclude built-in types
				'show_ui'  => true,        // Must have admin UI
			),
			'objects'
		);

		$eligible_types = array();

		foreach ( $post_types as $post_type => $post_type_obj ) {
			// Additional checks for eligibility
			if ( $this->is_post_type_eligible( $post_type_obj ) ) {
				$eligible_types[ $post_type ] = $post_type_obj;
			}
		}

		/**
		 * Filter eligible post types for activity generation.
		 *
		 * @since 4.0.0
		 *
		 * @param array $eligible_types Array of eligible post type objects.
		 */
		return apply_filters( 'bp_activity_filter_eligible_post_types', $eligible_types );
	}

	/**
	 * Check if a post type is eligible for activity generation.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post_Type $post_type_obj Post type object.
	 * @return bool
	 */
	private function is_post_type_eligible( $post_type_obj ) {
		// Must be public
		if ( ! $post_type_obj->public ) {
			return false;
		}

		// Must have admin UI
		if ( ! $post_type_obj->show_ui ) {
			return false;
		}

		// Should appear in menus (either admin menu or nav menus)
		if ( ! $post_type_obj->show_in_menu && ! $post_type_obj->show_in_nav_menus ) {
			return false;
		}

		// Should not be excluded from search (indicates it's meant to be found)
		if ( $post_type_obj->exclude_from_search ) {
			return false;
		}

		// Must support title (for meaningful activity entries)
		if ( ! post_type_supports( $post_type_obj->name, 'title' ) ) {
			return false;
		}

		// Exclude attachment and revision post types explicitly
		if ( in_array( $post_type_obj->name, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
			return false;
		}

		// Check if current user can edit posts of this type
		$post_type_caps = $post_type_obj->cap;
		if ( ! current_user_can( $post_type_caps->edit_posts ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Save settings.
	 *
	 * @since 4.0.0
	 */
	private function save_settings() {
		// Verify nonce
		if ( ! isset( $_POST['bp_activity_filter_nonce'] ) || ! wp_verify_nonce( $_POST['bp_activity_filter_nonce'], 'bp_activity_filter_save_settings' ) ) {
			add_settings_error(
				'bp_activity_filter_settings',
				'nonce_failed',
				esc_html__( 'Security check failed. Please try again.', 'bp-activity-filter' ),
				'error'
			);
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error(
				'bp_activity_filter_settings',
				'permission_denied',
				esc_html__( 'You do not have sufficient permissions to access this page.', 'bp-activity-filter' ),
				'error'
			);
			return;
		}

		$current_tab = isset( $_POST['current_tab'] ) ? sanitize_text_field( $_POST['current_tab'] ) : 'default';
		$updated = false;

		try {
			// Process settings based on current tab
			switch ( $current_tab ) {
				case 'hidden':
					$updated = $this->save_hidden_activities();
					break;
					
				case 'cpt':
					$updated = $this->save_cpt_settings();
					break;
					
				case 'default':
				default:
					$updated = $this->save_default_filters();
					break;
			}

			// Clear activity filter cookies
			$this->clear_activity_filter_cookies();

			// Show success message
			if ( $updated ) {
				add_settings_error(
					'bp_activity_filter_settings',
					'settings_updated',
					esc_html__( 'Settings saved successfully.', 'bp-activity-filter' ),
					'updated'
				);
			} else {
				add_settings_error(
					'bp_activity_filter_settings',
					'no_changes',
					esc_html__( 'No changes were made to the settings.', 'bp-activity-filter' ),
					'updated'
				);
			}

			/**
			 * Fires after settings are saved.
			 *
			 * @since 4.0.0
			 */
			do_action( 'bp_activity_filter_settings_saved' );

		} catch ( Exception $e ) {
			add_settings_error(
				'bp_activity_filter_settings',
				'save_error',
				esc_html__( 'Error saving settings: ', 'bp-activity-filter' ) . $e->getMessage(),
				'error'
			);
		}
	}

	/**
	 * Save default filters.
	 *
	 * @since 4.0.0
	 * @return bool Whether any settings were updated.
	 */
	private function save_default_filters() {
		$updated = false;

		// Save default filter
		if ( isset( $_POST['bp_activity_filter_default'] ) ) {
			$default_filter = sanitize_text_field( $_POST['bp_activity_filter_default'] );
			$old_value = get_option( 'bp_activity_filter_default' );
			
			if ( $old_value !== $default_filter ) {
				update_option( 'bp_activity_filter_default', $default_filter );
				$updated = true;
			}
		}

		// Save profile default filter
		if ( isset( $_POST['bp_activity_filter_profile_default'] ) ) {
			$profile_default = sanitize_text_field( $_POST['bp_activity_filter_profile_default'] );
			$old_value = get_option( 'bp_activity_filter_profile_default' );
			
			if ( $old_value !== $profile_default ) {
				update_option( 'bp_activity_filter_profile_default', $profile_default );
				$updated = true;
			}
		}

		return $updated;
	}

	/**
	 * Save hidden activities.
	 *
	 * @since 4.0.0
	 * @return bool Whether any settings were updated.
	 */
	private function save_hidden_activities() {
		$hidden = array();
		
		if ( isset( $_POST['bp_activity_filter_hidden'] ) && is_array( $_POST['bp_activity_filter_hidden'] ) ) {
			foreach ( $_POST['bp_activity_filter_hidden'] as $activity_key ) {
				$sanitized_key = sanitize_text_field( $activity_key );
				if ( ! empty( $sanitized_key ) ) {
					$hidden[] = $sanitized_key;
				}
			}
		}
		
		// Get old value to check if there's actually a change
		$old_hidden = get_option( 'bp_activity_filter_hidden', array() );
		
		// Check if values are different
		$is_different = ( serialize( $old_hidden ) !== serialize( $hidden ) );
		
		if ( $is_different ) {
			return update_option( 'bp_activity_filter_hidden', $hidden );
		}
		
		return false;
	}

	/**
	 * Save CPT settings.
	 *
	 * @since 4.0.0
	 * @return bool Whether any settings were updated.
	 */
	private function save_cpt_settings() {
		$old_cpt_settings = get_option( 'bp_activity_filter_cpt_settings', array() );
		$cpt_settings = array();

		if ( isset( $_POST['bp_activity_filter_cpt_settings'] ) && is_array( $_POST['bp_activity_filter_cpt_settings'] ) ) {
			foreach ( $_POST['bp_activity_filter_cpt_settings'] as $post_type => $settings ) {
				$post_type = sanitize_text_field( $post_type );
				
				if ( '_global' === $post_type ) {
					// Handle global settings
					$cpt_settings['_global'] = array(
						'hide_sitewide' => isset( $settings['hide_sitewide'] ) ? true : false,
					);
				} else {
					// Handle individual post type settings
					$cpt_settings[ $post_type ] = array(
						'enabled' => isset( $settings['enabled'] ) ? true : false,
						'label'   => isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '',
					);
				}
			}
		}
		
		// Check if settings actually changed
		$is_different = ( serialize( $old_cpt_settings ) !== serialize( $cpt_settings ) );
		
		if ( $is_different ) {
			return update_option( 'bp_activity_filter_cpt_settings', $cpt_settings );
		}
		
		// Ensure CPT settings option exists
		if ( false === get_option( 'bp_activity_filter_cpt_settings' ) ) {
			add_option( 'bp_activity_filter_cpt_settings', array() );
			return true;
		}

		return false;
	}

	/**
	 * Clear activity filter cookies.
	 *
	 * @since 4.0.0
	 */
	private function clear_activity_filter_cookies() {
		$cookies_to_clear = array(
			'bp-activity-filter',
			'bp_activity_filter_apply',
		);

		foreach ( $cookies_to_clear as $cookie ) {
			if ( isset( $_COOKIE[ $cookie ] ) ) {
				setcookie( $cookie, '', time() - 3600, '/' );
			}
		}
	}
}