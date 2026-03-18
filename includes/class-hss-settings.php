<?php
/**
 * 設定管理クラス
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * プラグイン設定の読み込み・保存・サニタイズを管理するクラス
 */
class HSS_Settings {

	/**
	 * オプションキー
	 *
	 * @var string
	 */
	const OPTION_KEY = 'htaccess_ss_settings';

	/**
	 * バックアップ用オプションキー（ルート .htaccess）
	 *
	 * @var string
	 */
	const BACKUP_ROOT_KEY = 'htaccess_ss_backup_root';

	/**
	 * バックアップ用オプションキー（wp-admin .htaccess）
	 *
	 * @var string
	 */
	const BACKUP_ADMIN_KEY = 'htaccess_ss_backup_admin';

	/**
	 * バックアップ日時用オプションキー
	 *
	 * @var string
	 */
	const BACKUP_TIME_KEY = 'htaccess_ss_backup_time';

	/**
	 * 有効なタブ一覧
	 *
	 * @var array
	 */
	const VALID_TABS = array( 'options', 'ip_block', 'rewrite', 'headers', 'cache', 'wp_admin' );

	/**
	 * デフォルト設定を返す（全項目 OFF のクリーンな初期状態）
	 *
	 * @return array
	 */
	public static function get_defaults() {
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

	/**
	 * プリセット定義を返す
	 *
	 * @return array キー => array( 'label' => string, 'description' => string, 'settings' => array )
	 */
	public static function get_presets() {
		$defaults = self::get_defaults();

		// おすすめ設定（セキュリティ・パフォーマンスのバランス設定）
		$recommended = array(
			'options'  => array(
				'disable_multiviews'  => true,
				'disable_indexes'     => true,
				'error_document'      => true,
				'block_xmlrpc'        => true,
				'protect_wp_config'   => true,
				'protect_htaccess'    => true,
				'block_dangerous_ext' => true,
				'wp_login_basic_auth' => false,
				'htpasswd_path'       => '',
			),
			'ip_block' => array(
				'enabled' => false,
				'list'    => '',
			),
			'rewrite'  => array(
				'normalize_slashes'     => true,
				'block_bad_bots'        => true,
				'bad_bot_list'          => "wget\ncurl\nlibwww-perl\npython\nnikto\nsqlmap\ntimpibot",
				'block_backdoors'       => true,
				'backdoor_list'         => "alfa.php\nadminfuns.php\nwp-fclass.php\nwp-themes.php\nioxi-o.php\n0x.php\nakc.php\ntxets.php",
				'block_wp_nesting'      => true,
				'block_wp_includes_dir' => true,
				'https_redirect'        => true,
				'x_forwarded_proto'     => true,
				'block_bad_query'       => true,
				'bad_query_list'        => '',
			),
			'headers'  => array(
				'hsts_enabled'            => true,
				'hsts_max_age'            => 63072000,
				'hsts_include_subdomains' => true,
				'hsts_preload'            => true,
				'csp_enabled'             => true,
				'csp_mode'                => 'enforce',
				'csp_upgrade_insecure'    => true,
				'csp_default_src'         => "'self' https:",
				'csp_script_src'          => "'self' 'unsafe-inline' 'unsafe-eval' https:",
				'csp_style_src'           => "'self' 'unsafe-inline' https:",
				'csp_img_src'             => "'self' https: data:",
				'csp_font_src'            => "'self' https: data:",
				'csp_connect_src'         => "'self' https:",
				'csp_frame_src'           => "'self' https:",
				'csp_frame_ancestors'     => "'self'",
				'x_content_type'          => true,
				'x_frame_options'         => 'SAMEORIGIN',
				'referrer_policy'         => 'strict-origin-when-cross-origin',
				'permissions_enabled'     => true,
				'perm_camera'             => true,
				'perm_microphone'         => true,
				'perm_payment'            => true,
				'perm_usb'                => true,
				'perm_gyroscope'          => true,
				'perm_magnetometer'       => true,
				'perm_geolocation'        => false,
			),
			'cache'    => array(
				'gzip'          => true,
				'expires'       => true,
				'cache_control' => true,
				'etag_disable'  => true,
				'keep_alive'    => true,
			),
			'wp_admin' => array(
				'basic_auth'         => false,
				'htpasswd_path'      => '',
				'ajax_exclude'       => true,
				'upgrade_ip_exclude' => true,
				'server_ip'          => '',
			),
		);

		return array(
			'recommended'  => array(
				'label'       => __( 'おすすめ設定', 'htaccess-ss' ),
				'description' => __( 'セキュリティとパフォーマンスのバランスが取れた推奨構成', 'htaccess-ss' ),
				'settings'    => $recommended,
			),
			'headers_only' => array(
				'label'       => __( 'セキュリティヘッダーのみ', 'htaccess-ss' ),
				'description' => __( 'セキュリティヘッダーだけを有効にし、他の設定はすべて無効化', 'htaccess-ss' ),
				'settings'    => array_replace_recursive(
					$defaults,
					array(
						'headers' => $recommended['headers'],
					)
				),
			),
			'performance'  => array(
				'label'       => __( 'パフォーマンス重視', 'htaccess-ss' ),
				'description' => __( 'キャッシュ・圧縮設定のみ有効化', 'htaccess-ss' ),
				'settings'    => array_replace_recursive(
					$defaults,
					array(
						'cache' => array(
							'gzip'          => true,
							'expires'       => true,
							'cache_control' => true,
							'etag_disable'  => true,
							'keep_alive'    => true,
						),
					)
				),
			),
			'max_security' => array(
				'label'       => __( '最大セキュリティ', 'htaccess-ss' ),
				'description' => __( 'Basic 認証を除くすべてのセキュリティ項目を有効化', 'htaccess-ss' ),
				'settings'    => array_replace_recursive(
					$recommended,
					array(
						'headers' => array(
							'perm_geolocation' => true,
						),
					)
				),
			),
		);
	}

	/**
	 * 指定キーのプリセット設定値を返す
	 *
	 * @param string $key プリセットキー（'defaults' で全 OFF のデフォルト設定を返す）
	 * @return array|null 設定配列。存在しないキーは null
	 */
	public static function get_preset( $key ) {
		if ( 'defaults' === $key ) {
			return self::get_defaults();
		}
		$presets = self::get_presets();
		return isset( $presets[ $key ] ) ? $presets[ $key ]['settings'] : null;
	}

	/**
	 * 現在の設定を取得する（デフォルトとマージ済み）
	 *
	 * @return array
	 */
	public function get_settings() {
		$defaults = self::get_defaults();
		$settings = get_option( self::OPTION_KEY, array() );
		return self::recursive_parse_args( $settings, $defaults );
	}

	/**
	 * 特定のタブの設定を取得する
	 *
	 * @param string $tab タブ名
	 * @return array
	 */
	public function get_tab_settings( $tab ) {
		$settings     = $this->get_settings();
		$defaults     = self::get_defaults();
		$tab_data     = isset( $settings[ $tab ] ) ? $settings[ $tab ] : array();
		$tab_defaults = isset( $defaults[ $tab ] ) ? $defaults[ $tab ] : array();
		return array_merge( $tab_defaults, $tab_data );
	}

	/**
	 * 設定を保存する
	 *
	 * @param array $settings 設定配列
	 * @return bool
	 */
	public function save_settings( $settings ) {
		return update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * フォーム送信データをサニタイズして既存設定にマージする
	 *
	 * @param array  $input POST データ
	 * @param string $tab   タブ名
	 * @return array マージ済みの全設定
	 */
	public function sanitize_and_merge( $input, $tab ) {
		$current = $this->get_settings();

		switch ( $tab ) {
			case 'options':
				$current['options'] = $this->sanitize_options_tab( $input );
				break;
			case 'ip_block':
				$current['ip_block'] = $this->sanitize_ip_block_tab( $input );
				break;
			case 'rewrite':
				$current['rewrite'] = $this->sanitize_rewrite_tab( $input );
				break;
			case 'headers':
				$current['headers'] = $this->sanitize_headers_tab( $input );
				break;
			case 'cache':
				$current['cache'] = $this->sanitize_cache_tab( $input );
				break;
			case 'wp_admin':
				$current['wp_admin'] = $this->sanitize_wp_admin_tab( $input );
				break;
		}

		return $current;
	}

	/**
	 * Options タブのサニタイズ
	 *
	 * @param array $input POST データ
	 * @return array
	 */
	private function sanitize_options_tab( $input ) {
		return array(
			'disable_multiviews'  => ! empty( $input['disable_multiviews'] ),
			'disable_indexes'     => ! empty( $input['disable_indexes'] ),
			'error_document'      => ! empty( $input['error_document'] ),
			'block_xmlrpc'        => ! empty( $input['block_xmlrpc'] ),
			'protect_wp_config'   => ! empty( $input['protect_wp_config'] ),
			'protect_htaccess'    => ! empty( $input['protect_htaccess'] ),
			'block_dangerous_ext' => ! empty( $input['block_dangerous_ext'] ),
			'wp_login_basic_auth' => ! empty( $input['wp_login_basic_auth'] ),
			'htpasswd_path'       => isset( $input['htpasswd_path'] ) ? sanitize_text_field( wp_unslash( $input['htpasswd_path'] ) ) : '',
		);
	}

	/**
	 * IP ブロックタブのサニタイズ
	 *
	 * @param array $input POST データ
	 * @return array
	 */
	private function sanitize_ip_block_tab( $input ) {
		$list = isset( $input['list'] ) ? sanitize_textarea_field( wp_unslash( $input['list'] ) ) : '';

		// 各行の IP アドレスをバリデーション
		$validated_lines = array();
		$lines           = explode( "\n", $list );
		foreach ( $lines as $line ) {
			$ip = trim( $line );
			if ( '' === $ip ) {
				continue;
			}
			// IP アドレスまたは CIDR 表記をバリデーション
			if ( $this->validate_ip( $ip ) ) {
				$validated_lines[] = $ip;
			}
		}

		return array(
			'enabled' => ! empty( $input['enabled'] ),
			'list'    => implode( "\n", $validated_lines ),
		);
	}

	/**
	 * リライトルールタブのサニタイズ
	 *
	 * @param array $input POST データ
	 * @return array
	 */
	private function sanitize_rewrite_tab( $input ) {
		return array(
			'normalize_slashes'     => ! empty( $input['normalize_slashes'] ),
			'block_bad_bots'        => ! empty( $input['block_bad_bots'] ),
			'bad_bot_list'          => $this->sanitize_line_list( $input, 'bad_bot_list' ),
			'block_backdoors'       => ! empty( $input['block_backdoors'] ),
			'backdoor_list'         => $this->sanitize_line_list( $input, 'backdoor_list' ),
			'block_wp_nesting'      => ! empty( $input['block_wp_nesting'] ),
			'block_wp_includes_dir' => ! empty( $input['block_wp_includes_dir'] ),
			'https_redirect'        => ! empty( $input['https_redirect'] ),
			'x_forwarded_proto'     => ! empty( $input['x_forwarded_proto'] ),
			'block_bad_query'       => ! empty( $input['block_bad_query'] ),
			'bad_query_list'        => $this->sanitize_line_list( $input, 'bad_query_list' ),
		);
	}

	/**
	 * セキュリティヘッダータブのサニタイズ
	 *
	 * @param array $input POST データ
	 * @return array
	 */
	private function sanitize_headers_tab( $input ) {
		$valid_csp_modes       = array( 'enforce', 'report-only' );
		$valid_x_frame         = array( 'DENY', 'SAMEORIGIN', '' );
		$valid_referrer_policy = array(
			'no-referrer',
			'no-referrer-when-downgrade',
			'origin',
			'origin-when-cross-origin',
			'same-origin',
			'strict-origin',
			'strict-origin-when-cross-origin',
			'unsafe-url',
		);

		$csp_mode = isset( $input['csp_mode'] ) ? sanitize_text_field( wp_unslash( $input['csp_mode'] ) ) : 'enforce';
		if ( ! in_array( $csp_mode, $valid_csp_modes, true ) ) {
			$csp_mode = 'enforce';
		}

		$x_frame = isset( $input['x_frame_options'] ) ? sanitize_text_field( wp_unslash( $input['x_frame_options'] ) ) : 'SAMEORIGIN';
		if ( ! in_array( $x_frame, $valid_x_frame, true ) ) {
			$x_frame = 'SAMEORIGIN';
		}

		$referrer = isset( $input['referrer_policy'] ) ? sanitize_text_field( wp_unslash( $input['referrer_policy'] ) ) : 'strict-origin-when-cross-origin';
		if ( ! in_array( $referrer, $valid_referrer_policy, true ) ) {
			$referrer = 'strict-origin-when-cross-origin';
		}

		$max_age = isset( $input['hsts_max_age'] ) ? absint( $input['hsts_max_age'] ) : 63072000;
		if ( $max_age < 0 || $max_age > 126144000 ) {
			$max_age = 63072000;
		}

		return array(
			'hsts_enabled'            => ! empty( $input['hsts_enabled'] ),
			'hsts_max_age'            => $max_age,
			'hsts_include_subdomains' => ! empty( $input['hsts_include_subdomains'] ),
			'hsts_preload'            => ! empty( $input['hsts_preload'] ),
			'csp_enabled'             => ! empty( $input['csp_enabled'] ),
			'csp_mode'                => $csp_mode,
			'csp_upgrade_insecure'    => ! empty( $input['csp_upgrade_insecure'] ),
			'csp_default_src'         => $this->sanitize_csp_value( $input, 'csp_default_src' ),
			'csp_script_src'          => $this->sanitize_csp_value( $input, 'csp_script_src' ),
			'csp_style_src'           => $this->sanitize_csp_value( $input, 'csp_style_src' ),
			'csp_img_src'             => $this->sanitize_csp_value( $input, 'csp_img_src' ),
			'csp_font_src'            => $this->sanitize_csp_value( $input, 'csp_font_src' ),
			'csp_connect_src'         => $this->sanitize_csp_value( $input, 'csp_connect_src' ),
			'csp_frame_src'           => $this->sanitize_csp_value( $input, 'csp_frame_src' ),
			'csp_frame_ancestors'     => $this->sanitize_csp_value( $input, 'csp_frame_ancestors' ),
			'x_content_type'          => ! empty( $input['x_content_type'] ),
			'x_frame_options'         => $x_frame,
			'referrer_policy'         => $referrer,
			'permissions_enabled'     => ! empty( $input['permissions_enabled'] ),
			'perm_camera'             => ! empty( $input['perm_camera'] ),
			'perm_microphone'         => ! empty( $input['perm_microphone'] ),
			'perm_payment'            => ! empty( $input['perm_payment'] ),
			'perm_usb'                => ! empty( $input['perm_usb'] ),
			'perm_gyroscope'          => ! empty( $input['perm_gyroscope'] ),
			'perm_magnetometer'       => ! empty( $input['perm_magnetometer'] ),
			'perm_geolocation'        => ! empty( $input['perm_geolocation'] ),
		);
	}

	/**
	 * キャッシュタブのサニタイズ
	 *
	 * @param array $input POST データ
	 * @return array
	 */
	private function sanitize_cache_tab( $input ) {
		return array(
			'gzip'          => ! empty( $input['gzip'] ),
			'expires'       => ! empty( $input['expires'] ),
			'cache_control' => ! empty( $input['cache_control'] ),
			'etag_disable'  => ! empty( $input['etag_disable'] ),
			'keep_alive'    => ! empty( $input['keep_alive'] ),
		);
	}

	/**
	 * 管理画面保護タブのサニタイズ
	 *
	 * @param array $input POST データ
	 * @return array
	 */
	private function sanitize_wp_admin_tab( $input ) {
		$server_ip = isset( $input['server_ip'] ) ? sanitize_text_field( wp_unslash( $input['server_ip'] ) ) : '';
		if ( '' !== $server_ip && ! $this->validate_ip( $server_ip ) ) {
			$server_ip = '';
		}

		return array(
			'basic_auth'         => ! empty( $input['basic_auth'] ),
			'htpasswd_path'      => isset( $input['htpasswd_path'] ) ? sanitize_text_field( wp_unslash( $input['htpasswd_path'] ) ) : '',
			'ajax_exclude'       => ! empty( $input['ajax_exclude'] ),
			'upgrade_ip_exclude' => ! empty( $input['upgrade_ip_exclude'] ),
			'server_ip'          => $server_ip,
		);
	}

	/**
	 * CSP ディレクティブ値のサニタイズ
	 *
	 * @param array  $input POST データ
	 * @param string $key   フィールドキー
	 * @return string
	 */
	private function sanitize_csp_value( $input, $key ) {
		$value = isset( $input[ $key ] ) ? sanitize_text_field( wp_unslash( $input[ $key ] ) ) : '';
		// CSP で安全な文字のみ許可: 英数字, ', -, :, *, ., +, /, =, スペース
		return preg_replace( '/[^a-zA-Z0-9\s\'\-\:\*\.\/\+\=]/', '', $value );
	}

	/**
	 * IP アドレスまたは CIDR 表記のバリデーション
	 *
	 * @param string $ip IP アドレス
	 * @return bool
	 */
	private function validate_ip( $ip ) {
		// CIDR 表記の場合
		if ( false !== strpos( $ip, '/' ) ) {
			$parts = explode( '/', $ip, 2 );
			if ( ! filter_var( $parts[0], FILTER_VALIDATE_IP ) ) {
				return false;
			}
			$prefix = (int) $parts[1];
			// IPv4: 0-32, IPv6: 0-128
			if ( filter_var( $parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return $prefix >= 0 && $prefix <= 32;
			}
			return $prefix >= 0 && $prefix <= 128;
		}

		return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
	}

	/**
	 * テキストエリアの行リストをサニタイズする
	 *
	 * @param array  $input POST データ
	 * @param string $key   フィールドキー
	 * @return string
	 */
	private function sanitize_line_list( $input, $key ) {
		$value = isset( $input[ $key ] ) ? sanitize_textarea_field( wp_unslash( $input[ $key ] ) ) : '';
		$lines = explode( "\n", $value );
		$clean = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$clean[] = sanitize_text_field( $line );
			}
		}
		return implode( "\n", $clean );
	}

	/**
	 * ネストした配列をデフォルトとマージする
	 *
	 * @param array $args     入力値
	 * @param array $defaults デフォルト値
	 * @return array
	 */
	public static function recursive_parse_args( $args, $defaults ) {
		$result = $defaults;
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) && isset( $result[ $key ] ) && is_array( $result[ $key ] ) ) {
				$result[ $key ] = self::recursive_parse_args( $value, $result[ $key ] );
			} else {
				$result[ $key ] = $value;
			}
		}
		return $result;
	}
}
