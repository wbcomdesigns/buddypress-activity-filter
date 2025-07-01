<?php
/**
 * Uninstall script for BuddyPress Activity Filter
 *
 * @package BuddyPress_Activity_Filter
 * @since 4.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin options on uninstall.
 *
 * @since 4.0.0
 */
function bp_activity_filter_uninstall_cleanup() {
	// List of new options to remove.
	$new_options = array(
		'bp_activity_filter_default',
		'bp_activity_filter_profile_default',
		'bp_activity_filter_hidden',
		'bp_activity_filter_cpt_settings',
		'bp_activity_filter_db_version',
		'bp_activity_filter_migration_complete',
		'bp_activity_filter_quick_fix_applied',
	);

	// List of legacy options to remove.
	$legacy_options = array(
		'bp-default-filter-name',
		'bp-default-profile-filter-name',
		'bp-hidden-filters-name',
		'bp-cpt-filters-settings',
	);

	// Combine all options.
	$all_options = array_merge( $new_options, $legacy_options );

	// Remove options.
	foreach ( $all_options as $option ) {
		delete_option( $option );
		delete_site_option( $option ); // For multisite.
	}

	// Remove transients.
	$transients_to_remove = array(
		'bp_activity_filter_activation_redirect',
		'_bp_activity_filter_migration_notice',
	);

	foreach ( $transients_to_remove as $transient ) {
		delete_transient( $transient );
		delete_site_transient( $transient ); // For multisite.
	}

	// Remove user meta (dismissal flags).
	global $wpdb;
	$wpdb->delete(
		$wpdb->usermeta,
		array(
			'meta_key' => 'bp_activity_filter_migration_notice_dismissed'
		)
	);

	$wpdb->delete(
		$wpdb->usermeta,
		array(
			'meta_key' => 'bp_quick_fix_notice_dismissed'
		)
	);

	// Clear any cached data.
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	// Remove any activity meta created by the plugin.
	if ( function_exists( 'bp_activity_delete_meta' ) ) {
		$wpdb->delete(
			$wpdb->prefix . 'bp_activity_meta',
			array(
				'meta_key' => 'bp_activity_filter_cpt'
			)
		);

		$wpdb->delete(
			$wpdb->prefix . 'bp_activity_meta',
			array(
				'meta_key' => 'bp_activity_filter_post_id'
			)
		);
	}
}

// Run cleanup.
bp_activity_filter_uninstall_cleanup();