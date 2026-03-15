<?php
/**
 * Uninstall handler.
 *
 * Removes all plugin data from the database and cleans up .htaccess files.
 *
 * @package HtaccessSS
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options.
delete_option( 'htaccess_ss_settings' );
delete_option( 'htaccess_ss_backup_root' );
delete_option( 'htaccess_ss_backup_admin' );
delete_option( 'htaccess_ss_backup_time' );

// Remove plugin blocks from .htaccess files.
if ( ! function_exists( 'insert_with_markers' ) ) {
	require_once ABSPATH . 'wp-admin/includes/misc.php';
}

$marker    = 'Htaccess Security Settings';
$root_file = ABSPATH . '.htaccess';
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
if ( file_exists( $root_file ) && is_writable( $root_file ) ) {
	insert_with_markers( $root_file, $marker, '' );
}

$admin_file = ABSPATH . 'wp-admin/.htaccess';
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
if ( file_exists( $admin_file ) && is_writable( $admin_file ) ) {
	insert_with_markers( $admin_file, $marker, '' );
}
