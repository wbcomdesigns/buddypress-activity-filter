<?php
/**
 * Uninstall script for BuddyPress Activity Filter.
 *
 * Handles complete cleanup of plugin data when the plugin is uninstalled.
 * This includes options, transients, user meta, and activity meta.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 * @version 4.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Security check - ensure we're in the WordPress environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main uninstall cleanup function.
 *
 * Removes all plugin-related data from the database including options,
 * transients, user meta, and activity meta.
 *
 * @since 4.0.0
 */
function bp_activity_filter_uninstall_cleanup() {
	global $wpdb;

	// Check if we should preserve data during uninstall.
	$preserve_data = apply_filters( 'bp_activity_filter_preserve_data_on_uninstall', false );
	
	if ( $preserve_data ) {
		return;
	}

	// Remove plugin options.
	bp_activity_filter_remove_options();

	// Remove transients.
	bp_activity_filter_remove_transients();

	// Remove user meta.
	bp_activity_filter_remove_user_meta();

	// Remove activity meta (if BuddyPress is active).
	bp_activity_filter_remove_activity_meta();

	// Remove scheduled events.
	bp_activity_filter_remove_scheduled_events();

	// Clear object cache.
	bp_activity_filter_clear_cache();

	// Log uninstall if debug mode is enabled.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'BuddyPress Activity Filter: Plugin data cleaned up during uninstall.' );
	}
}

/**
 * Remove all plugin options from the database.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_options() {
	// Current plugin options.
	$new_options = array(
		'bp_activity_filter_default',
		'bp_activity_filter_profile_default',
		'bp_activity_filter_hidden',
		'bp_activity_filter_cpt_settings',
		'bp_activity_filter_db_version',
		'bp_activity_filter_migration_complete',
		'bp_activity_filter_quick_fix_applied',
	);

	// Legacy options from older versions.
	$legacy_options = array(
		'bp-default-filter-name',
		'bp-default-profile-filter-name',
		'bp-hidden-filters-name',
		'bp-cpt-filters-settings',
	);

	// Combine all options.
	$all_options = array_merge( $new_options, $legacy_options );

	// Remove options (both single site and multisite).
	foreach ( $all_options as $option ) {
		delete_option( $option );
		delete_site_option( $option ); // For multisite networks.
	}
}

/**
 * Remove all plugin transients.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_transients() {
	$transients_to_remove = array(
		'bp_activity_filter_activation_redirect',
		'_bp_activity_filter_migration_notice',
		'bp_activity_filter_activity_actions',
		'bp_activity_filter_eligible_post_types',
	);

	foreach ( $transients_to_remove as $transient ) {
		delete_transient( $transient );
		delete_site_transient( $transient ); // For multisite networks.
	}
}

/**
 * Remove plugin-related user meta.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_user_meta() {
	global $wpdb;

	// User meta keys to remove.
	$user_meta_keys = array(
		'bp_activity_filter_migration_notice_dismissed',
		'bp_quick_fix_notice_dismissed',
		'bp_activity_filter_preference',
		'bp_activity_filter_last_viewed',
	);

	// Remove user meta safely.
	foreach ( $user_meta_keys as $meta_key ) {
		$wpdb->delete(
			$wpdb->usermeta,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
	}
}

/**
 * Remove plugin-related activity meta.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_activity_meta() {
	global $wpdb;

	// Check if BuddyPress is active and activity meta table exists.
	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'activity' ) ) {
		return;
	}

	$activity_meta_table = $wpdb->prefix . 'bp_activity_meta';
	
	// Check if table exists.
	$table_exists = $wpdb->get_var( 
		$wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$activity_meta_table 
		) 
	);

	if ( $table_exists !== $activity_meta_table ) {
		return;
	}

	// Activity meta keys to remove.
	$activity_meta_keys = array(
		'bp_activity_filter_cpt',
		'bp_activity_filter_post_id',
		'bp_activity_filter_generated',
	);

	// Remove activity meta safely.
	foreach ( $activity_meta_keys as $meta_key ) {
		$wpdb->delete(
			$activity_meta_table,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
	}
}

/**
 * Remove scheduled events/cron jobs.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_scheduled_events() {
	// List of scheduled events to remove.
	$scheduled_events = array(
		'bp_activity_filter_cleanup_old_data',
		'bp_activity_filter_migration_check',
	);

	foreach ( $scheduled_events as $event ) {
		$timestamp = wp_next_scheduled( $event );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $event );
		}
	}

	// Clear all scheduled events for this plugin.
	wp_clear_scheduled_hook( 'bp_activity_filter_cleanup_old_data' );
	wp_clear_scheduled_hook( 'bp_activity_filter_migration_check' );
}

/**
 * Clear WordPress object cache.
 *
 * @since 4.0.0
 */
function bp_activity_filter_clear_cache() {
	// Clear WordPress object cache.
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	// Clear specific plugin cache groups if using object caching.
	if ( function_exists( 'wp_cache_flush_group' ) ) {
		wp_cache_flush_group( 'bp_activity_filter' );
	}

	// Clear any persistent cache for BuddyPress activity.
	if ( function_exists( 'bp_core_clear_cache' ) ) {
		bp_core_clear_cache();
	}
}

/**
 * Remove plugin-specific database tables if any were created.
 *
 * Note: This plugin doesn't create custom tables, but this function
 * is included for future extensibility.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_custom_tables() {
	global $wpdb;

	// Currently no custom tables, but structure for future use.
	$custom_tables = array(
		// Example: $wpdb->prefix . 'bp_activity_filter_log',
	);

	foreach ( $custom_tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

/**
 * Clean up plugin-related options from wp_options autoload.
 *
 * This helps improve site performance by removing unnecessary autoloaded options.
 *
 * @since 4.0.0
 */
function bp_activity_filter_cleanup_autoload_options() {
	global $wpdb;

	// Get all plugin options that might be autoloaded.
	$plugin_options = array(
		'bp_activity_filter_default',
		'bp_activity_filter_profile_default',
		'bp_activity_filter_hidden',
		'bp_activity_filter_cpt_settings',
	);

	// Remove from autoload to improve performance during uninstall cleanup.
	foreach ( $plugin_options as $option ) {
		$wpdb->query( 
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET autoload = 'no' WHERE option_name = %s",
				$option
			)
		);
	}
}

/**
 * Log uninstall activity for debugging purposes.
 *
 * @since 4.0.0
 * @param string $message Log message.
 */
function bp_activity_filter_log_uninstall( $message ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$log_message = sprintf(
			'[%s] BuddyPress Activity Filter Uninstall: %s',
			gmdate( 'Y-m-d H:i:s' ),
			$message
		);
		error_log( $log_message );
	}
}

/**
 * Backup plugin data before uninstall (optional).
 *
 * This function can be used to create a backup of plugin settings
 * before removal, allowing for potential restoration.
 *
 * @since 4.0.0
 * @return bool True if backup was created, false otherwise.
 */
function bp_activity_filter_backup_data() {
	// Check if backup is requested.
	$create_backup = apply_filters( 'bp_activity_filter_create_uninstall_backup', false );
	
	if ( ! $create_backup ) {
		return false;
	}

	$backup_data = array(
		'version'      => '4.0.0',
		'timestamp'    => current_time( 'timestamp' ),
		'options'      => array(
			'bp_activity_filter_default'         => get_option( 'bp_activity_filter_default' ),
			'bp_activity_filter_profile_default' => get_option( 'bp_activity_filter_profile_default' ),
			'bp_activity_filter_hidden'          => get_option( 'bp_activity_filter_hidden' ),
			'bp_activity_filter_cpt_settings'    => get_option( 'bp_activity_filter_cpt_settings' ),
		),
	);

	// Store backup as a WordPress option with expiration.
	$backup_stored = add_option( 
		'bp_activity_filter_backup_' . time(), 
		$backup_data, 
		'', 
		'no' // Don't autoload backup data.
	);

	if ( $backup_stored ) {
		bp_activity_filter_log_uninstall( 'Plugin data backup created successfully.' );
	}

	return $backup_stored;
}

/**
 * Validate uninstall conditions.
 *
 * Ensures the plugin can be safely uninstalled without breaking dependencies.
 *
 * @since 4.0.0
 * @return bool True if safe to uninstall, false otherwise.
 */
function bp_activity_filter_validate_uninstall() {
	// Check if other plugins depend on this one.
	$dependent_plugins = apply_filters( 'bp_activity_filter_dependent_plugins', array() );
	
	if ( ! empty( $dependent_plugins ) ) {
		foreach ( $dependent_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				bp_activity_filter_log_uninstall( "Dependent plugin detected: {$plugin}" );
				// Don't block uninstall, just log for awareness.
			}
		}
	}

	// Always allow uninstall - just log potential issues.
	return true;
}

/**
 * Final cleanup and validation.
 *
 * Performs final checks and cleanup after main uninstall process.
 *
 * @since 4.0.0
 */
function bp_activity_filter_final_cleanup() {
	// Remove any remaining plugin traces.
	bp_activity_filter_cleanup_autoload_options();
	
	// Remove custom tables (if any in future versions).
	bp_activity_filter_remove_custom_tables();
	
	// Final cache clear.
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	// Log completion.
	bp_activity_filter_log_uninstall( 'Plugin uninstall completed successfully.' );
}

// Execute uninstall only if validation passes.
if ( bp_activity_filter_validate_uninstall() ) {
	// Create backup if requested.
	bp_activity_filter_backup_data();
	
	// Log start of uninstall process.
	bp_activity_filter_log_uninstall( 'Starting plugin uninstall process.' );
	
	// Run main cleanup.
	bp_activity_filter_uninstall_cleanup();
	
	// Final cleanup.
	bp_activity_filter_final_cleanup();
} else {
	bp_activity_filter_log_uninstall( 'Uninstall validation failed - aborting cleanup.' );
}