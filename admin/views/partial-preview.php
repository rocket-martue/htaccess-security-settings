<?php
/**
 * プレビューモーダル
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="htaccess-ss-preview-modal" class="htaccess-ss-modal" style="display:none;">
	<div class="htaccess-ss-modal-overlay"></div>
	<div class="htaccess-ss-modal-content">
		<div class="htaccess-ss-modal-header">
			<h2><?php esc_html_e( '.htaccess プレビュー', 'htaccess-ss' ); ?></h2>
			<button type="button" class="htaccess-ss-modal-close" aria-label="<?php esc_attr_e( '閉じる', 'htaccess-ss' ); ?>">&times;</button>
		</div>
		<div class="htaccess-ss-modal-body">
			<h3><?php esc_html_e( 'ルート .htaccess', 'htaccess-ss' ); ?></h3>
			<pre id="htaccess-ss-preview-root" class="htaccess-ss-preview-code"></pre>
			<h3 id="htaccess-ss-preview-admin-heading" style="display:none;"><?php esc_html_e( 'wp-admin/.htaccess', 'htaccess-ss' ); ?></h3>
			<pre id="htaccess-ss-preview-admin" class="htaccess-ss-preview-code" style="display:none;"></pre>
		</div>
	</div>
</div>
