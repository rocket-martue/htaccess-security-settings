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
	 * このプラグインはセットアップウィザードとして機能するため、
	 * 無効化しても .htaccess のルールはそのまま残す。
	 * ルールを削除したい場合は、無効化前に管理画面の「すべての設定を削除」ボタンを使用する。
	 */
	public static function deactivate() {
		// .htaccess のルールは意図的に残す。
	}
}
