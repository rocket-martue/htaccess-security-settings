=== Htaccess Security Settings ===
Contributors: rocketmartue
Tags: htaccess, security, headers, csp, performance
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress の管理画面から .htaccess のセキュリティ設定を GUI で管理できるプラグインです。

== Description ==

チェックボックスを切り替えるだけで、セキュリティヘッダー・リライトルール・キャッシュ設定などを `.htaccess` に反映できます。

**このプラグインはセットアップウィザードです。**
設定を保存すると `.htaccess` にルールが書き込まれ、プラグインを無効化・削除してもルールはそのまま残ります。ルールを削除したい場合は、先に管理画面の「すべての設定を削除」ボタンを使ってください。

= 機能一覧 =

**Options & ファイル保護**

* MultiViews 無効化
* ディレクトリ一覧（Indexes）無効化
* ErrorDocument のデフォルト化（403 / 404）
* xmlrpc.php ブロック
* wp-config.php / .htaccess の保護
* 危険な拡張子（.inc, .log, .sh, .sql）のアクセス制限
* wp-login.php への Basic 認証

**IP ブロック**

* 指定 IP からのアクセスを 403 で拒否
* CIDR 表記対応（例: 192.168.1.0/24）

**リライトルール**

* スラッシュ重複（//）の 301 リダイレクト正規化
* 悪意のあるボット（User-Agent）ブロック（カスタムリスト対応）
* バックドア / マルウェアファイル名ブロック（カスタムリスト対応）
* wp-* ディレクトリの多重ネスト防止
* wp-includes ディレクトリの直接ブラウズ防止
* HTTP → HTTPS 301 リダイレクト（X-Forwarded-Proto 対応）
* 不正クエリ文字列（?w=xxx）ブロック

**セキュリティヘッダー**

* HSTS: max-age / includeSubDomains / preload
* CSP: 本番適用 / Report-Only テストモード切り替え（default-src / script-src / style-src / img-src / font-src / connect-src / frame-src / frame-ancestors）
* X-Content-Type-Options: nosniff
* X-Frame-Options: SAMEORIGIN / DENY
* Referrer-Policy: 8 種類から選択
* Permissions-Policy: camera / microphone / payment / usb / gyroscope / magnetometer / geolocation

**キャッシュ & パフォーマンス**

* Gzip 圧縮（mod_deflate）
* ブラウザキャッシュ（Expires ヘッダー）
* Cache-Control: immutable（静的ファイル）
* ETag 無効化
* Keep-Alive 有効化
* MIME Type 定義

**wp-admin 保護**

* wp-admin への Basic 認証
* admin-ajax.php の認証除外（フロントエンド Ajax 対応）
* upgrade.php のサーバー IP 制限（自動更新対応）

= その他の機能 =

* **.htaccess プレビュー**: 保存前に生成されるディレクティブを Ajax で確認
* **バックアップ & 復元**: 設定保存時に自動バックアップ、ワンクリックで復元
* **安全な書き込み**: プラグインのルールは `# BEGIN WordPress` ブロックの前に配置し、RewriteRule の優先順位を確保

== Installation ==

1. プラグインフォルダを `wp-content/plugins/` にアップロード
2. WordPress 管理画面 → プラグイン → 有効化
3. 設定 → .htaccess セキュリティ から設定

== Frequently Asked Questions ==

= プラグインを無効化すると .htaccess の設定も消えますか？ =

いいえ。このプラグインはセットアップウィザードとして設計されているため、無効化・削除しても .htaccess に書き込まれたルールはそのまま残ります。設定完了後はプラグインを無効化したままにしておくことを推奨します。

= .htaccess のルールを削除するにはどうすればいいですか？ =

プラグインを有効化した状態で、管理画面の「すべての設定を削除」ボタンを使ってください。このボタンは DB の設定を削除し、.htaccess からプラグインブロックを除去します。

= Apache 以外の Web サーバーでも使えますか？ =

いいえ。このプラグインは Apache の `.htaccess` に特化しています。Nginx や LiteSpeed 等では動作しません。

== Changelog ==

= 1.2.0 =
* PHPUnit ユニットテストを導入（HSS_Htaccess_Builder / HSS_Settings / HSS_Htaccess_Writer）
* GitHub Actions に PHPUnit テストワークフローを追加（PHP 7.4〜8.3 × WP 6.0〜latest マトリクス）

= 1.1.1 =
* WordPress Playground 対応: blueprint.json を追加し README にライブプレビューリンクを設置
* readme.txt に v1.1.0 / v1.1.1 の Changelog・Upgrade Notice を追記

= 1.1.0 =
* 管理画面の UI を改善しモバイル対応レイアウトに変更
* プレビュー機能を削除（ボタン・モーダル・Ajax 関連コードを全除去）
* IP ブロックタブのトグル表示バグを修正
* 管理画面の JSON レスポンスで 'admin' キーを 'wp_admin' に変更
* デフォルト設定で生成される .htaccess 例を README に追加

= 1.0.1 =
* セットアップウィザードとしての設計に変更: 無効化・アンインストール時に .htaccess のルールを残すよう変更
* 管理画面にセットアップウィザードである旨の案内 notice を追加
* ボタンラベルを「すべての設定を削除」に統一

= 1.0.0 =
* 初回リリース

== Upgrade Notice ==

= 1.2.0 =
PHPUnit ユニットテストと GitHub Actions CI を導入しました。プラグインの動作に変更はありません。

= 1.1.1 =
WordPress Playground でプラグインを即座に試せるようになりました。Changelog も追記されています。

= 1.1.0 =
管理画面の UI をモバイル対応に改善し、プレビュー機能を削除しました。IP ブロックタブのトグル表示バグも修正されています。

= 1.0.1 =
プラグインを無効化・削除しても .htaccess のルールが残るようになりました。ルールを削除したい場合は「すべての設定を削除」ボタンをご利用ください。
