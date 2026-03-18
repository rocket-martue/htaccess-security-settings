<?php
/**
 * タブ4: セキュリティヘッダー
 *
 * @package HtaccessSS
 *
 * @var array $tab_settings タブの設定値
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'HSTS（HTTP Strict Transport Security）', 'htaccess-ss' ); ?></h2>
<table class="form-table" role="presentation">
	<tr class="htaccess-ss-has-children">
		<th scope="row"><?php esc_html_e( 'HSTS', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[hsts_enabled]" value="1" <?php checked( $tab_settings['hsts_enabled'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-hsts-options" />
				<?php esc_html_e( 'HSTS を有効にする', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'ブラウザに「今後必ずHTTPSで接続する」よう指示し、中間者攻撃を防ぎます。', 'htaccess-ss' ); ?></p>
			<div class="htaccess-ss-hsts-options htaccess-ss-sub-options" <?php echo $tab_settings['hsts_enabled'] ? '' : 'style="' . esc_attr( 'display:none;' ) . '"'; ?>>
				<label for="htaccess-ss-hsts-max-age"><?php esc_html_e( 'max-age（秒）', 'htaccess-ss' ); ?></label>
				<input type="number" id="htaccess-ss-hsts-max-age" name="htaccess_ss_settings[hsts_max_age]" value="<?php echo esc_attr( $tab_settings['hsts_max_age'] ); ?>" min="0" max="126144000" class="small-text" />
				<span class="description"><?php esc_html_e( '推奨: 63072000（2年）', 'htaccess-ss' ); ?></span>
				<br /><br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[hsts_include_subdomains]" value="1" <?php checked( $tab_settings['hsts_include_subdomains'] ); ?> />
					<?php esc_html_e( 'includeSubDomains（サブドメインも対象）', 'htaccess-ss' ); ?>
				</label>
				<br />
				<label>
					<input type="checkbox" name="htaccess_ss_settings[hsts_preload]" value="1" <?php checked( $tab_settings['hsts_preload'] ); ?> />
					<?php esc_html_e( 'preload（HSTS Preload List 登録用フラグ）', 'htaccess-ss' ); ?>
				</label>
			</div>
		</td>
	</tr>
</table>

<h2><?php esc_html_e( 'CSP（Content Security Policy）', 'htaccess-ss' ); ?></h2>
<table class="form-table" role="presentation">
	<tr class="htaccess-ss-has-children">
		<th scope="row"><?php esc_html_e( 'CSP', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[csp_enabled]" value="1" <?php checked( $tab_settings['csp_enabled'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-csp-options" />
				<?php esc_html_e( 'CSP を有効にする', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'ブラウザにリソースの読み込み元を制限する指示を送ります。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
</table>

<div class="htaccess-ss-csp-options htaccess-ss-sub-options" <?php echo $tab_settings['csp_enabled'] ? '' : 'style="' . esc_attr( 'display:none;' ) . '"'; ?>>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'CSP モード', 'htaccess-ss' ); ?></th>
			<td>
				<fieldset>
					<label>
						<input type="radio" name="htaccess_ss_settings[csp_mode]" value="enforce" <?php checked( $tab_settings['csp_mode'], 'enforce' ); ?> class="htaccess-ss-csp-mode" />
						<?php esc_html_e( '本番適用（Content-Security-Policy）', 'htaccess-ss' ); ?>
					</label>
					<br />
					<label>
						<input type="radio" name="htaccess_ss_settings[csp_mode]" value="report-only" <?php checked( $tab_settings['csp_mode'], 'report-only' ); ?> class="htaccess-ss-csp-mode" />
						<?php esc_html_e( 'テストモード（Content-Security-Policy-Report-Only）', 'htaccess-ss' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'テストモードではブロックせず、DevToolsコンソールに違反を表示します。まずテストモードで確認してから本番適用に切り替えることを推奨します。', 'htaccess-ss' ); ?></p>
				</fieldset>
			</td>
		</tr>
		<tr class="htaccess-ss-csp-upgrade-row" <?php echo 'enforce' === $tab_settings['csp_mode'] ? '' : 'style="' . esc_attr( 'display:none;' ) . '"'; ?>>
			<th scope="row"><?php esc_html_e( 'upgrade-insecure-requests', 'htaccess-ss' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="htaccess_ss_settings[csp_upgrade_insecure]" value="1" <?php checked( $tab_settings['csp_upgrade_insecure'] ); ?> />
					<?php esc_html_e( 'HTTP リソースを自動的に HTTPS に変換する', 'htaccess-ss' ); ?>
				</label>
				<p class="description"><?php esc_html_e( '※ Report-Only モードではこの設定は無視されます（アクション指示であり制限指示ではないため）。', 'htaccess-ss' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-default"><?php esc_html_e( 'default-src', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-default" name="htaccess_ss_settings[csp_default_src]" value="<?php echo esc_attr( $tab_settings['csp_default_src'] ); ?>" class="large-text" />
				<p class="description"><?php esc_html_e( '他で指定されていない全リソースのフォールバック', 'htaccess-ss' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-script"><?php esc_html_e( 'script-src', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-script" name="htaccess_ss_settings[csp_script_src]" value="<?php echo esc_attr( $tab_settings['csp_script_src'] ); ?>" class="large-text" />
				<p class="description"><?php esc_html_e( "JavaScript の読み込み元。WordPress では 'unsafe-inline' と 'unsafe-eval' が必要です。", 'htaccess-ss' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-style"><?php esc_html_e( 'style-src', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-style" name="htaccess_ss_settings[csp_style_src]" value="<?php echo esc_attr( $tab_settings['csp_style_src'] ); ?>" class="large-text" />
				<p class="description"><?php esc_html_e( "CSS の読み込み元。WordPress では 'unsafe-inline' が必要です。", 'htaccess-ss' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-img"><?php esc_html_e( 'img-src', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-img" name="htaccess_ss_settings[csp_img_src]" value="<?php echo esc_attr( $tab_settings['csp_img_src'] ); ?>" class="large-text" />
				<p class="description">
					<?php esc_html_e( "画像の読み込み元。必ず 'self' を含めてください（OG imageがブロックされます）。", 'htaccess-ss' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-font"><?php esc_html_e( 'font-src', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-font" name="htaccess_ss_settings[csp_font_src]" value="<?php echo esc_attr( $tab_settings['csp_font_src'] ); ?>" class="large-text" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-connect"><?php esc_html_e( 'connect-src', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-connect" name="htaccess_ss_settings[csp_connect_src]" value="<?php echo esc_attr( $tab_settings['csp_connect_src'] ); ?>" class="large-text" />
				<p class="description"><?php esc_html_e( 'Ajax / WebSocket / fetch の接続先', 'htaccess-ss' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-frame"><?php esc_html_e( 'frame-src', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-frame" name="htaccess_ss_settings[csp_frame_src]" value="<?php echo esc_attr( $tab_settings['csp_frame_src'] ); ?>" class="large-text" />
				<p class="description"><?php esc_html_e( 'iframe 埋め込み先', 'htaccess-ss' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="htaccess-ss-csp-ancestors"><?php esc_html_e( 'frame-ancestors', 'htaccess-ss' ); ?></label>
			</th>
			<td>
				<input type="text" id="htaccess-ss-csp-ancestors" name="htaccess_ss_settings[csp_frame_ancestors]" value="<?php echo esc_attr( $tab_settings['csp_frame_ancestors'] ); ?>" class="large-text" />
				<p class="description"><?php esc_html_e( 'iframe 埋め込み元の制限（X-Frame-Options の CSP 版）', 'htaccess-ss' ); ?></p>
			</td>
		</tr>
	</table>
</div>

<h2><?php esc_html_e( 'その他のセキュリティヘッダー', 'htaccess-ss' ); ?></h2>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><?php esc_html_e( 'X-Content-Type-Options', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[x_content_type]" value="1" <?php checked( $tab_settings['x_content_type'] ); ?> />
				<?php esc_html_e( 'nosniff を設定する', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'MIME スニッフィングを禁止し、ファイルの種類偽装によるXSS攻撃を防ぎます。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="htaccess-ss-x-frame"><?php esc_html_e( 'X-Frame-Options', 'htaccess-ss' ); ?></label>
		</th>
		<td>
			<select id="htaccess-ss-x-frame" name="htaccess_ss_settings[x_frame_options]">
				<option value="SAMEORIGIN" <?php selected( $tab_settings['x_frame_options'], 'SAMEORIGIN' ); ?>><?php esc_html_e( 'SAMEORIGIN（同一ドメインからのみ埋め込みOK）', 'htaccess-ss' ); ?></option>
				<option value="DENY" <?php selected( $tab_settings['x_frame_options'], 'DENY' ); ?>><?php esc_html_e( 'DENY（一切埋め込み禁止）', 'htaccess-ss' ); ?></option>
				<option value="" <?php selected( $tab_settings['x_frame_options'], '' ); ?>><?php esc_html_e( '無効（ヘッダーを送信しない）', 'htaccess-ss' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'クリックジャッキング攻撃を防止します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="htaccess-ss-referrer"><?php esc_html_e( 'Referrer-Policy', 'htaccess-ss' ); ?></label>
		</th>
		<td>
			<select id="htaccess-ss-referrer" name="htaccess_ss_settings[referrer_policy]">
				<option value="strict-origin-when-cross-origin" <?php selected( $tab_settings['referrer_policy'], 'strict-origin-when-cross-origin' ); ?>>strict-origin-when-cross-origin（推奨）</option>
				<option value="no-referrer" <?php selected( $tab_settings['referrer_policy'], 'no-referrer' ); ?>>no-referrer</option>
				<option value="no-referrer-when-downgrade" <?php selected( $tab_settings['referrer_policy'], 'no-referrer-when-downgrade' ); ?>>no-referrer-when-downgrade</option>
				<option value="origin" <?php selected( $tab_settings['referrer_policy'], 'origin' ); ?>>origin</option>
				<option value="origin-when-cross-origin" <?php selected( $tab_settings['referrer_policy'], 'origin-when-cross-origin' ); ?>>origin-when-cross-origin</option>
				<option value="same-origin" <?php selected( $tab_settings['referrer_policy'], 'same-origin' ); ?>>same-origin</option>
				<option value="strict-origin" <?php selected( $tab_settings['referrer_policy'], 'strict-origin' ); ?>>strict-origin</option>
				<option value="unsafe-url" <?php selected( $tab_settings['referrer_policy'], 'unsafe-url' ); ?>>unsafe-url</option>
			</select>
			<p class="description"><?php esc_html_e( 'リンク先に送るURL情報の範囲を制御します。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
</table>

<h2><?php esc_html_e( 'Permissions-Policy', 'htaccess-ss' ); ?></h2>
<table class="form-table" role="presentation">
	<tr class="htaccess-ss-has-children">
		<th scope="row"><?php esc_html_e( 'Permissions-Policy', 'htaccess-ss' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="htaccess_ss_settings[permissions_enabled]" value="1" <?php checked( $tab_settings['permissions_enabled'] ); ?> class="htaccess-ss-toggle-parent" data-target=".htaccess-ss-perm-options" />
				<?php esc_html_e( 'Permissions-Policy を有効にする', 'htaccess-ss' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'ブラウザのデバイスAPIへのアクセスを制限します。チェックした項目は無効化（禁止）されます。', 'htaccess-ss' ); ?></p>
		</td>
	</tr>
</table>

<div class="htaccess-ss-perm-options htaccess-ss-sub-options" <?php echo $tab_settings['permissions_enabled'] ? '' : 'style="' . esc_attr( 'display:none;' ) . '"'; ?>>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( '無効にする API', 'htaccess-ss' ); ?></th>
			<td>
				<fieldset>
					<label>
						<input type="checkbox" name="htaccess_ss_settings[perm_camera]" value="1" <?php checked( $tab_settings['perm_camera'] ); ?> />
						<?php esc_html_e( 'camera（カメラ）', 'htaccess-ss' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="htaccess_ss_settings[perm_microphone]" value="1" <?php checked( $tab_settings['perm_microphone'] ); ?> />
						<?php esc_html_e( 'microphone（マイク）', 'htaccess-ss' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="htaccess_ss_settings[perm_payment]" value="1" <?php checked( $tab_settings['perm_payment'] ); ?> />
						<?php esc_html_e( 'payment（Payment Request API）', 'htaccess-ss' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="htaccess_ss_settings[perm_usb]" value="1" <?php checked( $tab_settings['perm_usb'] ); ?> />
						<?php esc_html_e( 'usb（WebUSB）', 'htaccess-ss' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="htaccess_ss_settings[perm_gyroscope]" value="1" <?php checked( $tab_settings['perm_gyroscope'] ); ?> />
						<?php esc_html_e( 'gyroscope（ジャイロセンサー）', 'htaccess-ss' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="htaccess_ss_settings[perm_magnetometer]" value="1" <?php checked( $tab_settings['perm_magnetometer'] ); ?> />
						<?php esc_html_e( 'magnetometer（磁力センサー）', 'htaccess-ss' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="htaccess_ss_settings[perm_geolocation]" value="1" <?php checked( $tab_settings['perm_geolocation'] ); ?> />
						<?php esc_html_e( 'geolocation（位置情報）', 'htaccess-ss' ); ?>
					</label>
					<p class="description"><?php esc_html_e( '※ Google マップ等を使用する場合は geolocation のチェックを外してください。', 'htaccess-ss' ); ?></p>
				</fieldset>
			</td>
		</tr>
	</table>
</div>
