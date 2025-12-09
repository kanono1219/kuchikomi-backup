<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
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

            // 開始日時
            $start = new EventDateTime();
            $start->setDateTime(new \DateTime($event->start_date));
            $start->setTimeZone('Asia/Tokyo');
            $googleEvent->setStart($start);

            // 終了日時
            $end = new EventDateTime();
            $end->setDateTime(new \DateTime($event->end_date));
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
}