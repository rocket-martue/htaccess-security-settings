<?php
/**
 * 管理画面クラス
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 管理画面の設定ページを管理するクラス
 */
class HSS_Admin_Page {

	/**
	 * Settings インスタンス
	 *
	 * @var HSS_Settings
	 */
	private $settings;

	/**
	 * ページフック
	 *
	 * @var string
	 */
	private $page_hook;

	/**
	 * タブ定義
	 *
	 * @var array
	 */
	private $tabs;

	/**
	 * コンストラクタ
	 *
	 * @param HSS_Settings $settings Settings インスタンス
	 */
	public function __construct( HSS_Settings $settings ) {
		$this->settings = $settings;
		$this->tabs     = array(
			'options'  => 'Options & ファイル保護',
			'ip_block' => 'IP ブロック',
			'rewrite'  => 'リライトルール',
			'headers'  => 'セキュリティヘッダー',
			'cache'    => 'キャッシュ',
			'wp_admin' => 'wp-admin 保護',
		);

		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_htaccess_ss_download', array( $this, 'ajax_download' ) );
	}

	/**
	 * メニューページを追加する
	 */
	public function add_menu_page() {
		$this->page_hook = add_options_page(
			'.htaccess セキュリティ設定',
			'.htaccess セキュリティ',
			'manage_options',
			'htaccess-security-settings',
			array( $this, 'render_page' )
		);

		add_action( "load-{$this->page_hook}", array( $this, 'handle_form_submission' ) );
	}

	/**
	 * CSS と JS を読み込む
	 *
	 * @param string $hook 現在のページフック
	 */
	public function enqueue_assets( $hook ) {
		if ( $this->page_hook !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'htaccess-ss-admin',
			HSS_PLUGIN_URL . 'admin/css/admin-style.css',
			array(),
			HSS_VERSION
		);

		wp_enqueue_script(
			'htaccess-ss-admin',
			HSS_PLUGIN_URL . 'admin/js/admin-script.js',
			array( 'jquery' ),
			HSS_VERSION,
			true
		);

		wp_localize_script(
			'htaccess-ss-admin',
			'htaccessSS',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'downloadNonce' => wp_create_nonce( 'htaccess_ss_download' ),
			)
		);
	}

	/**
	 * フォーム送信を処理する
	 */
	public function handle_form_submission() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce はこの直後で検証している
		if ( ! isset( $_POST['htaccess_ss_action'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = sanitize_key( wp_unslash( $_POST['htaccess_ss_action'] ) );

		if ( 'save' === $action ) {
			$this->handle_save();
		} elseif ( 'restore' === $action ) {
			$this->handle_restore();
		} elseif ( 'apply_preset' === $action ) {
			$this->handle_apply_preset();
		} elseif ( 'delete_all' === $action ) {
			$this->handle_delete_all();
		}
	}

	/**
	 * 設定保存 & .htaccess 書き込みを処理する
	 */
	private function handle_save() {
		if ( ! isset( $_POST['htaccess_ss_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['htaccess_ss_nonce'] ) ), 'htaccess_ss_save' ) ) {
			wp_die( esc_html__( '不正なリクエストです。', 'htaccess-ss' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'htaccess-ss' ) );
		}

		$tab = isset( $_POST['_tab'] ) ? sanitize_key( wp_unslash( $_POST['_tab'] ) ) : '';
		if ( ! in_array( $tab, HSS_Settings::VALID_TABS, true ) ) {
			wp_die( esc_html__( '無効なタブです。', 'htaccess-ss' ) );
		}

		// サニタイズ & マージ
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_and_merge 内でサニタイズ済み
		$input        = isset( $_POST['htaccess_ss_settings'] ) ? wp_unslash( $_POST['htaccess_ss_settings'] ) : array();
		$new_settings = $this->settings->sanitize_and_merge( $input, $tab );

		// オプション保存
		$this->settings->save_settings( $new_settings );

		// .htaccess 書き込み
		$builder = new HSS_Htaccess_Builder();
		$writer  = new HSS_Htaccess_Writer();

		$root_lines  = $builder->build_root( $new_settings );
		$root_result = $writer->write_root( $root_lines );

		$admin_lines  = $builder->build_wp_admin( $new_settings );
		$admin_result = $writer->write_wp_admin( $admin_lines );

		// リダイレクト
		$status = 'saved';
		if ( is_wp_error( $root_result ) ) {
			$status = 'error_root';
		} elseif ( is_wp_error( $admin_result ) ) {
			$status = 'error_admin';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'htaccess-security-settings',
					'tab'    => $tab,
					'status' => $status,
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * バックアップからの復元を処理する
	 */
	private function handle_restore() {
		if ( ! isset( $_POST['htaccess_ss_restore_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['htaccess_ss_restore_nonce'] ) ), 'htaccess_ss_restore' ) ) {
			wp_die( esc_html__( '不正なリクエストです。', 'htaccess-ss' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'htaccess-ss' ) );
		}

		$tab    = isset( $_POST['_tab'] ) ? sanitize_key( wp_unslash( $_POST['_tab'] ) ) : 'options';
		$writer = new HSS_Htaccess_Writer();

		$root_result  = $writer->restore( 'root' );
		$admin_result = $writer->restore( 'admin' );

		$status = 'restored';
		if ( is_wp_error( $root_result ) && 'no_backup' !== $root_result->get_error_code() ) {
			$status = 'restore_error';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'htaccess-security-settings',
					'tab'    => $tab,
					'status' => $status,
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * プリセット適用を処理する
	 */
	private function handle_apply_preset() {
		if ( ! isset( $_POST['htaccess_ss_preset_nonce'] )
			|| ! wp_verify_nonce(
				sanitize_key( wp_unslash( $_POST['htaccess_ss_preset_nonce'] ) ),
				'htaccess_ss_preset'
			)
		) {
			wp_die( esc_html__( '不正なリクエストです。', 'htaccess-ss' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'htaccess-ss' ) );
		}

		$preset_key = isset( $_POST['preset_key'] )
			? sanitize_key( wp_unslash( $_POST['preset_key'] ) )
			: '';

		$new_settings = HSS_Settings::get_preset( $preset_key );
		if ( null === $new_settings ) {
			wp_die( esc_html__( '無効なプリセットです。', 'htaccess-ss' ) );
		}

		$this->settings->save_settings( $new_settings );

		// .htaccess 書き込み
		$builder      = new HSS_Htaccess_Builder();
		$writer       = new HSS_Htaccess_Writer();
		$root_result  = $writer->write_root( $builder->build_root( $new_settings ) );
		$admin_result = $writer->write_wp_admin( $builder->build_wp_admin( $new_settings ) );

		$tab = isset( $_POST['_tab'] ) ? sanitize_key( wp_unslash( $_POST['_tab'] ) ) : 'options';
		if ( ! in_array( $tab, HSS_Settings::VALID_TABS, true ) ) {
			$tab = 'options';
		}
		$status = 'preset_applied';
		if ( is_wp_error( $root_result ) ) {
			$status = 'error_root';
		} elseif ( is_wp_error( $admin_result ) ) {
			$status = 'error_admin';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'htaccess-security-settings',
					'tab'    => $tab,
					'status' => $status,
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * 全設定削除を処理する
	 */
	private function handle_delete_all() {
		if ( ! isset( $_POST['htaccess_ss_delete_all_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['htaccess_ss_delete_all_nonce'] ) ), 'htaccess_ss_delete_all' ) ) {
			wp_die( esc_html__( '不正なリクエストです。', 'htaccess-ss' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( '権限がありません。', 'htaccess-ss' ) );
		}

		// .htaccess からプラグインブロックを除去（backup() が先に走るので DB 削除より前に実行）
		$writer       = new HSS_Htaccess_Writer();
		$root_result  = $writer->write_root( array() );
		$admin_result = $writer->write_wp_admin( array() );

		// DB からすべてのオプションを削除
		delete_option( HSS_Settings::OPTION_KEY );
		delete_option( HSS_Settings::BACKUP_ROOT_KEY );
		delete_option( HSS_Settings::BACKUP_ADMIN_KEY );
		delete_option( HSS_Settings::BACKUP_TIME_KEY );

		$status = 'deleted_all';
		if ( is_wp_error( $root_result ) ) {
			$status = 'error_root';
		} elseif ( is_wp_error( $admin_result ) ) {
			$status = 'error_admin';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'htaccess-security-settings',
					'status' => $status,
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Ajax で .htaccess をダウンロードする
	 */
	public function ajax_download() {
		check_ajax_referer( 'htaccess_ss_download', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$writer    = new HSS_Htaccess_Writer();
		$root_path = $writer->get_root_path();

		if ( ! file_exists( $root_path ) ) {
			wp_die( esc_html__( '.htaccess ファイルが見つかりません。', 'htaccess-ss' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $root_path );

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=".htaccess"' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- .htaccess テキストファイルをそのままダウンロードする
		echo $content;
		exit;
	}

	/**
	 * 設定ページをレンダリングする
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$all_settings = $this->settings->get_settings();
		$current_tab  = $this->get_current_tab();
		$tabs         = $this->tabs;
		$writer       = new HSS_Htaccess_Writer();
		$backup_time  = $writer->get_backup_time();

		// 実際の .htaccess ファイル内容を取得（サイドバー表示用）
		$root_htaccess_path = $writer->get_root_path();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$root_htaccess = file_exists( $root_htaccess_path ) ? file_get_contents( $root_htaccess_path ) : '';

		$admin_htaccess_path = $writer->get_wp_admin_path();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$admin_htaccess = file_exists( $admin_htaccess_path ) ? file_get_contents( $admin_htaccess_path ) : '';

		// ステータスメッセージ
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 表示用の status パラメータのみ
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

		include HSS_PLUGIN_DIR . 'admin/views/page-main.php';
	}

	/**
	 * 現在のタブを取得する
	 *
	 * @return string
	 */
	private function get_current_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- タブ表示用のパラメータ
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'options';
		return in_array( $tab, HSS_Settings::VALID_TABS, true ) ? $tab : 'options';
	}
}
