# Htaccess Security Settings

## プロジェクト概要

`.docs/htaccess-security-guide.md` で解説している .htaccess のセキュリティ・パフォーマンス設定を、WordPress 管理画面から GUI で選択・設定し、`.htaccess` ファイルに自動反映するプラグイン。

**コンセプト**: 初心者でも .htaccess を直接編集せずにセキュリティ設定を適用・変更できるようにする。

## 動作環境

| 項目 | 要件 |
|---|---|
| WordPress | 6.0 以上 |
| PHP | 7.4 以上 |
| Web サーバー | Apache（`.htaccess` が利用可能であること） |

## 機能一覧

### 1. Options & ファイル保護

- MultiViews 無効化
- ディレクトリ一覧（Indexes）無効化
- ErrorDocument のデフォルト化（403 / 404）
- xmlrpc.php ブロック
- wp-config.php / .htaccess の保護
- 危険な拡張子（.inc, .log, .sh, .sql）のアクセス制限
- wp-login.php への Basic 認証

### 2. IP ブロック

- 指定 IP からのアクセスを 403 で拒否
- CIDR 表記対応（例: `192.168.1.0/24`）

### 3. リライトルール

- スラッシュ重複（`//`）の 301 リダイレクト正規化
- 悪意のあるボット（User-Agent）ブロック（カスタムリスト対応）
- バックドア / マルウェアファイル名ブロック（カスタムリスト対応）
- wp-* ディレクトリの多重ネスト防止
- wp-includes ディレクトリの直接ブラウズ防止
- HTTP → HTTPS 301 リダイレクト（X-Forwarded-Proto 対応）
- 不正クエリ文字列（`?w=xxx`）ブロック

### 4. セキュリティヘッダー

- **HSTS**: max-age / includeSubDomains / preload
- **CSP**: 本番適用 / Report-Only テストモード切り替え
  - default-src / script-src / style-src / img-src / font-src / connect-src / frame-src / frame-ancestors
  - upgrade-insecure-requests（Report-Only モードでは自動除外）
- **X-Content-Type-Options**: nosniff
- **X-Frame-Options**: SAMEORIGIN / DENY
- **Referrer-Policy**: 8 種類から選択
- **Permissions-Policy**: camera / microphone / payment / usb / gyroscope / magnetometer / geolocation

### 5. キャッシュ & パフォーマンス

- Gzip 圧縮（mod_deflate）
- ブラウザキャッシュ（Expires ヘッダー）
- Cache-Control: immutable（静的ファイル）
- ETag 無効化
- Keep-Alive 有効化
- MIME Type 定義（常時出力）

### 6. wp-admin 保護

- wp-admin への Basic 認証
- admin-ajax.php の認証除外（フロントエンド Ajax 対応）
- upgrade.php のサーバー IP 制限（自動更新対応）

## その他の機能

- **.htaccess プレビュー**: 保存前に生成されるディレクティブを Ajax で確認
- **バックアップ & 復元**: 設定保存時に自動バックアップ、ワンクリックで復元
- **安全な書き込み**: プラグインのルールは `# BEGIN WordPress` ブロックの前に配置し、RewriteRule の優先順位を確保
- **無効化時の自動クリーンアップ**: プラグインを無効化すると `.htaccess` からプラグインブロックを自動除去（設定は保持）
- **アンインストール時の完全削除**: プラグインを削除するとオプション・バックアップ・`.htaccess` ブロックをすべて削除

## インストール

1. プラグインフォルダを `wp-content/plugins/` にアップロード
2. WordPress 管理画面 → プラグイン → 有効化
3. 設定 → .htaccess セキュリティ から設定

## ファイル構成

```
htaccess-security-settings/
├── htaccess-security-settings.php    # メインプラグインファイル
├── uninstall.php                     # アンインストール処理
├── includes/
│   ├── class-hss-plugin.php          # メインクラス（Singleton）
│   ├── class-hss-settings.php        # 設定管理
│   ├── class-hss-admin-page.php      # 管理画面
│   ├── class-hss-htaccess-builder.php # ディレクティブ生成
│   ├── class-hss-htaccess-writer.php # ファイル I/O・バックアップ
│   └── class-hss-activator.php       # 有効化・無効化処理
├── admin/
│   ├── css/admin-style.css           # 管理画面スタイル
│   ├── js/admin-script.js            # 管理画面スクリプト
│   └── views/                        # テンプレート
│       ├── page-main.php
│       ├── tab-options.php
│       ├── tab-ip-block.php
│       ├── tab-rewrite.php
│       ├── tab-headers.php
│       ├── tab-cache.php
│       ├── tab-wp-admin.php
│       └── partial-preview.php
├── composer.json
├── .docs/　                          # ドキュメント（htaccess-security-guide.md など）
├── .github/                             # GitHub 関連ファイル（copilot-instructions.md など）
├── .gitignore
├── .local-docs/                      # ローカルドキュメント（wp-env-config.md など）
├── phpcs.xml
└── README.md
```

## 開発

```bash
# 依存関係のインストール
composer install

# コーディング規約チェック（WordPress Coding Standards 3.0）
composer phpcs

# 自動修正
composer phpcbf
```

## ライセンス

GPL-2.0-or-later
