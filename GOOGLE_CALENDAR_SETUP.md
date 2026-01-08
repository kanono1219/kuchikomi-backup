# Google Calendar 連携設定ガイド

このアプリケーションでは、ユーザーのGoogleカレンダーと連携して、カレンダー上にイベントを表示できます。

## 📋 機能概要

- ✅ Googleカレンダーのイベントをwebアプリのカレンダービューに表示
- ✅ OAuth2認証による安全な接続
- ✅ アプリイベント(ティール色)、お気に入りイベント(赤色)、Googleカレンダーイベント(オレンジ色)の色分け表示
- ✅ 自動トークンリフレッシュ機能

## 🔧 設定手順

### 1. Google Cloud Consoleでプロジェクトを作成

1. [Google Cloud Console](https://console.cloud.google.com/) にアクセス
2. 新しいプロジェクトを作成 (または既存のプロジェクトを選択)
3. プロジェクト名を設定 (例: "Okinawa Event App")

### 2. Google Calendar APIを有効化

1. 左メニューから「APIとサービス」→「ライブラリ」を選択
2. "Google Calendar API" を検索
3. 「有効にする」をクリック

### 3. OAuth 2.0 認証情報の作成

1. 「APIとサービス」→「認証情報」を選択
2. 「認証情報を作成」→「OAuth クライアント ID」をクリック
3. 同意画面の設定が必要な場合は、以下を設定:
   - ユーザータイプ: 外部 (テスト用) または 内部 (組織内のみ)
   - アプリ名: "Okinawa Event App"
   - サポートメール: あなたのメールアドレス
   - スコープ: 追加不要 (コードで指定)
4. アプリケーションの種類: **ウェブアプリケーション**
5. 名前: "Okinawa Event App Web Client"
6. 承認済みのリダイレクトURI:
   ```
   http://localhost/google-calendar/callback
   http://127.0.0.1:8000/google-calendar/callback
   https://your-production-domain.com/google-calendar/callback
   ```
7. 「作成」をクリック
8. **クライアントIDとクライアントシークレット**をダウンロード (JSON形式)

### 4. 設定ファイルの作成

1. ダウンロードしたJSON認証情報ファイルを開く
2. `config/google-calendar-config.json.example` をコピーして `config/google-calendar-config.json` を作成:
   ```bash
   cp config/google-calendar-config.json.example config/google-calendar-config.json
   ```
3. `config/google-calendar-config.json` を編集し、以下の値を設定:
   - `client_id`: Google Consoleからコピー
   - `client_secret`: Google Consoleからコピー
   - `project_id`: プロジェクトID
   - `redirect_uris`: アプリケーションのURLに合わせて設定

**重要**: `google-calendar-config.json` は `.gitignore` に含まれているため、Gitにコミットされません。本番環境にデプロイする際は、手動でアップロードしてください。

### 5. データベースマイグレーションの実行

Google Calendar連携に必要なデータベーステーブルを作成:

```bash
php artisan migrate
```

以下のカラムが `users` テーブルに追加されます:
- `google_calendar_connected` - 接続状態 (boolean)
- `google_calendar_token` - OAuth2トークン (longText, 暗号化推奨)

### 6. 動作確認

1. アプリケーションにログイン
2. イベント一覧ページまたはカレンダーページで「Google Calendar に接続」ボタンをクリック
3. Googleアカウントでログインし、カレンダーへのアクセスを許可
4. カレンダーページで、Googleカレンダーのイベントがオレンジ色で表示されることを確認

## 🎨 イベントの色分け

カレンダー上のイベントは以下の色で表示されます:

- **ティール (#4ECDC4)**: 通常のアプリイベント
- **赤 (#FF6B6B)**: お気に入り登録したアプリイベント
- **オレンジ (#FF8C00)**: Googleカレンダーから同期したイベント

## 🔒 セキュリティ注意事項

1. **トークンの暗号化**:
   - 本番環境では、`google_calendar_token` カラムを暗号化することを強く推奨します
   - Laravel の暗号化キャストを使用: `protected $casts = ['google_calendar_token' => 'encrypted'];`

2. **設定ファイルの保護**:
   - `google-calendar-config.json` は絶対にGitにコミットしないでください
   - ファイルのパーミッションを適切に設定: `chmod 600 config/google-calendar-config.json`

3. **OAuth2スコープ**:
   現在のアプリケーションは以下のスコープを使用:
   - `https://www.googleapis.com/auth/calendar.readonly` - カレンダー読み取り
   - `https://www.googleapis.com/auth/calendar` - カレンダー書き込み

## 🛠️ トラブルシューティング

### エラー: "Google Calendar config not configured"

- `config/google-calendar-config.json` が存在することを確認
- ファイルのパスとパーミッションを確認

### エラー: "Invalid token format"

- ユーザーのトークンが破損している可能性があります
- 「Google Calendar 接続を解除」してから再接続してください

### トークンの期限切れ

- アプリケーションは自動的にトークンをリフレッシュします
- リフレッシュトークンがない場合は、再度OAuth認証が必要です

### イベントが表示されない

- ブラウザのコンソール (F12キー) でエラーを確認
- カレンダーページの「デバッグコンソール」でAPI呼び出しの状態を確認
- ユーザーがGoogleカレンダーに接続しているか確認
- Google Calendar APIの利用制限を確認 (1日あたり100万リクエスト)

## 📚 参考リンク

- [Google Calendar API ドキュメント](https://developers.google.com/calendar/api/guides/overview)
- [OAuth 2.0 認証](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)

## 💡 使い方

### ユーザー向け

1. **接続**: イベント一覧ページまたはカレンダーページの「Google Calendar に接続」ボタンをクリック
2. **表示**: カレンダーページで自動的にGoogleカレンダーのイベントがオレンジ色で表示されます
3. **解除**: プロフィールページまたは設定から「Google Calendar 接続を解除」で接続を解除できます

### 開発者向け

- **ログ確認**: `storage/logs/laravel.log` でGoogle Calendar API関連のログを確認
- **デバッグ**: カレンダーページには開発者用のデバッグコンソールが表示されます
- **エンドポイント**:
  - `GET /google-calendar/auth` - OAuth認証開始
  - `GET /google-calendar/callback` - OAuthコールバック
  - `GET /calendar/google-events` - Googleカレンダーイベント取得
  - `GET /google-calendar/status` - 接続状態確認
  - `POST /google-calendar/disconnect` - 接続解除
