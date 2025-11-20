<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;

class GoogleCalendarController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Okinawa Events');
        $this->client->setScopes([Calendar::CALENDAR]);
        $this->client->setAuthConfig(config_path('google-calendar-config.json'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Google認証ページにリダイレクト
     */
    public function authenticate()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect($authUrl);
    }

    /**
     * Google認証コールバック
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('index')->with('error', 'Google認証がキャンセルされました');
        }

        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            $user = Auth::user();
            
            // ユーザーのトークンを保存
            $user->update([
                'google_calendar_token' => json_encode($accessToken),
                'google_calendar_connected' => true,
            ]);

            return redirect()->route('index')->with('success', 'Google カレンダーに接続しました');

        } catch (\Exception $e) {
            return redirect()->route('index')->with('error', 'Google認証エラー: ' . $e->getMessage());
        }
    }

    /**
     * Google カレンダーに イベントを追加
     */
    public function addEventToCalendar(Event $event)
    {
        $user = Auth::user();

        // ユーザーが Google カレンダーに接続しているかチェック
        if (!$user->google_calendar_connected || !$user->google_calendar_token) {
            return response()->json([
                'success' => false,
                'message' => 'Google カレンダーに接続してください',
            ], 400);
        }

        try {
            $this->client->setAccessToken(json_decode($user->google_calendar_token, true));

            // トークンが期限切れの場合はリフレッシュ
            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $user->update([
                    'google_calendar_token' => json_encode($this->client->getAccessToken()),
                ]);
            }

            $service = new Calendar($this->client);

            // イベント情報を作成
            $googleEvent = new GoogleEvent();
            $googleEvent->setSummary($event->name);
            $googleEvent->setDescription($event->overview);
            $googleEvent->setLocation($event->location);

            // 日時を設定
            $start = new \Google\Service\Calendar\EventDateTime();
            $start->setDateTime($event->start_date);
            $googleEvent->setStart($start);

            $end = new \Google\Service\Calendar\EventDateTime();
            $end->setDateTime($event->end_date);
            $googleEvent->setEnd($end);

            // Google カレンダーに追加
            $createdEvent = $service->events->insert('primary', $googleEvent);

            // イベントIDを保存
            $event->update([
                'google_calendar_event_id' => $createdEvent->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Google カレンダーに追加しました',
            ]);

        } catch (\Exception $e) {
            \Log::error('Google Calendar Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました',
            ], 500);
        }
    }

    /**
     * Google カレンダーからイベントを削除
     */
    public function removeEventFromCalendar(Event $event)
    {
        $user = Auth::user();

        if (!$event->google_calendar_event_id) {
            return response()->json([
                'success' => false,
                'message' => 'Google カレンダーに登録されていません',
            ], 400);
        }

        try {
            $this->client->setAccessToken(json_decode($user->google_calendar_token, true));

            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            }

            $service = new Calendar($this->client);
            $service->events->delete('primary', $event->google_calendar_event_id);

            // イベントIDをクリア
            $event->update([
                'google_calendar_event_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Google カレンダーから削除しました',
            ]);

        } catch (\Exception $e) {
            \Log::error('Google Calendar Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました',
            ], 500);
        }
    }

    /**
     * 接続状態を確認
     */
    public function getConnectionStatus()
    {
        $user = Auth::user();

        return response()->json([
            'connected' => (bool)$user->google_calendar_connected,
            'email' => $user->email,
        ]);
    }

    /**
     * 接続を解除
     */
    public function disconnect()
    {
        $user = Auth::user();

        $user->update([
            'google_calendar_token' => null,
            'google_calendar_connected' => false,
        ]);

        return redirect()->back()->with('success', 'Google カレンダーの接続を解除しました');
    }
}