# ユニットテスト導入計画

> **作成日**: 2026-03-17
> **参考記事**: [How to add automated unit tests to your WordPress plugin](https://developer.wordpress.org/news/2025/12/how-to-add-automated-unit-tests-to-your-wordpress-plugin/)
> **実行環境**: GitHub Actions（ローカル実行は対象外）

---

## 目次

1. [概要](#1-概要)
2. [技術スタック](#2-技術スタック)
3. [ディレクトリ構成](#3-ディレクトリ構成)
4. [セットアップ手順](#4-セットアップ手順)
5. [テスト対象と優先度](#5-テスト対象と優先度)
6. [GitHub Actions ワークフロー](#6-github-actions-ワークフロー)
7. [テストケース詳細](#7-テストケース詳細)
8. [実装ステップ](#8-実装ステップ)

---

## 1. 概要

プラグインの品質を保証し、リグレッションを防止するために WordPress 統合テスト（PHPUnit + `WP_UnitTestCase`）を導入する。

テストは **GitHub Actions でのみ実行** する設計とし、ローカル環境に MySQL や SVN を要求しない。

---

## 2. 技術スタック

| ツール | バージョン | 用途 |
|---|---|---|
| PHPUnit | 9.x / 10.x（PHP バージョンに依存） | テストフレームワーク |
| [wp-phpunit/wp-phpunit](https://github.com/wp-phpunit/wp-phpunit) | ^6.8 | WordPress テスト環境（`WP_UnitTestCase` 等） |
| [yoast/phpunit-polyfills](https://github.com/Yoast/PHPUnit-Polyfills) | ^3.0 | PHP 7.4〜8.x / PHPUnit 9〜10 の互換性吸収 |
| GitHub Actions | — | CI 実行環境 |

---

## 3. ディレクトリ構成

```
htaccess-security-settings/
├── bin/
│   └── install-wp-tests.sh           # WP テスト環境インストールスクリプト
├── tests/
│   ├── bootstrap.php                  # テストブートストラップ
│   └── Unit/
│       ├── HtaccessBuilderTest.php    # ディレクティブ生成テスト（P1）
│       ├── SettingsTest.php           # サニタイズ・バリデーションテスト（P2）
│       └── HtaccessWriterTest.php     # ファイル I/O テスト（P3）
├── phpunit.xml.dist                   # PHPUnit 設定
└── .github/
    └── workflows/
        └── phpunit.yml                # GitHub Actions ワークフロー
```

---

## 4. セットアップ手順

### 4-1. Composer 依存関係の追加

`composer.json` の `require-dev` に以下を追加する:

```json
{
  "require-dev": {
    "yoast/phpunit-polyfills": "^3.0",
    "wp-phpunit/wp-phpunit": "^6.8"
  }
}
```

### 4-2. composer scripts の追加

```json
{
  "scripts": {
    "test": "phpunit",
    "test-install": "bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest"
  }
}
```

> `test-install` は GitHub Actions で使用する。ローカルでの実行は想定しない。

### 4-3. bin/install-wp-tests.sh

`wp scaffold plugin-tests` で生成されるスクリプトをベースに配置する。WordPress テスト用 DB とコアファイルを `/tmp/` にインストールする。

### 4-4. phpunit.xml.dist

```xml
<?xml version="1.0"?>
<phpunit
  bootstrap="tests/bootstrap.php"
  backupGlobals="false"
  colors="true"
  convertErrorsToExceptions="true"
  convertNoticesToExceptions="true"
  convertWarningsToExceptions="true"
>
  <testsuites>
    <testsuite name="unit">
      <directory suffix="Test.php">./tests/Unit/</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

### 4-5. tests/bootstrap.php

```php
<?php
define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );

// WP_CORE_DIR が未定義の場合はデフォルトパスを設定
if ( ! defined( 'WP_CORE_DIR' ) ) {
    $_wp_core_dir = getenv( 'WP_CORE_DIR' );
    if ( ! $_wp_core_dir ) {
        $_wp_core_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress';
    }
    define( 'WP_CORE_DIR', $_wp_core_dir );
}

// WordPress テストスイートを読み込み
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

// テスト実行前にプラグインを読み込む
tests_add_filter( 'muplugins_loaded', function () {
    require TESTS_PLUGIN_DIR . '/htaccess-security-settings.php';
} );

require $_tests_dir . '/includes/bootstrap.php';
```

### 4-6. .gitignore への追加

```
.phpunit.result.cache
```

---

## 5. テスト対象と優先度

### P1（最優先）: `HSS_Htaccess_Builder` — ディレクティブ生成

プラグインの核心。出力が壊れると **本番サイトの .htaccess が破損** する。

| # | テストケース | 検証内容 |
|---|---|---|
| 1 | Options セクション | `Options -MultiViews -Indexes`、`ErrorDocument` の正しい出力 |
| 2 | ファイル保護 | xmlrpc.php / wp-config.php / .htaccess の `FilesMatch` ブロック |
| 3 | Basic 認証 | wp-login.php への `AuthType Basic` ブロック |
| 4 | 危険な拡張子ブロック | `.inc` / `.log` / `.sh` / `.sql` の `FilesMatch` |
| 5 | IP ブロック | 単一 IP → `Require not ip` に変換 |
| 6 | IP ブロック（CIDR） | `192.168.1.0/24` → `Require not ip` に変換 |
| 7 | リライト: スラッシュ正規化 | `//` → `/` の 301 リダイレクト |
| 8 | リライト: ボットブロック | User-Agent リストから `RewriteCond` 生成 |
| 9 | リライト: バックドアブロック | ファイル名リストから `RewriteCond` 生成 |
| 10 | リライト: wp-nesting 防止 | `wp-content/wp-content` 等のネスト防止 |
| 11 | リライト: wp-includes 防止 | wp-includes 直接アクセスブロック |
| 12 | リライト: HTTPS リダイレクト | `X-Forwarded-Proto` 対応の `RewriteRule` |
| 13 | リライト: 不正クエリブロック | `?w=xxx` のブロック |
| 14 | ヘッダー: HSTS | `max-age` / `includeSubDomains` / `preload` の組み合わせ |
| 15 | ヘッダー: CSP（Enforce） | `Content-Security-Policy` + `upgrade-insecure-requests` |
| 16 | ヘッダー: CSP（Report-Only） | `Content-Security-Policy-Report-Only`、`upgrade-insecure-requests` が **除外** されること |
| 17 | ヘッダー: X-Content-Type-Options | `nosniff` |
| 18 | ヘッダー: X-Frame-Options | `SAMEORIGIN` / `DENY` |
| 19 | ヘッダー: Referrer-Policy | 8 種類の値テスト |
| 20 | ヘッダー: Permissions-Policy | 各ディレクティブが正しく出力 |
| 21 | キャッシュ: Gzip | `mod_deflate` ブロック |
| 22 | キャッシュ: Expires | ブラウザキャッシュヘッダー |
| 23 | キャッシュ: Cache-Control | `immutable` 設定 |
| 24 | キャッシュ: ETag | 無効化 |
| 25 | キャッシュ: Keep-Alive | 有効化 |
| 26 | wp-admin: Basic 認証 | `AuthType Basic` + `admin-ajax.php` 除外 |
| 27 | wp-admin: upgrade.php | サーバー IP 制限 |
| 28 | デフォルト設定 | デフォルト設定で空またはMIMEのみの出力 |
| 29 | 全機能 ON | 全設定有効時にディレクティブが正しく結合 |
| 30 | マーカー | `# BEGIN/END HSS` マーカーの存在確認 |

### P2（高優先）: `HSS_Settings` — バリデーション & サニタイズ

不正入力がディレクティブに混入するとセキュリティリスクになる。

| # | テストケース | 検証内容 |
|---|---|---|
| 1 | IP バリデーション: 正常 IPv4 | `192.168.1.1` → true |
| 2 | IP バリデーション: 正常 CIDR | `10.0.0.0/8` → true |
| 3 | IP バリデーション: 不正値 | `abc.def.ghi` → false |
| 4 | IP バリデーション: 範囲外 CIDR | `192.168.1.0/33` → false |
| 5 | CSP 値サニタイズ | `'self' https:` → そのまま |
| 6 | CSP 値サニタイズ: 不正文字 | `<script>alert(1)</script>` → 除去 |
| 7 | 行リストサニタイズ | 空行・前後空白の除去、重複排除 |
| 8 | Options タブサニタイズ | チェックボックス値のバリデーション |
| 9 | Headers タブサニタイズ | HSTS max-age の数値バリデーション |
| 10 | デフォルト設定構造 | `get_defaults()` の全キー存在確認 |
| 11 | ネスト配列マージ | `recursive_parse_args()` の動作確認 |
| 12 | タブバリデーション | 無効なタブ名の拒否 |

### P3（中優先）: `HSS_Htaccess_Writer` — ファイル I/O

| # | テストケース | 検証内容 |
|---|---|---|
| 1 | WordPress ブロック前挿入 | `# BEGIN WordPress` の前にプラグインブロック配置 |
| 2 | 冪等性 | 2 回書き込みでブロック重複なし |
| 3 | バックアップ | `backup()` でオプションに保存 |
| 4 | 復元 | `restore()` で正しく復元 |
| 5 | ブロック除去 | プラグインブロックのみ安全に削除 |
| 6 | 書き込み権限チェック | `check_writable()` の判定 |

### P4（低優先）: `HSS_Admin_Page` — 統合テスト

| # | テストケース | 検証内容 |
|---|---|---|
| 1 | Nonce 検証 | 不正 nonce で処理拒否 |
| 2 | 権限チェック | `manage_options` 権限なしで拒否 |
| 3 | 保存フロー | 設定保存 → Builder → Writer の一連の流れ |
| 4 | リセット | デフォルト設定に正しく戻る |
| 5 | 全削除 | DB 設定削除 + .htaccess ブロック除去 |

---

## 6. GitHub Actions ワークフロー

`.github/workflows/phpunit.yml`:

```yaml
name: PHPUnit Tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        include:
          - php-version: '7.4'
            wp-version: '6.0'
          - php-version: '7.4'
            wp-version: 'latest'
          - php-version: '8.0'
            wp-version: '6.0'
          - php-version: '8.0'
            wp-version: 'latest'
          - php-version: '8.1'
            wp-version: '6.1'
          - php-version: '8.1'
            wp-version: 'latest'
          - php-version: '8.2'
            wp-version: '6.2'
          - php-version: '8.2'
            wp-version: 'latest'
          - php-version: '8.3'
            wp-version: '6.4'
          - php-version: '8.3'
            wp-version: 'latest'

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v6

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: mbstring, xml, zip, intl, pdo, mysqli
          coverage: none

      - name: Install SVN
        run: sudo apt-get update && sudo apt-get install -y subversion

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v5
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install WordPress test environment
        run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 ${{ matrix.wp-version }}

      - name: Run PHPUnit
        run: composer test
```

### テストマトリクス

各 PHP バージョンに対して、WordPress が公式に対応している最低バージョン + latest でテストする。

| PHP | 最低 WP | latest | 根拠 |
|---|---|---|---|
| 7.4 | 6.0 | ✅ | プラグイン最低要件 |
| 8.0 | 6.0 | ✅ | WP 5.9+ 対応（プラグイン最低 6.0） |
| 8.1 | 6.1 | ✅ | WP 6.1 で対応開始 |
| 8.2 | 6.2 | ✅ | WP 6.2 で対応開始 |
| 8.3 | 6.4 | ✅ | WP 6.4 で対応開始 |

---

## 7. テストケース詳細

### 7-1. HSS_Htaccess_Builder のテスト例

```php
class HtaccessBuilderTest extends WP_UnitTestCase {

    private $builder;

    public function set_up(): void {
        parent::set_up();
        $this->builder = new HSS_Htaccess_Builder();
    }

    /**
     * デフォルト設定ではMIME定義のみ出力される
     */
    public function test_build_root_with_defaults() {
        $settings = HSS_Settings::get_defaults();
        $output   = $this->builder->build_root( $settings );

        $this->assertIsString( $output );
    }

    /**
     * CSP Report-Only では upgrade-insecure-requests が除外される
     */
    public function test_csp_report_only_excludes_upgrade_insecure_requests() {
        $settings = HSS_Settings::get_defaults();
        $settings['headers']['csp_enabled']                   = true;
        $settings['headers']['csp_report_only']               = true;
        $settings['headers']['csp_upgrade_insecure_requests'] = true;

        $output = $this->builder->build_root( $settings );

        $this->assertStringContainsString( 'Content-Security-Policy-Report-Only', $output );
        $this->assertStringNotContainsString( 'upgrade-insecure-requests', $output );
    }

    /**
     * IP ブロックが Require not ip に変換される
     */
    public function test_ip_block_generates_require_not_ip() {
        $settings = HSS_Settings::get_defaults();
        $settings['ip_block']['enabled'] = true;
        $settings['ip_block']['ips']     = array( '192.168.1.100', '10.0.0.0/8' );

        $output = $this->builder->build_root( $settings );

        $this->assertStringContainsString( 'Require not ip 192.168.1.100', $output );
        $this->assertStringContainsString( 'Require not ip 10.0.0.0/8', $output );
    }
}
```

### 7-2. HSS_Settings のテスト例

```php
class SettingsTest extends WP_UnitTestCase {

    /**
     * 正常な IPv4 アドレスが検証を通る
     */
    public function test_validate_ip_accepts_valid_ipv4() {
        $result = HSS_Settings::validate_ip( '192.168.1.1' );
        $this->assertTrue( $result );
    }

    /**
     * 不正な IP アドレスが拒否される
     */
    public function test_validate_ip_rejects_invalid_ip() {
        $result = HSS_Settings::validate_ip( 'not-an-ip' );
        $this->assertFalse( $result );
    }

    /**
     * CSP 値から危険な文字が除去される
     */
    public function test_sanitize_csp_value_strips_dangerous_chars() {
        $result = HSS_Settings::sanitize_csp_value( "'self' https://example.com <script>" );
        $this->assertStringNotContainsString( '<script>', $result );
    }

    /**
     * デフォルト設定に必要な全キーが存在する
     */
    public function test_defaults_have_all_required_keys() {
        $defaults = HSS_Settings::get_defaults();

        $this->assertArrayHasKey( 'options', $defaults );
        $this->assertArrayHasKey( 'ip_block', $defaults );
        $this->assertArrayHasKey( 'rewrite', $defaults );
        $this->assertArrayHasKey( 'headers', $defaults );
        $this->assertArrayHasKey( 'cache', $defaults );
        $this->assertArrayHasKey( 'wp_admin', $defaults );
    }
}
```

---

## 8. 実装ステップ

| # | ステップ | 対象ファイル | 内容 |
|---|---|---|---|
| 1 | Composer 依存関係追加 | `composer.json` | `wp-phpunit` と `phpunit-polyfills` を `require-dev` に追加、`scripts` に `test` / `test-install` 追加 |
| 2 | テスト環境スクリプト作成 | `bin/install-wp-tests.sh` | `wp scaffold plugin-tests` ベースのインストールスクリプト |
| 3 | PHPUnit 設定作成 | `phpunit.xml.dist` | テストスイート定義 |
| 4 | ブートストラップ作成 | `tests/bootstrap.php` | WP テスト環境読み込み + プラグイン有効化 |
| 5 | Builder テスト作成 | `tests/Unit/HtaccessBuilderTest.php` | **P1: 最重要** — ディレクティブ生成の全セクション |
| 6 | Settings テスト作成 | `tests/Unit/SettingsTest.php` | **P2** — バリデーション & サニタイズ |
| 7 | Writer テスト作成 | `tests/Unit/HtaccessWriterTest.php` | **P3** — ファイル I/O・バックアップ・復元 |
| 8 | GitHub Actions 作成 | `.github/workflows/phpunit.yml` | CI ワークフロー |
| 9 | .gitignore 更新 | `.gitignore` | `.phpunit.result.cache` を追加 |
| 10 | 動作確認 | — | PR を作成して GitHub Actions で全マトリクス PASS を確認 |

---

## 注意事項

- **ローカル実行は対象外**: テストは GitHub Actions でのみ実行する。ローカル環境への MySQL / SVN インストールは不要
- **CSP Report-Only**: `upgrade-insecure-requests` が Report-Only モードで除外されるテストは必須（過去のバグ注意点）
- **Apache 依存**: `.htaccess` の実際の動作テスト（Apache パース）はスコープ外。生成されるディレクティブ文字列の正確性のみ検証する
- **テストの命名規則**: `test_` プレフィックス + スネークケースで内容がわかる名前にする
- **`set_up()` / `tear_down()`**: WordPress テストでは `setUp()` ではなく `set_up()` を使用する（`WP_UnitTestCase` の snake_case メソッド）
