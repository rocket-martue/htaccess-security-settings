<?php
/**
 * HSS_Admin_Page の権限チェックテスト
 *
 * @package HtaccessSS
 */

/**
 * 管理画面クラスの権限チェックテスト
 */
class AdminPageTest extends WP_UnitTestCase {

	/**
	 * テスト対象のインスタンス
	 *
	 * @var HSS_Admin_Page
	 */
	private $admin_page;

	/**
	 * Settings インスタンス
	 *
	 * @var HSS_Settings
	 */
	private $settings;

	/**
	 * テスト前のセットアップ
	 */
	public function set_up(): void {
		parent::set_up();
		$this->settings   = new HSS_Settings();
		$this->admin_page = new HSS_Admin_Page( $this->settings );

		delete_option( HSS_Settings::OPTION_KEY );
		delete_option( HSS_Settings::BACKUP_ROOT_KEY );
		delete_option( HSS_Settings::BACKUP_ADMIN_KEY );
		delete_option( HSS_Settings::BACKUP_TIME_KEY );
	}

	/**
	 * テスト後のクリーンアップ
	 */
	public function tear_down(): void {
		unset(
			$_POST['htaccess_ss_action'],
			$_POST['htaccess_ss_nonce'],
			$_POST['htaccess_ss_restore_nonce'],
			$_POST['htaccess_ss_preset_nonce'],
			$_POST['htaccess_ss_delete_all_nonce'],
			$_POST['preset_key'],
			$_POST['_tab'],
			$_REQUEST['nonce'],
			$_REQUEST['_ajax_nonce']
		);
		parent::tear_down();
	}

	// =========================================================================
	// add_menu_page — manage_options 権限
	// =========================================================================

	/**
	 * add_options_page() に manage_options が渡されていることを検証
	 */
	public function test_menu_page_requires_manage_options() {
		global $submenu;

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		set_current_screen( 'dashboard' );
		$this->admin_page->add_menu_page();

		$found_capability = '';
		if ( isset( $submenu['options-general.php'] ) ) {
			foreach ( $submenu['options-general.php'] as $item ) {
				if ( 'htaccess-security-settings' === $item[2] ) {
					$found_capability = $item[1];
					break;
				}
			}
		}

		$this->assertSame( 'manage_options', $found_capability, 'メニューの権限が manage_options であるべき' );
	}

	// =========================================================================
	// render_page — 権限なしで早期 return
	// =========================================================================

	/**
	 * 権限なしユーザーで render_page() が HTML を出力しないことを検証
	 */
	public function test_render_page_returns_early_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		ob_start();
		$this->admin_page->render_page();
		$output = ob_get_clean();

		$this->assertEmpty( $output, '権限なしユーザーでは出力なしであるべき' );
	}

	// =========================================================================
	// handle_form_submission — 権限なしで設定が変更されない
	// =========================================================================

	/**
	 * 権限なしユーザーで save アクションを送信しても設定が変更されない
	 */
	public function test_handle_form_submission_save_does_not_change_settings_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$before = get_option( HSS_Settings::OPTION_KEY );

		$_POST['htaccess_ss_action'] = 'save';
		$_POST['htaccess_ss_nonce']  = wp_create_nonce( 'htaccess_ss_save' );
		$_POST['_tab']               = 'options';

		$this->admin_page->handle_form_submission();

		$after = get_option( HSS_Settings::OPTION_KEY );
		$this->assertSame( $before, $after, '権限なしでは設定が変更されないべき' );
	}

	/**
	 * 権限なしユーザーで restore アクションを送信してもバックアップが復元されない
	 */
	public function test_handle_form_submission_restore_does_not_restore_without_capability() {
		// ダミーのバックアップデータを設置
		update_option( HSS_Settings::BACKUP_ROOT_KEY, 'dummy backup data' );
		$backup_before = get_option( HSS_Settings::BACKUP_ROOT_KEY );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_POST['htaccess_ss_action']        = 'restore';
		$_POST['htaccess_ss_restore_nonce'] = wp_create_nonce( 'htaccess_ss_restore' );

		$this->admin_page->handle_form_submission();

		$backup_after = get_option( HSS_Settings::BACKUP_ROOT_KEY );
		$this->assertSame( $backup_before, $backup_after, '権限なしではバックアップが消費されないべき' );
	}

	/**
	 * 権限なしユーザーで apply_preset アクションを送信しても設定が変更されない
	 */
	public function test_handle_form_submission_preset_does_not_apply_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$before = get_option( HSS_Settings::OPTION_KEY );

		$_POST['htaccess_ss_action']       = 'apply_preset';
		$_POST['htaccess_ss_preset_nonce'] = wp_create_nonce( 'htaccess_ss_preset' );
		$_POST['preset_key']               = 'recommended';

		$this->admin_page->handle_form_submission();

		$after = get_option( HSS_Settings::OPTION_KEY );
		$this->assertSame( $before, $after, '権限なしではプリセットが適用されないべき' );
	}

	/**
	 * 権限なしユーザーで delete_all アクションを送信しても設定が削除されない
	 */
	public function test_handle_form_submission_delete_all_does_not_delete_without_capability() {
		// 事前にオプションを設定
		update_option( HSS_Settings::OPTION_KEY, array( 'test' => true ) );
		update_option( HSS_Settings::BACKUP_TIME_KEY, '2026-01-01 00:00:00' );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_POST['htaccess_ss_action']           = 'delete_all';
		$_POST['htaccess_ss_delete_all_nonce'] = wp_create_nonce( 'htaccess_ss_delete_all' );

		$this->admin_page->handle_form_submission();

		$this->assertNotFalse( get_option( HSS_Settings::OPTION_KEY ), '権限なしでは設定が削除されないべき' );
		$this->assertNotFalse( get_option( HSS_Settings::BACKUP_TIME_KEY ), '権限なしではバックアップ日時が削除されないべき' );
	}

	// =========================================================================
	// ajax_download — 権限なしで拒否
	// =========================================================================

	/**
	 * 権限なしユーザーで ajax_download() が wp_die する（有効な nonce あり）
	 */
	public function test_ajax_download_dies_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// 有効な nonce を設定して権限チェックに到達させる
		$nonce                   = wp_create_nonce( 'htaccess_ss_download' );
		$_REQUEST['nonce']       = $nonce; // phpcs:ignore WordPress.Security
		$_REQUEST['_ajax_nonce'] = $nonce;

		$this->expectException( 'WPDieException' );

		$this->admin_page->ajax_download();
	}

	// =========================================================================
	// 管理者では正常動作
	// =========================================================================

	/**
	 * 管理者で render_page() が HTML を出力することを検証
	 */
	public function test_render_page_outputs_html_for_admin() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		ob_start();
		$this->admin_page->render_page();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output, '管理者は設定ページを表示できるべき' );
		$this->assertStringContainsString( '.htaccess', $output );
	}
}
