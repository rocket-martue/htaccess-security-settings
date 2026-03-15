<?php
/**
 * タブ1: Options & ファイルアクセス制限
 *
 * @package HtaccessSS
 *
 * @var array $tab_settings タブの設定値
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><?php esc_html_e( 'Options 設定', 'htaccess-ss' ); ?></th>
		<td>
			<fieldset>
				<label>
					<input type="checkbox" name="htaccess_ss_settings[disable_multiviews]" value="1" <?php checked( $tab_settings['disable_multiviews'] ); ?> />
					<?php esc_html_e( 'MultiViews を無効化する', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'mod_rewrite との衝突による内部リダイレクトループを防止します。', 'htaccess-ss' ); ?></p>
				<br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[disable_indexes]" value="1" <?php checked( $tab_settings['disable_indexes'] ); ?> />
					<?php esc_html_e( 'ディレクトリ一覧の表示を無効化する', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'ディレクトリにindex.htmlがない場合のファイル一覧表示を防ぎます。', 'htaccess-ss' ); ?></p>
			</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'ErrorDocument', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[error_document]" value="1" <?php checked( $tab_settings['error_document'] ); ?> />
				<?php esc_html_e( 'エラーページをApacheデフォルトにする', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( '403/404 エラー時にWordPressのリライトエンジンを動かさず、軽量なデフォルトエラーページを返します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'ファイルアクセス制限', 'htaccess-ss' ); ?></th>
		<td>
			<fieldset>
				<label>
					<input type="checkbox" name="htaccess_ss_settings[block_xmlrpc]" value="1" <?php checked( $tab_settings['block_xmlrpc'] ); ?> />
					<?php esc_html_e( 'xmlrpc.php をブロック', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'ブルートフォース攻撃やDDoS増幅に悪用される古いリモート投稿APIをブロックします。', 'htaccess-ss' ); ?></p>
				<br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[protect_wp_config]" value="1" <?php checked( $tab_settings['protect_wp_config'] ); ?> />
					<?php esc_html_e( 'wp-config.php を保護', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'データベースパスワード等が記載された設定ファイルへの外部アクセスを禁止します。', 'htaccess-ss' ); ?></p>
				<br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[protect_htaccess]" value="1" <?php checked( $tab_settings['protect_htaccess'] ); ?> />
					<?php esc_html_e( '.htaccess を保護', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'セキュリティ設定ファイル自体への外部アクセスを禁止します。', 'htaccess-ss' ); ?></p>
				<br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[block_dangerous_ext]" value="1" <?php checked( $tab_settings['block_dangerous_ext'] ); ?> />
					<?php esc_html_e( '危険なファイル拡張子をブロック（.inc, .log, .sh, .sql）', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'ログファイルやデータベースダンプなどの機密ファイルへのアクセスを禁止します。', 'htaccess-ss' ); ?></p>
			</fieldset>
		</td>
	</tr>
	<tr class="htaccess-ss-has-children">
		<th scope="row"><?php esc_html_e( 'wp-login.php Basic 認証', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[wp_login_basic_auth]" value="1" <?php checked( $tab_settings['wp_login_basic_auth'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-login-options" />
				<?php esc_html_e( 'wp-login.php にBasic認証を追加する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'ログインページの前にID/パスワード認証を追加し、ブルートフォース攻撃を防ぎます。', 'htaccess-ss' ); ?></p>
			<div class="htaccess-ss-login-options htaccess-ss-sub-options" <?php echo $tab_settings['wp_login_basic_auth'] ? '' : 'style="display:none;"'; ?>>
				<label for="htaccess-ss-htpasswd-path"><?php esc_html_e( '.htpasswd ファイルの絶対パス', 'htaccess-ss' ); ?></label>
				<input type="text" id="htaccess-ss-htpasswd-path" name="htaccess_ss_settings[htpasswd_path]" value="<?php echo esc_attr( $tab_settings['htpasswd_path'] ); ?>" class="regular-text" placeholder="/home/user/domain/htpasswd/wp-admin/.htpasswd" />
			</div>
		</td>
	</tr>
</table>
