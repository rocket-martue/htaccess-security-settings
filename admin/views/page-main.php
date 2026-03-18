<?php
/**
 * メイン設定ページテンプレート
 *
 * @package HtaccessSS
 *
 * @var array  $all_settings 全設定
 * @var string $current_tab  現在のタブ
 * @var array  $tabs         タブ定義
 * @var string $backup_time  バックアップ日時
 * @var string $status       ステータスメッセージ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( '.htaccess セキュリティ設定', 'htaccess-ss' ); ?></h1>

	<div class="notice notice-info">
		<p>
			<?php
			esc_html_e(
				'このプラグインは .htaccess のセットアップウィザードです。設定を保存すると .htaccess にルールが書き込まれ、プラグインを無効化・削除してもルールはそのまま残ります。',
				'htaccess-ss'
			);
			?>
			<br>
			<?php
			esc_html_e(
				'ルールを削除したい場合は、先に「すべての設定を削除」ボタンを使ってください。',
				'htaccess-ss'
			);
			?>
		</p>
	</div>

	<?php if ( 'saved' === $status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( '設定を保存し、.htaccess に反映しました。', 'htaccess-ss' ); ?></p>
		</div>
	<?php elseif ( 'error_root' === $status ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'ルート .htaccess への書き込みに失敗しました。ファイルのパーミッションを確認してください。', 'htaccess-ss' ); ?></p>
		</div>
	<?php elseif ( 'error_admin' === $status ) : ?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( '設定は保存されましたが、wp-admin/.htaccess への書き込みに失敗しました。', 'htaccess-ss' ); ?></p>
		</div>
	<?php elseif ( 'restored' === $status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'バックアップから .htaccess を復元しました。', 'htaccess-ss' ); ?></p>
		</div>
	<?php elseif ( 'restore_error' === $status ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( '.htaccess の復元に失敗しました。', 'htaccess-ss' ); ?></p>
		</div>
	<?php elseif ( 'preset_applied' === $status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'プリセットを適用し、.htaccess に反映しました。', 'htaccess-ss' ); ?></p>
		</div>
	<?php elseif ( 'deleted_all' === $status ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'すべての設定とバックアップを削除し、.htaccess からプラグインブロックを除去しました。', 'htaccess-ss' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="htaccess-ss-layout">

		<!-- 左カラム: アクションボタン -->
		<div class="htaccess-ss-action-column">
			<button type="button" id="htaccess-ss-download-btn" class="button button-secondary">
				<?php esc_html_e( '.htaccess をダウンロード', 'htaccess-ss' ); ?>
			</button>

			<button type="submit" form="htaccess-ss-main-form" class="button button-primary">
				<?php esc_html_e( '設定を保存して .htaccess に反映', 'htaccess-ss' ); ?>
			</button>

			<form method="post" action="" id="htaccess-ss-preset-form" class="htaccess-ss-action-form">
				<input type="hidden" name="htaccess_ss_action" value="apply_preset" />
				<input type="hidden" name="_tab" value="<?php echo esc_attr( $current_tab ); ?>" />
				<?php wp_nonce_field( 'htaccess_ss_preset', 'htaccess_ss_preset_nonce' ); ?>
				<label for="htaccess-ss-preset-select" class="screen-reader-text"><?php esc_html_e( 'プリセット選択', 'htaccess-ss' ); ?></label>
				<select name="preset_key" id="htaccess-ss-preset-select" class="htaccess-ss-preset-select">
					<option value=""><?php esc_html_e( '-- プリセットを選択 --', 'htaccess-ss' ); ?></option>
					<option value="defaults"><?php esc_html_e( 'デフォルト設定に戻す（全 OFF）', 'htaccess-ss' ); ?></option>
					<?php foreach ( HSS_Settings::get_presets() as $preset_key => $preset ) : ?>
						<option value="<?php echo esc_attr( $preset_key ); ?>">
							<?php echo esc_html( $preset['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="button button-primary" id="htaccess-ss-preset-btn">
					<?php esc_html_e( 'プリセットを適用', 'htaccess-ss' ); ?>
				</button>
			</form>

			<form method="post" action="" id="htaccess-ss-delete-all-form" class="htaccess-ss-action-form">
				<input type="hidden" name="htaccess_ss_action" value="delete_all" />
				<?php wp_nonce_field( 'htaccess_ss_delete_all', 'htaccess_ss_delete_all_nonce' ); ?>
				<button type="submit" class="button htaccess-ss-btn-danger">
					<?php esc_html_e( 'すべての設定を削除', 'htaccess-ss' ); ?>
				</button>
			</form>

			<div class="htaccess-ss-backup-section">
				<?php if ( $backup_time ) : ?>
					<form method="post" action="" id="htaccess-ss-restore-form" class="htaccess-ss-action-form">
						<input type="hidden" name="htaccess_ss_action" value="restore" />
						<input type="hidden" name="_tab" value="<?php echo esc_attr( $current_tab ); ?>" />
						<?php wp_nonce_field( 'htaccess_ss_restore', 'htaccess_ss_restore_nonce' ); ?>
						<span class="htaccess-ss-backup-time">
							<?php
							printf(
								/* translators: %s: backup datetime */
								esc_html__( '最終バックアップ: %s', 'htaccess-ss' ),
								esc_html( $backup_time )
							);
							?>
						</span>
						<button type="submit" class="button button-secondary button-small">
							<?php esc_html_e( 'バックアップから復元', 'htaccess-ss' ); ?>
						</button>
					</form>
				<?php else : ?>
					<span class="description"><?php esc_html_e( 'まだバックアップはありません。', 'htaccess-ss' ); ?></span>
				<?php endif; ?>
			</div>
		</div><!-- /.htaccess-ss-action-column -->

		<!-- 中央カラム: タブ + フォーム -->
		<div class="htaccess-ss-main-column">
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'page' => 'htaccess-security-settings',
								'tab'  => $tab_key,
							),
							admin_url( 'options-general.php' )
						)
					);
					?>
							"
						class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form id="htaccess-ss-main-form" method="post" action="" class="htaccess-ss-form">
				<input type="hidden" name="htaccess_ss_action" value="save" />
				<input type="hidden" name="_tab" value="<?php echo esc_attr( $current_tab ); ?>" />
				<?php wp_nonce_field( 'htaccess_ss_save', 'htaccess_ss_nonce' ); ?>

				<?php
				$tab_file = HSS_PLUGIN_DIR . 'admin/views/tab-' . str_replace( '_', '-', $current_tab ) . '.php';
				if ( file_exists( $tab_file ) ) {
					$tab_defaults = HSS_Settings::get_defaults();
					$tab_settings = isset( $all_settings[ $current_tab ] ) ? $all_settings[ $current_tab ] : array();
					if ( isset( $tab_defaults[ $current_tab ] ) ) {
						$tab_settings = array_merge( $tab_defaults[ $current_tab ], $tab_settings );
					}
					include $tab_file;
				}
				?>
			</form>
		</div><!-- /.htaccess-ss-main-column -->

		<!-- 右サイドバー: 実際の .htaccess ファイル内容 -->
		<div class="htaccess-ss-file-sidebar">
			<p class="htaccess-ss-sidebar-title"><?php esc_html_e( 'ルート .htaccess', 'htaccess-ss' ); ?></p>
			<?php if ( '' !== $root_htaccess ) : ?>
				<pre class="htaccess-ss-file-content"><?php echo esc_html( $root_htaccess ); ?></pre>
			<?php else : ?>
				<p class="htaccess-ss-sidebar-empty"><?php esc_html_e( 'ファイルが見つかりません。', 'htaccess-ss' ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $admin_htaccess ) : ?>
				<p class="htaccess-ss-sidebar-title htaccess-ss-sidebar-title--secondary"><?php esc_html_e( 'wp-admin/.htaccess', 'htaccess-ss' ); ?></p>
				<pre class="htaccess-ss-file-content"><?php echo esc_html( $admin_htaccess ); ?></pre>
			<?php endif; ?>
		</div><!-- /.htaccess-ss-file-sidebar -->

	</div><!-- /.htaccess-ss-layout -->

</div>
