<?php
/**
 * タブ5: キャッシュ & パフォーマンス
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
		<th scope="row"><?php esc_html_e( 'Gzip 圧縮', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[gzip]" value="1" <?php checked( $tab_settings['gzip'] ); ?> />
				<?php esc_html_e( 'Gzip 圧縮を有効にする', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'テキストベースのファイル（HTML, CSS, JS, JSON等）をサーバー側で圧縮し、転送量を削減します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'ブラウザキャッシュ（Expires）', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[expires]" value="1" <?php checked( $tab_settings['expires'] ); ?> />
				<?php esc_html_e( 'Expires ヘッダーを設定する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'ファイルの種類ごとにブラウザキャッシュの有効期限を設定し、ページ表示を高速化します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Cache-Control ヘッダー', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[cache_control]" value="1" <?php checked( $tab_settings['cache_control'] ); ?> />
				<?php esc_html_e( '静的ファイルに Cache-Control: immutable を設定する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'CSS, JS, 画像, フォント等に1年間のキャッシュと immutable フラグを設定します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'ETag', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[etag_disable]" value="1" <?php checked( $tab_settings['etag_disable'] ); ?> />
				<?php esc_html_e( 'ETag を無効化する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Expires / Cache-Control で制御している場合は ETag は冗長です。サーバー間の ETag 不一致問題も回避できます。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Keep-Alive', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[keep_alive]" value="1" <?php checked( $tab_settings['keep_alive'] ); ?> />
				<?php esc_html_e( 'Keep-Alive を有効にする', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'TCP接続を再利用し、複数リソースのダウンロードを高速化します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
</table>
