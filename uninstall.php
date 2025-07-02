<?php
/**
 * Uninstall script for BuddyPress Activity Filter.
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
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
 * @since 4.0.0
 */
function bp_activity_filter_uninstall_cleanup() {
	// Check if we should preserve data during uninstall.
	$preserve_data = apply_filters( 'bp_activity_filter_preserve_data_on_uninstall', false );
	
	if ( $preserve_data ) {
		return;
	}

	// Remove plugin options.
	bp_activity_filter_remove_options();

	// Remove user meta.
	bp_activity_filter_remove_user_meta();

	// Remove activity meta (if BuddyPress is active).
	bp_activity_filter_remove_activity_meta();

	// Clear object cache.
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

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
 * Remove plugin-related user meta.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_user_meta() {
	global $wpdb;

	// User meta keys to remove.
	$user_meta_keys = array(
		'bp_activity_filter_preference',
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

// Execute uninstall cleanup.
bp_activity_filter_uninstall_cleanup();