# Htaccess Security Settings

WordPress の管理画面から `.htaccess` のセキュリティ設定を GUI で管理できるプラグインです。  
チェックボックスを切り替えるだけで、セキュリティヘッダー・リライトルール・キャッシュ設定などを `.htaccess` に反映できます。
このプラグインは .htaccess のセットアップウィザードのようなものです。
設定を保存すると .htaccess にルールが書き込まれ、プラグインを無効化・削除してもルールはそのまま残ります。

[<img src="https://playground.wordpress.net/logo-square.svg" width="20" alt="WordPress Playground" /> WordPress Playground で試す](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/rocket-martue/htaccess-security-settings/main/blueprint.json)

> [!NOTE]
> WordPress Playground はブラウザ上で動作する仮想環境（Apache なし）のため、`.htaccess` への実際の書き込みは行われません。管理画面の UI やプレビュー機能の確認用としてご利用ください。

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
- **セットアップウィザード設計**: プラグインを無効化・削除しても `.htaccess` のルールはそのまま残る。設定完了後は無効化したままの運用を推奨
- **すべての設定を削除**: ルールを除去したい場合は管理画面の「すべての設定を削除」ボタンで DB 設定・バックアップ・`.htaccess` ブロックをまとめて削除

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
└── phpcs.xml
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

## デフォルト設定で生成される .htaccess

プラグインを有効化し、初期設定のまま保存した場合に `.htaccess` へ書き込まれるブロックです。  
IP ブロック・HTTPS リダイレクト・wp-admin Basic 認証はデフォルト OFF のため含まれません。

```apache
# BEGIN Htaccess Security Settings
# ===========================
# Security Settings
# ===========================
Options -MultiViews -Indexes

ErrorDocument 403 default
ErrorDocument 404 default

# .htaccess へのアクセス禁止
<Files .htaccess>
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		Deny from all
	</IfModule>
</Files>

# XML-RPCへのアクセスを無効化
<Files xmlrpc.php>
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		Deny from all
	</IfModule>
</Files>

# wp-config.php を保護
<Files wp-config.php>
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		Deny from all
	</IfModule>
</Files>

# 特定のファイルタイプへのアクセスを制限
<FilesMatch "\.(inc|log|sh|sql)$">
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		Deny from all
	</IfModule>
</FilesMatch>

# ===========================
# Rewrite Rules
# ===========================
<IfModule mod_rewrite.c>
	RewriteEngine On

	# スラッシュの重複（//）を正規化
	RewriteCond %{THE_REQUEST} \s[^\s]*//
	RewriteRule ^ %{REQUEST_URI} [R=301,L,NE]

	# 悪意のあるボット・スクリプトをブロック
	RewriteCond %{HTTP_USER_AGENT} (wget|curl|libwww\-perl|python|nikto|sqlmap|timpibot) [NC]
	RewriteRule .* - [F,L]

	# バックドア/マルウェア探索をブロック
	RewriteCond %{REQUEST_URI} (alfa\.php|adminfuns\.php|wp-fclass\.php|wp-themes\.php|ioxi-o\.php|0x\.php|akc\.php|txets\.php) [NC]
	RewriteRule .* - [F,L]

	# wp-*ディレクトリの多重ネストリクエストをブロック（内部リダイレクトループ防止）
	RewriteCond %{REQUEST_URI} wp-(content|admin|includes)/.*wp-(content|admin|includes)/ [NC]
	RewriteRule .* - [F,L]

	# wp-includes/ ディレクトリの直接ブラウズをブロック
	RewriteCond %{REQUEST_URI} ^/wp-includes/ [NC]
	RewriteCond %{REQUEST_FILENAME} -d
	RewriteRule .* - [F,L]

</IfModule>

# ===========================
# Cache & Performance Settings
# ===========================
# Gzip圧縮
<IfModule mod_deflate.c>
	SetOutputFilter DEFLATE
	AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css
	AddOutputFilterByType DEFLATE application/javascript application/x-javascript application/json
	AddOutputFilterByType DEFLATE application/xml application/xhtml+xml application/rss+xml
	AddOutputFilterByType DEFLATE image/svg+xml
	AddOutputFilterByType DEFLATE font/ttf font/otf font/woff font/woff2
</IfModule>

# ブラウザキャッシュ設定
<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresDefault "access plus 1 month"
	ExpiresByType text/css "access plus 1 year"
	ExpiresByType application/javascript "access plus 1 year"
	ExpiresByType application/x-javascript "access plus 1 year"
	ExpiresByType text/javascript "access plus 1 year"
	ExpiresByType image/jpeg "access plus 1 month"
	ExpiresByType image/png "access plus 1 month"
	ExpiresByType image/gif "access plus 1 month"
	ExpiresByType image/webp "access plus 1 month"
	ExpiresByType image/svg+xml "access plus 1 month"
	ExpiresByType image/x-icon "access plus 1 year"
	ExpiresByType image/vnd.microsoft.icon "access plus 1 year"
	ExpiresByType video/mp4 "access plus 1 month"
	ExpiresByType video/webm "access plus 1 month"
	ExpiresByType video/ogg "access plus 1 month"
	ExpiresByType font/woff "access plus 1 year"
	ExpiresByType font/woff2 "access plus 1 year"
	ExpiresByType font/ttf "access plus 1 year"
	ExpiresByType font/otf "access plus 1 year"
	ExpiresByType application/atom+xml "access plus 1 hour"
	ExpiresByType application/rdf+xml "access plus 1 hour"
	ExpiresByType application/rss+xml "access plus 1 hour"
	ExpiresByType application/json "access plus 0 seconds"
	ExpiresByType application/ld+json "access plus 0 seconds"
	ExpiresByType application/xml "access plus 0 seconds"
	ExpiresByType text/xml "access plus 0 seconds"
	ExpiresByType application/manifest+json "access plus 1 week"
	ExpiresByType text/html "access plus 0 seconds"
</IfModule>

# Cache-Control ヘッダー
<IfModule mod_headers.c>
	<FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf)$">
		Header set Cache-Control "public, max-age=31536000, immutable"
	</FilesMatch>
	<FilesMatch "\.(html|htm)$">
		Header set Cache-Control "no-cache, must-revalidate"
	</FilesMatch>
</IfModule>

# MIME Type
<IfModule mime_module>
	AddType image/x-icon .ico
	AddType image/svg+xml .svg
	AddType application/x-font-ttf .ttf
	AddType application/x-font-woff .woff
	AddType application/x-font-opentype .otf
	AddType application/vnd.ms-fontobject .eot
</IfModule>

# ETags を無効化
<IfModule mod_headers.c>
	Header unset ETag
</IfModule>
FileETag None

# Keep-Alive を有効化
<IfModule mod_headers.c>
	Header set Connection keep-alive
</IfModule>

# ===========================
# Security Response Headers
# ===========================
<IfModule mod_headers.c>
	# HSTS（HTTPS接続時のみ送信）
	Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" "expr=%{HTTPS} == 'on' || %{HTTP:X-Forwarded-Proto} == 'https'"

	# CSP
	Header always set Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data:; font-src 'self' https: data:; connect-src 'self' https:; frame-src 'self' https:; frame-ancestors 'self'; upgrade-insecure-requests;"

	# X-Content-Type-Options
	Header always set X-Content-Type-Options "nosniff"

	# X-Frame-Options
	Header always set X-Frame-Options "SAMEORIGIN"

	# Referrer-Policy
	Header always set Referrer-Policy "strict-origin-when-cross-origin"

	# Permissions-Policy
	Header always set Permissions-Policy "camera=(), microphone=(), payment=(), usb=(), gyroscope=(), magnetometer=()"
</IfModule>

# END Htaccess Security Settings
```

## ライセンス

GPL-2.0-or-later
