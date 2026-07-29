<?php
/**
 * Uninstall script for BuddyPress Activity Filter.
 *
 * @package BuddyPress_Activity_Filter
 * @since 3.1.0
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
 * @since 3.1.0
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

	// Remove post meta left behind by the removed CPT activity feature.
	bp_activity_filter_remove_post_meta();

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
 * @since 3.1.0
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
 * @since 3.1.0
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
 * @since 3.1.0
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
			'SHOW TABLES LIKE %s',
			$activity_meta_table
		)
	);

	if ( $table_exists !== $activity_meta_table ) {
		return;
	}

	// Activity meta keys to remove. All four were written by the CPT activity
	// feature removed in 4.0.0; sites that ran 3.x still carry these rows.
	$activity_meta_keys = array(
		'bp_activity_filter_cpt',
		'bp_activity_filter_post_id',
		'bp_activity_filter_created_time',
		'bp_activity_filter_version',
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
 * Remove plugin-related post meta.
 *
 * The CPT activity feature removed in 4.0.0 wrote these alongside the activity
 * meta above. They live in wp_postmeta, so they survive even when BuddyPress is
 * already gone - which is why this runs outside the bp_is_active() guard.
 *
 * @since 4.0.0
 */
function bp_activity_filter_remove_post_meta() {
	// Post meta keys to remove.
	$post_meta_keys = array(
		'_bp_activity_filter_activity_id',
		'_bp_activity_filter_recorded',
	);

	// delete_post_meta_by_key() is used rather than a direct DELETE so the
	// post-meta cache is invalidated for any object cache still in play.
	foreach ( $post_meta_keys as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}
}

// Execute uninstall cleanup.
bp_activity_filter_uninstall_cleanup();
