<?php
/**
 * 有効化・無効化処理クラス
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * プラグインの有効化・無効化時の処理を管理するクラス
 */
class HSS_Activator {

	/**
	 * プラグイン有効化時の処理
	 *
	 * デフォルト設定をオプションに保存する（既存設定がなければ）
	 */
	public static function activate() {
		$existing = get_option( HSS_Settings::OPTION_KEY );
		if ( false === $existing ) {
			update_option( HSS_Settings::OPTION_KEY, HSS_Settings::get_defaults() );
		}
	}

	/**
	 * プラグイン無効化時の処理
	 *
	 * .htaccess からプラグインのブロックを除去する
	 * 設定はオプションに残す（再有効化時に復元できるように）
	 */
	public static function deactivate() {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$writer = new HSS_Htaccess_Writer();

		// ルート .htaccess からプラグインブロックを除去
		$root_path = $writer->get_root_path();
		if ( file_exists( $root_path ) ) {
			insert_with_markers( $root_path, HSS_Htaccess_Writer::MARKER, '' );
		}

		// wp-admin/.htaccess からプラグインブロックを除去
		$admin_path = $writer->get_wp_admin_path();
		if ( file_exists( $admin_path ) ) {
			insert_with_markers( $admin_path, HSS_Htaccess_Writer::MARKER, '' );
		}
	}
}
