# Google Calendar 予定が表示されない場合のデバッグ手順

## 1. データベースにレコードがあるか確認

Cloud9のターミナルで以下を実行：

```bash
cd ~/environment/kuchikomi-backup

# Tinkerを起動してレコード数を確認
php artisan tinker
```

Tinker内で以下を実行：

```php
// Google Calendarイベントの総数
\App\Models\GoogleCalendarEvent::count();

// 接続済みユーザー数
\App\Models\User::where('google_calendar_connected', true)->count();

// 特定ユーザーのGoogle Calendarイベント（ユーザーID=1の場合）
\App\Models\GoogleCalendarEvent::where('user_id', 1)->count();

// 最新の5件を確認
\App\Models\GoogleCalendarEvent::latest()->take(5)->get(['id', 'name', 'start_date', 'user_id']);

// 終了
exit
```

## 2. マイグレーションが実行されているか確認

```bash
php artisan migrate:status | grep google_calendar
```

もし実行されていない場合：

```bash
php artisan migrate
```

## 3. ログファイルを確認

```bash
# 最新のログを確認
tail -f storage/logs/laravel.log

# Google Calendar関連のログのみフィルタ
tail -n 100 storage/logs/laravel.log | grep -i "google"
```

## 4. 手動で同期を実行

ブラウザでマイページ（/mypage）にアクセスすると、自動的に同期が実行されます。
その後、ログを確認：

```bash
tail -n 50 storage/logs/laravel.log | grep -i "sync"
```

## 5. Google Calendar接続状態を確認

Tinkerで以下を実行：

```php
$user = \App\Models\User::find(1); // 自分のユーザーID
echo "Connected: " . ($user->google_calendar_connected ? 'Yes' : 'No') . "\n";
echo "Has token: " . ($user->google_calendar_token ? 'Yes' : 'No') . "\n";
exit
```

## 6. APIレスポンスを直接確認

ブラウザの開発者ツール（F12）を開いて、以下を確認：

1. カレンダーページにアクセス
2. Networkタブで `/calendar/google-events` を探す
3. レスポンスを確認（空配列 `[]` か、データが返ってきているか）
4. Consoleタブでデバッグメッセージを確認

## 7. よくある問題と解決策

### データベースにレコードがない場合

**原因**: 自動同期が実行されていない、またはGoogleカレンダーに予定がない

**解決策**:
- マイページ（/mypage）にアクセスして自動同期を実行
- Googleカレンダーに6ヶ月前〜6ヶ月後の予定があるか確認

### 接続状態が false の場合

**原因**: Google Calendarとの接続が切れている

**解決策**:
- マイページで「Google と接続」ボタンをクリック
- Google認証を完了させる

### トークンがない場合

**原因**: 接続プロセスが完了していない

**解決策**:
- 一度接続を解除してから再接続

### 同期でエラーが発生している場合

**原因**: Google Calendar APIのエラー、トークンの期限切れ

**解決策**:
```bash
# ログでエラーメッセージを確認
tail -n 100 storage/logs/laravel.log | grep -i "error"
```

- トークンの期限切れ: 再接続
- API制限: しばらく待ってから再試行

## 8. 強制的に再同期

Tinkerで以下を実行：

```php
// 既存のデータを削除
\App\Models\GoogleCalendarEvent::where('user_id', 1)->delete();

// 再接続が必要
exit
```

その後、ブラウザでマイページにアクセスして再同期。
