# .htaccess セキュリティ & パフォーマンス設定ガイド

WordPress サイトで使用している `.htaccess` の設定内容を初心者向けに解説したドキュメントです。

> **作成日**: 2026-03-07
> **更新日**: 2026-03-15（wp-includes ディレクトリブラウズブロックを追加）
> **対象**: XServer 上の WordPress サイト

---

## 目次

1. [.htaccess とは？](#1-htaccess-とは)
2. [Options の設定](#2-options-の設定)
3. [ファイルアクセス制限](#3-ファイルアクセス制限)
4. [IPアドレスによるブロック](#4-ipアドレスによるブロック)
5. [リライトルール（Rewrite Rules）](#5-リライトルールrewrite-rules)
6. [セキュリティレスポンスヘッダー](#6-セキュリティレスポンスヘッダー)
7. [キャッシュ & パフォーマンス設定](#7-キャッシュ--パフォーマンス設定)
8. [wp-admin ディレクトリの .htaccess](#8-wp-admin-ディレクトリの-htaccess)
9. [WordPress のリライトルール](#9-wordpress-のリライトルール)
10. [Apacheエラーログの読み方](#10-apacheエラーログの読み方)
11. [よくある疑問と注意点](#11-よくある疑問と注意点)

---

## 1. .htaccess とは？

Apache ウェブサーバーの設定ファイル。サイトのルートに置くと、そのディレクトリ以下の挙動を制御できる。

- **リクエストの書き換え**（URL のリダイレクトなど）
- **アクセス制限**（特定ファイルやIPのブロック）
- **レスポンスヘッダーの追加**（セキュリティ設定）
- **キャッシュの制御**（ブラウザキャッシュの有効期限）

WordPress では「パーマリンク設定」を保存すると `# BEGIN WordPress` ～ `# END WordPress` ブロックが自動生成される。

---

## 2. Options の設定

```apache
Options -MultiViews -Indexes
```

Apache の機能を個別に ON/OFF する設定。`-` は「無効化」の意味。

### -MultiViews（コンテンツネゴシエーション無効化）

**MultiViews とは？**

ファイル名の拡張子なしでアクセスされた時に、似た名前のファイルを Apache が**勝手に探して返す**機能。

```
/about にアクセス
  → Apache が about.html や about.php を自動で探す
  → 見つけたら内部リダイレクトで返す
```

**なぜ無効化するのか？**

WordPress では全 URL を `mod_rewrite`（リライトルール）で制御しているため、MultiViews が横から割り込むと衝突する。

```
1. ボットが /wp-admin/install にアクセス
2. MultiViews が install.php を発見 → 内部リダイレクト
3. mod_rewrite がルール適用 → 書き換え
4. MultiViews がまた反応 → 内部リダイレクト
5. 繰り返し...10回でエラー（AH00124）💥
```

`-MultiViews` にすれば「勝手に探しに行かない → ループしない」。

### -Indexes（ディレクトリ一覧の無効化）

**Indexes とは？**

ディレクトリに `index.html` や `index.php` がない時に、ファイル一覧を自動生成して表示する機能。

```
Index of /wp-content/uploads/2026/03/

Name              Size
─────────────────────
secret-doc.pdf    2.1M
client-photo.jpg  850K
backup.sql        15M   ← 危険！
```

攻撃者にとっては「宝の地図」になる。`-Indexes` にすることで 403 Forbidden を返し、一覧を一切見せない。

> **補足**: XServer を含む最近のレンタルサーバーでは `-Indexes` がデフォルトで有効になっている。それでもあえて `.htaccess` に明記しているのは、サーバー移行時に移行先のデフォルトが異なるリスクを排除するため。書いてもパフォーマンスへの影響はゼロなので、「明示は正義」の精神で記述しておく。

---

## 3. ファイルアクセス制限

特定のファイルへの外部アクセスを完全にブロックする設定。

### xmlrpc.php

```apache
<Files xmlrpc.php>
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</Files>
```

WordPress の古いリモート投稿 API。現在は REST API があるため基本不要。ボットが**ブルートフォース攻撃**（パスワード総当たり）や **DDoS 増幅**（pingback.ping 悪用）に使う定番の標的なので、ブロックする。

### wp-config.php

```apache
<Files wp-config.php>
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</Files>
```

データベースのパスワードなど重要な情報が記載されたファイル。外部から絶対に読めてはいけない。

### .htaccess 自身

```apache
<Files .htaccess>
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</Files>
```

セキュリティ設定そのものが外部に見られないように保護。

### wp-login.php（Basic 認証）

```apache
<Files wp-login.php>
    AuthUserFile "/home/.../htpasswd/wp-admin/.htpasswd"
    AuthName "Member Site"
    AuthType BASIC
    require valid-user
</Files>
```

ログインページの前に Basic 認証（ID/パスワードのポップアップ）を追加する。WordPress のログインフォームに到達する前にブロックできるため、ブルートフォース攻撃に非常に有効。

### 危険なファイル拡張子のブロック

```apache
<FilesMatch "\.(inc|log|sh|sql)$">
    Require all denied
</FilesMatch>
```

`.inc`（PHPインクルードファイル）、`.log`（ログ）、`.sh`（シェルスクリプト）、`.sql`（データベースダンプ）への外部アクセスを禁止。

---

## 4. IPアドレスによるブロック

```apache
<RequireAll>
    Require all granted
    Require not ip 49.205.208.246
    Require not ip 20.205.42.22
    ...
</RequireAll>
```

エラーログで**繰り返し不正アクセスしてくるIP**を特定し、サーバーの入り口で 403 拒否する。

### IPの特定方法

1. Apache のエラーログに `[client xxx.xxx.xxx.xxx:ポート]` として記録される
2. IP を whois で調べて、正規ユーザーではないことを確認
3. `Require not ip` で追加

### よく見かける攻撃元の種類

| IP 所有者 | 特徴 |
|-----------|------|
| AWS / Azure / GCP | クラウド上のボット。レンタルサーバーから自動スキャン |
| GitHub / Microsoft | セキュリティスキャナー |
| 不明な海外 IP | referer 偽装（`www.google.com` など）付きのスキャナー |
| Webシェルスキャナー | UAのスペルミス（`Mozlila`, `Bulid`, `Moblie`）で見分け可能 |

---

## 5. リライトルール（Rewrite Rules）

Apache の `mod_rewrite` を使った URL 書き換え。上から順に評価され、`[L]` フラグで処理終了。

### ErrorDocument の設定

```apache
ErrorDocument 403 default
ErrorDocument 404 default
```

エラーが発生した時に Apache のデフォルトエラーページを返す設定。これがないと WordPress のリライトエンジンが動いてしまい、エラー処理が重くなる。

### スラッシュ重複の正規化

```apache
RewriteCond %{THE_REQUEST} \s[^\s]*//
RewriteRule ^ %{REQUEST_URI} [R=301,L,NE]
```

`https://your-domain.com//page/` のようなスラッシュ重複 URL を `https://your-domain.com/page/` に 301 リダイレクト。SEO 的にも重複 URL を一本化できる。

**なぜ `%{THE_REQUEST}` を使うのか？**

`%{REQUEST_URI}` は Apache が内部で正規化済み（`//` が `/` に変換済み）のため、ダブルスラッシュを検出できない。`%{THE_REQUEST}` はクライアントが送信した**生のリクエスト行**（例: `GET //wp-admin/ HTTP/1.1`）なので、正規化前の `//` を検出できる。

**攻撃者がダブルスラッシュを使う理由:**

1. **セキュリティルールの回避** — `.htaccess` や WAF のパスマッチングは正規化後の URL で判定することが多い。正規化前の `//wp-admin/` は `/wp-admin/` 向けのルールをすり抜ける可能性がある
2. **パストラバーサルの試行** — `//` や `/../` を組み合わせて、意図しないディレクトリにアクセスを試みる
3. **アプリケーションの挙動差の悪用** — Web サーバー（Apache）とアプリケーション（WordPress/PHP）で URL の解釈が異なる場合、予期しないファイルが実行される可能性がある（パーサー差異攻撃）
4. **情報収集** — サーバーがダブルスラッシュにどう反応するか（エラーメッセージ、リダイレクト先など）を見て、サーバーの種類やバージョンを推測する

この設定により、ダブルスラッシュは正規化された URL に 301 リダイレクトされ、上記すべての攻撃パターンを無効化できる。

### 悪意のあるボット・スクリプトのブロック

```apache
RewriteCond %{HTTP_USER_AGENT} (wget|curl|libwww-perl|python|nikto|sqlmap) [NC]
RewriteRule .* - [F,L]
```

User-Agent ヘッダーに特定の文字列が含まれるリクエストを 403 で拒否。`nikto` や `sqlmap` はハッキングツールの名前。`timpibot` は「分散型検索エンジン」を名乗るが、レート制限なしで数秒間に100件以上のリクエストを送りつける帯域浮費型クローラー。

> **注意**: 正規の API クライアントが `curl` や `python` を使う場合もあるので、必要に応じて調整する。

### バックドア / マルウェア探索のブロック

```apache
RewriteCond %{REQUEST_URI} (alfa\.php|adminfuns\.php|...) [NC]
RewriteRule .* - [F,L]
```

既知のバックドアファイル名へのアクセスを 403 で拒否。これらのファイルが実際に存在しなくても、探索自体をブロックする。`txets.php` は Web シェルの一種で、偽装 UA（`Mozlila`/`Bulid`/`Moblie` などのスペルミス）で探索されることが確認されたため追加。

### wp-* ディレクトリの多重ネスト防止

```apache
RewriteCond %{REQUEST_URI} wp-(content|admin|includes)/.*wp-(content|admin|includes)/ [NC]
RewriteRule .* - [F,L]
```

ボットが `/wp-admin/wp-admin/wp-admin/.../install.php` のような**多重にネストした wp-* パス**をリクエストしてきた場合に 403 で即拒否する。

**なぜこれが必要なのか？**

WordPress マルチサイトのリライトルールには「サブサイトプレフィックスを1段剥がす」処理がある（→ [セクション9](#9-wordpress-のリライトルール) 参照）。

```apache
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
```

このルールの `[L]` フラグは「このパスの処理を終了」するだけで、**書き換え後の URL で `.htaccess` が最初から再評価**される。ネストが深いと1パスで1段しか剥がせず、10段以上のネストで Apache の内部リダイレクト上限（10回）に到達してしまう。

```
パス1: wp-admin/wp-admin/.../install.php → 1段剥がす
パス2: wp-admin/wp-admin/.../install.php → もう1段剥がす
   ：
パス10: まだ残ってる → AH00124 💥
```

正常なリクエスト（`/wp-content/themes/...` 等）では `wp-*` が1回しか出現しないため、このルールに引っかかることはない。

### wp-includes ディレクトリの直接ブラウズブロック

```apache
RewriteCond %{REQUEST_URI} ^/wp-includes/ [NC]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule .* - [F,L]
```

ボットが `/wp-includes/` や `/wp-includes/PHPMailer/`、`/wp-includes/html-api/` などのディレクトリ URL に直接アクセスしてくることがある。`-Indexes` で一覧表示は防げるが、Apache は `AH01276`（autoindex エラー）をログに記録するため、ログにノイズが溜まる。

このルールを入れると：

1. `%{REQUEST_URI}` が `/wp-includes/` で始まるかチェック
2. `%{REQUEST_FILENAME}` が実在するディレクトリかチェック（`-d` 条件）
3. 両方満たせば即 403 Forbidden を返す

`-d` 条件があるため、`/wp-includes/js/jquery/jquery.min.js` のような**ファイルへの正常なアクセスは影響を受けない**。ディレクトリを直接ブラウズしようとするスキャンだけをブロックし、autoindex エラーの代わりに 403 を返すことでログがすっきりする。

### HTTPS リダイレクト

```apache
RewriteCond %{HTTPS} !=on [NC]
RewriteCond %{HTTP:X-Forwarded-Proto} !https [NC]
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

HTTP でアクセスされた場合に HTTPS へ 301 リダイレクト。XServer はリバースプロキシ（Nginx → Apache）構成のため、`%{HTTPS}` だけでなく `X-Forwarded-Proto` ヘッダーも確認する。

### 不正なクエリ文字列のブロック

```apache
RewriteCond %{QUERY_STRING} (^|&)w=[^&]+(&|$) [NC]
RewriteRule ^ - [R=410,L]
```

`?w=xxx` のような不正なクエリパラメータ付きリクエストに 410 Gone を返す。

---

## 6. セキュリティレスポンスヘッダー

ブラウザにセキュリティ上の指示を伝えるための HTTP レスポンスヘッダー。

### `Header set` vs `Header always set`

| 構文 | 対象 |
|------|------|
| `Header set` | 成功レスポンス（200系）のみ |
| `Header always set` | エラーレスポンス（403, 404, 500等）にも付与 |

セキュリティヘッダーはエラーページにも必要なので `always` を使う。

### HSTS（HTTP Strict Transport Security）

```apache
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" "expr=%{HTTPS} == 'on' || %{HTTP:X-Forwarded-Proto} == 'https'"
```

**役割**: ブラウザに「このサイトは今後必ず HTTPS で接続して」と宣言する。

| パラメータ | 意味 |
|-----------|------|
| `max-age=63072000` | 2年間この指示を覚えておく |
| `includeSubDomains` | サブドメインも HTTPS 強制 |
| `preload` | Chrome の HSTS Preload List に登録申請できるフラグ |

**末尾の `expr=` について**

「HTTPS 接続の時だけこのヘッダーを送る」という条件。HTTP 接続時に HSTS を送っても意味がない＆ HSTS Preload の審査で警告が出るため、この条件で回避する。

**動作の流れ:**

```
1. ユーザーが初めて https://your-domain.com にアクセス
2. ブラウザ「HSTS ヘッダー受信！2年間覚えとこ」
3. 次回、ユーザーが http://your-domain.com と入力
4. ブラウザ「HTTPS じゃないとダメって記憶してる。サーバーに聞く前に自分で HTTPS に変換」
   → サーバーへの HTTP リクエスト自体が発生しない！
```

`.htaccess` の HTTPS リダイレクトはサーバーに到達してからリダイレクトするが、HSTS はブラウザ側で最初から HTTPS 化するため、中間者攻撃（MITM）のリスクをゼロにできる。

### CSP（Content Security Policy）

```apache
Header always set Content-Security-Policy "upgrade-insecure-requests;"
```

**役割**: ページ内の `http://` リソースを自動的に `https://` に書き換えて読み込む。

```html
<!-- ページ内に古い HTTP リンクが残ってても -->
<img src="http://your-domain.com/image.jpg">

<!-- ブラウザが自動的にこう変換して読み込む -->
<img src="https://your-domain.com/image.jpg">
```

WordPress の投稿本文やプラグインが `http://` の URL を出力することがあり、そのままだと「Mixed Content」警告が出て鍵マークが消える。この設定で自動修正される。

#### CSP ソースディレクティブ（リソース制限）

`upgrade-insecure-requests` だけではリソースの読み込み元を制限しない。セキュリティを強化するには、ソースディレクティブを追加して「どこからリソースを読み込んで良いか」をブラウザに指示する。

```apache
# 例: ソースディレクティブを含むフルCSP
Header always set Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data:; font-src 'self' https: data:; connect-src 'self' https:; frame-src 'self' https:; frame-ancestors 'self'; upgrade-insecure-requests;"
```

| ディレクティブ | 制御対象 | WordPress での推奨値 |
|--------------|---------|--------------------|
| `default-src` | 他で指定されていない全リソースのフォールバック | `'self' https:` |
| `script-src` | JavaScript | `'self' 'unsafe-inline' 'unsafe-eval' https:` |
| `style-src` | CSS | `'self' 'unsafe-inline' https:` |
| `img-src` | 画像（OG image 含む） | `'self' https: data:` |
| `font-src` | フォント | `'self' https: data:` |
| `connect-src` | Ajax / WebSocket / fetch | `'self' https:` |
| `frame-src` | iframe 埋め込み先 | `'self' https:` |
| `frame-ancestors` | iframe 埋め込み元（X-Frame-Options の CSP 版） | `'self'` |

WordPress 固有の要件:
- **`'unsafe-inline'`**: WP コア・プラグインがインラインスクリプト/スタイルを多用するため必須
- **`'unsafe-eval'`**: Gutenberg ブロックエディターが `eval()` を使うため必須
- **`data:`**: プラグインが Base64 埋め込み画像・フォントを使うことがあるため `img-src` と `font-src` に必要
- **`https:`**: 外部 CDN・Google Fonts・Gravatar 等の外部リソースを許可

#### ⚠️ `img-src` を明示する時の注意（OG image が壊れる罠）

CSP には**フォールバックルール**がある:

```
img-src が未指定 → default-src の値が使われる
img-src を明示  → default-src のフォールバックは効かない
```

つまり `img-src` を書いた瞬間、その値**だけ**が画像の制限になる。`'self'` を入れ忘れると自サイトの画像すら読み込めなくなる。

```apache
# ❌ NG: 'self' が抜けている → 自サイト画像・OG image がブロックされる
Header always set Content-Security-Policy "default-src 'self'; img-src data:;"

# ✅ OK: 'self' を含めている
Header always set Content-Security-Policy "default-src 'self'; img-src 'self' https: data:;"
```

| 設定 | OG image | 外部画像 | Base64画像 |
|------|---------|---------|----------|
| CSP なし | ✅ | ✅ | ✅ |
| `default-src 'self'`（img-src 未指定） | ✅ | ❌ | ❌ |
| `img-src data:`（'self' 漏れ） | ❌ | ❌ | ✅ |
| `img-src 'self' https: data:` | ✅ | ✅ | ✅ |

#### Report-Only で安全にテストする

CSP を**いきなり本番適用するとサイトが壊れるリスク**がある。まず `Content-Security-Policy-Report-Only` で「ブロックはしないけど違反をブラウザの DevTools コンソールに表示する」モードで確認する。

```apache
# Step 1: Report-Only で様子見（ブロックしない）
# ※ upgrade-insecure-requests はアクション指示なので Report-Only では無視される → 含めない
Header always set Content-Security-Policy-Report-Only "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data:; font-src 'self' https: data:; connect-src 'self' https:; frame-src 'self' https:; frame-ancestors 'self';"

# Step 2: DevTools のコンソールで違反が出ないことを確認

# Step 3: 問題なければ Content-Security-Policy に昇格（upgrade-insecure-requests はここで追加）
Header always set Content-Security-Policy "default-src 'self' https:; ...; upgrade-insecure-requests;"
```

**DevTools での確認方法:**

1. Chrome/Edge でサイトを開く
2. F12 → Console タブ
3. `[Report Only]` で始まる黄色い警告が出たら、そのリソースが本番 CSP ではブロックされる
4. 警告が出なくなるまでディレクティブを調整
5. 問題なければ `Content-Security-Policy` に切り替え、`Report-Only` を削除

### X-Content-Type-Options

```apache
Header always set X-Content-Type-Options "nosniff"
```

**役割**: ブラウザの MIME スニッフィング（ファイルの中身を見て種類を推測する機能）を禁止する。

**攻撃シナリオ:**

```
1. 攻撃者が uploads/ に悪意のあるファイルをアップロード
   → ファイル名: evil.jpg（でも中身は JavaScript）
2. ブラウザ（nosniff なし）「中身見たら JS っぽい…実行しちゃお！」
   → XSS 攻撃成立 💀

3. nosniff あり → 「Content-Type が image/jpeg だから JS としては実行しないよ」
   → 攻撃失敗 ✅
```

### X-Frame-Options

```apache
Header always set X-Frame-Options "SAMEORIGIN"
```

**役割**: このサイトを他サイトの `<iframe>` に埋め込むことを制限する。

| 値 | 意味 |
|----|------|
| `DENY` | 一切埋め込み禁止 |
| `SAMEORIGIN` | 同じドメインからのみ埋め込み OK |

**クリックジャッキング防止:**

```
攻撃者のサイト:
┌────────────────────────────────┐
│  「おめでとう！ここをクリック！」 │
│  ┌────────────────────────┐    │
│  │ your-domain.com の     │    │  ← 透明な iframe で重ねてある
│  │ 「投稿を削除」ボタン     │    │
│  └────────────────────────┘    │
└────────────────────────────────┘

ユーザーは「クリック」を押したつもりが、
実は「投稿を削除」を押してしまう
```

`SAMEORIGIN` にすると、自分のドメイン以外からの `<iframe>` 埋め込みをブラウザがブロックする。

### Referrer-Policy

```apache
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

**役割**: リンクをクリックした時に、リンク先にどこまでの URL 情報を送るかを制御する。

**`strict-origin-when-cross-origin` の動作:**

| 遷移 | 送信される情報 |
|------|--------------|
| 同じサイト内（`/page-a` → `/page-b`） | フル URL |
| 外部サイトへ（HTTPS → HTTPS） | ドメインだけ（`https://your-domain.com`） |
| HTTPS → HTTP（ダウングレード） | 何も送らない |

自サイト内のページ遷移追跡は維持しつつ、外部に URL の詳細を漏らさないバランス型。

### Permissions-Policy

```apache
Header always set Permissions-Policy "camera=(), microphone=(), payment=(), usb=(), gyroscope=(), magnetometer=()"
```

**役割**: ブラウザのデバイス API へのアクセスを制限する。

| API | `()` の意味 | 制限しなかった場合のリスク |
|-----|-----------|------------------------|
| `camera` | カメラ使用禁止 | 悪意のあるスクリプトがカメラ起動を試みる |
| `microphone` | マイク使用禁止 | 盗聴の試み |
| `payment` | Payment Request API 禁止 | 偽の決済画面を表示 |
| `usb` | WebUSB 禁止 | USB デバイスへの不正アクセス |
| `gyroscope` | ジャイロセンサー禁止 | デバイスの傾きから入力推測 |
| `magnetometer` | 磁力センサー禁止 | 同上 |

`()` = 全オリジンで無効、`(self)` = 自サイトだけ許可、`*` = 全許可。

> **Note**: `geolocation` は Google マップ等を使う場合に必要なので外してある。

### 全体の防御マップ

```
┌── HSTS ──────────── HTTP→HTTPS 強制（ブラウザレベル）
│
├── CSP ───────────── Mixed Content 自動修正
│
├── X-Content-Type ── MIME 偽装による XSS 防止
│
├── X-Frame-Options ─ クリックジャッキング防止
│
├── Referrer-Policy ─ URL 情報の漏洩制御
│
└── Permissions ───── デバイス API の不正利用防止
```

---

## 7. キャッシュ & パフォーマンス設定

### Gzip 圧縮

```apache
<IfModule mod_deflate.c>
    SetOutputFilter DEFLATE
    AddOutputFilterByType DEFLATE text/html text/plain text/css ...
</IfModule>
```

サーバーからブラウザへの転送時にデータを圧縮し、転送量を削減する。テキストベースのファイル（HTML, CSS, JS, JSON 等）に特に効果的。

### ブラウザキャッシュ（Expires）

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 month"
    ...
</IfModule>
```

ファイルの種類ごとにブラウザキャッシュの有効期限を設定。一度ダウンロードしたファイルを再ダウンロードしなくなるため、ページ表示が速くなる。

| ファイル種類 | 有効期限 | 理由 |
|------------|---------|------|
| CSS / JS | 1年 | ハッシュ付きファイル名が前提。変更時はファイル名が変わる |
| 画像 | 1ヶ月 | 更新頻度は中程度 |
| フォント | 1年 | ほぼ変更されない |
| HTML | 0秒 | 動的コンテンツなので常に最新を取得 |
| JSON / XML | 0秒 | API レスポンスはキャッシュしない |

### Cache-Control ヘッダー

```apache
<FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|...)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
</FilesMatch>
```

| 値 | 意味 |
|----|------|
| `public` | CDN やプロキシにもキャッシュ OK |
| `max-age=31536000` | 1年間有効 |
| `immutable` | 有効期限内は再検証リクエストも送らない（最速） |

### ETag の無効化

```apache
Header unset ETag
FileETag None
```

ETag はファイル変更の検出に使われるが、Expires / Cache-Control で制御している場合は冗長。サーバー間で ETag が不一致になる問題も避けられる。

---

## 8. wp-admin ディレクトリの .htaccess

ルートの `.htaccess` とは別に、`wp-admin/` ディレクトリにも専用の `.htaccess` を設置して、管理画面全体を Basic 認証で保護している。

### 設置場所

```
public_html/
├── .htaccess          ← ルートの設定（これまで解説した内容）
└── wp-admin/
    └── .htaccess      ← 管理画面専用の設定（このセクション）
```

### Basic 認証（管理画面全体）

```apache
AuthUserFile "/home/ユーザー名/ドメイン/htpasswd/wp-admin/.htpasswd"
AuthName "Member Site"
AuthType BASIC
require valid-user
```

`wp-admin/` 以下の全ファイルに Basic 認証をかける。ルートの `.htaccess` では `wp-login.php` だけを保護しているが、こちらは管理画面のすべてのページが対象になる。

| 設定項目 | 意味 |
|---------|------|
| `AuthUserFile` | パスワードファイル（`.htpasswd`）の絶対パス |
| `AuthName` | 認証ダイアログに表示される名前 |
| `AuthType BASIC` | Basic 認証方式を使用 |
| `require valid-user` | `.htpasswd` に登録されたユーザーのみ通過 |

### admin-ajax.php の除外

```apache
<Files admin-ajax.php>
    <IfModule mod_authz_core.c>
        <RequireAny>
            Require all granted
        </RequireAny>
    </IfModule>
</Files>
```

**なぜ除外するのか？**

`admin-ajax.php` は WordPress の Ajax リクエストのエンドポイント。フロントエンド（ログインしていない訪問者）からも使われる。

```
例: お問い合わせフォーム送信、コメント投稿、検索サジェストなど

訪問者 → admin-ajax.php → WordPress が処理 → レスポンス返却
```

Basic 認証がかかったままだと、フォーム送信時に認証ダイアログが出てしまう。`Require all granted` で認証なしでアクセスできるようにしている。

> **セキュリティ上の懸念は？** `admin-ajax.php` 自体は WordPress のアクション名（`action` パラメータ）で処理を振り分けており、各アクションには `wp_ajax_nopriv_` / `wp_ajax_` フックで個別に権限チェックが入る。ファイル自体を開放しても、内部の権限管理が機能していれば問題ない。

### upgrade.php のサーバー内部 IP 除外

```apache
<Files upgrade.php>
    <IfModule mod_authz_core.c>
        <RequireAny>
            # ↓ サーバーの内部IPを指定（※サーバーパネルで確認）
            Require ip xxx.xxx.xxx.xxx
            Require valid-user
        </RequireAny>
    </IfModule>
</Files>
```

`upgrade.php` は WordPress のアップデート後にデータベース更新を実行するファイル。

**なぜ IP 除外するのか？**

WordPress の自動更新が走った後、サーバー内部から `upgrade.php` が呼ばれることがある。Basic 認証がかかっていると自動更新が失敗するため、サーバーの内部 IP からのアクセスは Basic 認証なしで通す。XServer の場合、サーバーパネルの「サーバー情報」で内部 IP を確認できる。

| 条件 | 動作 |
|------|------|
| サーバー内部 IP に該当 | 認証なしでアクセス OK |
| その他の IP | Basic 認証が必要 |

`<RequireAny>` は「**どちらか一方**を満たせば OK」の意味。外部からは Basic 認証必須、サーバー内部からは自動でスルー。

### 全体の認証フロー図

```
外部ユーザー
    │
    ├─→ /wp-admin/index.php ─→ Basic 認証 ─→ WP ログイン画面
    │
    ├─→ /wp-admin/admin-ajax.php ─→ 認証スキップ ─→ Ajax 処理
    │
    └─→ /wp-admin/upgrade.php
            ├─ サーバー内部IP ─→ 認証スキップ ─→ DB 更新
            └─ その他 ─→ Basic 認証 ─→ DB 更新
```

---

## 9. WordPress のリライトルール

### シングルサイト

```apache
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
```

1. `index.php` 自体へのリクエスト → そのまま通す
2. 実在するファイルでもディレクトリでもない URL → `index.php` に転送（WordPress が処理）

### マルチサイト（サブディレクトリ型）

```apache
RewriteRule ^index\.php$ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . /index.php [L]
```

シングルサイトとの違い:

- **`wp-admin` のスラッシュ追加**: `サブサイト/wp-admin` → `サブサイト/wp-admin/` にリダイレクト
- **実在ファイル/ディレクトリの優先**: `-f` / `-d` チェックで実ファイルはそのまま返す
- **サブサイトプレフィックスの除去**: `/subsite/wp-content/...` → `/wp-content/...` に変換。ルートの `wp-content` 等に正しくアクセスできるようにする

> **重要**: このブロックに `RewriteCond %{REQUEST_URI} !^/wp-(content|admin|includes)` を追加する修正案が提案されることがあるが、これは不要かつ副作用がある（存在しない wp-content パスへのアクセスが WordPress のフルスタックを通るようになり、パフォーマンスが劣化する）。

### `[L]` フラグと内部リダイレクトループの関係

Apache の `[L]` フラグは**「現在のリライトパスを終了する」**という意味であって、**「.htaccess の処理を完全に終了する」ではない**。書き換え後の URL で `.htaccess` が最初から再評価される。

**サブサイトプレフィックス除去ルール**では、この再評価のたびに先頭の `wp-*` プレフィックスが1つだけ剥がされる：

```
リクエスト: /wp-admin/wp-admin/wp-admin/install.php

  パス1: ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*)
         $1 = "wp-admin/", $2 = "wp-admin/wp-admin/install.php"
         → wp-admin/wp-admin/install.php に書き換え → [L] で終了 → 再評価

  パス2: $1 = "wp-admin/", $2 = "wp-admin/install.php"
         → wp-admin/install.php に書き換え → [L] で終了 → 再評価

  パス3: -f チェック → wp-admin/install.php が実在 → [L] で終了（ループ脱出）
```

ネスト3段なら3パスで収束する。しかしボットが11段以上ネストしたURLを送ると、Apacheの上限（10回）に到達して `AH00124` エラーになる。

**対策**: カスタムリライトルールで `wp-*` の2段以上ネストを即 403 にする（→ [セクション5](#5-リライトルールrewrite-rules) 参照）。

```apache
RewriteCond %{REQUEST_URI} wp-(content|admin|includes)/.*wp-(content|admin|includes)/ [NC]
RewriteRule .* - [F,L]
```

これにより WordPress のリライトルールに到達する前にブロックされ、ループ自体が発生しない。

---

## 10. Apacheエラーログの読み方

### ログの形式

```
[日時] [モジュール:レベル] [pid:tid] [client IP:ポート] メッセージ
```

### よく見るエラーコード

#### AH00124 — 内部リダイレクトループ

```
AH00124: Request exceeded the limit of 10 internal redirects
due to probable configuration error.
```

Apache 内部で URL の書き換えが 10 回繰り返されて上限に達した。

**主な原因:**

| 原因 | 対策 |
|------|------|
| `MultiViews` と `mod_rewrite` の衝突 | `Options -MultiViews` |
| **wp-* ディレクトリの多重ネストリクエスト**（ボット） | `wp-*` ネスト検出ルールで即 403 |

マルチサイトのリライトルールが `[L]` フラグで1パスごとにプレフィックスを1段剥がす構造のため、ボットが `wp-admin/wp-admin/wp-admin/...` のようなネスト URL を送ると10回の上限に到達する。詳細は [セクション9](#9-wordpress-のリライトルール) を参照。

> このエラーは常に 2 行セットで出力される（エラー発生 + スタックトレース案内）。

#### AH01276 — ディレクトリ Index なし

```
AH01276: Cannot serve directory /path/to/dir/:
No matching DirectoryIndex found,
and server-generated directory index forbidden by Options directive
```

ボットがディレクトリ URL に直接アクセスしたが `index.html` / `index.php` がなく、ディレクトリ一覧表示も禁止されているため拒否。**これは正常動作**（ブロックできている証拠）。

#### AH01630 — サーバー設定でアクセス拒否

```
AH01630: client denied by server configuration: /path/to/file
```

`.htaccess` や Apache の設定で明示的にアクセス拒否した場合のログ。xmlrpc.php のブロック等で出る。**これも正常動作**。

### ボットの見分け方

| 特徴 | 判定 |
|------|------|
| referer が `www.google.com`（`https://` なし） | フェイク referer のボット |
| 数秒間に大量リクエスト | 自動スキャナー |
| IP が AWS / Azure / GCP | クラウドホスティングされたボット |
| `uploads/admin.php` へのアクセス | バックドア（Web シェル）の探索 |
| UA のスペルミス（`Mozlila`, `Bulid`） | 偽装 UA の Web シェルスキャナー |
| 数秒間に100件以上のリクエスト | レート制限なしの攻撃的クローラー（例: Timpibot） |

---

## 11. よくある疑問と注意点

### Q: `# BEGIN WordPress` ～ `# END WordPress` は編集していいの？

WordPress がパーマリンク設定保存時に**自動で上書き**する。手動編集すると上書きされて消える可能性があるので、基本的には触らない。カスタムルールはこのブロックの**外**に書く。

### Q: `Header set` と `Header always set` どっちを使う？

- **セキュリティヘッダー** → `always set`（エラーページにも必要）
- **Cache-Control** → `set`（成功レスポンスのみで OK）

### Q: `<IfModule>` は何のため？

該当モジュールが Apache に読み込まれていない場合にエラーでサーバーが停止するのを防ぐ。例えば `mod_rewrite` がない環境でも `.htaccess` の読み込みでエラーにならない。

### Q: IP ブロックはどのくらい追加していいの？

数十件程度なら問題ない。ただし数百件を超えると `.htaccess` の解析自体がパフォーマンスに影響する可能性があるので、その場合はファイアウォール（Wordfence 等）や XServer のアクセス制限機能を使う方が適切。

### Q: EWWWIO / Wordfence / Nginx Cache のブロックは触る？

触らない。これらはプラグインやサーバーが自動生成・更新するブロック。手動で変更しても上書きされる。設定変更はプラグインの管理画面やサーバーパネルから行う。

---

## 参考: 防御の全体像

```
            インターネット
                │
    ┌───────────▼───────────┐
    │    IP ブロック         │  ← 既知の攻撃 IP を 403 で即拒否
    ├───────────────────────┤
    │   User-Agent ブロック  │  ← ハッキングツールの UA を 403
    ├───────────────────────┤
    │   ファイルアクセス制限  │  ← xmlrpc.php, wp-config.php 等
    ├───────────────────────┤
    │   HTTPS リダイレクト   │  ← HTTP → HTTPS に 301
    ├───────────────────────┤
    │    Options 制限       │  ← MultiViews / Indexes 無効化
    ├───────────────────────┤
    │    WordPress          │  ← WP が動的にページ生成
    ├───────────────────────┤
    │   レスポンスヘッダー    │  ← HSTS, CSP, X-Frame 等
    │                       │     ブラウザに安全な動作を指示
    └───────────────────────┘
                │
            ブラウザ
```

---

## サンプルコード

### A. ルートの .htaccess（public_html/.htaccess）

シングルサイト向けの完全なサンプル。
マルチサイト（サブディレクトリ型）の場合は `# BEGIN WordPress` ブロックを標準のものに置き換える。

> **使い方**: `AuthUserFile` のパスと `Require not ip` の IP アドレスは自分の環境に合わせて変更すること。

```apache
# ===========================
# Security Settings
# ===========================
# MultiViewsによる内部リダイレクトループ防止 & ディレクトリリスティング無効化
Options -MultiViews -Indexes

# エラー発生時にWordPressのリライトエンジンを動かさない設定
ErrorDocument 403 default
ErrorDocument 404 default

# wp-login.php を保護（※パスは自分の環境に合わせる）
<Files wp-login.php>
AuthUserFile "/home/ユーザー名/ドメイン/htpasswd/wp-admin/.htpasswd"
AuthName "Member Site"
AuthType BASIC
require valid-user
</Files>

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

# 既知の攻撃IPをブロック（※IPはエラーログを見て追加する）
<RequireAll>
    Require all granted
    # Require not ip xxx.xxx.xxx.xxx
</RequireAll>

# ===========================
# Rewrite Rules
# ===========================
<IfModule mod_rewrite.c>
	RewriteEngine On

	# スラッシュの重複（//）を正規化
	RewriteCond %{THE_REQUEST} \s[^\s]*//
	RewriteRule ^ %{REQUEST_URI} [R=301,L,NE]

	# 悪意のあるボット・スクリプトをブロック
	RewriteCond %{HTTP_USER_AGENT} (wget|curl|libwww-perl|python|nikto|sqlmap|timpibot) [NC]
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

	# HTTPSリダイレクト（XServer対応: X-Forwarded-Proto を考慮）
	RewriteCond %{HTTPS} !=on [NC]
	RewriteCond %{HTTP:X-Forwarded-Proto} !https [NC]
	RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

	# 不正なクエリ文字列をブロック
	RewriteCond %{QUERY_STRING} (^|&)w=[^&]+(&|$) [NC]
	RewriteRule ^ - [R=410,L]

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

# ===========================
# WordPress Rewrite（※自動生成 — 手動編集しない）
# ===========================

# BEGIN WordPress
# ※ シングルサイトの場合:
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>

# ※ マルチサイト（サブディレクトリ型）の場合:
# <IfModule mod_rewrite.c>
#     RewriteEngine On
#     RewriteBase /
#     RewriteRule ^index\.php$ - [L]
#     RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]
#     RewriteCond %{REQUEST_FILENAME} -f [OR]
#     RewriteCond %{REQUEST_FILENAME} -d
#     RewriteRule ^ - [L]
#     RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
#     RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
#     RewriteRule . /index.php [L]
# </IfModule>
# END WordPress

# ===========================
# MIME Type
# ===========================
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

	# Permissions-Policy（※不要なAPIを制限。geolocationが必要なら追加しない）
	Header always set Permissions-Policy "camera=(), microphone=(), payment=(), usb=(), gyroscope=(), magnetometer=()"
</IfModule>
```

### B. wp-admin/.htaccess

管理画面全体を Basic 認証で保護し、Ajax と自動更新は除外する。

> **使い方**: `AuthUserFile` のパスと `Require ip` のサーバー内部 IP は自分の環境に合わせて変更すること。

```apache
AuthUserFile "/home/ユーザー名/ドメイン/htpasswd/wp-admin/.htpasswd"
AuthName "Member Site"
AuthType BASIC
require valid-user

# admin-ajax.php へのアクセスを許可（フロントエンドの Ajax 用）
<Files admin-ajax.php>
	<IfModule mod_authz_core.c>
		<RequireAny>
			Require all granted
		</RequireAny>
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order allow,deny
		Allow from all
		Satisfy any
	</IfModule>
</Files>

# upgrade.php はサーバー内部IPのみBasic認証をスキップ（自動更新用）
<Files upgrade.php>
	<IfModule mod_authz_core.c>
		<RequireAny>
			# ↓ サーバーの内部IPを指定（※サーバーパネルで確認）
			Require ip xxx.xxx.xxx.xxx
			Require valid-user
		</RequireAny>
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		# ↓ サーバーの内部IPを指定（※サーバーパネルで確認）
		Allow from xxx.xxx.xxx.xxx
		Satisfy any
	</IfModule>
</Files>
```
