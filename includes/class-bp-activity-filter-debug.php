<?php
/**
 * Debug and testing utilities.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug class for troubleshooting.
 *
 * @since 4.0.0
 */
class BP_Activity_Filter_Debug {

	/**
	 * Initialize debug features.
	 *
	 * @since 4.0.0
	 */
	public static function init() {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		add_action( 'admin_footer', array( __CLASS__, 'admin_debug_info' ) );
		add_action( 'wp_ajax_bp_activity_filter_debug_options', array( __CLASS__, 'ajax_debug_options' ) );
	}

	/**
	 * Display debug information in admin footer.
	 *
	 * @since 4.0.0
	 */
	public static function admin_debug_info() {
		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_bp-activity-filter' !== $screen->id ) {
			return;
		}

		?>
		<div id="bp-activity-filter-debug" style="margin-top: 20px; padding: 15px; background: #f0f0f0; border: 1px solid #ccc; font-family: monospace; font-size: 12px;">
			<h4 style="margin: 0 0 10px 0;">🐛 BP Activity Filter Debug Info</h4>
			
			<div style="margin-bottom: 10px;">
				<strong>Current Option Values:</strong><br>
				<div style="margin-left: 15px;">
					bp_activity_filter_default: <code><?php echo esc_html( get_option( 'bp_activity_filter_default', 'NOT SET' ) ); ?></code><br>
					bp_activity_filter_profile_default: <code><?php echo esc_html( get_option( 'bp_activity_filter_profile_default', 'NOT SET' ) ); ?></code><br>
					bp_activity_filter_hidden: <code><?php echo esc_html( print_r( get_option( 'bp_activity_filter_hidden', 'NOT SET' ), true ) ); ?></code><br>
					bp_activity_filter_cpt_settings: <code><?php echo esc_html( print_r( get_option( 'bp_activity_filter_cpt_settings', 'NOT SET' ), true ) ); ?></code>
				</div>
			</div>

			<div style="margin-bottom: 10px;">
				<strong>Legacy Option Values:</strong><br>
				<div style="margin-left: 15px;">
					bp-default-filter-name: <code><?php echo esc_html( get_option( 'bp-default-filter-name', 'NOT SET' ) ); ?></code><br>
					bp-default-profile-filter-name: <code><?php echo esc_html( get_option( 'bp-default-profile-filter-name', 'NOT SET' ) ); ?></code><br>
					bp-hidden-filters-name: <code><?php echo esc_html( print_r( get_option( 'bp-hidden-filters-name', 'NOT SET' ), true ) ); ?></code><br>
					bp-cpt-filters-settings: <code><?php echo esc_html( print_r( get_option( 'bp-cpt-filters-settings', 'NOT SET' ), true ) ); ?></code>
				</div>
			</div>

			<div style="margin-bottom: 10px;">
				<strong>Installation Type:</strong><br>
				<div style="margin-left: 15px;">
					<?php 
					$migration_status = get_option( 'bp_activity_filter_migration_complete', 'NOT SET' );
					$db_version = get_option( 'bp_activity_filter_db_version', 'NOT SET' );
					
					if ( is_array( $migration_status ) && 'fresh_install' === $migration_status['status'] ) {
						echo '<span style="color: green; font-weight: bold;">✅ FRESH INSTALL</span>';
					} elseif ( is_array( $migration_status ) && 'completed' === $migration_status['status'] ) {
						echo '<span style="color: blue; font-weight: bold;">🔄 MIGRATED FROM LEGACY</span>';
					} elseif ( '4.0.0' === $db_version ) {
						echo '<span style="color: orange; font-weight: bold;">⚠️ VERSION 4.0.0 (Status Unknown)</span>';
					} else {
						echo '<span style="color: red; font-weight: bold;">❌ NEEDS MIGRATION</span>';
					}
					?>
				</div>
			</div>

			<div style="margin-bottom: 10px;">
				<strong>Migration Status:</strong><br>
				<div style="margin-left: 15px;">
					Database Version: <code><?php echo esc_html( get_option( 'bp_activity_filter_db_version', 'NOT SET' ) ); ?></code><br>
					Migration Flag: <code><?php echo esc_html( print_r( get_option( 'bp_activity_filter_migration_complete', 'NOT SET' ), true ) ); ?></code>
				</div>
			</div>

			<div style="margin-bottom: 10px;">
				<strong>Form Debug (Last POST):</strong><br>
				<div style="margin-left: 15px;">
					<?php if ( ! empty( $_POST ) ) : ?>
						<details>
							<summary>Click to view POST data</summary>
							<pre style="margin: 5px 0; padding: 10px; background: #fff; border: 1px solid #ddd; max-height: 200px; overflow: auto;"><?php 
								$safe_post = $_POST;
								// Remove sensitive data
								unset( $safe_post['bp_activity_filter_nonce'] );
								echo esc_html( print_r( $safe_post, true ) ); 
							?></pre>
						</details>
					<?php else : ?>
						No POST data available
					<?php endif; ?>
				</div>
			</div>

			<div style="margin-bottom: 10px;">
				<strong>WordPress Info:</strong><br>
				<div style="margin-left: 15px;">
					WordPress Version: <code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code><br>
					BuddyPress Version: <code><?php echo function_exists( 'buddypress' ) ? esc_html( buddypress()->version ) : 'NOT INSTALLED'; ?></code><br>
					Plugin Version: <code><?php echo esc_html( BP_ACTIVITY_FILTER_VERSION ); ?></code><br>
					Current User Can Manage Options: <code><?php echo current_user_can( 'manage_options' ) ? 'YES' : 'NO'; ?></code>
				</div>
			</div>

			<button type="button" onclick="bpActivityFilterDebugTest()" style="padding: 5px 10px; margin-right: 10px;">Test Option Save</button>
			<button type="button" onclick="bpActivityFilterDebugTestHidden()" style="padding: 5px 10px; margin-right: 10px;">Test Hidden Save</button>
			<button type="button" onclick="bpActivityFilterForceMigration()" style="padding: 5px 10px; margin-right: 10px; background: #ff6b6b; color: white;">Force Migration</button>
			<button type="button" onclick="bpActivityFilterFixMissing()" style="padding: 5px 10px; margin-right: 10px; background: #28a745; color: white;">Fix Missing Options</button>
			<button type="button" onclick="bpActivityFilterDebugReset()" style="padding: 5px 10px;">Reset All Options</button>
		</div>

		<script>
		function bpActivityFilterDebugTest() {
			if (confirm('This will test saving a value to bp_activity_filter_default option. Continue?')) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onreadystatechange = function() {
					if (xhr.readyState === 4) {
						alert('Test result: ' + xhr.responseText);
						location.reload();
					}
				};
				xhr.send('action=bp_activity_filter_debug_options&test=save&nonce=<?php echo wp_create_nonce( 'bp_debug_nonce' ); ?>');
			}
		}

		function bpActivityFilterDebugTestHidden() {
			if (confirm('This will test saving hidden activities array. Continue?')) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onreadystatechange = function() {
					if (xhr.readyState === 4) {
						alert('Hidden test result: ' + xhr.responseText);
						location.reload();
					}
				};
				xhr.send('action=bp_activity_filter_debug_options&test=hidden&nonce=<?php echo wp_create_nonce( 'bp_debug_nonce' ); ?>');
			}
		}

		function bpActivityFilterForceMigration() {
			if (confirm('This will force re-run the migration process. This should fix the missing option values. Continue?')) {
				window.location.href = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'force_bp_migration=1';
			}
		}

		function bpActivityFilterFixMissing() {
			if (confirm('This will create any missing options with default values. Continue?')) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onreadystatechange = function() {
					if (xhr.readyState === 4) {
						alert('Fix result: ' + xhr.responseText);
						location.reload();
					}
				};
				xhr.send('action=bp_activity_filter_debug_options&test=fix_missing&nonce=<?php echo wp_create_nonce( 'bp_debug_nonce' ); ?>');
			}
		}

		function bpActivityFilterDebugReset() {
			if (confirm('This will delete ALL plugin options. Are you sure? This cannot be undone!')) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl, true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onreadystatechange = function() {
					if (xhr.readyState === 4) {
						alert('Reset result: ' + xhr.responseText);
						location.reload();
					}
				};
				xhr.send('action=bp_activity_filter_debug_options&test=reset&nonce=<?php echo wp_create_nonce( 'bp_debug_nonce' ); ?>');
			}
		}
		</script>
		<?php
	}

	/**
	 * AJAX handler for debug options.
	 *
	 * @since 4.0.0
	 */
	public static function ajax_debug_options() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'bp_debug_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$test = sanitize_text_field( $_POST['test'] );

		switch ( $test ) {
			case 'save':
				$test_value = 'test_' . time();
				$result = update_option( 'bp_activity_filter_default', $test_value );
				echo 'Test save result: ' . ( $result ? 'SUCCESS' : 'FAILED' ) . ' (value: ' . $test_value . ')';
				break;

			case 'hidden':
				$test_hidden = array( 'new_member', 'updated_profile', 'test_' . time() );
				$old_value = get_option( 'bp_activity_filter_hidden', 'not set' );
				
				// First test: direct option update
				$result1 = update_option( 'bp_activity_filter_hidden', $test_hidden );
				$new_value = get_option( 'bp_activity_filter_hidden' );
				
				// Second test: simulate form processing
				$_POST['bp_activity_filter_hidden'] = $test_hidden;
				$processed_hidden = array();
				if ( isset( $_POST['bp_activity_filter_hidden'] ) && is_array( $_POST['bp_activity_filter_hidden'] ) ) {
					foreach ( $_POST['bp_activity_filter_hidden'] as $activity_key ) {
						$sanitized_key = sanitize_text_field( $activity_key );
						if ( ! empty( $sanitized_key ) ) {
							$processed_hidden[] = $sanitized_key;
						}
					}
				}
				$result2 = update_option( 'bp_activity_filter_hidden', $processed_hidden );
				$final_value = get_option( 'bp_activity_filter_hidden' );
				
				echo 'Hidden test results:<br>';
				echo 'Direct update: ' . ( $result1 ? 'SUCCESS' : 'FAILED' ) . '<br>';
				echo 'Form simulation: ' . ( $result2 ? 'SUCCESS' : 'FAILED' ) . '<br>';
				echo 'Old value: ' . print_r( $old_value, true ) . '<br>';
				echo 'Test value: ' . print_r( $test_hidden, true ) . '<br>';
				echo 'Processed value: ' . print_r( $processed_hidden, true ) . '<br>';
				echo 'Final DB value: ' . print_r( $final_value, true );
				break;

			case 'fix_missing':
				$required_options = array(
					'bp_activity_filter_default'         => '0',
					'bp_activity_filter_profile_default' => '-1',
					'bp_activity_filter_hidden'          => array(),
					'bp_activity_filter_cpt_settings'    => array(),
				);

				$fixed = 0;
				$created = array();
				
				foreach ( $required_options as $option => $default_value ) {
					if ( false === get_option( $option ) ) {
						$result = add_option( $option, $default_value );
						if ( $result ) {
							$fixed++;
							$created[] = $option;
						}
					}
				}
				
				echo 'Fix missing options result: Fixed ' . $fixed . ' options. ';
				if ( ! empty( $created ) ) {
					echo 'Created: ' . implode( ', ', $created );
				} else {
					echo 'No missing options found.';
				}
				break;

			case 'reset':
				$options = array(
					'bp_activity_filter_default',
					'bp_activity_filter_profile_default',
					'bp_activity_filter_hidden',
					'bp_activity_filter_cpt_settings',
					'bp_activity_filter_db_version',
					'bp_activity_filter_migration_complete',
				);

				$deleted = 0;
				foreach ( $options as $option ) {
					if ( delete_option( $option ) ) {
						$deleted++;
					}
				}
				echo 'Reset complete. Deleted ' . $deleted . ' options.';
				break;

			default:
				echo 'Unknown test: ' . $test;
		}

		wp_die();
	}

	/**
	 * Log debug message with context.
	 *
	 * @since 4.0.0
	 *
	 * @param string $message Debug message.
	 * @param array  $context Additional context.
	 */
	public static function log( $message, $context = array() ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_message = '[BP Activity Filter Debug] ' . $message;
		
		if ( ! empty( $context ) ) {
			$log_message .= ' | Context: ' . print_r( $context, true );
		}

		error_log( $log_message );
	}

	/**
	 * Check if current request is plugin admin page.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	public static function is_plugin_admin_page() {
		return isset( $_GET['page'] ) && 'bp-activity-filter' === $_GET['page'];
	}

	/**
	 * Get all plugin options as array.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public static function get_all_plugin_options() {
		return array(
			'new_options' => array(
				'bp_activity_filter_default' => get_option( 'bp_activity_filter_default' ),
				'bp_activity_filter_profile_default' => get_option( 'bp_activity_filter_profile_default' ),
				'bp_activity_filter_hidden' => get_option( 'bp_activity_filter_hidden' ),
				'bp_activity_filter_cpt_settings' => get_option( 'bp_activity_filter_cpt_settings' ),
			),
			'legacy_options' => array(
				'bp-default-filter-name' => get_option( 'bp-default-filter-name' ),
				'bp-default-profile-filter-name' => get_option( 'bp-default-profile-filter-name' ),
				'bp-hidden-filters-name' => get_option( 'bp-hidden-filters-name' ),
				'bp-cpt-filters-settings' => get_option( 'bp-cpt-filters-settings' ),
			),
			'migration' => array(
				'db_version' => get_option( 'bp_activity_filter_db_version' ),
				'migration_complete' => get_option( 'bp_activity_filter_migration_complete' ),
			),
		);
	}
}

// Initialize debug features if WP_DEBUG is enabled.
BP_Activity_Filter_Debug::init();