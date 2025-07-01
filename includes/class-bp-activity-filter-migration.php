<?php
/**
 * Migration and backward compatibility handler.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration class for handling version upgrades and backward compatibility.
 *
 * @since 4.0.0
 */
class BP_Activity_Filter_Migration {

	/**
	 * Current plugin version.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private $current_version;

	/**
	 * Database version option key.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private $db_version_key = 'bp_activity_filter_db_version';

	/**
	 * Migration flag option key.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	private $migration_flag_key = 'bp_activity_filter_migration_complete';

	/**
	 * Legacy option mappings.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	private $legacy_option_mappings = array(
		// Old option => New option
		'bp-default-filter-name'         => 'bp_activity_filter_default',
		'bp-default-profile-filter-name' => 'bp_activity_filter_profile_default',
		'bp-hidden-filters-name'         => 'bp_activity_filter_hidden',
		'bp-cpt-filters-settings'        => 'bp_activity_filter_cpt_settings',
	);

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		$this->current_version = BP_ACTIVITY_FILTER_VERSION;
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 4.0.0
	 */
	private function setup_hooks() {
		add_action( 'admin_init', array( $this, 'maybe_migrate' ) );
		add_action( 'admin_notices', array( $this, 'show_migration_notice' ) );
		add_action( 'wp_ajax_bp_activity_filter_dismiss_migration_notice', array( $this, 'dismiss_migration_notice' ) );
	}

	/**
	 * Check if migration is needed and run it.
	 *
	 * @since 4.0.0
	 */
	public function maybe_migrate() {
		$db_version = get_option( $this->db_version_key, '0' );
		
		// If this is a fresh install, set current version and skip migration
		if ( '0' === $db_version && ! $this->has_legacy_options() ) {
			update_option( $this->db_version_key, $this->current_version );
			update_option( $this->migration_flag_key, 'fresh_install' );
			return;
		}

		// Check if we need to force migration (for debugging or manual trigger)
		$force_migration = isset( $_GET['force_bp_migration'] ) && current_user_can( 'manage_options' );

		// If migration already completed for this version and not forcing, skip
		if ( version_compare( $db_version, $this->current_version, '>=' ) && ! $force_migration ) {
			return;
		}

		// Run migration
		$this->run_migration( $db_version );
	}

	/**
	 * Check if legacy options exist.
	 *
	 * @since 4.0.0
	 * @return bool
	 */
	private function has_legacy_options() {
		foreach ( array_keys( $this->legacy_option_mappings ) as $legacy_option ) {
			if ( false !== get_option( $legacy_option ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Run the migration process.
	 *
	 * @since 4.0.0
	 * @param string $from_version Version migrating from.
	 */
	private function run_migration( $from_version ) {
		// Log migration start
		BP_Activity_Filter_Helper::log( "Starting migration from version {$from_version} to {$this->current_version}" );

		$migration_data = array(
			'from_version' => $from_version,
			'to_version'   => $this->current_version,
			'timestamp'    => current_time( 'mysql' ),
			'status'       => 'started',
			'migrated_options' => array(),
		);

		try {
			// Step 1: Migrate legacy options
			$this->migrate_legacy_options( $migration_data );

			// Step 2: Migrate CPT settings format
			$this->migrate_cpt_settings( $migration_data );

			// Step 3: Ensure all required options exist with defaults
			$this->ensure_required_options_exist( $migration_data );

			// Step 4: Clean up deprecated options (only in major versions)
			if ( version_compare( $from_version, '4.0.0', '<' ) ) {
				$this->cleanup_deprecated_options( $migration_data );
			}

			// Step 5: Update database version
			update_option( $this->db_version_key, $this->current_version );
			
			$migration_data['status'] = 'completed';
			update_option( $this->migration_flag_key, $migration_data );

			// Log success
			BP_Activity_Filter_Helper::log( 'Migration completed successfully' );

		} catch ( Exception $e ) {
			$migration_data['status'] = 'failed';
			$migration_data['error'] = $e->getMessage();
			update_option( $this->migration_flag_key, $migration_data );

			// Log error
			BP_Activity_Filter_Helper::log( 'Migration failed: ' . $e->getMessage(), 'error' );
		}
	}

	/**
	 * Ensure all required options exist with defaults.
	 *
	 * @since 4.0.0
	 * @param array $migration_data Migration tracking data.
	 */
	private function ensure_required_options_exist( &$migration_data ) {
		$required_options = array(
			'bp_activity_filter_default'         => '0',
			'bp_activity_filter_profile_default' => '-1',
			'bp_activity_filter_hidden'          => array(),
			'bp_activity_filter_cpt_settings'    => array(),
		);

		foreach ( $required_options as $option => $default_value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $default_value );
				$migration_data['created_missing_options'][ $option ] = $default_value;
				BP_Activity_Filter_Helper::log( "Created missing option: {$option} with default value" );
			}
		}
	}

	/**
	 * Migrate legacy options to new format.
	 *
	 * @since 4.0.0
	 * @param array $migration_data Migration tracking data.
	 */
	private function migrate_legacy_options( &$migration_data ) {
		foreach ( $this->legacy_option_mappings as $legacy_key => $new_key ) {
			$legacy_value = get_option( $legacy_key );
			
			if ( false === $legacy_value ) {
				BP_Activity_Filter_Helper::log( "Legacy option {$legacy_key} does not exist, skipping" );
				continue; // Option doesn't exist
			}

			// Check if new option already has a value (don't overwrite manual settings)
			$existing_new_value = get_option( $new_key );
			if ( false !== $existing_new_value && ! empty( $existing_new_value ) ) {
				BP_Activity_Filter_Helper::log( "New option {$new_key} already has value, skipping migration from {$legacy_key}" );
				continue;
			}

			// Migrate the value with any necessary transformations
			$migrated_value = $this->transform_option_value( $legacy_key, $legacy_value );
			
			$update_result = update_option( $new_key, $migrated_value );
			$migration_data['migrated_options'][ $legacy_key ] = array(
				'new_key' => $new_key,
				'old_value' => $legacy_value,
				'new_value' => $migrated_value,
				'success' => $update_result
			);

			BP_Activity_Filter_Helper::log( "Migrated option: {$legacy_key} -> {$new_key}, success: " . ( $update_result ? 'YES' : 'NO' ) );
		}
	}

	/**
	 * Transform option values during migration if needed.
	 *
	 * @since 4.0.0
	 * @param string $legacy_key Legacy option key.
	 * @param mixed  $value Option value.
	 * @return mixed Transformed value.
	 */
	private function transform_option_value( $legacy_key, $value ) {
		switch ( $legacy_key ) {
			case 'bp-cpt-filters-settings':
				return $this->transform_cpt_settings( $value );
			
			case 'bp-hidden-filters-name':
				// Ensure it's an array
				return is_array( $value ) ? $value : array();
			
			default:
				return $value;
		}
	}

	/**
	 * Transform CPT settings to new format.
	 *
	 * @since 4.0.0
	 * @param mixed $value CPT settings value.
	 * @return array Transformed CPT settings.
	 */
	private function transform_cpt_settings( $value ) {
		if ( ! is_array( $value ) || ! isset( $value['bpaf_admin_settings'] ) ) {
			return array();
		}

		$old_settings = $value['bpaf_admin_settings'];
		$new_settings = array();

		foreach ( $old_settings as $post_type => $settings ) {
			$new_settings[ $post_type ] = array(
				'enabled' => ! empty( $settings['display_type'] ) && 'enable' === $settings['display_type'],
				'label'   => isset( $settings['new_label'] ) ? $settings['new_label'] : '',
			);
		}

		return $new_settings;
	}

	/**
	 * Migrate CPT settings format.
	 *
	 * @since 4.0.0
	 * @param array $migration_data Migration tracking data.
	 */
	private function migrate_cpt_settings( &$migration_data ) {
		$cpt_settings = get_option( 'bp_activity_filter_cpt_settings', array() );
		
		if ( empty( $cpt_settings ) ) {
			return;
		}

		// Check if settings are in old format and need migration
		$needs_migration = false;
		foreach ( $cpt_settings as $post_type => $settings ) {
			if ( ! is_array( $settings ) || ! isset( $settings['enabled'] ) ) {
				$needs_migration = true;
				break;
			}
		}

		if ( $needs_migration ) {
			$migrated_settings = $this->transform_cpt_settings( array( 'bpaf_admin_settings' => $cpt_settings ) );
			update_option( 'bp_activity_filter_cpt_settings', $migrated_settings );
			$migration_data['cpt_settings_migrated'] = true;
		}
	}

	/**
	 * Clean up deprecated options.
	 *
	 * @since 4.0.0
	 * @param array $migration_data Migration tracking data.
	 */
	private function cleanup_deprecated_options( &$migration_data ) {
		// Only clean up after successful migration and user confirmation
		$cleanup_deprecated = apply_filters( 'bp_activity_filter_cleanup_deprecated_options', false );
		
		if ( ! $cleanup_deprecated ) {
			return;
		}

		$deprecated_options = array();
		
		foreach ( array_keys( $this->legacy_option_mappings ) as $legacy_key ) {
			if ( false !== get_option( $legacy_key ) ) {
				delete_option( $legacy_key );
				$deprecated_options[] = $legacy_key;
			}
		}

		if ( ! empty( $deprecated_options ) ) {
			$migration_data['cleaned_up_options'] = $deprecated_options;
			BP_Activity_Filter_Helper::log( 'Cleaned up deprecated options: ' . implode( ', ', $deprecated_options ) );
		}
	}

	/**
	 * Show migration notice to admin.
	 *
	 * @since 4.0.0
	 */
	public function show_migration_notice() {
		$migration_data = get_option( $this->migration_flag_key );
		
		if ( ! $migration_data || ! is_array( $migration_data ) ) {
			return;
		}

		// Don't show notice for fresh installs
		if ( 'fresh_install' === $migration_data ) {
			return;
		}

		// Don't show if already dismissed
		if ( get_user_meta( get_current_user_id(), 'bp_activity_filter_migration_notice_dismissed', true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_bp-activity-filter' === $screen->id ) {
			return; // Don't show on plugin settings page
		}

		$this->render_migration_notice( $migration_data );
	}

	/**
	 * Render migration notice.
	 *
	 * @since 4.0.0
	 * @param array $migration_data Migration data.
	 */
	private function render_migration_notice( $migration_data ) {
		$status = isset( $migration_data['status'] ) ? $migration_data['status'] : 'unknown';
		?>
		<div class="notice notice-info is-dismissible bp-activity-filter-migration-notice">
			<h3><?php esc_html_e( 'BuddyPress Activity Filter Updated', 'bp-activity-filter' ); ?></h3>
			
			<?php if ( 'completed' === $status ) : ?>
				<p>
					<?php
					printf(
						/* translators: %1$s: from version, %2$s: to version */
						esc_html__( 'Your settings have been successfully migrated from version %1$s to %2$s. All your previous configurations are preserved.', 'bp-activity-filter' ),
						esc_html( $migration_data['from_version'] ),
						esc_html( $migration_data['to_version'] )
					);
					?>
				</p>
				
				<?php if ( ! empty( $migration_data['migrated_options'] ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Migrated Settings:', 'bp-activity-filter' ); ?></strong>
					</p>
					<ul>
						<li><?php esc_html_e( 'Default activity filters', 'bp-activity-filter' ); ?></li>
						<li><?php esc_html_e( 'Hidden activity types', 'bp-activity-filter' ); ?></li>
						<?php if ( isset( $migration_data['cpt_settings_migrated'] ) ) : ?>
							<li><?php esc_html_e( 'Custom post type settings', 'bp-activity-filter' ); ?></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
				
				<p>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=bp-activity-filter' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Review Settings', 'bp-activity-filter' ); ?>
					</a>
					<button type="button" class="button button-secondary bp-activity-filter-dismiss-notice">
						<?php esc_html_e( 'Dismiss Notice', 'bp-activity-filter' ); ?>
					</button>
				</p>
				
			<?php elseif ( 'failed' === $status ) : ?>
				<p class="error">
					<?php esc_html_e( 'Migration encountered some issues. Your old settings are still preserved. Please check the plugin settings page.', 'bp-activity-filter' ); ?>
					<?php if ( isset( $migration_data['error'] ) ) : ?>
						<br><strong><?php esc_html_e( 'Error:', 'bp-activity-filter' ); ?></strong> 
						<?php echo esc_html( $migration_data['error'] ); ?>
					<?php endif; ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=bp-activity-filter' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Check Settings', 'bp-activity-filter' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.bp-activity-filter-dismiss-notice').on('click', function() {
				$.post(ajaxurl, {
					action: 'bp_activity_filter_dismiss_migration_notice',
					nonce: '<?php echo esc_js( wp_create_nonce( 'bp_activity_filter_dismiss_notice' ) ); ?>'
				});
				$('.bp-activity-filter-migration-notice').fadeOut();
			});
		});
		</script>
		<?php
	}

	/**
	 * Dismiss migration notice.
	 *
	 * @since 4.0.0
	 */
	public function dismiss_migration_notice() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'bp_activity_filter_dismiss_notice' ) ) {
			wp_die( 'Security check failed' );
		}

		update_user_meta( get_current_user_id(), 'bp_activity_filter_migration_notice_dismissed', true );
		wp_die();
	}

	/**
	 * Get legacy option value with fallback to new option.
	 *
	 * @since 4.0.0
	 * @param string $new_option_key New option key.
	 * @param mixed  $default Default value.
	 * @return mixed Option value.
	 */
	public static function get_option_with_fallback( $new_option_key, $default = false ) {
		// First try the new option
		$value = get_option( $new_option_key, null );
		
		// If new option exists (even if empty), use it
		if ( null !== $value ) {
			return $value;
		}
		
		// New option doesn't exist, try legacy option
		$legacy_mappings = self::get_legacy_mappings();
		$legacy_key = array_search( $new_option_key, $legacy_mappings, true );
		
		if ( $legacy_key ) {
			$legacy_value = get_option( $legacy_key, null );
			if ( null !== $legacy_value ) {
				// Transform legacy value if needed
				$instance = new self();
				$transformed_value = $instance->transform_option_value( $legacy_key, $legacy_value );
				
				// Save the transformed value to new option for future use
				update_option( $new_option_key, $transformed_value );
				
				return $transformed_value;
			}
		}
		
		// No legacy option found, create the new option with default value
		add_option( $new_option_key, $default );
		
		return $default;
	}

	/**
	 * Get legacy option mappings.
	 *
	 * @since 4.0.0
	 * @return array Legacy mappings.
	 */
	private static function get_legacy_mappings() {
		return array(
			'bp-default-filter-name'         => 'bp_activity_filter_default',
			'bp-default-profile-filter-name' => 'bp_activity_filter_profile_default',
			'bp-hidden-filters-name'         => 'bp_activity_filter_hidden',
			'bp-cpt-filters-settings'        => 'bp_activity_filter_cpt_settings',
		);
	}

	/**
	 * Force migration (for testing or manual trigger).
	 *
	 * @since 4.0.0
	 */
	public function force_migration() {
		delete_option( $this->db_version_key );
		delete_option( $this->migration_flag_key );
		$this->maybe_migrate();
	}
}