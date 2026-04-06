<?php
/**
 * Perform plugin uninstallation routines for Patchwing.
 *
 * This file is called automatically when the user clicks 'Delete' in the WP Admin.
 * It ensures all plugin specific data is purged from the database.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

// Security Check: Exit if accessed directly or not during a WP uninstall trigger.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Execute cleanup for Patchwing.
 */
function patchwing_uninstall_cleanup() {
    global $wpdb;

    // Remove options and transients introduced by Patchwing.
    delete_option( 'patchwing_settings' );
    delete_transient( 'patchwing_real_cores' );

    // Drop performance table.
    $table_name = $wpdb->prefix . 'patchwing_performance';

    $sql = sprintf( 'DROP TABLE IF EXISTS `%s`', esc_sql( $table_name ) );
    
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- DROP TABLE during plugin uninstall; caching is inapplicable for schema cleanup. Query built with esc_sql() above.
    $wpdb->query( $sql );
}

// Run cleanup.
patchwing_uninstall_cleanup();