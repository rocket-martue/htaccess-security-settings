<?php
/**
 * タブ3: リライトルール
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
		<th scope="row"><?php esc_html_e( 'URL 正規化', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[normalize_slashes]" value="1" <?php checked( $tab_settings['normalize_slashes'] ); ?> />
				<?php esc_html_e( 'スラッシュ重複（//）を正規化する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'ダブルスラッシュURLを正規URLに301リダイレクトします。SEO改善とセキュリティルール回避の防止に有効です。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr class="htaccess-ss-has-children">
		<th scope="row"><?php esc_html_e( 'ボットブロック', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[block_bad_bots]" value="1" <?php checked( $tab_settings['block_bad_bots'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-bot-options" />
				<?php esc_html_e( '悪意のあるボット・スクリプトをブロック', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'User-Agentに特定の文字列が含まれるリクエストを403で拒否します。', 'htaccess-ss' ); ?></p>
			<div class="htaccess-ss-bot-options htaccess-ss-sub-options" <?php echo $tab_settings['block_bad_bots'] ? '' : 'style="display:none;"'; ?>>
				<label for="htaccess-ss-bad-bot-list"><?php esc_html_e( 'ブロック対象 User-Agent（1行に1つ）', 'htaccess-ss' ); ?></label>
				<textarea id="htaccess-ss-bad-bot-list" name="htaccess_ss_settings[bad_bot_list]" rows="6" class="large-text code"><?php echo esc_textarea( $tab_settings['bad_bot_list'] ); ?></textarea>
				<p class="description"><?php esc_html_e( '※ curl, python など正規のAPIクライアントが使う場合もあるので注意してください。', 'htaccess-ss' ); ?></p>
			</div>
		</td>
	</tr>
	<tr class="htaccess-ss-has-children">
		<th scope="row"><?php esc_html_e( 'バックドア探索', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[block_backdoors]" value="1" <?php checked( $tab_settings['block_backdoors'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-backdoor-options" />
				<?php esc_html_e( 'バックドア/マルウェア探索をブロック', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( '既知のバックドアファイル名へのアクセスを403で拒否します。', 'htaccess-ss' ); ?></p>
			<div class="htaccess-ss-backdoor-options htaccess-ss-sub-options" <?php echo $tab_settings['block_backdoors'] ? '' : 'style="display:none;"'; ?>>
				<label for="htaccess-ss-backdoor-list"><?php esc_html_e( 'ブロック対象ファイル名（1行に1つ）', 'htaccess-ss' ); ?></label>
				<textarea id="htaccess-ss-backdoor-list" name="htaccess_ss_settings[backdoor_list]" rows="6" class="large-text code"><?php echo esc_textarea( $tab_settings['backdoor_list'] ); ?></textarea>
			</div>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'WordPress 固有の保護', 'htaccess-ss' ); ?></th>
		<td>
			<fieldset>
				<label>
					<input type="checkbox" name="htaccess_ss_settings[block_wp_nesting]" value="1" <?php checked( $tab_settings['block_wp_nesting'] ); ?> />
					<?php esc_html_e( 'wp-* ディレクトリの多重ネストをブロック', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( '/wp-admin/wp-admin/... のようなネストされたリクエストによる内部リダイレクトループを防止します。', 'htaccess-ss' ); ?></p>
				<br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[block_wp_includes_dir]" value="1" <?php checked( $tab_settings['block_wp_includes_dir'] ); ?> />
					<?php esc_html_e( 'wp-includes ディレクトリの直接ブラウズをブロック', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'ディレクトリへの直接アクセスをブロックします。ファイルへの通常のアクセスには影響しません。', 'htaccess-ss' ); ?></p>
			</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'HTTPS リダイレクト', 'htaccess-ss' ); ?></th>
		<td>
			<fieldset>
				<label>
					<input type="checkbox" name="htaccess_ss_settings[https_redirect]" value="1" <?php checked( $tab_settings['https_redirect'] ); ?> />
					<?php esc_html_e( 'HTTP → HTTPS に301リダイレクトする', 'htaccess-ss' ); ?>
				</label>
				<br /><br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[x_forwarded_proto]" value="1" <?php checked( $tab_settings['x_forwarded_proto'] ); ?> />
					<?php esc_html_e( 'X-Forwarded-Proto を考慮する（リバースプロキシ対応）', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'XServerなどリバースプロキシ（Nginx → Apache）構成のサーバーではONにしてください。', 'htaccess-ss' ); ?></p>
			</fieldset>
		</td>
	</tr>
	<tr class="htaccess-ss-has-children">
		<th scope="row"><?php esc_html_e( 'その他', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[block_bad_query]" value="1" <?php checked( $tab_settings['block_bad_query'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-bad-query-options" />
				<?php esc_html_e( '不正なクエリ文字列をブロック', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( '指定したパラメータ名を含むリクエストに 410 Gone を返します。', 'htaccess-ss' ); ?></p>
			<div class="htaccess-ss-bad-query-options htaccess-ss-sub-options" <?php echo $tab_settings['block_bad_query'] ? '' : 'style="display:none;"'; ?>>
				<label for="htaccess-ss-bad-query-list"><?php esc_html_e( 'ブロック対象パラメータ名（1行に1つ）', 'htaccess-ss' ); ?></label>
				<textarea id="htaccess-ss-bad-query-list" name="htaccess_ss_settings[bad_query_list]" rows="4" class="large-text code"><?php echo esc_textarea( $tab_settings['bad_query_list'] ); ?></textarea>
				<p class="description"><?php esc_html_e( '例: w と入力すると ?w=xxx のクエリをブロックします。', 'htaccess-ss' ); ?></p>
			</div>
		</td>
	</tr>
</table>
