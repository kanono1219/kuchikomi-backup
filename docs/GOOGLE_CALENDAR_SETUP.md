# Google Calendar API 設定ガイド

このアプリケーションでGoogle Calendar統合機能を使用するには、Google Cloud Consoleで認証情報を設定する必要があります。

## セットアップ手順

### 1. Google Cloud Console でプロジェクトを作成

1. [Google Cloud Console](https://console.cloud.google.com/) にアクセス
2. 新しいプロジェクトを作成、または既存のプロジェクトを選択

### 2. Google Calendar API を有効化

1. 左側のメニューから「APIとサービス」→「ライブラリ」を選択
2. 検索ボックスに「Google Calendar API」と入力
3. 「Google Calendar API」を選択し、「有効にする」をクリック

### 3. OAuth 2.0 認証情報を作成

1. 左側のメニューから「APIとサービス」→「認証情報」を選択
2. 「認証情報を作成」→「OAuth クライアント ID」を選択
3. アプリケーションの種類で「ウェブアプリケーション」を選択
4. 名前を入力（例：「Okinawa Events」）
5. 「承認済みのリダイレクト URI」に以下を追加：
   ```
   http://localhost:8000/google-calendar/callback
   https://your-domain.com/google-calendar/callback
   ```
6. 「作成」をクリック
7. 表示されたクライアントIDとクライアントシークレットをコピー

### 4. 環境変数を設定

`.env`ファイルに以下の設定を追加：

```env
GOOGLE_CALENDAR_CLIENT_ID=あなたのクライアントID
GOOGLE_CALENDAR_CLIENT_SECRET=あなたのクライアントシークレット
GOOGLE_CALENDAR_REDIRECT_URI=${APP_URL}/google-calendar/callback
```

### 5. データベースマイグレーション

Google Calendar用のテーブルカラムを追加：

```bash
php artisan migrate
```

## 使用方法

### ユーザー側の操作

1. トップページの「Google カレンダーと連携」セクションで「Google カレンダーに接続」ボタンをクリック
2. Googleアカウントでログイン
3. カレンダーへのアクセス許可を承認
4. 以降、お気に入りに追加したイベントが自動的にGoogle カレンダーに同期されます

### 機能

- **自動同期**: お気に入り登録したイベントがGoogle カレンダーに自動追加
- **自動削除**: お気に入りから削除するとGoogle カレンダーからも削除
- **トークン自動更新**: アクセストークンの有効期限が切れた場合は自動的に更新

## トラブルシューティング

### エラー: "Google Calendar APIが設定されていません"

- `.env`ファイルに`GOOGLE_CALENDAR_CLIENT_ID`と`GOOGLE_CALENDAR_CLIENT_SECRET`が正しく設定されているか確認
- 設定後、アプリケーションキャッシュをクリア：`php artisan config:clear`

### エラー: "redirect_uri_mismatch"

- Google Cloud Consoleの「承認済みのリダイレクトURI」に、アプリケーションの正確なURLが登録されているか確認
- `.env`の`APP_URL`が本番環境のURLと一致しているか確認

### トークンの有効期限切れ

- アプリケーションは自動的にトークンを更新しますが、更新に失敗する場合は以下を試してください：
  1. Google Calendar接続を解除
  2. 再度接続を試みる

## セキュリティに関する注意事項

- **認証情報を公開しない**: `.env`ファイルは絶対にGitにコミットしないでください
- **HTTPS使用**: 本番環境では必ずHTTPSを使用してください
- **権限の最小化**: 必要な権限（カレンダーの読み書き）のみを要求しています

## 開発者向け情報

### 関連ファイル

- **コントローラー**: `app/Http/Controllers/GoogleCalendarController.php`
- **ルーティング**: `routes/web.php` (google-calendar グループ)
- **マイグレーション**: `database/migrations/2025_11_17_101835_add_google_calendar_to_users_table.php`
- **ビュー**: `resources/views/events/index.blade.php`

### APIエンドポイント

- `GET /google-calendar/auth` - Google認証ページへリダイレクト
- `GET /google-calendar/callback` - OAuth認証コールバック
- `POST /google-calendar/add-event/{event}` - イベントをカレンダーに追加
- `POST /google-calendar/remove-event/{event}` - イベントをカレンダーから削除
- `GET /google-calendar/status` - 接続状態を確認
- `POST /google-calendar/disconnect` - 接続を解除

## 参考リンク

- [Google Calendar API ドキュメント](https://developers.google.com/calendar)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)
- [OAuth 2.0 設定ガイド](https://developers.google.com/identity/protocols/oauth2)
