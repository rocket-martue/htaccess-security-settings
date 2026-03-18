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
		$('.htaccess-ss-toggle-parent').on(
			'change',
			function () {
				const $sub = $(this).closest('td').find('.htaccess-ss-sub-options');
				if ($(this).is(':checked')) {
					$sub.slideDown(200);
				} else {
					$sub.slideUp(200);
				}
			}
		);
	}

	/**
	 * CSP mode radio: show/hide upgrade-insecure-requests row.
	 */
	function initCspModeToggle() {
		$('input[name="htaccess_ss_settings[csp_mode]"]').on(
			'change',
			function () {
				const isEnforce = $(this).val() === 'enforce';
				$('.htaccess-ss-csp-upgrade-row').toggle(isEnforce);
			}
		);
	}

	/**
	 * CSP enabled toggle: show/hide all CSP sub-options.
	 */
	function initCspToggle() {
		$('#htaccess-ss-csp-enabled').on(
			'change',
			function () {
				$('.htaccess-ss-csp-options').toggle($(this).is(':checked'));
			}
		);
	}

	/**
	 * Permissions Policy enabled toggle.
	 */
	function initPermissionsToggle() {
		$('#htaccess-ss-permissions-enabled').on(
			'change',
			function () {
				$('.htaccess-ss-permissions-options').toggle($(this).is(':checked'));
			}
		);
	}

	/**
	 * Restore confirmation.
	 */
	function initRestore() {
		$('#htaccess-ss-restore-form').on(
			'submit',
			function (e) {
				/* eslint-disable-next-line no-alert */
				if (!window.confirm('バックアップから .htaccess を復元します。現在の .htaccess は上書きされます。よろしいですか？')) {
					e.preventDefault();
				}
			}
		);
	}

	/**
	 * Preset apply: enable button on select change and show confirm dialog.
	 */
	function initPreset() {
		const $select = $('#htaccess-ss-preset-select');
		const $button = $('#htaccess-ss-preset-btn');

		// 初期表示時：未選択ならボタンを無効化
		$button.prop('disabled', $select.val() === '');

		$select.on(
			'change',
			function () {
				$button.prop('disabled', $(this).val() === '');
			}
		);

		$('#htaccess-ss-preset-form').on(
			'submit',
			function (e) {
				const selected = $select.find('option:selected').text();
				/* eslint-disable-next-line no-alert */
				if (!window.confirm(
					'「' + selected + '」を適用します。現在の設定はすべて上書きされます。よろしいですか？'
				)) {
					e.preventDefault();
				}
			}
		);
	}

	/**
	 * Download .htaccess file.
	 */
	function initDownload() {
		$('#htaccess-ss-download-btn').on(
			'click',
			function () {
				const url = htaccessSS.ajaxUrl +
					'?action=htaccess_ss_download&nonce=' +
					encodeURIComponent(htaccessSS.downloadNonce);
				window.location.href = url;
			}
		);
	}

	/**
	 * Delete all settings confirmation.
	 */
	function initDeleteAll() {
		$('#htaccess-ss-delete-all-form').on(
			'submit',
			function (e) {
				/* eslint-disable-next-line no-alert */
				if (!window.confirm('すべての設定・バックアップを削除し、.htaccess からプラグインの記述を除去します。この操作は取り消せません。よろしいですか？')) {
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
			initRestore();
			initPreset();
			initDownload();
			initDeleteAll();
		}
	);
})(jQuery);
