# Googleカレンダーイベントが表示されない場合のトラブルシューティング

## 🔍 問題の診断手順

### ステップ1: Google Calendar API設定の確認

**確認方法:**
```bash
ls -la config/google-calendar-config.json
```

**結果:**
- ❌ ファイルが存在しない → **これが原因です**
- ✅ ファイルが存在する → ステップ2へ

**解決方法:**
1. `GOOGLE_CALENDAR_SETUP.md` を参照してGoogle Cloud Consoleでプロジェクトを作成
2. OAuth 2.0認証情報を作成
3. `config/google-calendar-config.json` を作成して認証情報を設定

**テンプレート:**
```bash
cp config/google-calendar-config.json.example config/google-calendar-config.json
# 上記ファイルを編集してGoogle Consoleの情報を入力
```

---

### ステップ2: データベースマイグレーションの確認

**確認方法:**
```bash
php artisan migrate:status
```

**必要なマイグレーション:**
- `2025_11_17_101835_add_google_calendar_to_users_table`

**解決方法:**
```bash
php artisan migrate
```

---

### ステップ3: ユーザーがGoogleカレンダーに接続しているか確認

**確認方法:**
1. webアプリにログイン
2. イベント一覧ページまたはカレンダーページを開く
3. 「Google Calendar 連携」セクションを確認

**状態:**
- ❌ 「Google Calendar を接続する」ボタンが表示されている → **接続が必要です**
- ✅ 「✅ Google Calendar に接続しています」と表示 → ステップ4へ

**解決方法:**
1. 「Google Calendar に接続」ボタンをクリック
2. Googleアカウントでログイン
3. カレンダーへのアクセスを許可

---

### ステップ4: ブラウザのJavaScriptエラーを確認

**確認方法:**
1. カレンダーページを開く
2. F12キーを押して開発者ツールを開く
3. 「Console」タブを確認

**よくあるエラー:**

#### エラー1: 401 Unauthorized
```
GET /calendar/google-events?start=... 401 (Unauthorized)
```
**原因:** ユーザーが認証されていない、またはGoogleカレンダーに接続していない
**解決:** ステップ3を実行

#### エラー2: 500 Internal Server Error
```
GET /calendar/google-events?start=... 500 (Internal Server Error)
```
**原因:** サーバー側のエラー（設定ファイル不足、トークン期限切れなど）
**解決:**
1. ステップ1でconfig fileを確認
2. Laravelログを確認: `tail -100 storage/logs/laravel.log`
3. 必要に応じてGoogleカレンダー接続を解除して再接続

#### エラー3: CORS エラー
```
Access to fetch at '...' has been blocked by CORS policy
```
**原因:** CORS設定の問題
**解決:** サーバー設定を確認

---

### ステップ5: カレンダーページのデバッグコンソールを確認

カレンダーページには開発者用のデバッグコンソールが表示されています。

**確認項目:**
1. 「Googleカレンダー API URL」が表示されているか
2. 「Googleカレンダーレスポンスステータス」が200か
3. 「Googleカレンダーイベント取得数」が0以外か

**メッセージの意味:**

| メッセージ | 意味 | 対処法 |
|-----------|------|--------|
| ⚠️ Googleカレンダーイベント取得スキップ (未接続またはエラー) | ユーザーが接続していない | ステップ3で接続 |
| ✅ Googleカレンダーイベント取得成功 | 正常に取得できた | - |
| ❌ Googleカレンダーイベント取得エラー | サーバーエラー | ステップ4のエラー確認 |

---

### ステップ6: Laravelログを確認

**確認方法:**
```bash
tail -100 storage/logs/laravel.log | grep -i google
```

**よくあるエラー:**

#### エラー1: Google config file not found
```
[ERROR] Google config file not found: /path/to/config/google-calendar-config.json
```
**解決:** ステップ1を実行

#### エラー2: Invalid token format
```
[ERROR] Invalid token data format
```
**解決:** Googleカレンダー接続を解除して再接続

#### エラー3: Token refresh failed
```
[ERROR] Token refresh failed
```
**解決:** Googleカレンダー接続を解除して再接続

---

## 🛠️ よくある問題と解決方法

### 問題1: 「Google Calendar 連携」セクションが表示されない

**原因:** ログインしていない
**解決:** ログインする

---

### 問題2: Googleカレンダーに接続しているのにイベントが表示されない

**可能性:**

#### A. Googleカレンダーにイベントが登録されていない
**確認:** Googleカレンダー（https://calendar.google.com）を開いてイベントがあるか確認
**解決:** Googleカレンダーに予定を追加

#### B. 表示期間外のイベントしかない
**確認:** カレンダーの月を変更して確認
**解決:** カレンダーの表示期間を変更

#### C. トークンが期限切れ
**確認:** ブラウザのコンソールでエラーを確認
**解決:** Googleカレンダー接続を解除して再接続

---

### 問題3: 「Google認証開始に失敗しました」エラー

**原因:** `config/google-calendar-config.json` が存在しないか、形式が間違っている
**解決:**
1. ファイルが存在するか確認
2. JSON形式が正しいか確認（JSONバリデーターを使用）
3. Google Consoleで正しい認証情報を取得

---

### 問題4: OAuth認証後にエラーが表示される

**原因:** リダイレクトURIの設定ミス
**解決:**
1. Google Cloud Consoleを開く
2. 「認証情報」→ 作成したOAuth 2.0クライアントIDを選択
3. 「承認済みのリダイレクトURI」に以下を追加:
   ```
   http://localhost/google-calendar/callback
   http://your-domain.com/google-calendar/callback
   ```

---

## 📊 正常動作時の確認方法

### カレンダーページのデバッグコンソール（正常時の出力例）

```
[12:34:56] === イベント取得開始 ===
[12:34:56] 開始日: 2025-12-01T00:00:00.000Z
[12:34:56] 終了日: 2025-12-31T23:59:59.999Z
[12:34:57] アプリイベントAPI URL: /calendar/events?start=...
[12:34:57] アプリイベントレスポンスステータス: 200
[12:34:57] アプリイベント取得数: 5
[12:34:57] GoogleカレンダーAPI URL: /calendar/google-events?start=...
[12:34:58] Googleカレンダーレスポンスステータス: 200
[12:34:58] Googleカレンダーイベント取得数: 3
[12:34:58] ✅ Googleカレンダーイベント取得成功
[12:34:58] 合計イベント数: 8
[12:34:58] ✅ イベント取得成功
```

### カレンダー表示の確認

正常動作時は、カレンダー上に以下の色でイベントが表示されます：

- 🟦 **ティール (#4ECDC4)**: 通常のアプリイベント
- 🟥 **赤 (#FF6B6B)**: お気に入り登録したアプリイベント
- 🟧 **オレンジ (#FF8C00)**: **Googleカレンダーのイベント**

---

## 🔧 完全なリセット手順

すべてをリセットして最初からやり直す場合：

```bash
# 1. Googleカレンダー接続を解除（webアプリ上で実行）

# 2. データベースをリセット（注意: すべてのデータが削除されます）
php artisan migrate:fresh --seed

# 3. キャッシュをクリア
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 4. 再度マイグレーション実行
php artisan migrate

# 5. Google Calendar設定ファイルを再作成
# GOOGLE_CALENDAR_SETUP.md を参照
```

---

## 📞 サポート

それでも解決しない場合は、以下の情報を収集してください：

1. ブラウザのコンソールエラー（F12 → Console）
2. Laravelログ: `tail -100 storage/logs/laravel.log`
3. デバッグコンソールの出力（カレンダーページ下部）
4. Google Cloud Consoleの設定スクリーンショット

---

## ✅ チェックリスト

Googleカレンダーイベントを表示するために必要な条件：

- [ ] `config/google-calendar-config.json` が存在し、正しい内容が設定されている
- [ ] データベースマイグレーションが実行されている
- [ ] ユーザーがwebアプリにログインしている
- [ ] ユーザーがGoogleカレンダーに接続している
- [ ] Googleカレンダーにイベントが登録されている
- [ ] イベントの日付がカレンダーの表示期間内である
- [ ] ブラウザのJavaScriptエラーがない
- [ ] サーバーが起動している

すべてにチェックが入れば、Googleカレンダーのイベントが表示されるはずです！
