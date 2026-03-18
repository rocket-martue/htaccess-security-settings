<?php
/**
 * .htaccess ディレクティブ生成クラス
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定値から .htaccess のディレクティブ文字列を生成するクラス
 */
class HSS_Htaccess_Builder {

	/**
	 * Htpasswd パスをサニタイズ・検証し、実パスを返す
	 *
	 * @param string $path 入力パス
	 * @return string|false 検証済み実パス。無効なら false
	 */
	private function resolve_htpasswd_path( $path ) {
		$sanitized = sanitize_text_field( $path );
		$real_path = realpath( $sanitized );
		if ( ! $real_path || ! file_exists( $real_path ) || is_dir( $real_path ) ) {
			return false;
		}
		return $real_path;
	}

	/**
	 * ルート .htaccess のディレクティブを生成する
	 *
	 * @param array $settings 全設定配列
	 * @return array 行の配列
	 */
	public function build_root( $settings ) {
		$lines = array();

		$options_lines = $this->build_options_section( $settings['options'] );
		$files_lines   = $this->build_file_protection_section( $settings['options'] );
		$ip_lines      = $this->build_ip_block_section( $settings['ip_block'] );
		$rewrite_lines = $this->build_rewrite_section( $settings['rewrite'] );
		$cache_lines   = $this->build_cache_section( $settings['cache'] );
		$header_lines  = $this->build_headers_section( $settings['headers'] );

		if ( ! empty( $options_lines ) || ! empty( $files_lines ) || ! empty( $ip_lines ) ) {
			$lines[] = '# ===========================';
			$lines[] = '# Security Settings';
			$lines[] = '# ===========================';
			$lines   = array_merge( $lines, $options_lines, $files_lines, $ip_lines );
		}

		if ( ! empty( $rewrite_lines ) ) {
			$lines[] = '# ===========================';
			$lines[] = '# Rewrite Rules';
			$lines[] = '# ===========================';
			$lines   = array_merge( $lines, $rewrite_lines );
		}

		if ( ! empty( $cache_lines ) ) {
			$lines[] = '# ===========================';
			$lines[] = '# Cache & Performance Settings';
			$lines[] = '# ===========================';
			$lines   = array_merge( $lines, $cache_lines );
		}

		if ( ! empty( $header_lines ) ) {
			$lines[] = '# ===========================';
			$lines[] = '# Security Response Headers';
			$lines[] = '# ===========================';
			$lines   = array_merge( $lines, $header_lines );
		}

		return $lines;
	}

	/**
	 * 管理画面用 .htaccess ディレクティブを生成する
	 *
	 * @param array $settings 全設定配列
	 * @return array 行の配列
	 */
	public function build_wp_admin( $settings ) {
		$admin = $settings['wp_admin'];

		if ( ! $admin['basic_auth'] || empty( $admin['htpasswd_path'] ) ) {
			return array();
		}

		$real_path = $this->resolve_htpasswd_path( $admin['htpasswd_path'] );
		if ( ! $real_path ) {
			return array();
		}

		$lines   = array();
		$lines[] = 'AuthUserFile "' . $real_path . '"';
		$lines[] = 'AuthName "Member Site"';
		$lines[] = 'AuthType BASIC';
		$lines[] = 'require valid-user';

		// admin-ajax.php の除外
		if ( $admin['ajax_exclude'] ) {
			$lines[] = '';
			$lines[] = '# admin-ajax.php へのアクセスを許可（フロントエンドの Ajax 用）';
			$lines[] = '<Files admin-ajax.php>';
			$lines[] = "\t" . '<IfModule mod_authz_core.c>';
			$lines[] = "\t\t" . '<RequireAny>';
			$lines[] = "\t\t\t" . 'Require all granted';
			$lines[] = "\t\t" . '</RequireAny>';
			$lines[] = "\t" . '</IfModule>';
			$lines[] = "\t" . '<IfModule !mod_authz_core.c>';
			$lines[] = "\t\t" . 'Order allow,deny';
			$lines[] = "\t\t" . 'Allow from all';
			$lines[] = "\t\t" . 'Satisfy any';
			$lines[] = "\t" . '</IfModule>';
			$lines[] = '</Files>';
		}

		// upgrade.php のサーバー内部 IP 除外
		if ( $admin['upgrade_ip_exclude'] && ! empty( $admin['server_ip'] ) ) {
			$lines[] = '';
			$lines[] = '# upgrade.php はサーバー内部IPのみBasic認証をスキップ（自動更新用）';
			$lines[] = '<Files upgrade.php>';
			$lines[] = "\t" . '<IfModule mod_authz_core.c>';
			$lines[] = "\t\t" . '<RequireAny>';
			$lines[] = "\t\t\t" . 'Require ip ' . $admin['server_ip'];
			$lines[] = "\t\t\t" . 'Require valid-user';
			$lines[] = "\t\t" . '</RequireAny>';
			$lines[] = "\t" . '</IfModule>';
			$lines[] = "\t" . '<IfModule !mod_authz_core.c>';
			$lines[] = "\t\t" . 'Order deny,allow';
			$lines[] = "\t\t" . 'Allow from ' . $admin['server_ip'];
			$lines[] = "\t\t" . 'Satisfy any';
			$lines[] = "\t" . '</IfModule>';
			$lines[] = '</Files>';
		}

		return $lines;
	}

	/**
	 * Options セクション（Options ディレクティブ + ErrorDocument）を生成する
	 *
	 * @param array $options options 設定
	 * @return array
	 */
	private function build_options_section( $options ) {
		$lines = array();
		$opts  = array();

		if ( $options['disable_multiviews'] ) {
			$opts[] = '-MultiViews';
		}
		if ( $options['disable_indexes'] ) {
			$opts[] = '-Indexes';
		}

		if ( ! empty( $opts ) ) {
			$lines[] = 'Options ' . implode( ' ', $opts );
		}

		if ( $options['error_document'] ) {
			$lines[] = '';
			$lines[] = 'ErrorDocument 403 default';
			$lines[] = 'ErrorDocument 404 default';
		}

		if ( ! empty( $lines ) ) {
			$lines[] = '';
		}

		return $lines;
	}

	/**
	 * ファイルアクセス制限セクションを生成する
	 *
	 * @param array $options options 設定
	 * @return array
	 */
	private function build_file_protection_section( $options ) {
		$lines = array();

		// wp-login.php Basic 認証
		if ( $options['wp_login_basic_auth'] && ! empty( $options['htpasswd_path'] ) ) {
			$real_path = $this->resolve_htpasswd_path( $options['htpasswd_path'] );
			if ( $real_path ) {
				$lines[] = '# wp-login.php を保護';
				$lines[] = '<Files wp-login.php>';
				$lines[] = "\t" . 'AuthUserFile "' . $real_path . '"';
				$lines[] = "\t" . 'AuthName "Member Site"';
				$lines[] = "\t" . 'AuthType BASIC';
				$lines[] = "\t" . 'require valid-user';
				$lines[] = '</Files>';
				$lines[] = '';
			}
		}

		// .htaccess 保護
		if ( $options['protect_htaccess'] ) {
			$lines[] = '# .htaccess へのアクセス禁止';
			$lines   = array_merge( $lines, $this->build_deny_files_block( '.htaccess' ) );
			$lines[] = '';
		}

		// xmlrpc.php ブロック
		if ( $options['block_xmlrpc'] ) {
			$lines[] = '# XML-RPCへのアクセスを無効化';
			$lines   = array_merge( $lines, $this->build_deny_files_block( 'xmlrpc.php' ) );
			$lines[] = '';
		}

		// wp-config.php 保護
		if ( $options['protect_wp_config'] ) {
			$lines[] = '# wp-config.php を保護';
			$lines   = array_merge( $lines, $this->build_deny_files_block( 'wp-config.php' ) );
			$lines[] = '';
		}

		// 危険な拡張子ブロック
		if ( $options['block_dangerous_ext'] ) {
			$lines[] = '# 特定のファイルタイプへのアクセスを制限';
			$lines[] = '<FilesMatch "\.(inc|log|sh|sql)$">';
			$lines[] = "\t" . '<IfModule mod_authz_core.c>';
			$lines[] = "\t\t" . 'Require all denied';
			$lines[] = "\t" . '</IfModule>';
			$lines[] = "\t" . '<IfModule !mod_authz_core.c>';
			$lines[] = "\t\t" . 'Order deny,allow';
			$lines[] = "\t\t" . 'Deny from all';
			$lines[] = "\t" . '</IfModule>';
			$lines[] = '</FilesMatch>';
			$lines[] = '';
		}

		return $lines;
	}

	/**
	 * IP ブロックセクションを生成する
	 *
	 * @param array $ip_block IP ブロック設定
	 * @return array
	 */
	private function build_ip_block_section( $ip_block ) {
		if ( ! $ip_block['enabled'] || empty( $ip_block['list'] ) ) {
			return array();
		}

		$ips   = array_filter( array_map( 'trim', explode( "\n", $ip_block['list'] ) ) );
		$lines = array();

		if ( ! empty( $ips ) ) {
			$lines[] = '# 既知の攻撃IPをブロック';
			$lines[] = '<RequireAll>';
			$lines[] = "\t" . 'Require all granted';
			foreach ( $ips as $ip ) {
				$lines[] = "\t" . 'Require not ip ' . $ip;
			}
			$lines[] = '</RequireAll>';
			$lines[] = '';
		}

		return $lines;
	}

	/**
	 * リライトルールセクションを生成する
	 *
	 * @param array $rewrite リライト設定
	 * @return array
	 */
	private function build_rewrite_section( $rewrite ) {
		$rules = array();

		// スラッシュ重複の正規化
		if ( $rewrite['normalize_slashes'] ) {
			$rules[] = '';
			$rules[] = "\t" . '# スラッシュの重複（//）を正規化';
			$rules[] = "\t" . 'RewriteCond %{THE_REQUEST} \s[^\s]*//';
			$rules[] = "\t" . 'RewriteRule ^ %{REQUEST_URI} [R=301,L,NE]';
		}

		// 悪意のあるボットブロック
		if ( $rewrite['block_bad_bots'] && ! empty( $rewrite['bad_bot_list'] ) ) {
			$bots = array_filter( array_map( 'trim', explode( "\n", $rewrite['bad_bot_list'] ) ) );
			if ( ! empty( $bots ) ) {
				$rules[] = '';
				$rules[] = "\t" . '# 悪意のあるボット・スクリプトをブロック';
				$rules[] = "\t" . 'RewriteCond %{HTTP_USER_AGENT} (' . implode( '|', array_map( 'preg_quote', $bots ) ) . ') [NC]';
				$rules[] = "\t" . 'RewriteRule .* - [F,L]';
			}
		}

		// バックドア探索ブロック
		if ( $rewrite['block_backdoors'] && ! empty( $rewrite['backdoor_list'] ) ) {
			$files = array_filter( array_map( 'trim', explode( "\n", $rewrite['backdoor_list'] ) ) );
			if ( ! empty( $files ) ) {
				$escaped = array_map(
					function ( $f ) {
						return str_replace( '.', '\\.', $f );
					},
					$files
				);
				$rules[] = '';
				$rules[] = "\t" . '# バックドア/マルウェア探索をブロック';
				$rules[] = "\t" . 'RewriteCond %{REQUEST_URI} (' . implode( '|', $escaped ) . ') [NC]';
				$rules[] = "\t" . 'RewriteRule .* - [F,L]';
			}
		}

		// wp-* ネスト防止
		if ( $rewrite['block_wp_nesting'] ) {
			$rules[] = '';
			$rules[] = "\t" . '# wp-*ディレクトリの多重ネストリクエストをブロック（内部リダイレクトループ防止）';
			$rules[] = "\t" . 'RewriteCond %{REQUEST_URI} wp-(content|admin|includes)/.*wp-(content|admin|includes)/ [NC]';
			$rules[] = "\t" . 'RewriteRule .* - [F,L]';
		}

		// wp-includes ディレクトリブラウズブロック
		if ( $rewrite['block_wp_includes_dir'] ) {
			$rules[] = '';
			$rules[] = "\t" . '# wp-includes/ ディレクトリの直接ブラウズをブロック';
			$rules[] = "\t" . 'RewriteCond %{REQUEST_URI} ^/wp-includes/ [NC]';
			$rules[] = "\t" . 'RewriteCond %{REQUEST_FILENAME} -d';
			$rules[] = "\t" . 'RewriteRule .* - [F,L]';
		}

		// HTTPS リダイレクト
		if ( $rewrite['https_redirect'] ) {
			$rules[] = '';
			$rules[] = "\t" . '# HTTPSリダイレクト';
			$rules[] = "\t" . 'RewriteCond %{HTTPS} !=on [NC]';
			if ( $rewrite['x_forwarded_proto'] ) {
				$rules[] = "\t" . 'RewriteCond %{HTTP:X-Forwarded-Proto} !https [NC]';
			}
			$rules[] = "\t" . 'RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]';
		}

		// 不正クエリ文字列ブロック
		if ( $rewrite['block_bad_query'] && ! empty( $rewrite['bad_query_list'] ) ) {
			$params = array_filter( array_map( 'trim', explode( "\n", $rewrite['bad_query_list'] ) ) );
			// パラメータ名として妥当な文字列のみ許可（英数字・ハイフン・アンダースコア）
			$params = array_filter(
				$params,
				function ( $p ) {
					return preg_match( '/^[a-zA-Z0-9_\-]+$/', $p );
				}
			);
			if ( $params ) {
				$rules[] = '';
				$rules[] = "\t" . '# 不正なクエリ文字列をブロック';
				foreach ( $params as $param ) {
					$param   = preg_quote( $param, '/' );
					$rules[] = "\t" . 'RewriteCond %{QUERY_STRING} (^|&)' . $param . '=[^&]+(&|$) [NC]';
					$rules[] = "\t" . 'RewriteRule ^ - [R=410,L]';
				}
			}
		}

		if ( empty( $rules ) ) {
			return array();
		}

		$lines   = array();
		$lines[] = '<IfModule mod_rewrite.c>';
		$lines[] = "\t" . 'RewriteEngine On';
		$lines   = array_merge( $lines, $rules );
		$lines[] = '';
		$lines[] = '</IfModule>';
		$lines[] = '';

		return $lines;
	}

	/**
	 * キャッシュ & パフォーマンスセクションを生成する
	 *
	 * @param array $cache キャッシュ設定
	 * @return array
	 */
	private function build_cache_section( $cache ) {
		$lines = array();

		// Gzip 圧縮
		if ( $cache['gzip'] ) {
			$lines[] = '# Gzip圧縮';
			$lines[] = '<IfModule mod_deflate.c>';
			$lines[] = "\t" . 'SetOutputFilter DEFLATE';
			$lines[] = "\t" . 'AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css';
			$lines[] = "\t" . 'AddOutputFilterByType DEFLATE application/javascript application/x-javascript application/json';
			$lines[] = "\t" . 'AddOutputFilterByType DEFLATE application/xml application/xhtml+xml application/rss+xml';
			$lines[] = "\t" . 'AddOutputFilterByType DEFLATE image/svg+xml';
			$lines[] = "\t" . 'AddOutputFilterByType DEFLATE font/ttf font/otf font/woff font/woff2';
			$lines[] = '</IfModule>';
			$lines[] = '';
		}

		// ブラウザキャッシュ（Expires）
		if ( $cache['expires'] ) {
			$lines[] = '# ブラウザキャッシュ設定';
			$lines[] = '<IfModule mod_expires.c>';
			$lines[] = "\t" . 'ExpiresActive On';
			$lines[] = "\t" . 'ExpiresDefault "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType text/css "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType application/javascript "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType application/x-javascript "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType text/javascript "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType image/jpeg "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType image/png "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType image/gif "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType image/webp "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType image/svg+xml "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType image/x-icon "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType image/vnd.microsoft.icon "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType video/mp4 "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType video/webm "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType video/ogg "access plus 1 month"';
			$lines[] = "\t" . 'ExpiresByType font/woff "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType font/woff2 "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType font/ttf "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType font/otf "access plus 1 year"';
			$lines[] = "\t" . 'ExpiresByType application/atom+xml "access plus 1 hour"';
			$lines[] = "\t" . 'ExpiresByType application/rdf+xml "access plus 1 hour"';
			$lines[] = "\t" . 'ExpiresByType application/rss+xml "access plus 1 hour"';
			$lines[] = "\t" . 'ExpiresByType application/json "access plus 0 seconds"';
			$lines[] = "\t" . 'ExpiresByType application/ld+json "access plus 0 seconds"';
			$lines[] = "\t" . 'ExpiresByType application/xml "access plus 0 seconds"';
			$lines[] = "\t" . 'ExpiresByType text/xml "access plus 0 seconds"';
			$lines[] = "\t" . 'ExpiresByType application/manifest+json "access plus 1 week"';
			$lines[] = "\t" . 'ExpiresByType text/html "access plus 0 seconds"';
			$lines[] = '</IfModule>';
			$lines[] = '';
		}

		// Cache-Control ヘッダー
		if ( $cache['cache_control'] ) {
			$lines[] = '# Cache-Control ヘッダー';
			$lines[] = '<IfModule mod_headers.c>';
			$lines[] = "\t" . '<FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf)$">';
			$lines[] = "\t\t" . 'Header set Cache-Control "public, max-age=31536000, immutable"';
			$lines[] = "\t" . '</FilesMatch>';
			$lines[] = "\t" . '<FilesMatch "\.(html|htm)$">';
			$lines[] = "\t\t" . 'Header set Cache-Control "no-cache, must-revalidate"';
			$lines[] = "\t" . '</FilesMatch>';
			$lines[] = '</IfModule>';
			$lines[] = '';
		}

		// MIME Type（キャッシュ系設定が1つ以上有効な場合のみ含める）
		$has_cache_settings = $cache['gzip'] || $cache['expires'] || $cache['cache_control'] || $cache['etag_disable'] || $cache['keep_alive'];
		if ( $has_cache_settings ) {
			$lines[] = '# MIME Type';
			$lines[] = '<IfModule mime_module>';
			$lines[] = "\t" . 'AddType image/x-icon .ico';
			$lines[] = "\t" . 'AddType image/svg+xml .svg';
			$lines[] = "\t" . 'AddType application/x-font-ttf .ttf';
			$lines[] = "\t" . 'AddType application/x-font-woff .woff';
			$lines[] = "\t" . 'AddType application/x-font-opentype .otf';
			$lines[] = "\t" . 'AddType application/vnd.ms-fontobject .eot';
			$lines[] = '</IfModule>';
			$lines[] = '';
		}

		// ETag 無効化
		if ( $cache['etag_disable'] ) {
			$lines[] = '# ETags を無効化';
			$lines[] = '<IfModule mod_headers.c>';
			$lines[] = "\t" . 'Header unset ETag';
			$lines[] = '</IfModule>';
			$lines[] = 'FileETag None';
			$lines[] = '';
		}

		// Keep-Alive
		if ( $cache['keep_alive'] ) {
			$lines[] = '# Keep-Alive を有効化';
			$lines[] = '<IfModule mod_headers.c>';
			$lines[] = "\t" . 'Header set Connection keep-alive';
			$lines[] = '</IfModule>';
			$lines[] = '';
		}

		return $lines;
	}

	/**
	 * セキュリティレスポンスヘッダーセクションを生成する
	 *
	 * @param array $headers ヘッダー設定
	 * @return array
	 */
	private function build_headers_section( $headers ) {
		$directives = array();

		// HSTS
		if ( $headers['hsts_enabled'] ) {
			$hsts_value = 'max-age=' . (int) $headers['hsts_max_age'];
			if ( $headers['hsts_include_subdomains'] ) {
				$hsts_value .= '; includeSubDomains';
			}
			if ( $headers['hsts_preload'] ) {
				$hsts_value .= '; preload';
			}
			$directives[] = "\t" . '# HSTS（HTTPS接続時のみ送信）';
			$directives[] = "\t" . 'Header always set Strict-Transport-Security "' . $hsts_value . '" "expr=%{HTTPS} == \'on\' || %{HTTP:X-Forwarded-Proto} == \'https\'"';
		}

		// CSP
		if ( $headers['csp_enabled'] ) {
			$csp_parts = array();

			if ( ! empty( $headers['csp_default_src'] ) ) {
				$csp_parts[] = 'default-src ' . $headers['csp_default_src'];
			}
			if ( ! empty( $headers['csp_script_src'] ) ) {
				$csp_parts[] = 'script-src ' . $headers['csp_script_src'];
			}
			if ( ! empty( $headers['csp_style_src'] ) ) {
				$csp_parts[] = 'style-src ' . $headers['csp_style_src'];
			}
			if ( ! empty( $headers['csp_img_src'] ) ) {
				$csp_parts[] = 'img-src ' . $headers['csp_img_src'];
			}
			if ( ! empty( $headers['csp_font_src'] ) ) {
				$csp_parts[] = 'font-src ' . $headers['csp_font_src'];
			}
			if ( ! empty( $headers['csp_connect_src'] ) ) {
				$csp_parts[] = 'connect-src ' . $headers['csp_connect_src'];
			}
			if ( ! empty( $headers['csp_frame_src'] ) ) {
				$csp_parts[] = 'frame-src ' . $headers['csp_frame_src'];
			}
			if ( ! empty( $headers['csp_frame_ancestors'] ) ) {
				$csp_parts[] = 'frame-ancestors ' . $headers['csp_frame_ancestors'];
			}

			// upgrade-insecure-requests は enforce モードのみ（Report-Only では無視されるため）
			if ( 'enforce' === $headers['csp_mode'] && $headers['csp_upgrade_insecure'] ) {
				$csp_parts[] = 'upgrade-insecure-requests';
			}

			$csp_value = implode( '; ', $csp_parts ) . ';';

			if ( 'report-only' === $headers['csp_mode'] ) {
				$header_name = 'Content-Security-Policy-Report-Only';
			} else {
				$header_name = 'Content-Security-Policy';
			}

			$directives[] = '';
			$directives[] = "\t" . '# CSP';
			$directives[] = "\t" . 'Header always set ' . $header_name . ' "' . $csp_value . '"';
		}

		// X-Content-Type-Options
		if ( $headers['x_content_type'] ) {
			$directives[] = '';
			$directives[] = "\t" . '# X-Content-Type-Options';
			$directives[] = "\t" . 'Header always set X-Content-Type-Options "nosniff"';
		}

		// X-Frame-Options
		if ( ! empty( $headers['x_frame_options'] ) ) {
			$directives[] = '';
			$directives[] = "\t" . '# X-Frame-Options';
			$directives[] = "\t" . 'Header always set X-Frame-Options "' . $headers['x_frame_options'] . '"';
		}

		// Referrer-Policy
		if ( ! empty( $headers['referrer_policy'] ) ) {
			$directives[] = '';
			$directives[] = "\t" . '# Referrer-Policy';
			$directives[] = "\t" . 'Header always set Referrer-Policy "' . $headers['referrer_policy'] . '"';
		}

		// Permissions-Policy
		if ( $headers['permissions_enabled'] ) {
			$perms = array();
			if ( $headers['perm_camera'] ) {
				$perms[] = 'camera=()';
			}
			if ( $headers['perm_microphone'] ) {
				$perms[] = 'microphone=()';
			}
			if ( $headers['perm_payment'] ) {
				$perms[] = 'payment=()';
			}
			if ( $headers['perm_usb'] ) {
				$perms[] = 'usb=()';
			}
			if ( $headers['perm_gyroscope'] ) {
				$perms[] = 'gyroscope=()';
			}
			if ( $headers['perm_magnetometer'] ) {
				$perms[] = 'magnetometer=()';
			}
			if ( $headers['perm_geolocation'] ) {
				$perms[] = 'geolocation=()';
			}

			if ( ! empty( $perms ) ) {
				$directives[] = '';
				$directives[] = "\t" . '# Permissions-Policy';
				$directives[] = "\t" . 'Header always set Permissions-Policy "' . implode( ', ', $perms ) . '"';
			}
		}

		if ( empty( $directives ) ) {
			return array();
		}

		$lines   = array();
		$lines[] = '<IfModule mod_headers.c>';
		$lines   = array_merge( $lines, $directives );
		$lines[] = '</IfModule>';
		$lines[] = '';

		return $lines;
	}

	/**
	 * ファイルアクセス拒否ブロックを生成する（Apache 2.2/2.4 両対応）
	 *
	 * @param string $filename ファイル名
	 * @return array
	 */
	private function build_deny_files_block( $filename ) {
		return array(
			'<Files ' . $filename . '>',
			"\t" . '<IfModule mod_authz_core.c>',
			"\t\t" . 'Require all denied',
			"\t" . '</IfModule>',
			"\t" . '<IfModule !mod_authz_core.c>',
			"\t\t" . 'Order deny,allow',
			"\t\t" . 'Deny from all',
			"\t" . '</IfModule>',
			'</Files>',
		);
	}
}
