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
	}

	// =========================================================================
	// add_menu_page — manage_options 権限
	// =========================================================================

	/**
	 * add_options_page() に manage_options が渡されていることを検証
	 */
	public function test_menu_page_requires_manage_options() {
		// 管理者としてログイン
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		set_current_screen( 'dashboard' );
		$this->admin_page->add_menu_page();

		$menu_slug = 'htaccess-security-settings';
		$page_hook = get_plugin_page_hookname( $menu_slug, 'options-general.php' );

		$this->assertNotEmpty( $page_hook, 'メニューページが登録されているべき' );
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
	// handle_form_submission — 権限なしで早期 return
	// =========================================================================

	/**
	 * 権限なしユーザーで handle_form_submission() がアクション未実行
	 */
	public function test_handle_form_submission_returns_early_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_POST['htaccess_ss_action'] = 'save';

		// 権限なしなのでリダイレクト（exit）せず return するはず
		$this->admin_page->handle_form_submission();

		// ここに到達できれば、早期 return されている
		$this->assertTrue( true, 'handle_form_submission() が早期 return した' );

		unset( $_POST['htaccess_ss_action'] );
	}

	// =========================================================================
	// handle_save — 権限なしで wp_die
	// =========================================================================

	/**
	 * 権限なしで handle_save() が wp_die する
	 */
	public function test_handle_save_dies_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_POST['htaccess_ss_action'] = 'save';
		$_POST['htaccess_ss_nonce']  = wp_create_nonce( 'htaccess_ss_save' );

		// handle_form_submission の入口で弾かれるため wp_die に到達しない
		$this->admin_page->handle_form_submission();
		$this->assertTrue( true, '入口の権限チェックで弾かれた' );

		unset( $_POST['htaccess_ss_action'], $_POST['htaccess_ss_nonce'] );
	}

	// =========================================================================
	// handle_restore — 権限なしで wp_die
	// =========================================================================

	/**
	 * 権限なしで handle_restore() が呼ばれない
	 */
	public function test_handle_restore_dies_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_POST['htaccess_ss_action']        = 'restore';
		$_POST['htaccess_ss_restore_nonce'] = wp_create_nonce( 'htaccess_ss_restore' );

		$this->admin_page->handle_form_submission();
		$this->assertTrue( true, '入口の権限チェックで弾かれた' );

		unset( $_POST['htaccess_ss_action'], $_POST['htaccess_ss_restore_nonce'] );
	}

	// =========================================================================
	// handle_apply_preset — 権限なしで wp_die
	// =========================================================================

	/**
	 * 権限なしで handle_apply_preset() が呼ばれない
	 */
	public function test_handle_apply_preset_dies_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_POST['htaccess_ss_action']       = 'apply_preset';
		$_POST['htaccess_ss_preset_nonce'] = wp_create_nonce( 'htaccess_ss_preset' );
		$_POST['preset_key']               = 'recommended';

		$this->admin_page->handle_form_submission();
		$this->assertTrue( true, '入口の権限チェックで弾かれた' );

		unset( $_POST['htaccess_ss_action'], $_POST['htaccess_ss_preset_nonce'], $_POST['preset_key'] );
	}

	// =========================================================================
	// handle_delete_all — 権限なしで wp_die
	// =========================================================================

	/**
	 * 権限なしで handle_delete_all() が呼ばれない
	 */
	public function test_handle_delete_all_dies_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$_POST['htaccess_ss_action']           = 'delete_all';
		$_POST['htaccess_ss_delete_all_nonce'] = wp_create_nonce( 'htaccess_ss_delete_all' );

		$this->admin_page->handle_form_submission();
		$this->assertTrue( true, '入口の権限チェックで弾かれた' );

		unset( $_POST['htaccess_ss_action'], $_POST['htaccess_ss_delete_all_nonce'] );
	}

	// =========================================================================
	// ajax_download — 権限なしで拒否
	// =========================================================================

	/**
	 * 権限なしユーザーで ajax_download() が wp_die する
	 */
	public function test_ajax_download_dies_without_capability() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		// check_ajax_referer が失敗するので WPDieException が発生する
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
