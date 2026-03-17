<?php
/**
 * HSS_Htaccess_Writer のテスト
 *
 * @package HtaccessSS
 */

/**
 * ファイル I/O クラスのテスト
 */
class HtaccessWriterTest extends WP_UnitTestCase {

	/**
	 * テスト対象のインスタンス
	 *
	 * @var HSS_Htaccess_Writer
	 */
	private $writer;

	/**
	 * テスト用 .htaccess ファイルのパス
	 *
	 * @var string
	 */
	private $test_htaccess;

	/**
	 * テスト用 wp-admin .htaccess ファイルのパス
	 *
	 * @var string
	 */
	private $test_admin_htaccess;

	/**
	 * テスト前のセットアップ
	 */
	public function set_up(): void {
		parent::set_up();

		$this->writer = new HSS_Htaccess_Writer();

		$this->test_htaccess       = ABSPATH . '.htaccess';
		$this->test_admin_htaccess = ABSPATH . 'wp-admin/.htaccess';

		// テスト用にクリーンな状態にする
		if ( file_exists( $this->test_htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $this->test_htaccess );
		}

		if ( file_exists( $this->test_admin_htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $this->test_admin_htaccess );
		}

		// wp-admin ディレクトリが存在することを確認
		$admin_dir = ABSPATH . 'wp-admin';
		if ( ! is_dir( $admin_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $admin_dir, 0755, true );
		}

		delete_option( HSS_Settings::BACKUP_ROOT_KEY );
		delete_option( HSS_Settings::BACKUP_ADMIN_KEY );
		delete_option( HSS_Settings::BACKUP_TIME_KEY );
	}

	/**
	 * テスト後のクリーンアップ
	 */
	public function tear_down(): void {
		if ( file_exists( $this->test_htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $this->test_htaccess );
		}
		if ( file_exists( $this->test_admin_htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $this->test_admin_htaccess );
		}

		parent::tear_down();
	}

	// =========================================================================
	// パス取得テスト
	// =========================================================================

	/**
	 * get_root_path が ABSPATH + .htaccess を返す
	 */
	public function test_get_root_path() {
		$this->assertSame( ABSPATH . '.htaccess', $this->writer->get_root_path() );
	}

	/**
	 * get_wp_admin_path が ABSPATH + wp-admin/.htaccess を返す
	 */
	public function test_get_wp_admin_path() {
		$this->assertSame( ABSPATH . 'wp-admin/.htaccess', $this->writer->get_wp_admin_path() );
	}

	// =========================================================================
	// マーカー定数テスト
	// =========================================================================

	/**
	 * MARKER 定数が正しい値を持っている
	 */
	public function test_marker_constant() {
		$this->assertSame( 'Htaccess Security Settings', HSS_Htaccess_Writer::MARKER );
	}

	// =========================================================================
	// write_root テスト
	// =========================================================================

	/**
	 * write_root が true を返す
	 */
	public function test_write_root_returns_true() {
		$lines  = array( '# Test directive' );
		$result = $this->writer->write_root( $lines );

		$this->assertTrue( $result );
	}

	/**
	 * write_root でファイルが作成される
	 */
	public function test_write_root_creates_file() {
		$lines = array( '# Test directive' );
		$this->writer->write_root( $lines );

		$this->assertFileExists( $this->test_htaccess );
	}

	/**
	 * write_root でプラグインブロックマーカーが含まれる
	 */
	public function test_write_root_contains_markers() {
		$lines = array( '# Test directive' );
		$this->writer->write_root( $lines );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_htaccess );

		$this->assertStringContainsString( '# BEGIN Htaccess Security Settings', $content );
		$this->assertStringContainsString( '# END Htaccess Security Settings', $content );
		$this->assertStringContainsString( '# Test directive', $content );
	}

	/**
	 * write_root で WordPress ブロックの前に挿入される
	 */
	public function test_write_root_inserts_before_wordpress_block() {
		// 既存の WordPress ブロックを作成
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents(
			$this->test_htaccess,
			"# BEGIN WordPress\nRewriteEngine On\n# END WordPress\n"
		);

		$lines = array( 'Options -Indexes' );
		$this->writer->write_root( $lines );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_htaccess );

		$plugin_pos = strpos( $content, '# BEGIN Htaccess Security Settings' );
		$wp_pos     = strpos( $content, '# BEGIN WordPress' );

		$this->assertNotFalse( $plugin_pos );
		$this->assertNotFalse( $wp_pos );
		$this->assertLessThan( $wp_pos, $plugin_pos, 'プラグインブロックが WordPress ブロックの前にない' );
	}

	/**
	 * write_root で WordPress ブロックがない場合は末尾に追加される
	 */
	public function test_write_root_appends_when_no_wordpress_block() {
		// 既存の内容（WordPress ブロックなし）
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents(
			$this->test_htaccess,
			"# Some existing content\n"
		);

		$lines = array( 'Options -Indexes' );
		$this->writer->write_root( $lines );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_htaccess );

		$this->assertStringContainsString( '# Some existing content', $content );
		$this->assertStringContainsString( '# BEGIN Htaccess Security Settings', $content );
	}

	/**
	 * write_root を2回呼んでもブロックが重複しない（冪等性）
	 */
	public function test_write_root_idempotent() {
		$lines = array( 'Options -Indexes' );
		$this->writer->write_root( $lines );
		$this->writer->write_root( $lines );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_htaccess );

		$count = substr_count( $content, '# BEGIN Htaccess Security Settings' );
		$this->assertSame( 1, $count, 'プラグインブロックが重複している' );
	}

	/**
	 * write_root で空の配列を渡すとブロックが除去される
	 */
	public function test_write_root_empty_lines_removes_block() {
		// まず書き込む
		$this->writer->write_root( array( 'Options -Indexes' ) );

		// 空の配列で更新
		$this->writer->write_root( array() );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_htaccess );

		$this->assertStringNotContainsString( '# BEGIN Htaccess Security Settings', $content );
	}

	// =========================================================================
	// write_wp_admin テスト
	// =========================================================================

	/**
	 * write_wp_admin が true を返す
	 */
	public function test_write_wp_admin_returns_true() {
		$lines  = array( 'AuthType BASIC' );
		$result = $this->writer->write_wp_admin( $lines );

		$this->assertTrue( $result );
	}

	/**
	 * write_wp_admin でファイルが作成される
	 */
	public function test_write_wp_admin_creates_file() {
		$lines = array( 'AuthType BASIC' );
		$this->writer->write_wp_admin( $lines );

		$this->assertFileExists( $this->test_admin_htaccess );
	}

	/**
	 * write_wp_admin で空配列を渡すとブロックが除去される
	 */
	public function test_write_wp_admin_empty_removes_block() {
		// まず書き込む
		$this->writer->write_wp_admin( array( 'AuthType BASIC' ) );

		// 空配列で更新
		$this->writer->write_wp_admin( array() );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_admin_htaccess );

		$this->assertStringNotContainsString( 'AuthType BASIC', $content );
	}

	/**
	 * write_wp_admin でファイルが存在せず空配列ならエラーなし
	 */
	public function test_write_wp_admin_empty_no_file_returns_true() {
		$result = $this->writer->write_wp_admin( array() );

		$this->assertTrue( $result );
	}

	// =========================================================================
	// backup & restore テスト
	// =========================================================================

	/**
	 * backup でルート .htaccess がオプションに保存される
	 */
	public function test_backup_root_saves_to_option() {
		// テストファイルを作成
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->test_htaccess, 'test content' );

		$this->writer->backup( 'root' );

		$backup = get_option( HSS_Settings::BACKUP_ROOT_KEY );
		$this->assertSame( 'test content', $backup );
	}

	/**
	 * backup で admin .htaccess がオプションに保存される
	 */
	public function test_backup_admin_saves_to_option() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->test_admin_htaccess, 'admin content' );

		$this->writer->backup( 'admin' );

		$backup = get_option( HSS_Settings::BACKUP_ADMIN_KEY );
		$this->assertSame( 'admin content', $backup );
	}

	/**
	 * backup でタイムスタンプが保存される
	 */
	public function test_backup_saves_timestamp() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->test_htaccess, 'test' );

		$this->writer->backup( 'root' );

		$time = $this->writer->get_backup_time();
		$this->assertNotFalse( $time );
		$this->assertNotEmpty( $time );
	}

	/**
	 * restore でバックアップからファイルが復元される
	 */
	public function test_restore_root_from_backup() {
		$original_content = "# Original content\nOptions -Indexes\n";

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->test_htaccess, $original_content );

		// バックアップ
		$this->writer->backup( 'root' );

		// ファイルを変更
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->test_htaccess, 'modified content' );

		// 復元
		$result = $this->writer->restore( 'root' );

		$this->assertTrue( $result );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$restored = file_get_contents( $this->test_htaccess );
		$this->assertSame( $original_content, $restored );
	}

	/**
	 * バックアップが無い場合に restore が WP_Error を返す
	 */
	public function test_restore_no_backup_returns_error() {
		$result = $this->writer->restore( 'root' );

		$this->assertWPError( $result );
		$this->assertSame( 'no_backup', $result->get_error_code() );
	}

	// =========================================================================
	// write_root がバックアップを作成する
	// =========================================================================

	/**
	 * write_root が既存ファイルのバックアップを作成する
	 */
	public function test_write_root_creates_backup() {
		$initial = "# Initial content\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->test_htaccess, $initial );

		$this->writer->write_root( array( 'Options -Indexes' ) );

		$backup = get_option( HSS_Settings::BACKUP_ROOT_KEY );
		$this->assertSame( $initial, $backup );
	}

	// =========================================================================
	// 既存コンテンツ保持テスト
	// =========================================================================

	/**
	 * write_root が WordPress ブロックを保持する
	 */
	public function test_write_root_preserves_wordpress_block() {
		$wp_block = "# BEGIN WordPress\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n# END WordPress\n";

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->test_htaccess, $wp_block );

		$this->writer->write_root( array( 'Options -Indexes' ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_htaccess );

		$this->assertStringContainsString( '# BEGIN WordPress', $content );
		$this->assertStringContainsString( 'RewriteEngine On', $content );
		$this->assertStringContainsString( '# END WordPress', $content );
		$this->assertStringContainsString( 'Options -Indexes', $content );
	}

	/**
	 * write_root で更新時に内容が正しく置き換わる
	 */
	public function test_write_root_updates_content() {
		// 初回書き込み
		$this->writer->write_root( array( 'Options -Indexes' ) );

		// 別の内容で更新
		$this->writer->write_root( array( 'Options -MultiViews' ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $this->test_htaccess );

		$this->assertStringContainsString( 'Options -MultiViews', $content );
		$this->assertStringNotContainsString( 'Options -Indexes', $content );
	}
}
