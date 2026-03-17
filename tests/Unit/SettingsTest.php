<?php
/**
 * HSS_Settings のテスト
 *
 * @package HtaccessSS
 */

/**
 * 設定管理クラスのテスト
 */
class SettingsTest extends WP_UnitTestCase {

	/**
	 * テスト対象のインスタンス
	 *
	 * @var HSS_Settings
	 */
	private $settings;

	/**
	 * テスト前のセットアップ
	 */
	public function set_up(): void {
		parent::set_up();
		$this->settings = new HSS_Settings();
		delete_option( HSS_Settings::OPTION_KEY );
	}

	// =========================================================================
	// get_defaults
	// =========================================================================

	/**
	 * get_defaults が全タブのキーを持っている
	 */
	public function test_get_defaults_has_all_tabs() {
		$defaults = HSS_Settings::get_defaults();

		foreach ( HSS_Settings::VALID_TABS as $tab ) {
			$this->assertArrayHasKey( $tab, $defaults, "タブ {$tab} がデフォルトに存在しない" );
			$this->assertIsArray( $defaults[ $tab ] );
		}
	}

	/**
	 * デフォルト設定が空でないことを確認
	 */
	public function test_get_defaults_not_empty() {
		$defaults = HSS_Settings::get_defaults();

		$this->assertNotEmpty( $defaults );
		$this->assertNotEmpty( $defaults['options'] );
		$this->assertNotEmpty( $defaults['headers'] );
		$this->assertNotEmpty( $defaults['cache'] );
	}

	/**
	 * デフォルトの options タブに全キーが揃っている
	 */
	public function test_get_defaults_options_keys() {
		$defaults = HSS_Settings::get_defaults();
		$options  = $defaults['options'];

		$expected_keys = array(
			'disable_multiviews',
			'disable_indexes',
			'error_document',
			'block_xmlrpc',
			'protect_wp_config',
			'protect_htaccess',
			'block_dangerous_ext',
			'wp_login_basic_auth',
			'htpasswd_path',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $options, "options に {$key} が無い" );
		}
	}

	/**
	 * デフォルトの headers タブの CSP 関連キーが揃っている
	 */
	public function test_get_defaults_headers_csp_keys() {
		$defaults = HSS_Settings::get_defaults();
		$headers  = $defaults['headers'];

		$csp_keys = array(
			'csp_enabled',
			'csp_mode',
			'csp_upgrade_insecure',
			'csp_default_src',
			'csp_script_src',
			'csp_style_src',
			'csp_img_src',
			'csp_font_src',
			'csp_connect_src',
			'csp_frame_src',
			'csp_frame_ancestors',
		);

		foreach ( $csp_keys as $key ) {
			$this->assertArrayHasKey( $key, $headers, "headers に {$key} が無い" );
		}
	}

	// =========================================================================
	// get_settings
	// =========================================================================

	/**
	 * オプションが未保存ならデフォルトが返る
	 */
	public function test_get_settings_returns_defaults_when_no_option() {
		$settings = $this->settings->get_settings();
		$defaults = HSS_Settings::get_defaults();

		$this->assertSame( $defaults, $settings );
	}

	/**
	 * 一部のみ保存されていてもデフォルトとマージされる
	 */
	public function test_get_settings_merges_partial_saved() {
		update_option(
			HSS_Settings::OPTION_KEY,
			array(
				'options' => array(
					'disable_multiviews' => false,
				),
			)
		);

		$settings = $this->settings->get_settings();

		$this->assertFalse( $settings['options']['disable_multiviews'] );
		// 保存されていないキーはデフォルト値に
		$defaults = HSS_Settings::get_defaults();
		$this->assertSame( $defaults['options']['disable_indexes'], $settings['options']['disable_indexes'] );
		// 他のタブもデフォルト値に
		$this->assertSame( $defaults['headers'], $settings['headers'] );
	}

	// =========================================================================
	// save_settings
	// =========================================================================

	/**
	 * save_settings で設定が保存され get_settings で取得できる
	 */
	public function test_save_and_get_settings() {
		$defaults                                  = HSS_Settings::get_defaults();
		$defaults['options']['disable_multiviews'] = false;

		$this->settings->save_settings( $defaults );

		$loaded = $this->settings->get_settings();
		$this->assertFalse( $loaded['options']['disable_multiviews'] );
	}

	// =========================================================================
	// sanitize_and_merge: Options タブ
	// =========================================================================

	/**
	 * options タブのチェックボックスがサニタイズされる
	 */
	public function test_sanitize_options_tab_checkboxes() {
		$input = array(
			'disable_multiviews'  => '1',
			'disable_indexes'     => '1',
			'error_document'      => '',
			'block_xmlrpc'        => '1',
			'protect_wp_config'   => '1',
			'protect_htaccess'    => '1',
			'block_dangerous_ext' => '1',
			'wp_login_basic_auth' => '',
			'htpasswd_path'       => '/path/to/.htpasswd',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'options' );

		$this->assertTrue( $result['options']['disable_multiviews'] );
		$this->assertTrue( $result['options']['disable_indexes'] );
		$this->assertFalse( $result['options']['error_document'] );
		$this->assertFalse( $result['options']['wp_login_basic_auth'] );
		$this->assertSame( '/path/to/.htpasswd', $result['options']['htpasswd_path'] );
	}

	/**
	 * htpasswd_path が sanitize_text_field で処理される
	 */
	public function test_sanitize_options_htpasswd_path_sanitized() {
		$input = array(
			'htpasswd_path' => '/path/to/.htpasswd<script>alert(1)</script>',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'options' );

		$this->assertStringNotContainsString( '<script>', $result['options']['htpasswd_path'] );
	}

	// =========================================================================
	// sanitize_and_merge: IP Block タブ
	// =========================================================================

	/**
	 * 有効な IP がバリデーションを通過する
	 */
	public function test_sanitize_ip_block_valid_ips() {
		$input = array(
			'enabled' => '1',
			'list'    => "192.168.1.100\n10.0.0.0/8\n172.16.0.1",
		);

		$result = $this->settings->sanitize_and_merge( $input, 'ip_block' );

		$this->assertTrue( $result['ip_block']['enabled'] );
		$this->assertStringContainsString( '192.168.1.100', $result['ip_block']['list'] );
		$this->assertStringContainsString( '10.0.0.0/8', $result['ip_block']['list'] );
		$this->assertStringContainsString( '172.16.0.1', $result['ip_block']['list'] );
	}

	/**
	 * 無効な IP がフィルタされる
	 */
	public function test_sanitize_ip_block_invalid_ips_filtered() {
		$input = array(
			'enabled' => '1',
			'list'    => "192.168.1.100\nnot-an-ip\n10.0.0.1",
		);

		$result = $this->settings->sanitize_and_merge( $input, 'ip_block' );

		$this->assertStringContainsString( '192.168.1.100', $result['ip_block']['list'] );
		$this->assertStringNotContainsString( 'not-an-ip', $result['ip_block']['list'] );
		$this->assertStringContainsString( '10.0.0.1', $result['ip_block']['list'] );
	}

	/**
	 * IPv6 アドレスが許可される
	 */
	public function test_sanitize_ip_block_ipv6() {
		$input = array(
			'enabled' => '1',
			'list'    => '::1',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'ip_block' );

		$this->assertStringContainsString( '::1', $result['ip_block']['list'] );
	}

	/**
	 * IPv6 CIDR が許可される
	 */
	public function test_sanitize_ip_block_ipv6_cidr() {
		$input = array(
			'enabled' => '1',
			'list'    => '2001:db8::/32',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'ip_block' );

		$this->assertStringContainsString( '2001:db8::/32', $result['ip_block']['list'] );
	}

	/**
	 * 不正な CIDR プレフィックスが拒否される（IPv4 で /33）
	 */
	public function test_sanitize_ip_block_invalid_cidr_prefix() {
		$input = array(
			'enabled' => '1',
			'list'    => '192.168.1.0/33',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'ip_block' );

		$this->assertStringNotContainsString( '192.168.1.0/33', $result['ip_block']['list'] );
	}

	/**
	 * 空行がフィルタされる
	 */
	public function test_sanitize_ip_block_empty_lines_filtered() {
		$input = array(
			'enabled' => '1',
			'list'    => "192.168.1.1\n\n\n10.0.0.1\n",
		);

		$result = $this->settings->sanitize_and_merge( $input, 'ip_block' );

		$lines = explode( "\n", $result['ip_block']['list'] );
		$this->assertCount( 2, $lines );
	}

	// =========================================================================
	// sanitize_and_merge: Headers タブ
	// =========================================================================

	/**
	 * CSP モードが正しくバリデーションされる
	 */
	public function test_sanitize_headers_csp_mode_valid() {
		$input = array(
			'csp_mode' => 'report-only',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertSame( 'report-only', $result['headers']['csp_mode'] );
	}

	/**
	 * 無効な CSP モードはデフォルトに戻る
	 */
	public function test_sanitize_headers_csp_mode_invalid() {
		$input = array(
			'csp_mode' => 'invalid-mode',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertSame( 'enforce', $result['headers']['csp_mode'] );
	}

	/**
	 * X-Frame-Options の有効な値が受理される
	 */
	public function test_sanitize_headers_x_frame_options_valid() {
		$input = array(
			'x_frame_options' => 'DENY',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertSame( 'DENY', $result['headers']['x_frame_options'] );
	}

	/**
	 * X-Frame-Options の無効な値はデフォルトに戻る
	 */
	public function test_sanitize_headers_x_frame_options_invalid() {
		$input = array(
			'x_frame_options' => 'ALLOW-ALL',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertSame( 'SAMEORIGIN', $result['headers']['x_frame_options'] );
	}

	/**
	 * Referrer-Policy の有効な値が受理される
	 */
	public function test_sanitize_headers_referrer_policy_valid() {
		$valid_policies = array(
			'no-referrer',
			'no-referrer-when-downgrade',
			'origin',
			'origin-when-cross-origin',
			'same-origin',
			'strict-origin',
			'strict-origin-when-cross-origin',
			'unsafe-url',
		);

		foreach ( $valid_policies as $policy ) {
			$input  = array( 'referrer_policy' => $policy );
			$result = $this->settings->sanitize_and_merge( $input, 'headers' );
			$this->assertSame( $policy, $result['headers']['referrer_policy'], "Referrer-Policy {$policy} が受理されない" );
		}
	}

	/**
	 * Referrer-Policy の無効な値はデフォルトに戻る
	 */
	public function test_sanitize_headers_referrer_policy_invalid() {
		$input = array(
			'referrer_policy' => 'invalid-policy',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertSame( 'strict-origin-when-cross-origin', $result['headers']['referrer_policy'] );
	}

	/**
	 * HSTS max_age が上限を超えるとデフォルトに戻る
	 */
	public function test_sanitize_headers_hsts_max_age_overflow() {
		$input = array(
			'hsts_max_age' => 999999999,
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertSame( 63072000, $result['headers']['hsts_max_age'] );
	}

	/**
	 * HSTS max_age が 0 で受理される
	 */
	public function test_sanitize_headers_hsts_max_age_zero() {
		$input = array(
			'hsts_max_age' => 0,
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertSame( 0, $result['headers']['hsts_max_age'] );
	}

	/**
	 * CSP の値から不正な文字が除去される
	 */
	public function test_sanitize_headers_csp_value_strips_bad_chars() {
		$input = array(
			'csp_default_src' => "'self' https:<script>alert(1)</script>",
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertStringNotContainsString( '<', $result['headers']['csp_default_src'] );
		$this->assertStringNotContainsString( '>', $result['headers']['csp_default_src'] );
		$this->assertStringNotContainsString( '(', $result['headers']['csp_default_src'] );
		$this->assertStringNotContainsString( ')', $result['headers']['csp_default_src'] );
	}

	/**
	 * CSP の値で許可された文字が残る
	 */
	public function test_sanitize_headers_csp_value_allows_safe_chars() {
		$input = array(
			'csp_default_src' => "'self' https: *.example.com",
		);

		$result = $this->settings->sanitize_and_merge( $input, 'headers' );

		$this->assertStringContainsString( "'self'", $result['headers']['csp_default_src'] );
		$this->assertStringContainsString( 'https:', $result['headers']['csp_default_src'] );
		$this->assertStringContainsString( '*.example.com', $result['headers']['csp_default_src'] );
	}

	// =========================================================================
	// sanitize_and_merge: Rewrite タブ
	// =========================================================================

	/**
	 * rewrite タブの行リストがサニタイズされる
	 */
	public function test_sanitize_rewrite_line_list() {
		$input = array(
			'block_bad_bots' => '1',
			'bad_bot_list'   => "wget\n\n  curl  \n\nsqlmap",
		);

		$result = $this->settings->sanitize_and_merge( $input, 'rewrite' );

		$lines = explode( "\n", $result['rewrite']['bad_bot_list'] );
		$this->assertCount( 3, $lines );
		$this->assertSame( 'wget', $lines[0] );
		$this->assertSame( 'curl', $lines[1] );
		$this->assertSame( 'sqlmap', $lines[2] );
	}

	// =========================================================================
	// sanitize_and_merge: Cache タブ
	// =========================================================================

	/**
	 * cache タブの全チェックボックスがサニタイズされる
	 */
	public function test_sanitize_cache_tab() {
		$input = array(
			'gzip'          => '1',
			'expires'       => '1',
			'cache_control' => '',
			'etag_disable'  => '1',
			'keep_alive'    => '',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'cache' );

		$this->assertTrue( $result['cache']['gzip'] );
		$this->assertTrue( $result['cache']['expires'] );
		$this->assertFalse( $result['cache']['cache_control'] );
		$this->assertTrue( $result['cache']['etag_disable'] );
		$this->assertFalse( $result['cache']['keep_alive'] );
	}

	// =========================================================================
	// sanitize_and_merge: WP Admin タブ
	// =========================================================================

	/**
	 * wp_admin タブの server_ip が不正なら空文字になる
	 */
	public function test_sanitize_wp_admin_invalid_server_ip() {
		$input = array(
			'basic_auth'         => '1',
			'htpasswd_path'      => '/path/.htpasswd',
			'upgrade_ip_exclude' => '1',
			'server_ip'          => 'not-valid-ip',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'wp_admin' );

		$this->assertSame( '', $result['wp_admin']['server_ip'] );
	}

	/**
	 * wp_admin タブの有効な server_ip が保持される
	 */
	public function test_sanitize_wp_admin_valid_server_ip() {
		$input = array(
			'basic_auth'         => '1',
			'htpasswd_path'      => '/path/.htpasswd',
			'upgrade_ip_exclude' => '1',
			'server_ip'          => '127.0.0.1',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'wp_admin' );

		$this->assertSame( '127.0.0.1', $result['wp_admin']['server_ip'] );
	}

	// =========================================================================
	// sanitize_and_merge が他タブに影響しない
	// =========================================================================

	/**
	 * 1つのタブのサニタイズが他タブの設定を変更しない
	 */
	public function test_sanitize_and_merge_preserves_other_tabs() {
		// まず options タブをカスタマイズして保存
		$defaults                                  = HSS_Settings::get_defaults();
		$defaults['options']['disable_multiviews'] = false;
		$this->settings->save_settings( $defaults );

		// cache タブをサニタイズ
		$input = array(
			'gzip' => '',
		);

		$result = $this->settings->sanitize_and_merge( $input, 'cache' );

		// options タブは変更されていない
		$this->assertFalse( $result['options']['disable_multiviews'] );
		// cache タブは更新されている
		$this->assertFalse( $result['cache']['gzip'] );
	}

	// =========================================================================
	// recursive_parse_args
	// =========================================================================

	/**
	 * 1 階層の配列がマージされる
	 */
	public function test_recursive_parse_args_simple() {
		$args     = array( 'a' => 1 );
		$defaults = array(
			'a' => 0,
			'b' => 2,
		);

		$result = HSS_Settings::recursive_parse_args( $args, $defaults );

		$this->assertSame( 1, $result['a'] );
		$this->assertSame( 2, $result['b'] );
	}

	/**
	 * ネストした配列がディープマージされる
	 */
	public function test_recursive_parse_args_nested() {
		$args = array(
			'level1' => array(
				'a' => 'custom',
			),
		);

		$defaults = array(
			'level1' => array(
				'a' => 'default',
				'b' => 'default',
			),
			'level2' => array(
				'c' => 'default',
			),
		);

		$result = HSS_Settings::recursive_parse_args( $args, $defaults );

		$this->assertSame( 'custom', $result['level1']['a'] );
		$this->assertSame( 'default', $result['level1']['b'] );
		$this->assertSame( 'default', $result['level2']['c'] );
	}

	/**
	 * 空の args だとデフォルトがそのまま返る
	 */
	public function test_recursive_parse_args_empty_args() {
		$defaults = array(
			'key' => 'value',
		);

		$result = HSS_Settings::recursive_parse_args( array(), $defaults );

		$this->assertSame( $defaults, $result );
	}

	// =========================================================================
	// 定数テスト
	// =========================================================================

	/**
	 * OPTION_KEY が正しい値を持っている
	 */
	public function test_option_key_constant() {
		$this->assertSame( 'htaccess_ss_settings', HSS_Settings::OPTION_KEY );
	}

	/**
	 * VALID_TABS が 6 タブ分ある
	 */
	public function test_valid_tabs_count() {
		$this->assertCount( 6, HSS_Settings::VALID_TABS );
	}
}
