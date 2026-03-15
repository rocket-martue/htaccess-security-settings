/**
 * Htaccess Security Settings - Admin Script
 *
 * @package HtaccessSS
 */

/* global jQuery, htaccessSS */
(function ($) {
	'use strict';

	/**
	 * Toggle parent checkbox: show/hide child sub-options.
	 */
	function initToggleParent() {
		$( '.htaccess-ss-toggle-parent' ).on(
			'change',
			function () {
				const $sub = $( this ).closest( 'td' ).find( '.htaccess-ss-sub-options' );
				if ($( this ).is( ':checked' )) {
					$sub.slideDown( 200 );
				} else {
					$sub.slideUp( 200 );
				}
			}
		);
	}

	/**
	 * CSP mode radio: show/hide upgrade-insecure-requests row.
	 */
	function initCspModeToggle() {
		$( 'input[name="htaccess_ss_settings[csp_mode]"]' ).on(
			'change',
			function () {
				const isEnforce = $( this ).val() === 'enforce';
				$( '.htaccess-ss-csp-upgrade-row' ).toggle( isEnforce );
			}
		);
	}

	/**
	 * CSP enabled toggle: show/hide all CSP sub-options.
	 */
	function initCspToggle() {
		$( '#htaccess-ss-csp-enabled' ).on(
			'change',
			function () {
				$( '.htaccess-ss-csp-options' ).toggle( $( this ).is( ':checked' ) );
			}
		);
	}

	/**
	 * Permissions Policy enabled toggle.
	 */
	function initPermissionsToggle() {
		$( '#htaccess-ss-permissions-enabled' ).on(
			'change',
			function () {
				$( '.htaccess-ss-permissions-options' ).toggle( $( this ).is( ':checked' ) );
			}
		);
	}

	/**
	 * Preview button: fetch generated .htaccess via Ajax.
	 */
	function initPreview() {
		$( '#htaccess-ss-preview-btn' ).on(
			'click',
			function (e) {
				e.preventDefault();

				const $btn     = $( this );
				const $spinner = $btn.siblings( '.spinner' );

				$btn.prop( 'disabled', true );
				$spinner.addClass( 'is-active' );

				$.post(
					htaccessSS.ajaxUrl,
					{
						action: 'htaccess_ss_preview',
						_ajax_nonce: htaccessSS.nonce,
					},
					function (response) {
						$btn.prop( 'disabled', false );
						$spinner.removeClass( 'is-active' );

						if (response.success) {
							$( '#htaccess-ss-preview-root' ).text( response.data.root );

							if (response.data.wp_admin) {
								$( '#htaccess-ss-preview-admin' ).text( response.data.wp_admin ).show();
								$( '#htaccess-ss-preview-admin-heading' ).show();
							} else {
								$( '#htaccess-ss-preview-admin' ).hide();
								$( '#htaccess-ss-preview-admin-heading' ).hide();
							}

							$( '#htaccess-ss-preview-modal' ).fadeIn( 200 );
						} else {
							/* eslint-disable-next-line no-alert */
							window.alert( response.data || 'プレビューの取得に失敗しました。' );
						}
					}
				).fail(
					function () {
						$btn.prop( 'disabled', false );
						$spinner.removeClass( 'is-active' );
						/* eslint-disable-next-line no-alert */
						window.alert( '通信エラーが発生しました。' );
					}
				);
			}
		);
	}

	/**
	 * Modal close handlers.
	 */
	function initModal() {
		$( '.htaccess-ss-modal-close, .htaccess-ss-modal-overlay' ).on(
			'click',
			function () {
				$( '#htaccess-ss-preview-modal' ).fadeOut( 200 );
			}
		);

		$( document ).on(
			'keydown',
			function (e) {
				if (e.key === 'Escape') {
					$( '#htaccess-ss-preview-modal' ).fadeOut( 200 );
				}
			}
		);
	}

	/**
	 * Restore confirmation.
	 */
	function initRestore() {
		$( '#htaccess-ss-restore-form' ).on(
			'submit',
			function (e) {
				/* eslint-disable-next-line no-alert */
				if ( ! window.confirm( 'バックアップから .htaccess を復元します。現在の .htaccess は上書きされます。よろしいですか？' )) {
					e.preventDefault();
				}
			}
		);
	}

	/**
	 * Reset defaults confirmation.
	 */
	function initResetDefaults() {
		$( '#htaccess-ss-reset-form' ).on(
			'submit',
			function (e) {
				/* eslint-disable-next-line no-alert */
				if ( ! window.confirm( 'すべての設定をデフォルトに戻します。この操作は取り消せません。よろしいですか？' )) {
					e.preventDefault();
				}
			}
		);
	}

	/**
	 * Download .htaccess file.
	 */
	function initDownload() {
		$( '#htaccess-ss-download-btn' ).on(
			'click',
			function () {
				const url            = htaccessSS.ajaxUrl +
					'?action=htaccess_ss_download&nonce=' +
					encodeURIComponent( htaccessSS.downloadNonce );
				window.location.href = url;
			}
		);
	}

	/**
	 * Delete all settings confirmation.
	 */
	function initDeleteAll() {
		$( '#htaccess-ss-delete-all-form' ).on(
			'submit',
			function (e) {
				/* eslint-disable-next-line no-alert */
				if ( ! window.confirm( 'すべての設定・バックアップを削除し、.htaccess からプラグインの記述を除去します。この操作は取り消せません。よろしいですか？' )) {
					e.preventDefault();
				}
			}
		);
	}

	/**
	 * Initialize.
	 */
	$(
		function () {
			initToggleParent();
			initCspModeToggle();
			initCspToggle();
			initPermissionsToggle();
			initPreview();
			initModal();
			initRestore();
			initResetDefaults();
			initDownload();
			initDeleteAll();
		}
	);
})( jQuery );
