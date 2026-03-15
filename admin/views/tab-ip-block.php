<?php
/**
 * タブ2: IP ブロック
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
		<th scope="row"><?php esc_html_e( 'IP ブロック', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[enabled]" value="1" <?php checked( $tab_settings['enabled'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-ip-options" />
				<?php esc_html_e( 'IP ブロックを有効にする', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( '指定したIPアドレスからのアクセスを 403 Forbidden で拒否します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr class="htaccess-ss-ip-options" <?php echo $tab_settings['enabled'] ? '' : 'style="display:none;"'; ?>>
		<th scope="row">
			<label for="htaccess-ss-ip-list"><?php esc_html_e( 'ブロック IP リスト', 'htaccess-ss' ); ?></label>
		</th>
		<td>
			<textarea id="htaccess-ss-ip-list" name="htaccess_ss_settings[list]" rows="12" class="large-text code"><?php echo esc_textarea( $tab_settings['list'] ); ?></textarea>
			<p class="description">
				<?php esc_html_e( '1行に1つのIPアドレスを入力してください。CIDR表記（例: 192.168.1.0/24）にも対応しています。', 'htaccess-ss' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( '※ 数百件を超える場合はファイアウォールプラグインやサーバーのアクセス制限機能の利用を検討してください。', 'htaccess-ss' ); ?>
			</p>
		</td>
	</tr>
</table>
