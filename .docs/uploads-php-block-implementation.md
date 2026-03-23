# uploads ディレクトリ PHP 実行禁止 — 実装計画

> **作成日**: 2026-03-23
> **対象 Issue**: [#14 wp-content/uploads ディレクトリの PHP 実行禁止も設定できるようにする](https://github.com/rocket-martue/htaccess-security-settings/issues/14)
> **現在のバージョン**: v1.3.1

---

## 目次

1. [概要](#1-概要)
2. [攻撃シナリオと対策](#2-攻撃シナリオと対策)
3. [生成される .htaccess ディレクティブ](#3-生成される-htaccess-ディレクティブ)
4. [.htaccess の設置場所](#4-htaccess-の設置場所)
5. [変更対象ファイル一覧](#5-変更対象ファイル一覧)
6. [各ファイルの変更詳細](#6-各ファイルの変更詳細)
7. [テスト](#7-テスト)
8. [設計上の注意点](#8-設計上の注意点)

---

## 1. 概要

`wp-content/uploads/` ディレクトリ内の PHP 関連ファイル（`.php` / `.phar` / `.phtml`）へのHTTPアクセスを 403 Forbidden で拒否する機能を追加する。

プラグインの脆弱性を突いて uploads にバックドア（悪意のある .php ファイル）がアップロードされた場合でも、PHP の実行を防止できる。

---

## 2. 攻撃シナリオと対策

### 攻撃の流れ

1. 攻撃者がプラグインの脆弱性を悪用
2. `uploads/` に `evil.php`（バックドア）をアップロード
3. `https://example.com/wp-content/uploads/evil.php` にアクセス
4. PHP が実行されてサーバーを掌握

### 対策後

3 のアクセスで 403 Forbidden → PHP が実行されない → 攻撃失敗

### なぜ `php_flag engine off` を使わないのか

| 方法 | mod_php | PHP-FPM | 備考 |
|:-----|:--------|:--------|:-----|
| `php_flag engine off` | ✅ 有効 | ❌ 無効 | PHP の実行方式に依存 |
| `FilesMatch` + `Require all denied` | ✅ 有効 | ✅ 有効 | Apache レベルで拒否 |

XServer を含む多くのレンタルサーバーは PHP-FPM を採用しているため、`FilesMatch` 方式が確実。
また、ルートの `.htaccess` で使っている `xmlrpc.php` や `wp-config.php` のブロックと書き方が統一できる。

### `.phar` / `.phtml` を対象に含める理由

- `.phar` — PHP Archive。PHP として実行可能なアーカイブ形式
- `.phtml` — PHP のレガシーな拡張子。一部のサーバーで PHP として処理される
- 攻撃者は `.php` がブロックされている場合、別の拡張子でアップロードを試みる
- 正常な WordPress 運用で uploads に `.phar` / `.phtml` が入ることはないので、ブロックして問題なし

---

## 3. 生成される .htaccess ディレクティブ

`wp-content/uploads/.htaccess` に以下が書き込まれる：

```apache
# BEGIN Htaccess Security Settings
# PHP 関連ファイルの実行を禁止
<FilesMatch "(?i)\.(?:php|phar|phtml)$">
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		Deny from all
	</IfModule>
</FilesMatch>
# END Htaccess Security Settings
```

**設計ポイント**:

- `(?i)` で大文字拡張子（`.PHP` / `.PHTML` 等）にも対応
- Apache 2.4（`mod_authz_core.c`）と Apache 2.2（`!mod_authz_core.c`）の両方に対応
  - 既存の `build_deny_files_block()` と統一したパターン
- `insert_with_markers()` により `# BEGIN / # END` マーカーブロック内に書き込み
  - 他のプラグインが作成した既存のルールには一切影響しない

---

## 4. .htaccess の設置場所

```
public_html/
├── .htaccess              ← ルートの設定（既存）
├── wp-admin/
│   └── .htaccess          ← 管理画面専用の設定（既存）
└── wp-content/
    └── uploads/
        └── .htaccess      ← アップロードディレクトリ専用（今回追加）
```

`.htaccess` では `<Directory>` ディレクティブは使えない（`httpd.conf` 専用）。ディレクトリ単位で制御したい場合は、そのディレクトリに別の `.htaccess` を設置する設計になる。

---

## 5. 変更対象ファイル一覧

| # | ファイル | 変更種別 | 変更内容 |
|:--|:---------|:---------|:---------|
| 1 | `includes/class-hss-settings.php` | 修正 | 設定の定義・サニタイズ・プリセット |
| 2 | `includes/class-hss-htaccess-builder.php` | 修正 | ディレクティブ生成メソッド追加 |
| 3 | `includes/class-hss-htaccess-writer.php` | 修正 | ファイル I/O・バックアップ・復元 |
| 4 | `includes/class-hss-admin-page.php` | 修正 | タブ追加・保存/復元/プリセット/削除処理 |
| 5 | `admin/views/tab-uploads.php` | **新規作成** | 管理画面テンプレート |
| 6 | `admin/views/page-main.php` | 修正 | サイドバーに uploads の .htaccess 表示追加 |
| 7 | `uninstall.php` | 修正 | バックアップオプション削除追加 |
| 8 | `tests/Unit/HtaccessBuilderTest.php` | 修正 | テストケース追加 |
| 9 | `tests/Unit/SettingsTest.php` | 修正 | テストケース追加 |

### 変更しないファイル

| ファイル | 理由 |
|:---------|:-----|
| `includes/class-hss-activator.php` | セットアップウィザード設計に従い `deactivate()` は変更しない（既存の root / wp-admin と同じ方針） |
| `admin/js/admin-script.js` | 単純なチェックボックスのため JS 制御不要 |
| `admin/css/admin-style.css` | 既存スタイルで十分 |

---

## 6. 各ファイルの変更詳細

### 6-1. `includes/class-hss-settings.php`

#### バックアップキー定数の追加

```php
const BACKUP_UPLOADS_KEY = 'htaccess_ss_backup_uploads';
```

#### VALID_TABS に追加

```php
const VALID_TABS = array( 'options', 'ip_block', 'rewrite', 'headers', 'cache', 'wp_admin', 'uploads' );
```

#### `get_defaults()` に追加

```php
return array(
    // ... 既存の設定 ...
    'wp_admin' => array( /* ... */ ),
    'uploads'  => array(
        'block_php' => false,
    ),
);
```

#### `sanitize_and_merge()` に case 追加

```php
case 'uploads':
    $current['uploads'] = $this->sanitize_uploads_tab( $input );
    break;
```

#### サニタイズメソッドの追加

```php
/**
 * uploads タブのサニタイズ
 *
 * @param array $input POST データ。
 * @return array
 */
private function sanitize_uploads_tab( $input ) {
    return array(
        'block_php' => ! empty( $input['block_php'] ),
    );
}
```

#### プリセットへの反映

`get_presets()` 内で以下のプリセットに `uploads` を追加する：

| プリセット | `block_php` | 理由 |
|:-----------|:------------|:-----|
| `recommended` | `true` | セキュリティ推奨設定に含める |
| `headers_only` | 変更不要 | デフォルト値（`false`）がそのまま使われる |
| `performance` | 変更不要 | デフォルト値（`false`）がそのまま使われる |
| `max_security` | 変更不要 | `$recommended` をベースにしているため自動的に `true` が適用される |

---

### 6-2. `includes/class-hss-htaccess-builder.php`

#### `build_uploads()` メソッドの追加

```php
/**
 * uploads ディレクトリ用 .htaccess ディレクティブを生成する
 *
 * @param array $settings 全設定配列。
 * @return array 行の配列。
 */
public function build_uploads( $settings ) {
    $uploads = $settings['uploads'];

    if ( ! $uploads['block_php'] ) {
        return array();
    }

    $lines   = array();
    $lines[] = '# PHP 関連ファイルの実行を禁止';
    $lines[] = '<FilesMatch "(?i)\.(?:php|phar|phtml)$">';
    $lines[] = "\t" . '<IfModule mod_authz_core.c>';
    $lines[] = "\t\t" . 'Require all denied';
    $lines[] = "\t" . '</IfModule>';
    $lines[] = "\t" . '<IfModule !mod_authz_core.c>';
    $lines[] = "\t\t" . 'Order deny,allow';
    $lines[] = "\t\t" . 'Deny from all';
    $lines[] = "\t" . '</IfModule>';
    $lines[] = '</FilesMatch>';

    return $lines;
}
```

---

### 6-3. `includes/class-hss-htaccess-writer.php`

#### パス取得メソッドの追加

```php
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
```

> `wp_get_upload_dir()` はディレクトリ作成を行わない軽量版。WP 4.5+ で導入され、本プラグインの要件は WP 6.0+ なので問題なし。

#### 書き込みメソッドの追加

```php
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
                sprintf( '%s ディレクトリを作成できませんでした。パーミッションを確認してください。', $dir )
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
            sprintf( 'アップロードディレクトリの .htaccess (%s) への書き込みに失敗しました。', $file )
        );
    }

    return true;
}
```

> 書き込み時に `wp_mkdir_p()` でディレクトリ作成を保証。エラーメッセージには実際のファイルパスを含めて原因特定を容易にする。

#### `backup()` メソッドに `'uploads'` type を追加

現在の `if / else` 構造を `if / elseif / elseif / else` に拡張する。

```php
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

    if ( file_exists( $file ) ) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents( $file );
        update_option( $option_key, $content, false );
        update_option( HSS_Settings::BACKUP_TIME_KEY, current_time( 'mysql' ), false );
    }
}
```

#### `restore()` メソッドに `'uploads'` type を追加

現在の `if / else` 構造を `if / elseif / elseif / else` に拡張する。

```php
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
```

---

### 6-4. `includes/class-hss-admin-page.php`

#### タブ定義に追加

```php
$this->tabs = array(
    'options'  => 'Options & ファイル保護',
    'ip_block' => 'IP ブロック',
    'rewrite'  => 'リライトルール',
    'headers'  => 'セキュリティヘッダー',
    'cache'    => 'キャッシュ',
    'wp_admin' => 'wp-admin 保護',
    'uploads'  => 'uploads 保護',
);
```

#### `handle_save()` に uploads の書き込みを追加

```php
$builder = new HSS_Htaccess_Builder();
$writer  = new HSS_Htaccess_Writer();

$root_lines  = $builder->build_root( $new_settings );
$root_result = $writer->write_root( $root_lines );

$admin_lines  = $builder->build_wp_admin( $new_settings );
$admin_result = $writer->write_wp_admin( $admin_lines );

$uploads_lines  = $builder->build_uploads( $new_settings );
$uploads_result = $writer->write_uploads( $uploads_lines );

$status = 'saved';
if ( is_wp_error( $root_result ) ) {
    $status = 'error_root';
} elseif ( is_wp_error( $admin_result ) ) {
    $status = 'error_admin';
} elseif ( is_wp_error( $uploads_result ) ) {
    $status = 'error_uploads';
}
```

#### `handle_restore()` に追加

```php
$root_result    = $writer->restore( 'root' );
$admin_result   = $writer->restore( 'admin' );
$uploads_result = $writer->restore( 'uploads' );
```

#### `handle_apply_preset()` に追加

```php
$root_result    = $writer->write_root( $builder->build_root( $new_settings ) );
$admin_result   = $writer->write_wp_admin( $builder->build_wp_admin( $new_settings ) );
$uploads_result = $writer->write_uploads( $builder->build_uploads( $new_settings ) );
```

#### `handle_delete_all()` に追加

```php
$writer          = new HSS_Htaccess_Writer();
$root_result     = $writer->write_root( array() );
$admin_result    = $writer->write_wp_admin( array() );
$uploads_result  = $writer->write_uploads( array() );

delete_option( HSS_Settings::OPTION_KEY );
delete_option( HSS_Settings::BACKUP_ROOT_KEY );
delete_option( HSS_Settings::BACKUP_ADMIN_KEY );
delete_option( HSS_Settings::BACKUP_UPLOADS_KEY );
delete_option( HSS_Settings::BACKUP_TIME_KEY );
```

#### `render_page()` に追加

```php
$uploads_htaccess_path = $writer->get_uploads_path();
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
$uploads_htaccess = file_exists( $uploads_htaccess_path )
    ? file_get_contents( $uploads_htaccess_path ) : '';
```

#### ステータスメッセージの追加

`render_page()` 内（または `page-main.php` テンプレート内）に `error_uploads` ステータスの表示を追加する。

---

### 6-5. `admin/views/tab-uploads.php`（新規作成）

独立タブとして新規作成する。wp-admin と同粒度で管理し、将来の拡張（画像ホットリンク防止など）にも対応しやすくする。

```php
<?php
/**
 * Uploads 保護タブ
 *
 * @package HtaccessSS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><?php esc_html_e( 'PHP 実行の禁止', 'htaccess-ss' ); ?></th>
		<td>
			<fieldset>
				<label>
					<input type="checkbox"
						name="htaccess_ss_settings[block_php]"
						value="1"
						<?php checked( $tab_settings['block_php'] ); ?>>
					<?php esc_html_e( 'uploads ディレクトリ内の PHP ファイルの実行をブロック', 'htaccess-ss' ); ?>
				</label>
				<p class="description">
					<?php
					echo wp_kses(
						__(
							'.php / .phar / .phtml ファイルへのアクセスを 403 Forbidden で拒否します。<br>プラグインの脆弱性を突いたバックドアアップロード攻撃を防止します。<br>wp-content/uploads/.htaccess に書き込まれます。',
							'htaccess-ss'
						),
						array( 'br' => array() )
					);
					?>
				</p>
			</fieldset>
		</td>
	</tr>
</table>
```

**設計の根拠:** `tab-options.php` に1行追加する案と比較したが、以下の理由で独立タブが適切と判断した。

- `wp-admin` も独立タブ（`tab-wp-admin.php`）として管理されており、同じ粒度で扱うのが自然
- uploads は root でも wp-admin でもない「3つ目の .htaccess ファイル」を管理する機能
- `page-main.php` のタブ読み込みロジック（`tab-{slug}.php`）がそのまま使える
- 将来 uploads に他の設定を追加する場合も拡張しやすい

---

### 6-6. `admin/views/page-main.php`

右サイドバーに uploads `.htaccess` の内容表示を追加する。wp-admin の表示ブロックの下に配置。

```php
<?php if ( '' !== $uploads_htaccess ) : ?>
	<p class="htaccess-ss-sidebar-title htaccess-ss-sidebar-title--secondary">
		<?php esc_html_e( 'uploads/.htaccess', 'htaccess-ss' ); ?>
	</p>
	<pre class="htaccess-ss-file-content"><?php echo esc_html( $uploads_htaccess ); ?></pre>
<?php endif; ?>
```

---

### 6-7. `uninstall.php`

バックアップオプションの削除を追加する。

```php
delete_option( 'htaccess_ss_settings' );
delete_option( 'htaccess_ss_backup_root' );
delete_option( 'htaccess_ss_backup_admin' );
delete_option( 'htaccess_ss_backup_uploads' );
delete_option( 'htaccess_ss_backup_time' );
```

---

## 7. テスト

### 7-1. `tests/Unit/HtaccessBuilderTest.php`

```php
/**
 * uploads: block_php が有効な場合にディレクティブが生成されること
 */
public function test_build_uploads_enabled() {
    $builder  = new HSS_Htaccess_Builder();
    $settings = HSS_Settings::get_defaults();
    $settings['uploads']['block_php'] = true;
    $lines    = $builder->build_uploads( $settings );
    $output   = implode( "\n", $lines );

    $this->assertNotEmpty( $lines );
    $this->assertStringContainsString( 'php|phar|phtml', $output );
    $this->assertStringContainsString( 'Require all denied', $output );
}

/**
 * uploads: block_php が無効な場合に空配列が返ること
 */
public function test_build_uploads_disabled() {
    $builder  = new HSS_Htaccess_Builder();
    $settings = HSS_Settings::get_defaults();
    $lines    = $builder->build_uploads( $settings );

    $this->assertEmpty( $lines );
}

/**
 * uploads: Apache 2.2 フォールバックが含まれること
 */
public function test_build_uploads_contains_apache22_fallback() {
    $builder  = new HSS_Htaccess_Builder();
    $settings = HSS_Settings::get_defaults();
    $settings['uploads']['block_php'] = true;
    $lines    = $builder->build_uploads( $settings );
    $output   = implode( "\n", $lines );

    $this->assertStringContainsString( '<IfModule !mod_authz_core.c>', $output );
    $this->assertStringContainsString( 'Deny from all', $output );
}

/**
 * uploads: FilesMatch の正規表現が非キャプチャグループを使用していること
 */
public function test_build_uploads_uses_non_capturing_group() {
    $builder  = new HSS_Htaccess_Builder();
    $settings = HSS_Settings::get_defaults();
    $settings['uploads']['block_php'] = true;
    $lines    = $builder->build_uploads( $settings );
    $output   = implode( "\n", $lines );

    $this->assertStringContainsString( '(?:', $output );
}
```

### 7-2. `tests/Unit/SettingsTest.php`

```php
/**
 * デフォルト設定に uploads セクションが含まれること
 */
public function test_defaults_contain_uploads_section() {
    $defaults = HSS_Settings::get_defaults();

    $this->assertArrayHasKey( 'uploads', $defaults );
    $this->assertArrayHasKey( 'block_php', $defaults['uploads'] );
    $this->assertFalse( $defaults['uploads']['block_php'] );
}

/**
 * VALID_TABS に uploads が含まれること
 */
public function test_valid_tabs_contain_uploads() {
    $this->assertContains( 'uploads', HSS_Settings::VALID_TABS );
}

/**
 * uploads タブのサニタイズが正しく動作すること
 */
public function test_sanitize_uploads_tab() {
    $settings = new HSS_Settings();

    // ON の場合
    $result = $settings->sanitize_and_merge( array( 'block_php' => '1' ), 'uploads' );
    $this->assertTrue( $result['uploads']['block_php'] );

    // OFF の場合（チェックなし = キーが存在しない）
    $result = $settings->sanitize_and_merge( array(), 'uploads' );
    $this->assertFalse( $result['uploads']['block_php'] );
}
```

---

## 8. 設計上の注意点

### `wp_upload_dir()` の使い方

- `wp_upload_dir()` は内部で `wp_mkdir_p()` を呼ぶ可能性があるため、WordPress が完全に初期化された後（`init` 以降）に呼ぶべき
- Writer のメソッド内で呼んでいるため問題ないが、**定数やコンストラクタでパスを保持しないこと**
- wp-admin のパスは `ABSPATH . 'wp-admin/.htaccess'` でハードコードしているが、uploads は `wp_upload_dir()` で動的に取得する

**理由:**
- `wp-content/uploads/` がデフォルトだが、`wp-config.php` の `UPLOADS` 定数で変更されている可能性がある
- マルチサイトではサブサイトごとにパスが異なる（`wp-content/uploads/sites/{site_id}/`）
- `wp_upload_dir()` なら WordPress が正しいパスを返す

### 既存の `uploads/.htaccess` との共存

- 一部のセキュリティプラグイン（Wordfence 等）が既に uploads に `.htaccess` を作成していることがある
- `insert_with_markers()` を使用するため、プラグインのマーカーブロック（`# BEGIN/END Htaccess Security Settings`）内だけを操作し、他のルールには一切影響しない
- wp-admin の `write_wp_admin()` と同じ安全設計

### マルチサイト対応

- `wp_upload_dir()` はマルチサイトでサブサイトごとに `wp-content/uploads/sites/{site_id}/` を返す
- サブサイトごとに別の `.htaccess` が生成される
- ネットワーク全体で一括設定する機能は将来の拡張課題として別途検討

### セットアップウィザード設計の維持

- プラグインを無効化しても、`uploads/.htaccess` に書き込まれたルールはそのまま残る（root / wp-admin と同じ方針）
- `deactivate()` は変更しない
- 「すべての設定を削除」ボタンで `uploads/.htaccess` からもブロックを除去する
