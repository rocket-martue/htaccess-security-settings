<?php
/**
 * Plugin Name: Htaccess Security Settings
 * Description: WordPress のセキュリティを強化するために .htaccess ファイルに設定を追加するプラグインです。
 * Version: 1.3.1
 * Author: Rocket Martue
 * Plugin URI: https://github.com/rocket-martue/htaccess-security-settings
 * License: GPL-2.0-or-later
 * Text Domain: htaccess-ss
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HSS_VERSION', '1.3.1' );
define( 'HSS_PLUGIN_FILE', __FILE__ );
define( 'HSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HSS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once HSS_PLUGIN_DIR . 'includes/class-hss-settings.php';
require_once HSS_PLUGIN_DIR . 'includes/class-hss-htaccess-builder.php';
require_once HSS_PLUGIN_DIR . 'includes/class-hss-htaccess-writer.php';
require_once HSS_PLUGIN_DIR . 'includes/class-hss-admin-page.php';
require_once HSS_PLUGIN_DIR . 'includes/class-hss-activator.php';
require_once HSS_PLUGIN_DIR . 'includes/class-hss-plugin.php';

// 有効化・無効化フック（トップレベルで登録が必須）
register_activation_hook( __FILE__, array( 'HSS_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HSS_Activator', 'deactivate' ) );

/**
 * プラグインを初期化する
 *
 * @return HSS_Plugin
 */
function htaccess_ss_init() {
	return HSS_Plugin::get_instance();
}

add_action( 'plugins_loaded', 'htaccess_ss_init' );
