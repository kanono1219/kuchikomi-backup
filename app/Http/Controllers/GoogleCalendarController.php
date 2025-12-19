<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\GoogleCalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Exception;

class GoogleCalendarController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Okinawa Events');
        $this->client->setScopes([Calendar::CALENDAR]);
        
        $configPath = config_path('google-calendar-config.json');
        Log::info('Google Config Path: ' . $configPath);
        
        if (!file_exists($configPath)) {
            Log::error('Google config file not found: ' . $configPath);
            throw new Exception('Google Calendar config file not found');
        }
        
        $configJson = json_decode(file_get_contents($configPath), true);
        Log::info('Google Config loaded successfully');
        
        $this->client->setAuthConfig($configJson);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setRedirectUri(url('/google-calendar/callback'));
    }

    /**
     * Google Calendar 認証を開始
     */
    public function authenticate()
    {
        try {
            Log::info('Google authenticate() called for user: ' . Auth::id());
            
            $authUrl = $this->client->createAuthUrl();
            Log::info('Redirecting to: ' . substr($authUrl, 0, 100) . '...');
            
            return redirect()->away($authUrl);
        } catch (Exception $e) {
            Log::error('Google authenticate() error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Google認証開始に失敗しました');
        }
    }

    /**
     * Google Calendar OAuth2 コールバック
     */
    public function callback(Request $request)
    {
        try {
            Log::info('Google callback() called');
            Log::info('Query params: ' . json_encode($request->query()));
            
            $code = $request->query('code');
            $error = $request->query('error');

            // エラーが返された場合
            if ($error) {
                Log::warning('Google authorization error: ' . $error);
                return redirect('/')->with('error', 'Google認証がキャンセルされました');
            }

            // 認可コードがない場合
            if (!$code) {
                Log::error('No authorization code received');
                return redirect('/')->with('error', 'Google認証がキャンセルされました');
            }

            Log::info('Authorization code received');
            
            // ユーザーが認証されているか確認
            if (!Auth::check()) {
                Log::error('No authenticated user found');
                return redirect('/login')->with('error', 'ログインしてください');
            }

            // アクセストークンを取得
            Log::info('Fetching access token with authorization code');
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            Log::info('Access token obtained successfully');
            
            $user = Auth::user();
            $tokenJson = json_encode($accessToken);

            // ユーザーのトークンを保存
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'google_calendar_token' => $tokenJson,
                    'google_calendar_connected' => true,
                    'updated_at' => now(),
                ]);

            Log::info('Google Calendar token saved for user: ' . $user->id);

            // 接続テスト
            try {
                $this->client->setAccessToken($accessToken);
                $service = new Calendar($this->client);
                $service->calendarList->listCalendarList(['maxResults' => 1]);
                Log::info('Google Calendar connection test successful');
            } catch (Exception $e) {
                Log::warning('Google Calendar connection test failed: ' . $e->getMessage());
            }

            return redirect('/')->with('success', '✅ Google カレンダーに接続しました');

        } catch (Exception $e) {
            Log::error('Google callback error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return redirect('/')->with('error', 'Google認証エラー: ' . $e->getMessage());
        }
    }

    /**
     * イベントを Google Calendar に追加
     */
    public function addEventToCalendar(Event $event)
    {
        try {
            $user = Auth::user();

            // ユーザーが Google Calendar に接続しているか確認
            if (!$user || !$user->google_calendar_connected || !$user->google_calendar_token) {
                Log::warning('User not connected to Google Calendar: ' . Auth::id());
                return response()->json([
                    'success' => false,
                    'message' => 'Google カレンダーに接続してください',
                ], 400);
            }

            Log::info('Adding event to Google Calendar: ' . $event->id);
            
            // トークンを設定
            $tokenData = json_decode($user->google_calendar_token, true);
            $this->client->setAccessToken($tokenData);

            // トークンが期限切れの場合は更新
            if ($this->client->isAccessTokenExpired()) {
                Log::info('Token expired, refreshing for user: ' . $user->id);
                
                $refreshToken = $tokenData['refresh_token'] ?? null;
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    
                    $newToken = $this->client->getAccessToken();
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'google_calendar_token' => json_encode($newToken),
                        ]);
                    Log::info('Token refreshed successfully');
                } else {
                    Log::error('No refresh token available');
                    throw new Exception('リフレッシュトークンがありません');
                }
            }

            // Google Calendar API を初期化
            $service = new Calendar($this->client);

            // Google Calendar イベントを作成
            $googleEvent = new GoogleEvent();
            
            // イベント名（name カラムを使用）
            $googleEvent->setSummary($event->name);
            
            // 説明（overview カラムを使用）
            if ($event->overview) {
                $googleEvent->setDescription($event->overview);
            }
            
            // 場所
            if ($event->location) {
                $googleEvent->setLocation($event->location);
            }

            // 開始日時 (RFC3339形式)
            $start = new EventDateTime();
            $startDateTime = new \DateTime($event->start_date);
            $startDateTime->setTimezone(new \DateTimeZone('Asia/Tokyo'));
            $start->setDateTime($startDateTime->format(\DateTime::RFC3339));
            $start->setTimeZone('Asia/Tokyo');
            $googleEvent->setStart($start);

            // 終了日時 (RFC3339形式)
            $end = new EventDateTime();
            $endDateTime = new \DateTime($event->end_date);
            $endDateTime->setTimezone(new \DateTimeZone('Asia/Tokyo'));
            $end->setDateTime($endDateTime->format(\DateTime::RFC3339));
            $end->setTimeZone('Asia/Tokyo');
            $googleEvent->setEnd($end);

            Log::info('Creating Google Calendar event', [
                'event_id' => $event->id,
                'summary' => $event->name,
                'location' => $event->location,
                'start' => $event->start_date,
                'end' => $event->end_date,
            ]);

            // Google Calendar に追加
            $createdEvent = $service->events->insert('primary', $googleEvent);
            Log::info('Event created in Google Calendar: ' . $createdEvent->getId());

            // イベント ID をデータベースに保存
            $event->update([
                'google_calendar_event_id' => $createdEvent->getId(),
            ]);

            Log::info('Google Calendar event ID saved: ' . $createdEvent->getId());

            return response()->json([
                'success' => true,
                'message' => '✅ Google カレンダーに追加しました',
                'event_id' => $createdEvent->getId(),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to add event to Google Calendar', [
                'event_id' => $event->id ?? 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * イベントを Google Calendar から削除
     */
    public function removeEventFromCalendar(Event $event)
    {
        try {
            $user = Auth::user();

            // Google Calendar のイベント ID がない場合
            if (!$event->google_calendar_event_id) {
                Log::warning('Event has no Google Calendar event ID: ' . $event->id);
                return response()->json([
                    'success' => false,
                    'message' => 'Google カレンダーに登録されていません',
                ], 400);
            }

            // Google Calendar に接続しているか確認
            if (!$user || !$user->google_calendar_token) {
                Log::warning('User not connected to Google Calendar: ' . Auth::id());
                return response()->json([
                    'success' => false,
                    'message' => 'Google カレンダーに接続していません',
                ], 400);
            }

            Log::info('Removing event from Google Calendar', [
                'event_id' => $event->id,
                'google_calendar_event_id' => $event->google_calendar_event_id
            ]);

            // トークンを設定
            $tokenData = json_decode($user->google_calendar_token, true);
            $this->client->setAccessToken($tokenData);

            // トークンが期限切れの場合は更新
            if ($this->client->isAccessTokenExpired()) {
                Log::info('Token expired, refreshing');
                
                $refreshToken = $tokenData['refresh_token'] ?? null;
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newToken = $this->client->getAccessToken();
                    
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'google_calendar_token' => json_encode($newToken),
                        ]);
                    Log::info('Token refreshed successfully');
                }
            }

            // Google Calendar API を初期化
            $service = new Calendar($this->client);

            // Google Calendar から削除
            $service->events->delete('primary', $event->google_calendar_event_id);
            Log::info('Event deleted from Google Calendar successfully');

            // Google Calendar イベント ID をクリア
            $event->update([
                'google_calendar_event_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Google カレンダーから削除しました',
            ]);

        } catch (Exception $e) {
            Log::error('Failed to remove event from Google Calendar', [
                'event_id' => $event->id ?? 'unknown',
                'google_event_id' => $event->google_calendar_event_id ?? 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Google Calendar との接続状態を取得
     */
    public function getConnectionStatus()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'connected' => false,
                    'message' => 'ログインしていません',
                ]);
            }

            $connected = (bool)$user->google_calendar_connected && !empty($user->google_calendar_token);

            // トークンが有効か確認
            if ($connected) {
                try {
                    $tokenData = json_decode($user->google_calendar_token, true);
                    $this->client->setAccessToken($tokenData);
                    
                    // テスト的にカレンダーリストを取得
                    $service = new Calendar($this->client);
                    $service->calendarList->listCalendarList(['maxResults' => 1]);
                    
                    Log::info('Google Calendar connection test successful for user: ' . $user->id);

                    return response()->json([
                        'connected' => true,
                        'message' => 'Google Calendar に接続しています',
                    ]);
                } catch (Exception $e) {
                    Log::warning('Google Calendar token validation failed: ' . $e->getMessage());
                    
                    // トークンが無効な場合はリセット
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'google_calendar_token' => null,
                            'google_calendar_connected' => false,
                        ]);

                    return response()->json([
                        'connected' => false,
                        'message' => 'Google Calendar トークンが期限切れです',
                    ]);
                }
            }

            return response()->json([
                'connected' => false,
                'message' => 'Google Calendar に接続していません',
            ]);

        } catch (Exception $e) {
            Log::error('Error checking Google Calendar status: ' . $e->getMessage());
            
            return response()->json([
                'connected' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Google Calendar との接続を解除
     */
    public function disconnect()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect('/login')->with('error', 'ログインしてください');
            }

            Log::info('Disconnecting Google Calendar for user: ' . $user->id);

            // Googleカレンダーイベントをすべて削除
            $deletedCount = GoogleCalendarEvent::where('user_id', $user->id)->delete();
            Log::info('Deleted Google Calendar events', [
                'user_id' => $user->id,
                'count' => $deletedCount
            ]);

            // ユーザーのトークンと接続状態をクリア
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'google_calendar_token' => null,
                    'google_calendar_connected' => false,
                    'updated_at' => now(),
                ]);

            Log::info('Google Calendar disconnected for user: ' . $user->id);

            return redirect()->back()->with('success', '✅ Google カレンダーの接続を解除しました');

        } catch (Exception $e) {
            Log::error('Failed to disconnect Google Calendar: ' . $e->getMessage());
            return redirect()->back()->with('error', 'エラーが発生しました');
        }
    }

    /**
     * Google Calendar からイベントを取得
     */
    public function fetchEvents(Request $request)
    {
        try {
            $user = Auth::user();

            // Google Calendar に接続しているか確認
            if (!$user || !$user->google_calendar_connected || !$user->google_calendar_token) {
                return redirect()->back()->with('error', 'Google カレンダーに接続してください');
            }

            Log::info('Fetching events from Google Calendar for user: ' . $user->id);

            // トークンを設定
            $tokenData = json_decode($user->google_calendar_token, true);
            $this->client->setAccessToken($tokenData);

            // トークンが期限切れの場合は更新
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $tokenData['refresh_token'] ?? null;
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newToken = $this->client->getAccessToken();
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['google_calendar_token' => json_encode($newToken)]);
                }
            }

            // Google Calendar API を初期化
            $service = new Calendar($this->client);

            // 6ヶ月前から6ヶ月後までのイベントを取得
            $timeMin = new \DateTime('-6 months', new \DateTimeZone('Asia/Tokyo'));
            $timeMax = new \DateTime('+6 months', new \DateTimeZone('Asia/Tokyo'));

            $optParams = [
                'maxResults' => 250,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $timeMin->format(\DateTime::RFC3339),
                'timeMax' => $timeMax->format(\DateTime::RFC3339),
            ];

            $results = $service->events->listEvents('primary', $optParams);
            $events = $results->getItems();

            Log::info('Fetched ' . count($events) . ' events from Google Calendar');

            // 取得したイベントをビューに渡す
            return view('google-calendar.events', [
                'googleEvents' => $events,
                'user' => $user,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch events from Google Calendar', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Google カレンダーからのイベント取得に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Google Calendar のイベントをローカルDBにインポート
     */
    public function importEvent(Request $request)
    {
        try {
            $user = Auth::user();

            // Google Calendar に接続しているか確認
            if (!$user || !$user->google_calendar_connected || !$user->google_calendar_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google カレンダーに接続してください',
                ], 400);
            }

            $googleEventId = $request->input('google_event_id');
            if (!$googleEventId) {
                return response()->json([
                    'success' => false,
                    'message' => 'イベントIDが指定されていません',
                ], 400);
            }

            Log::info('Importing event from Google Calendar', [
                'google_event_id' => $googleEventId,
                'user_id' => $user->id
            ]);

            // トークンを設定
            $tokenData = json_decode($user->google_calendar_token, true);
            $this->client->setAccessToken($tokenData);

            // トークンが期限切れの場合は更新
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $tokenData['refresh_token'] ?? null;
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newToken = $this->client->getAccessToken();
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['google_calendar_token' => json_encode($newToken)]);
                }
            }

            // Google Calendar API を初期化
            $service = new Calendar($this->client);

            // イベントを取得
            $googleEvent = $service->events->get('primary', $googleEventId);

            // 既にインポート済みか確認
            $existingEvent = GoogleCalendarEvent::where('google_event_id', $googleEventId)
                ->where('user_id', $user->id)
                ->first();

            if ($existingEvent) {
                return response()->json([
                    'success' => false,
                    'message' => '既にインポート済みのイベントです',
                ], 400);
            }

            // 開始日時と終了日時を取得
            $startDateTime = $googleEvent->getStart()->getDateTime();
            $endDateTime = $googleEvent->getEnd()->getDateTime();

            // 終日イベントの場合は日付のみ
            if (!$startDateTime) {
                $startDateTime = $googleEvent->getStart()->getDate() . ' 00:00:00';
            }
            if (!$endDateTime) {
                $endDateTime = $googleEvent->getEnd()->getDate() . ' 23:59:59';
            }

            // GoogleカレンダーイベントをローカルDBに保存
            $gcEvent = GoogleCalendarEvent::create([
                'user_id' => $user->id,
                'google_event_id' => $googleEventId,
                'name' => $googleEvent->getSummary() ?: 'Googleカレンダーからのイベント',
                'description' => $googleEvent->getDescription() ?: '',
                'location' => $googleEvent->getLocation() ?: '',
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'html_link' => $googleEvent->getHtmlLink() ?: '',
            ]);

            Log::info('Google Calendar event imported successfully', [
                'gc_event_id' => $gcEvent->id,
                'google_event_id' => $googleEventId
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Googleカレンダーイベントをインポートしました',
                'gc_event_id' => $gcEvent->id,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to import event from Google Calendar', [
                'google_event_id' => $request->input('google_event_id'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Google Calendar のイベントを自動同期（新しいイベントのみインポート）
     */
    public function autoSync()
    {
        try {
            $user = Auth::user();

            // Google Calendar に接続しているか確認
            if (!$user || !$user->google_calendar_connected || !$user->google_calendar_token) {
                return [
                    'success' => false,
                    'message' => 'Google カレンダーに接続していません',
                    'imported_count' => 0,
                ];
            }

            Log::info('Auto-syncing events from Google Calendar for user: ' . $user->id);

            // トークンを設定
            $tokenData = json_decode($user->google_calendar_token, true);
            $this->client->setAccessToken($tokenData);

            // トークンが期限切れの場合は更新
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $tokenData['refresh_token'] ?? null;
                if ($refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newToken = $this->client->getAccessToken();
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['google_calendar_token' => json_encode($newToken)]);
                }
            }

            // Google Calendar API を初期化
            $service = new Calendar($this->client);

            // 6ヶ月前から6ヶ月後までのイベントを取得
            $timeMin = new \DateTime('-6 months', new \DateTimeZone('Asia/Tokyo'));
            $timeMax = new \DateTime('+6 months', new \DateTimeZone('Asia/Tokyo'));

            $optParams = [
                'maxResults' => 250,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $timeMin->format(\DateTime::RFC3339),
                'timeMax' => $timeMax->format(\DateTime::RFC3339),
            ];

            $results = $service->events->listEvents('primary', $optParams);
            $googleEvents = $results->getItems();

            Log::info('Fetched ' . count($googleEvents) . ' events from Google Calendar for auto-sync');

            $importedCount = 0;

            foreach ($googleEvents as $googleEvent) {
                $googleEventId = $googleEvent->getId();

                // ★重要★ webアプリのイベントで既に追加済みか確認
                // webアプリのイベントがGoogleカレンダーに追加されている場合はスキップ
                $webAppEvent = Event::where('google_calendar_event_id', $googleEventId)->first();
                if ($webAppEvent) {
                    Log::info('Skipping event already in web app', [
                        'google_event_id' => $googleEventId,
                        'web_app_event_id' => $webAppEvent->id,
                        'event_name' => $webAppEvent->name
                    ]);
                    continue; // webアプリのイベントは重複を避けてスキップ
                }

                // 既にインポート済みか確認
                $existingEvent = GoogleCalendarEvent::where('google_event_id', $googleEventId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingEvent) {
                    continue; // 既にインポート済みならスキップ
                }

                try {
                    // 開始日時と終了日時を取得
                    $startDateTime = $googleEvent->getStart()->getDateTime();
                    $endDateTime = $googleEvent->getEnd()->getDateTime();

                    // 終日イベントの場合は日付のみ
                    if (!$startDateTime) {
                        $startDateTime = $googleEvent->getStart()->getDate() . ' 00:00:00';
                    }
                    if (!$endDateTime) {
                        $endDateTime = $googleEvent->getEnd()->getDate() . ' 23:59:59';
                    }

                    // GoogleカレンダーイベントをローカルDBに保存
                    $gcEvent = GoogleCalendarEvent::create([
                        'user_id' => $user->id,
                        'google_event_id' => $googleEventId,
                        'name' => $googleEvent->getSummary() ?: 'Googleカレンダーからのイベント',
                        'description' => $googleEvent->getDescription() ?: '',
                        'location' => $googleEvent->getLocation() ?: '',
                        'start_date' => $startDateTime,
                        'end_date' => $endDateTime,
                        'html_link' => $googleEvent->getHtmlLink() ?: '',
                    ]);

                    $importedCount++;

                    Log::info('Google Calendar event auto-synced', [
                        'gc_event_id' => $gcEvent->id,
                        'google_event_id' => $googleEventId
                    ]);
                } catch (Exception $e) {
                    Log::warning('Failed to auto-sync Google Calendar event', [
                        'google_event_id' => $googleEventId,
                        'error' => $e->getMessage()
                    ]);
                    // エラーがあってもスキップして次のイベントへ
                    continue;
                }
            }

            Log::info('Auto-sync completed', [
                'user_id' => $user->id,
                'imported_count' => $importedCount
            ]);

            return [
                'success' => true,
                'message' => $importedCount > 0
                    ? "{$importedCount}件の新しいイベントをインポートしました"
                    : '新しいイベントはありませんでした',
                'imported_count' => $importedCount,
            ];

        } catch (Exception $e) {
            Log::error('Failed to auto-sync Google Calendar', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage(),
                'imported_count' => 0,
            ];
        }
    }
}