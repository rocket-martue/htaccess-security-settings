<?php
/**
 * タブ7: uploads 保護
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
		<th scope="row"><?php esc_html_e( 'PHP 実行禁止', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[block_php]" value="1" <?php checked( $tab_settings['block_php'] ); ?> />
				<?php esc_html_e( 'uploads ディレクトリでの PHP ファイル実行を禁止する', 'htaccess-ss' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'wp-content/uploads 内の .php / .phar / .phtml ファイルへのアクセスを 403 で拒否します。', 'htaccess-ss' ); ?>
				<br>
				<?php esc_html_e( 'アップロードされたマルウェアの実行を防止します。', 'htaccess-ss' ); ?>
			</p>
		</td>
	</tr>
</table>
