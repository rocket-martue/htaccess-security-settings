<?php
/**
 * HSS_Htaccess_Builder のテスト
 *
 * @package HtaccessSS
 */

/**
 * ディレクティブ生成テストクラス
 */
class HtaccessBuilderTest extends WP_UnitTestCase {

	/**
	 * テスト対象のビルダーインスタンス
	 *
	 * @var HSS_Htaccess_Builder
	 */
	private $builder;

	/**
	 * テスト前のセットアップ
	 */
	public function set_up(): void {
		parent::set_up();
		$this->builder = new HSS_Htaccess_Builder();
	}

	/**
	 * ヘルパー: build_root の結果を文字列に結合する
	 *
	 * @param array $settings 設定配列
	 * @return string
	 */
	private function build_root_string( $settings ) {
		return implode( "\n", $this->builder->build_root( $settings ) );
	}

	/**
	 * ヘルパー: 全機能 OFF の設定を返す
	 *
	 * @return array
	 */
	private function get_all_off_settings() {
		return array(
			'options'  => array(
				'disable_multiviews'  => false,
				'disable_indexes'     => false,
				'error_document'      => false,
				'block_xmlrpc'        => false,
				'protect_wp_config'   => false,
				'protect_htaccess'    => false,
				'block_dangerous_ext' => false,
				'wp_login_basic_auth' => false,
				'htpasswd_path'       => '',
			),
			'ip_block' => array(
				'enabled' => false,
				'list'    => '',
			),
			'rewrite'  => array(
				'normalize_slashes'     => false,
				'block_bad_bots'        => false,
				'bad_bot_list'          => '',
				'block_backdoors'       => false,
				'backdoor_list'         => '',
				'block_wp_nesting'      => false,
				'block_wp_includes_dir' => false,
				'https_redirect'        => false,
				'x_forwarded_proto'     => false,
				'block_bad_query'       => false,
				'bad_query_list'        => '',
			),
			'headers'  => array(
				'hsts_enabled'            => false,
				'hsts_max_age'            => 63072000,
				'hsts_include_subdomains' => false,
				'hsts_preload'            => false,
				'csp_enabled'             => false,
				'csp_mode'                => 'enforce',
				'csp_upgrade_insecure'    => false,
				'csp_default_src'         => '',
				'csp_script_src'          => '',
				'csp_style_src'           => '',
				'csp_img_src'             => '',
				'csp_font_src'            => '',
				'csp_connect_src'         => '',
				'csp_frame_src'           => '',
				'csp_frame_ancestors'     => '',
				'x_content_type'          => false,
				'x_frame_options'         => '',
				'referrer_policy'         => '',
				'permissions_enabled'     => false,
				'perm_camera'             => false,
				'perm_microphone'         => false,
				'perm_payment'            => false,
				'perm_usb'                => false,
				'perm_gyroscope'          => false,
				'perm_magnetometer'       => false,
				'perm_geolocation'        => false,
			),
			'cache'    => array(
				'gzip'          => false,
				'expires'       => false,
				'cache_control' => false,
				'etag_disable'  => false,
				'keep_alive'    => false,
			),
			'wp_admin' => array(
				'basic_auth'         => false,
				'htpasswd_path'      => '',
				'ajax_exclude'       => false,
				'upgrade_ip_exclude' => false,
				'server_ip'          => '',
			),
		);
	}

	// =========================================================================
	// build_root: 全体構造テスト
	// =========================================================================

	/**
	 * build_root が配列を返すことを確認
	 */
	public function test_build_root_returns_array() {
		$settings = HSS_Settings::get_defaults();
		$result   = $this->builder->build_root( $settings );

		$this->assertIsArray( $result );
	}

	/**
	 * ヘルパー: recommended プリセットの設定を返す
	 *
	 * @return array
	 */
	private function get_recommended_settings() {
		return HSS_Settings::get_preset( 'recommended' );
	}

	/**
	 * 全機能 OFF で build_root は空配列を返す
	 */
	public function test_build_root_all_off_returns_empty() {
		$settings = $this->get_all_off_settings();
		$result   = $this->builder->build_root( $settings );

		$this->assertSame( array(), $result );
	}

	/**
	 * おすすめ設定でセキュリティセクションヘッダーが出力される
	 */
	public function test_build_root_recommended_has_security_section() {
		$settings = $this->get_recommended_settings();
		$output   = $this->build_root_string( $settings );

		$this->assertStringContainsString( '# Security Settings', $output );
	}

	/**
	 * おすすめ設定でリライトルールセクションが出力される
	 */
	public function test_build_root_recommended_has_rewrite_section() {
		$settings = $this->get_recommended_settings();
		$output   = $this->build_root_string( $settings );

		$this->assertStringContainsString( '# Rewrite Rules', $output );
	}

	/**
	 * おすすめ設定でキャッシュセクションが出力される
	 */
	public function test_build_root_recommended_has_cache_section() {
		$settings = $this->get_recommended_settings();
		$output   = $this->build_root_string( $settings );

		$this->assertStringContainsString( '# Cache & Performance Settings', $output );
	}

	/**
	 * おすすめ設定でヘッダーセクションが出力される
	 */
	public function test_build_root_recommended_has_headers_section() {
		$settings = $this->get_recommended_settings();
		$output   = $this->build_root_string( $settings );

		$this->assertStringContainsString( '# Security Response Headers', $output );
	}

	// =========================================================================
	// Options セクション
	// =========================================================================

	/**
	 * Options -MultiViews -Indexes が出力される
	 */
	public function test_options_multiviews_and_indexes() {
		$settings                                  = $this->get_all_off_settings();
		$settings['options']['disable_multiviews'] = true;
		$settings['options']['disable_indexes']    = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Options -MultiViews -Indexes', $output );
	}

	/**
	 * MultiViews のみ有効
	 */
	public function test_options_multiviews_only() {
		$settings                                  = $this->get_all_off_settings();
		$settings['options']['disable_multiviews'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Options -MultiViews', $output );
		$this->assertStringNotContainsString( '-Indexes', $output );
	}

	/**
	 * ErrorDocument が出力される
	 */
	public function test_options_error_document() {
		$settings                              = $this->get_all_off_settings();
		$settings['options']['error_document'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'ErrorDocument 403 default', $output );
		$this->assertStringContainsString( 'ErrorDocument 404 default', $output );
	}

	// =========================================================================
	// ファイル保護セクション
	// =========================================================================

	/**
	 * xmlrpc.php ブロックが出力される
	 */
	public function test_file_protection_xmlrpc() {
		$settings                            = $this->get_all_off_settings();
		$settings['options']['block_xmlrpc'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<Files xmlrpc.php>', $output );
		$this->assertStringContainsString( 'Require all denied', $output );
	}

	/**
	 * wp-config.php 保護が出力される
	 */
	public function test_file_protection_wp_config() {
		$settings                                 = $this->get_all_off_settings();
		$settings['options']['protect_wp_config'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<Files wp-config.php>', $output );
		$this->assertStringContainsString( 'Require all denied', $output );
	}

	/**
	 * .htaccess 保護が出力される
	 */
	public function test_file_protection_htaccess() {
		$settings                                = $this->get_all_off_settings();
		$settings['options']['protect_htaccess'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<Files .htaccess>', $output );
		$this->assertStringContainsString( 'Require all denied', $output );
	}

	/**
	 * 危険な拡張子ブロックが出力される
	 */
	public function test_file_protection_dangerous_extensions() {
		$settings                                   = $this->get_all_off_settings();
		$settings['options']['block_dangerous_ext'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<FilesMatch "\.(inc|log|sh|sql)$">', $output );
		$this->assertStringContainsString( 'Require all denied', $output );
	}

	/**
	 * wp-login.php Basic 認証が出力される
	 */
	public function test_file_protection_wp_login_basic_auth() {
		$settings                                   = $this->get_all_off_settings();
		$settings['options']['wp_login_basic_auth'] = true;
		$settings['options']['htpasswd_path']       = '/path/to/.htpasswd';

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<Files wp-login.php>', $output );
		$this->assertStringContainsString( 'AuthUserFile "/path/to/.htpasswd"', $output );
		$this->assertStringContainsString( 'AuthType BASIC', $output );
		$this->assertStringContainsString( 'require valid-user', $output );
	}

	/**
	 * wp-login.php Basic 認証は htpasswd_path が空なら出力されない
	 */
	public function test_file_protection_wp_login_no_path_no_output() {
		$settings                                   = $this->get_all_off_settings();
		$settings['options']['wp_login_basic_auth'] = true;
		$settings['options']['htpasswd_path']       = '';

		$output = $this->build_root_string( $settings );

		$this->assertStringNotContainsString( 'AuthType BASIC', $output );
	}

	/**
	 * ファイル保護ブロックが Apache 2.2/2.4 両対応になっている
	 */
	public function test_file_protection_apache_compat() {
		$settings                            = $this->get_all_off_settings();
		$settings['options']['block_xmlrpc'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<IfModule mod_authz_core.c>', $output );
		$this->assertStringContainsString( '<IfModule !mod_authz_core.c>', $output );
		$this->assertStringContainsString( 'Order deny,allow', $output );
		$this->assertStringContainsString( 'Deny from all', $output );
	}

	// =========================================================================
	// IP ブロックセクション
	// =========================================================================

	/**
	 * 単一 IP が Require not ip に変換される
	 */
	public function test_ip_block_single_ip() {
		$settings                        = $this->get_all_off_settings();
		$settings['ip_block']['enabled'] = true;
		$settings['ip_block']['list']    = '192.168.1.100';

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<RequireAll>', $output );
		$this->assertStringContainsString( 'Require all granted', $output );
		$this->assertStringContainsString( 'Require not ip 192.168.1.100', $output );
		$this->assertStringContainsString( '</RequireAll>', $output );
	}

	/**
	 * CIDR 表記が Require not ip に変換される
	 */
	public function test_ip_block_cidr() {
		$settings                        = $this->get_all_off_settings();
		$settings['ip_block']['enabled'] = true;
		$settings['ip_block']['list']    = '10.0.0.0/8';

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Require not ip 10.0.0.0/8', $output );
	}

	/**
	 * 複数 IP が全て Require not ip に変換される
	 */
	public function test_ip_block_multiple_ips() {
		$settings                        = $this->get_all_off_settings();
		$settings['ip_block']['enabled'] = true;
		$settings['ip_block']['list']    = "192.168.1.100\n10.0.0.0/8\n172.16.0.1";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Require not ip 192.168.1.100', $output );
		$this->assertStringContainsString( 'Require not ip 10.0.0.0/8', $output );
		$this->assertStringContainsString( 'Require not ip 172.16.0.1', $output );
	}

	/**
	 * IP ブロック無効時は出力なし
	 */
	public function test_ip_block_disabled() {
		$settings                        = $this->get_all_off_settings();
		$settings['ip_block']['enabled'] = false;
		$settings['ip_block']['list']    = '192.168.1.100';

		$output = $this->build_root_string( $settings );

		$this->assertStringNotContainsString( 'Require not ip', $output );
	}

	/**
	 * IP ブロック有効だがリストが空なら出力なし
	 */
	public function test_ip_block_empty_list() {
		$settings                        = $this->get_all_off_settings();
		$settings['ip_block']['enabled'] = true;
		$settings['ip_block']['list']    = '';

		$output = $this->build_root_string( $settings );

		$this->assertStringNotContainsString( 'RequireAll', $output );
	}

	// =========================================================================
	// リライトルールセクション
	// =========================================================================

	/**
	 * スラッシュ正規化が出力される
	 */
	public function test_rewrite_normalize_slashes() {
		$settings                                 = $this->get_all_off_settings();
		$settings['rewrite']['normalize_slashes'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'RewriteEngine On', $output );
		$this->assertStringContainsString( 'RewriteCond %{THE_REQUEST} \s[^\s]*//', $output );
		$this->assertStringContainsString( 'RewriteRule ^ %{REQUEST_URI} [R=301,L,NE]', $output );
	}

	/**
	 * ボットブロックが出力される
	 */
	public function test_rewrite_block_bad_bots() {
		$settings                              = $this->get_all_off_settings();
		$settings['rewrite']['block_bad_bots'] = true;
		$settings['rewrite']['bad_bot_list']   = "wget\ncurl\nsqlmap";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'HTTP_USER_AGENT', $output );
		$this->assertStringContainsString( 'wget', $output );
		$this->assertStringContainsString( 'curl', $output );
		$this->assertStringContainsString( 'sqlmap', $output );
		$this->assertStringContainsString( '[NC]', $output );
		$this->assertStringContainsString( '[F,L]', $output );
	}

	/**
	 * ボットブロック有効でもリストが空なら出力なし
	 */
	public function test_rewrite_block_bad_bots_empty_list() {
		$settings                              = $this->get_all_off_settings();
		$settings['rewrite']['block_bad_bots'] = true;
		$settings['rewrite']['bad_bot_list']   = '';

		$output = $this->build_root_string( $settings );

		$this->assertStringNotContainsString( 'HTTP_USER_AGENT', $output );
	}

	/**
	 * バックドアブロックが出力される
	 */
	public function test_rewrite_block_backdoors() {
		$settings                               = $this->get_all_off_settings();
		$settings['rewrite']['block_backdoors'] = true;
		$settings['rewrite']['backdoor_list']   = "alfa.php\n0x.php";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'REQUEST_URI', $output );
		$this->assertStringContainsString( 'alfa\\.php', $output );
		$this->assertStringContainsString( '0x\\.php', $output );
		$this->assertStringContainsString( '[F,L]', $output );
	}

	/**
	 * wp-nesting 防止が出力される
	 */
	public function test_rewrite_block_wp_nesting() {
		$settings                                = $this->get_all_off_settings();
		$settings['rewrite']['block_wp_nesting'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'wp-(content|admin|includes)/.*wp-(content|admin|includes)/', $output );
	}

	/**
	 * wp-includes ディレクトリブラウズ防止が出力される
	 */
	public function test_rewrite_block_wp_includes_dir() {
		$settings                                     = $this->get_all_off_settings();
		$settings['rewrite']['block_wp_includes_dir'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '^/wp-includes/', $output );
		$this->assertStringContainsString( 'REQUEST_FILENAME', $output );
	}

	/**
	 * HTTPS リダイレクトが出力される
	 */
	public function test_rewrite_https_redirect() {
		$settings                              = $this->get_all_off_settings();
		$settings['rewrite']['https_redirect'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'HTTPS', $output );
		$this->assertStringContainsString( 'https://%{HTTP_HOST}%{REQUEST_URI}', $output );
		$this->assertStringContainsString( '[R=301,L]', $output );
	}

	/**
	 * HTTPS リダイレクト + X-Forwarded-Proto 対応
	 */
	public function test_rewrite_https_redirect_with_x_forwarded_proto() {
		$settings                                 = $this->get_all_off_settings();
		$settings['rewrite']['https_redirect']    = true;
		$settings['rewrite']['x_forwarded_proto'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'X-Forwarded-Proto', $output );
	}

	/**
	 * HTTPS リダイレクトで X-Forwarded-Proto なし
	 */
	public function test_rewrite_https_redirect_without_x_forwarded_proto() {
		$settings                                 = $this->get_all_off_settings();
		$settings['rewrite']['https_redirect']    = true;
		$settings['rewrite']['x_forwarded_proto'] = false;

		$output = $this->build_root_string( $settings );

		$this->assertStringNotContainsString( 'X-Forwarded-Proto', $output );
	}

	/**
	 * 不正クエリブロックが出力される
	 */
	public function test_rewrite_block_bad_query() {
		$settings                               = $this->get_all_off_settings();
		$settings['rewrite']['block_bad_query'] = true;
		$settings['rewrite']['bad_query_list']  = "w\ntest_param";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'QUERY_STRING', $output );
		$this->assertStringContainsString( '[R=410,L]', $output );
	}

	/**
	 * リライトルールは mod_rewrite の IfModule で囲まれる
	 */
	public function test_rewrite_section_wrapped_in_ifmodule() {
		$settings                                 = $this->get_all_off_settings();
		$settings['rewrite']['normalize_slashes'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<IfModule mod_rewrite.c>', $output );
		$this->assertStringContainsString( '</IfModule>', $output );
	}

	// =========================================================================
	// セキュリティヘッダーセクション
	// =========================================================================

	/**
	 * HSTS ヘッダーが出力される
	 */
	public function test_headers_hsts() {
		$settings                            = $this->get_all_off_settings();
		$settings['headers']['hsts_enabled'] = true;
		$settings['headers']['hsts_max_age'] = 63072000;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Strict-Transport-Security', $output );
		$this->assertStringContainsString( 'max-age=63072000', $output );
	}

	/**
	 * HSTS に includeSubDomains が含まれる
	 */
	public function test_headers_hsts_include_subdomains() {
		$settings                                       = $this->get_all_off_settings();
		$settings['headers']['hsts_enabled']            = true;
		$settings['headers']['hsts_max_age']            = 63072000;
		$settings['headers']['hsts_include_subdomains'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'includeSubDomains', $output );
	}

	/**
	 * HSTS に preload が含まれる
	 */
	public function test_headers_hsts_preload() {
		$settings                            = $this->get_all_off_settings();
		$settings['headers']['hsts_enabled'] = true;
		$settings['headers']['hsts_max_age'] = 63072000;
		$settings['headers']['hsts_preload'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'preload', $output );
	}

	/**
	 * HSTS は HTTPS 接続時のみ送信される（expr 条件付き）
	 */
	public function test_headers_hsts_https_only() {
		$settings                            = $this->get_all_off_settings();
		$settings['headers']['hsts_enabled'] = true;
		$settings['headers']['hsts_max_age'] = 63072000;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( "expr=%{HTTPS} == 'on'", $output );
	}

	/**
	 * CSP Enforce モードで Content-Security-Policy ヘッダーが出力される
	 */
	public function test_headers_csp_enforce() {
		$settings                               = $this->get_all_off_settings();
		$settings['headers']['csp_enabled']     = true;
		$settings['headers']['csp_mode']        = 'enforce';
		$settings['headers']['csp_default_src'] = "'self'";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Content-Security-Policy', $output );
		$this->assertStringNotContainsString( 'Content-Security-Policy-Report-Only', $output );
		$this->assertStringContainsString( "default-src 'self'", $output );
	}

	/**
	 * CSP Report-Only モードで Content-Security-Policy-Report-Only が出力される
	 */
	public function test_headers_csp_report_only() {
		$settings                               = $this->get_all_off_settings();
		$settings['headers']['csp_enabled']     = true;
		$settings['headers']['csp_mode']        = 'report-only';
		$settings['headers']['csp_default_src'] = "'self'";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Content-Security-Policy-Report-Only', $output );
	}

	/**
	 * CSP Enforce モードで upgrade-insecure-requests が含まれる
	 */
	public function test_headers_csp_enforce_includes_upgrade_insecure() {
		$settings                                    = $this->get_all_off_settings();
		$settings['headers']['csp_enabled']          = true;
		$settings['headers']['csp_mode']             = 'enforce';
		$settings['headers']['csp_upgrade_insecure'] = true;
		$settings['headers']['csp_default_src']      = "'self'";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'upgrade-insecure-requests', $output );
	}

	/**
	 * CSP Report-Only モードでは upgrade-insecure-requests が除外される
	 */
	public function test_headers_csp_report_only_excludes_upgrade_insecure() {
		$settings                                    = $this->get_all_off_settings();
		$settings['headers']['csp_enabled']          = true;
		$settings['headers']['csp_mode']             = 'report-only';
		$settings['headers']['csp_upgrade_insecure'] = true;
		$settings['headers']['csp_default_src']      = "'self'";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Content-Security-Policy-Report-Only', $output );
		$this->assertStringNotContainsString( 'upgrade-insecure-requests', $output );
	}

	/**
	 * CSP の各ディレクティブが全て出力される
	 */
	public function test_headers_csp_all_directives() {
		$settings                                   = $this->get_all_off_settings();
		$settings['headers']['csp_enabled']         = true;
		$settings['headers']['csp_mode']            = 'enforce';
		$settings['headers']['csp_default_src']     = "'self'";
		$settings['headers']['csp_script_src']      = "'self' 'unsafe-inline'";
		$settings['headers']['csp_style_src']       = "'self' 'unsafe-inline'";
		$settings['headers']['csp_img_src']         = "'self' data:";
		$settings['headers']['csp_font_src']        = "'self' https:";
		$settings['headers']['csp_connect_src']     = "'self'";
		$settings['headers']['csp_frame_src']       = "'self'";
		$settings['headers']['csp_frame_ancestors'] = "'self'";

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( "default-src 'self'", $output );
		$this->assertStringContainsString( "script-src 'self' 'unsafe-inline'", $output );
		$this->assertStringContainsString( "style-src 'self' 'unsafe-inline'", $output );
		$this->assertStringContainsString( "img-src 'self' data:", $output );
		$this->assertStringContainsString( "font-src 'self' https:", $output );
		$this->assertStringContainsString( "connect-src 'self'", $output );
		$this->assertStringContainsString( "frame-src 'self'", $output );
		$this->assertStringContainsString( "frame-ancestors 'self'", $output );
	}

	/**
	 * X-Content-Type-Options nosniff が出力される
	 */
	public function test_headers_x_content_type_options() {
		$settings                              = $this->get_all_off_settings();
		$settings['headers']['x_content_type'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'X-Content-Type-Options', $output );
		$this->assertStringContainsString( 'nosniff', $output );
	}

	/**
	 * X-Frame-Options SAMEORIGIN が出力される
	 */
	public function test_headers_x_frame_options_sameorigin() {
		$settings                               = $this->get_all_off_settings();
		$settings['headers']['x_frame_options'] = 'SAMEORIGIN';

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'X-Frame-Options', $output );
		$this->assertStringContainsString( 'SAMEORIGIN', $output );
	}

	/**
	 * X-Frame-Options DENY が出力される
	 */
	public function test_headers_x_frame_options_deny() {
		$settings                               = $this->get_all_off_settings();
		$settings['headers']['x_frame_options'] = 'DENY';

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '"DENY"', $output );
	}

	/**
	 * Referrer-Policy が出力される
	 */
	public function test_headers_referrer_policy() {
		$settings                               = $this->get_all_off_settings();
		$settings['headers']['referrer_policy'] = 'strict-origin-when-cross-origin';

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Referrer-Policy', $output );
		$this->assertStringContainsString( 'strict-origin-when-cross-origin', $output );
	}

	/**
	 * Permissions-Policy が出力される
	 */
	public function test_headers_permissions_policy() {
		$settings                                   = $this->get_all_off_settings();
		$settings['headers']['permissions_enabled'] = true;
		$settings['headers']['perm_camera']         = true;
		$settings['headers']['perm_microphone']     = true;
		$settings['headers']['perm_payment']        = true;
		$settings['headers']['perm_usb']            = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Permissions-Policy', $output );
		$this->assertStringContainsString( 'camera=()', $output );
		$this->assertStringContainsString( 'microphone=()', $output );
		$this->assertStringContainsString( 'payment=()', $output );
		$this->assertStringContainsString( 'usb=()', $output );
	}

	/**
	 * Permissions-Policy で geolocation のみ有効
	 */
	public function test_headers_permissions_policy_geolocation_only() {
		$settings                                   = $this->get_all_off_settings();
		$settings['headers']['permissions_enabled'] = true;
		$settings['headers']['perm_geolocation']    = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'geolocation=()', $output );
		$this->assertStringNotContainsString( 'camera=()', $output );
	}

	/**
	 * ヘッダーセクションは mod_headers の IfModule で囲まれる
	 */
	public function test_headers_section_wrapped_in_ifmodule() {
		$settings                              = $this->get_all_off_settings();
		$settings['headers']['x_content_type'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<IfModule mod_headers.c>', $output );
	}

	// =========================================================================
	// キャッシュセクション
	// =========================================================================

	/**
	 * Gzip 圧縮が出力される
	 */
	public function test_cache_gzip() {
		$settings                  = $this->get_all_off_settings();
		$settings['cache']['gzip'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<IfModule mod_deflate.c>', $output );
		$this->assertStringContainsString( 'SetOutputFilter DEFLATE', $output );
	}

	/**
	 * Expires ヘッダーが出力される
	 */
	public function test_cache_expires() {
		$settings                     = $this->get_all_off_settings();
		$settings['cache']['expires'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( '<IfModule mod_expires.c>', $output );
		$this->assertStringContainsString( 'ExpiresActive On', $output );
	}

	/**
	 * Cache-Control immutable が出力される
	 */
	public function test_cache_control() {
		$settings                           = $this->get_all_off_settings();
		$settings['cache']['cache_control'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Cache-Control', $output );
		$this->assertStringContainsString( 'immutable', $output );
	}

	/**
	 * ETag 無効化が出力される
	 */
	public function test_cache_etag_disable() {
		$settings                          = $this->get_all_off_settings();
		$settings['cache']['etag_disable'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Header unset ETag', $output );
		$this->assertStringContainsString( 'FileETag None', $output );
	}

	/**
	 * Keep-Alive が出力される
	 */
	public function test_cache_keep_alive() {
		$settings                        = $this->get_all_off_settings();
		$settings['cache']['keep_alive'] = true;

		$output = $this->build_root_string( $settings );

		$this->assertStringContainsString( 'Connection keep-alive', $output );
	}

	// =========================================================================
	// build_wp_admin テスト
	// =========================================================================

	/**
	 * wp-admin Basic 認証が出力される
	 */
	public function test_wp_admin_basic_auth() {
		$settings                              = $this->get_all_off_settings();
		$settings['wp_admin']['basic_auth']    = true;
		$settings['wp_admin']['htpasswd_path'] = '/path/to/.htpasswd';

		$result = $this->builder->build_wp_admin( $settings );
		$output = implode( "\n", $result );

		$this->assertStringContainsString( 'AuthUserFile "/path/to/.htpasswd"', $output );
		$this->assertStringContainsString( 'AuthType BASIC', $output );
		$this->assertStringContainsString( 'require valid-user', $output );
	}

	/**
	 * wp-admin Basic 認証で admin-ajax.php が除外される
	 */
	public function test_wp_admin_ajax_exclude() {
		$settings                              = $this->get_all_off_settings();
		$settings['wp_admin']['basic_auth']    = true;
		$settings['wp_admin']['htpasswd_path'] = '/path/to/.htpasswd';
		$settings['wp_admin']['ajax_exclude']  = true;

		$result = $this->builder->build_wp_admin( $settings );
		$output = implode( "\n", $result );

		$this->assertStringContainsString( '<Files admin-ajax.php>', $output );
		$this->assertStringContainsString( 'Require all granted', $output );
	}

	/**
	 * wp-admin upgrade.php にサーバー IP 除外が出力される
	 */
	public function test_wp_admin_upgrade_ip_exclude() {
		$settings                                   = $this->get_all_off_settings();
		$settings['wp_admin']['basic_auth']         = true;
		$settings['wp_admin']['htpasswd_path']      = '/path/to/.htpasswd';
		$settings['wp_admin']['upgrade_ip_exclude'] = true;
		$settings['wp_admin']['server_ip']          = '127.0.0.1';

		$result = $this->builder->build_wp_admin( $settings );
		$output = implode( "\n", $result );

		$this->assertStringContainsString( '<Files upgrade.php>', $output );
		$this->assertStringContainsString( 'Require ip 127.0.0.1', $output );
	}

	/**
	 * wp-admin Basic 認証無効時は空配列
	 */
	public function test_wp_admin_disabled_returns_empty() {
		$settings                           = $this->get_all_off_settings();
		$settings['wp_admin']['basic_auth'] = false;

		$result = $this->builder->build_wp_admin( $settings );

		$this->assertEmpty( $result );
	}

	/**
	 * wp-admin の htpasswd_path が空なら空配列
	 */
	public function test_wp_admin_empty_path_returns_empty() {
		$settings                              = $this->get_all_off_settings();
		$settings['wp_admin']['basic_auth']    = true;
		$settings['wp_admin']['htpasswd_path'] = '';

		$result = $this->builder->build_wp_admin( $settings );

		$this->assertEmpty( $result );
	}

	// =========================================================================
	// 全機能 ON テスト
	// =========================================================================

	/**
	 * 全設定を有効にした場合にエラーなく出力される
	 */
	public function test_build_root_all_enabled() {
		$settings = $this->get_recommended_settings();

		$settings['ip_block']['enabled']            = true;
		$settings['ip_block']['list']               = "1.2.3.4\n5.6.7.8/24";
		$settings['options']['wp_login_basic_auth'] = true;
		$settings['options']['htpasswd_path']       = '/path/.htpasswd';
		$settings['rewrite']['block_bad_query']     = true;
		$settings['rewrite']['bad_query_list']      = 'w';

		$result = $this->builder->build_root( $settings );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		$output = implode( "\n", $result );
		$this->assertStringContainsString( '# Security Settings', $output );
		$this->assertStringContainsString( '# Rewrite Rules', $output );
		$this->assertStringContainsString( '# Cache & Performance Settings', $output );
		$this->assertStringContainsString( '# Security Response Headers', $output );
	}
}
