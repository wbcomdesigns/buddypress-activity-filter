<?php
/**
 * Migration and backward compatibility handler.
 *
 * @package BuddyPress_Activity_Filter
 * @since 3.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration class for handling version upgrades and backward compatibility.
 *
 * @since 3.1.0
 */
class BP_Activity_Filter_Migration {

	/**
	 * Current plugin version.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	private $current_version;

	/**
	 * Database version option key.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	private $db_version_key = 'bp_activity_filter_db_version';

	/**
	 * Legacy option mappings.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	private $legacy_option_mappings = array(
		// Old option => New option.
		'bp-default-filter-name'         => 'bp_activity_filter_default',
		'bp-default-profile-filter-name' => 'bp_activity_filter_profile_default',
		'bp-hidden-filters-name'         => 'bp_activity_filter_hidden',
	);

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 */
	public function __construct() {
		$this->current_version = BP_ACTIVITY_FILTER_VERSION;
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 3.1.0
	 */
	private function setup_hooks() {
		add_action( 'admin_init', array( $this, 'maybe_migrate' ) );
	}

	/**
	 * Check if migration is needed and run it.
	 *
	 * @since 3.1.0
	 */
	public function maybe_migrate() {
		$db_version = get_option( $this->db_version_key, '0' );

		// If this is a fresh install, set current version and skip migration.
		if ( '0' === $db_version && ! $this->has_legacy_options() ) {
			update_option( $this->db_version_key, $this->current_version );
			return;
		}

		// If migration already completed for this version, skip.
		if ( version_compare( $db_version, $this->current_version, '>=' ) ) {
			return;
		}

		// Run migration.
		$this->run_migration( $db_version );
	}

	/**
	 * Check if legacy options exist.
	 *
	 * @since 3.1.0
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
	 * @since 3.1.0
	 * @param string $from_version Version migrating from.
	 */
	private function run_migration( $from_version ) {
		try {
			// Step 1: Migrate legacy options.
			$this->migrate_legacy_options();

			// Step 2: Ensure all required options exist with defaults.
			$this->ensure_required_options_exist();

			// Step 3: Update database version.
			update_option( $this->db_version_key, $this->current_version );

		} catch ( Exception $e ) {
			// Fail silently in production, log in debug mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'BP Activity Filter Migration failed: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Ensure all required options exist with defaults.
	 *
	 * @since 3.1.0
	 */
	private function ensure_required_options_exist() {
		$required_options = array(
			'bp_activity_filter_default'         => '0',
			'bp_activity_filter_profile_default' => '-1',
			'bp_activity_filter_hidden'          => array(),
		);

		foreach ( $required_options as $option => $default_value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $default_value );
			}
		}
	}

	/**
	 * Migrate legacy options to new format.
	 *
	 * @since 3.1.0
	 */
	private function migrate_legacy_options() {
		foreach ( $this->legacy_option_mappings as $legacy_key => $new_key ) {
			$legacy_value = get_option( $legacy_key );

			if ( false === $legacy_value ) {
				continue; // Option doesn't exist.
			}

			// Check if new option already has a value (don't overwrite manual settings).
			$existing_new_value = get_option( $new_key );
			if ( false !== $existing_new_value && ! empty( $existing_new_value ) ) {
				continue;
			}

			// Migrate the value with any necessary transformations.
			$migrated_value = $this->transform_option_value( $legacy_key, $legacy_value );

			update_option( $new_key, $migrated_value );
		}
	}

	/**
	 * Transform option values during migration if needed.
	 *
	 * @since 3.1.0
	 * @param string $legacy_key Legacy option key.
	 * @param mixed  $value Option value.
	 * @return mixed Transformed value.
	 */
	private function transform_option_value( $legacy_key, $value ) {
		switch ( $legacy_key ) {
			case 'bp-hidden-filters-name':
				// Ensure it's an array.
				return is_array( $value ) ? $value : array();

			default:
				return $value;
		}
	}

	/**
	 * Get legacy option value with fallback to new option.
	 *
	 * @since 3.1.0
	 * @param string $new_option_key New option key.
	 * @param mixed  $default Default value.
	 * @return mixed Option value.
	 */
	public static function get_option_with_fallback( $new_option_key, $default = false ) {
		// First try the new option.
		$value = get_option( $new_option_key, null );

		// If new option exists (even if empty), use it.
		if ( null !== $value ) {
			return $value;
		}

		// New option doesn't exist, try legacy option.
		$legacy_mappings = self::get_legacy_mappings();
		$legacy_key      = array_search( $new_option_key, $legacy_mappings, true );

		if ( $legacy_key ) {
			$legacy_value = get_option( $legacy_key, null );
			if ( null !== $legacy_value ) {
				// Transform legacy value if needed.
				$instance          = new self();
				$transformed_value = $instance->transform_option_value( $legacy_key, $legacy_value );

				// Save the transformed value to new option for future use.
				update_option( $new_option_key, $transformed_value );

				return $transformed_value;
			}
		}

		// No legacy option found, create the new option with default value.
		add_option( $new_option_key, $default );

		return $default;
	}

	/**
	 * Get legacy option mappings.
	 *
	 * @since 3.1.0
	 * @return array Legacy mappings.
	 */
	private static function get_legacy_mappings() {
		return array(
			'bp-default-filter-name'         => 'bp_activity_filter_default',
			'bp-default-profile-filter-name' => 'bp_activity_filter_profile_default',
			'bp-hidden-filters-name'         => 'bp_activity_filter_hidden',
		);
	}
}
