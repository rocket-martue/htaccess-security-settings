<?php
/**
 * PHPUnit bootstrap file
 *
 * @package HtaccessSS
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );

// WP_CORE_DIR が未定義の場合はデフォルトパスを設定
if ( ! defined( 'WP_CORE_DIR' ) ) {
	$_wp_core_dir = getenv( 'WP_CORE_DIR' );
	if ( ! $_wp_core_dir ) {
		$_wp_core_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress';
	}
	define( 'WP_CORE_DIR', $_wp_core_dir );
}

// WordPress テストスイートを読み込み
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * テスト実行前にプラグインを読み込む
 */
tests_add_filter(
	'muplugins_loaded',
	function () {
		require TESTS_PLUGIN_DIR . '/htaccess-security-settings.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';
