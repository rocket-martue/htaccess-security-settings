<?php
/**
 * タブ6: wp-admin 保護
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
		<th scope="row"><?php esc_html_e( 'Basic 認証', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" class="htaccess-ss-toggle-parent" name="htaccess_ss_settings[basic_auth]" value="1" <?php checked( $tab_settings['basic_auth'] ); ?> />
				<?php esc_html_e( 'wp-admin に Basic 認証をかける', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( '管理画面へのアクセスにユーザー名・パスワードを要求します。', 'htaccess-ss' ); ?></p>
			<div class="htaccess-ss-sub-options" <?php echo $tab_settings['basic_auth'] ? '' : 'style="' . esc_attr( 'display:none;' ) . '"'; ?>>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="htaccess-ss-htpasswd-path"><?php esc_html_e( '.htpasswd ファイルパス', 'htaccess-ss' ); ?></label>
						</th>
						<td>
							<input type="text" id="htaccess-ss-htpasswd-path" name="htaccess_ss_settings[htpasswd_path]" value="<?php echo esc_attr( $tab_settings['htpasswd_path'] ); ?>" class="regular-text code" placeholder="/home/username/.htpasswd" />
							<p class="description"><?php esc_html_e( 'サーバー上の .htpasswd ファイルのフルパスを指定してください。', 'htaccess-ss' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'admin-ajax.php 除外', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[ajax_exclude]" value="1" <?php checked( $tab_settings['ajax_exclude'] ); ?> />
				<?php esc_html_e( 'admin-ajax.php を Basic 認証から除外する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'フロントエンドから Ajax リクエストを使う場合は除外が必要です。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'upgrade.php IP 制限', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" class="htaccess-ss-toggle-parent" name="htaccess_ss_settings[upgrade_ip_exclude]" value="1" <?php checked( $tab_settings['upgrade_ip_exclude'] ); ?> />
				<?php esc_html_e( 'upgrade.php へのアクセスをサーバーIPのみに制限する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'WordPress の自動更新に必要な upgrade.php をサーバー自身のIPからのみ許可します。', 'htaccess-ss' ); ?></p>
			<div class="htaccess-ss-sub-options" <?php echo $tab_settings['upgrade_ip_exclude'] ? '' : 'style="' . esc_attr( 'display:none;' ) . '"'; ?>>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="htaccess-ss-server-ip"><?php esc_html_e( 'サーバー IP アドレス', 'htaccess-ss' ); ?></label>
						</th>
						<td>
							<input type="text" id="htaccess-ss-server-ip" name="htaccess_ss_settings[server_ip]" value="<?php echo esc_attr( $tab_settings['server_ip'] ); ?>" class="regular-text code" placeholder="例: 203.0.113.1" />
							<p class="description"><?php esc_html_e( 'サーバー自身の IP アドレスを入力してください。', 'htaccess-ss' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
