<?php
/**
 * .htaccess ファイル操作クラス
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * .htaccess ファイルの読み書き・バックアップ・復元を管理するクラス
 */
class HSS_Htaccess_Writer {

	/**
	 * マーカー文字列
	 *
	 * @var string
	 */
	const MARKER = 'Htaccess Security Settings';

	/**
	 * ルート .htaccess にディレクティブを書き込む
	 *
	 * WordPress ブロックの前に配置し、RewriteRule の優先順位を確保する
	 *
	 * @param array $lines 書き込む行の配列
	 * @return true|WP_Error
	 */
	public function write_root( $lines ) {
		$file = $this->get_root_path();

		$check = $this->check_writable( $file );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		// バックアップ
		$this->backup( 'root' );

		return $this->write_with_position( $file, $lines );
	}

	/**
	 * 管理画面用 .htaccess にディレクティブを書き込む
	 *
	 * @param array $lines 書き込む行の配列
	 * @return true|WP_Error
	 */
	public function write_wp_admin( $lines ) {
		$file = $this->get_wp_admin_path();

		// 空の場合はファイルからブロックを除去
		if ( empty( $lines ) ) {
			if ( file_exists( $file ) ) {
				$this->backup( 'admin' );
				return $this->remove_block( $file );
			}
			return true;
		}

		$check = $this->check_writable( $file );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$this->backup( 'admin' );

		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$result = insert_with_markers( $file, self::MARKER, $lines );
		return $result ? true : new WP_Error( 'write_failed', 'wp-admin/.htaccess への書き込みに失敗しました。' );
	}

	/**
	 * Uploads ディレクトリの .htaccess にディレクティブを書き込む
	 *
	 * @param array $lines 書き込む行の配列。
	 * @return true|WP_Error
	 */
	public function write_uploads( $lines ) {
		$file = $this->get_uploads_path();
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		if ( empty( $lines ) ) {
			if ( file_exists( $file ) ) {
				$this->backup( 'uploads' );
				return $this->remove_block( $file );
			}
			return true;
		}

		// ディレクトリが未作成の場合は作成を試みる。
		$dir = dirname( $file );
		if ( ! is_dir( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return new WP_Error(
					'upload_dir_unavailable',
					sprintf(
						/* translators: %s: directory path */
						'%s ディレクトリを作成できませんでした。パーミッションを確認してください。',
						$dir
					)
				);
			}
		}

		$check = $this->check_writable( $file );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$this->backup( 'uploads' );

		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$result = insert_with_markers( $file, self::MARKER, $lines );
		if ( ! $result ) {
			return new WP_Error(
				'write_failed',
				sprintf(
					/* translators: %s: file path */
					'アップロードディレクトリの .htaccess (%s) への書き込みに失敗しました。',
					$file
				)
			);
		}

		return true;
	}

	/**
	 * バックアップから .htaccess を復元する
	 *
	 * @param string $type 'root', 'admin', または 'uploads'。
	 * @return true|WP_Error
	 */
	public function restore( $type ) {
		if ( 'root' === $type ) {
			$file       = $this->get_root_path();
			$option_key = HSS_Settings::BACKUP_ROOT_KEY;
		} elseif ( 'admin' === $type ) {
			$file       = $this->get_wp_admin_path();
			$option_key = HSS_Settings::BACKUP_ADMIN_KEY;
		} elseif ( 'uploads' === $type ) {
			$file       = $this->get_uploads_path();
			$option_key = HSS_Settings::BACKUP_UPLOADS_KEY;
		} else {
			return new WP_Error( 'invalid_type', '無効なバックアップタイプです。' );
		}

		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$backup = get_option( $option_key );
		if ( false === $backup ) {
			return new WP_Error( 'no_backup', 'バックアップが見つかりません。' );
		}

		$check = $this->check_writable( $file );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $file, $backup, LOCK_EX );
		return false !== $result ? true : new WP_Error( 'restore_failed', '.htaccess の復元に失敗しました。' );
	}

	/**
	 * 現在の .htaccess をバックアップする
	 *
	 * @param string $type 'root', 'admin', または 'uploads'。
	 */
	public function backup( $type ) {
		if ( 'root' === $type ) {
			$file       = $this->get_root_path();
			$option_key = HSS_Settings::BACKUP_ROOT_KEY;
		} elseif ( 'admin' === $type ) {
			$file       = $this->get_wp_admin_path();
			$option_key = HSS_Settings::BACKUP_ADMIN_KEY;
		} elseif ( 'uploads' === $type ) {
			$file       = $this->get_uploads_path();
			$option_key = HSS_Settings::BACKUP_UPLOADS_KEY;
		} else {
			return;
		}

		if ( is_wp_error( $file ) ) {
			return;
		}

		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$content = file_get_contents( $file );
			update_option( $option_key, $content, false );
			update_option( HSS_Settings::BACKUP_TIME_KEY, current_time( 'mysql' ), false );
		}
	}

	/**
	 * バックアップ日時を取得する
	 *
	 * @return string|false
	 */
	public function get_backup_time() {
		return get_option( HSS_Settings::BACKUP_TIME_KEY, false );
	}

	/**
	 * ルート .htaccess のパスを取得する
	 *
	 * @return string
	 */
	public function get_root_path() {
		return ABSPATH . '.htaccess';
	}

	/**
	 * 管理画面用 .htaccess のパスを取得する
	 *
	 * @return string
	 */
	public function get_wp_admin_path() {
		return ABSPATH . 'wp-admin/.htaccess';
	}

	/**
	 * Uploads ディレクトリの .htaccess パスを取得する
	 *
	 * @return string|WP_Error
	 */
	public function get_uploads_path() {
		$upload_dir = wp_get_upload_dir();

		if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) ) {
			return new WP_Error(
				'upload_dir_unavailable',
				'アップロードディレクトリのパスを取得できませんでした。'
			);
		}

		return rtrim( $upload_dir['basedir'], '/\\' ) . '/.htaccess';
	}

	/**
	 * ファイルの書き込み可否をチェックする
	 *
	 * @param string $file ファイルパス
	 * @return true|WP_Error
	 */
	private function check_writable( $file ) {
		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
			if ( ! is_writable( $file ) ) {
				return new WP_Error(
					'not_writable',
					/* translators: %s: file path */
					sprintf( '%s に書き込み権限がありません。ファイルのパーミッションを確認してください。', $file )
				);
			}
			return true;
		}

		// ファイルが存在しない場合はディレクトリの書き込み権限をチェック
		$dir = dirname( $file );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		if ( ! is_writable( $dir ) ) {
			return new WP_Error(
				'dir_not_writable',
				/* translators: %s: directory path */
				sprintf( '%s ディレクトリに書き込み権限がありません。', $dir )
			);
		}

		return true;
	}

	/**
	 * WordPress ブロックの前にプラグインブロックを配置して書き込む
	 *
	 * @param string $file  ファイルパス
	 * @param array  $lines 書き込む行の配列
	 * @return true|WP_Error
	 */
	private function write_with_position( $file, $lines ) {
		// 既存の内容を読み込み
		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$existing = file_get_contents( $file );
			if ( false === $existing ) {
				return new WP_Error( 'read_failed', '.htaccess の読み取りに失敗しました。' );
			}
			$existing_lines = explode( "\n", $existing );
		} else {
			$existing_lines = array();
		}

		// 既存のプラグインブロックを除去
		$output   = array();
		$in_block = false;
		foreach ( $existing_lines as $line ) {
			if ( false !== strpos( $line, '# BEGIN ' . self::MARKER ) ) {
				$in_block = true;
				continue;
			}
			if ( false !== strpos( $line, '# END ' . self::MARKER ) ) {
				$in_block = false;
				continue;
			}
			if ( ! $in_block ) {
				$output[] = $line;
			}
		}

		// 書き込む内容がない場合（全設定 OFF）
		if ( empty( $lines ) ) {
			// ファイルが存在しなければ何もしない
			if ( ! file_exists( $file ) ) {
				return true;
			}
			$content = implode( "\n", $output );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$result = file_put_contents( $file, $content, LOCK_EX );
			return false !== $result ? true : new WP_Error( 'write_failed', '.htaccess への書き込みに失敗しました。' );
		}

		// プラグインブロックを構築
		$block   = array();
		$block[] = '# BEGIN ' . self::MARKER;
		$block   = array_merge( $block, $lines );
		$block[] = '# END ' . self::MARKER;
		$block[] = '';

		// WordPress ブロックの位置を探す
		$wp_pos = null;
		foreach ( $output as $i => $line ) {
			if ( false !== strpos( $line, '# BEGIN WordPress' ) ) {
				$wp_pos = $i;
				break;
			}
		}

		// WordPress ブロックの前に挿入（見つからなければ末尾に追加）
		if ( null !== $wp_pos ) {
			array_splice( $output, $wp_pos, 0, $block );
		} else {
			$output = array_merge( $output, $block );
		}

		$content = implode( "\n", $output );

		// 末尾に改行を保証
		if ( '' !== $content && "\n" !== substr( $content, -1 ) ) {
			$content .= "\n";
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $file, $content, LOCK_EX );
		return false !== $result ? true : new WP_Error( 'write_failed', '.htaccess への書き込みに失敗しました。' );
	}

	/**
	 * ファイルからプラグインブロックのみを除去する
	 *
	 * @param string $file ファイルパス
	 * @return true|WP_Error
	 */
	private function remove_block( $file ) {
		if ( ! file_exists( $file ) ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$existing = file_get_contents( $file );
		if ( false === $existing ) {
			return new WP_Error( 'read_failed', '.htaccess の読み取りに失敗しました。' );
		}
		$existing_lines = explode( "\n", $existing );

		$output   = array();
		$in_block = false;
		foreach ( $existing_lines as $line ) {
			if ( false !== strpos( $line, '# BEGIN ' . self::MARKER ) ) {
				$in_block = true;
				continue;
			}
			if ( false !== strpos( $line, '# END ' . self::MARKER ) ) {
				$in_block = false;
				continue;
			}
			if ( ! $in_block ) {
				$output[] = $line;
			}
		}

		$content = implode( "\n", $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $file, $content, LOCK_EX );
		return false !== $result ? true : new WP_Error( 'remove_failed', '.htaccess からのブロック除去に失敗しました。' );
	}
}
