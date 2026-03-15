<?php
/**
 * Uninstall handler.
 *
 * Removes all plugin data from the database.
 * .htaccess rules are intentionally left in place — this plugin works as a
 * setup wizard, so the rules should persist after uninstallation.
 * To remove the rules, use the "すべての設定を削除" button before uninstalling.
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
