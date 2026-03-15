<?php
/**
 * プラグインメインクラス
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * プラグインの初期化とクラス管理を行うメインクラス
 */
class HSS_Plugin {

	/**
	 * シングルトンインスタンス
	 *
	 * @var HSS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings インスタンス
	 *
	 * @var HSS_Settings
	 */
	private $settings;

	/**
	 * Admin_Page インスタンス
	 *
	 * @var HSS_Admin_Page|null
	 */
	private $admin_page;

	/**
	 * シングルトンインスタンスを取得する
	 *
	 * @return HSS_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		$this->settings = new HSS_Settings();

		if ( is_admin() ) {
			$this->admin_page = new HSS_Admin_Page( $this->settings );
		}
	}

	/**
	 * Settings インスタンスを取得する
	 *
	 * @return HSS_Settings
	 */
	public function get_settings() {
		return $this->settings;
	}
}
