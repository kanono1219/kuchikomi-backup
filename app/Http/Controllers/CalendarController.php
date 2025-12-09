<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class CalendarController extends Controller
{
    /**
     * カレンダービュー表示（認証不要）
     */
    public function index()
    {
        Log::info('Calendar index page loaded');
        return view('calendar.index');
    }

    /**
     * ★修正★ webアプリ側のイベント取得（認証不要に変更）
     */
    public function getEvents(Request $request)
    {
        try {
            $start = $request->get('start');
            $end = $request->get('end');
            $user = Auth::user();

            if (!$start || !$end) {
                Log::warning('Missing required parameters', ['start' => $start, 'end' => $end]);
                return response()->json([
                    'error' => 'start and end parameters are required'
                ], 400);
            }

            Log::info('Fetching app events', [
                'user_id' => $user->id ?? 'guest',
                'start' => $start,
                'end' => $end
            ]);

            try {
                $startDate = new \DateTime($start);
                $endDate = new \DateTime($end);
            } catch (Exception $e) {
                Log::error('Invalid date format', ['start' => $start, 'end' => $end, 'error' => $e->getMessage()]);
                return response()->json([
                    'error' => 'Invalid date format: ' . $e->getMessage()
                ], 400);
            }

            // ★重要★ webアプリ側のイベントを取得（削除されていないもののみ）
            $events = Event::with('category')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($startDate, $endDate) {
                    // 開始日が範囲内 OR 終了日が範囲内 OR 範囲をカバーしている
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($event) use ($user) {
                    // ユーザーがお気に入り登録しているか確認
                    $isFavorited = $user ? $user->favoriteEvents()
                        ->where('event_id', $event->id)
                        ->exists() : false;

                    // イベントデータを FullCalendar フォーマットに変換
                    return [
                        'id' => (string)$event->id,
                        'title' => $event->name,
                        'start' => $event->start_date ? Carbon::parse($event->start_date)->format('Y-m-d\TH:i:s') : null,
                        'end' => $event->end_date ? Carbon::parse($event->end_date)->format('Y-m-d\TH:i:s') : null,
                        'color' => $isFavorited ? '#FF6B6B' : '#4ECDC4', // Red for favorited, teal for normal
                        'extendedProps' => [
                            'type' => 'app_event',
                            'category' => $event->category->name ?? '',
                            'location' => $event->location ?? '',
                            'description' => $event->overview ?? '',
                            'image' => $event->image_url ?? null,
                            'isFavorited' => $isFavorited
                        ],
                        'url' => route('events.show', $event->id)
                    ];
                });

            Log::info('App events fetched successfully', [
                'user_id' => $user->id ?? 'guest',
                'count' => $events->count(),
                'range' => ['start' => $start, 'end' => $end]
            ]);

            return response()->json($events->values());

        } catch (Exception $e) {
            Log::error('Failed to fetch app events', [
                'user_id' => Auth::id() ?? 'guest',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch events: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Google Calendar からイベント取得（認証必須）
     */
    public function getGoogleCalendarEvents(Request $request)
    {
        try {
            $user = Auth::user();

            // ユーザーが認証されていない場合
            if (!$user) {
                Log::warning('User not authenticated for Google Calendar events');
                return response()->json([
                    'events' => [],
                    'message' => 'User not authenticated'
                ]);
            }

            // ユーザーが Google Calendar に接続していない場合
            if (!$user->google_calendar_connected || !$user->google_calendar_token) {
                Log::info('User not connected to Google Calendar', ['user_id' => $user->id]);
                return response()->json([
                    'events' => [],
                    'message' => 'Google Calendar not connected'
                ]);
            }

            $start = $request->get('start', now()->startOfDay()->toIso8601String());
            $end = $request->get('end', now()->addMonths(3)->endOfDay()->toIso8601String());

            Log::info('Fetching Google Calendar events', [
                'user_id' => $user->id,
                'start' => $start,
                'end' => $end
            ]);

            try {
                $service = $this->getCalendarService($user);

                // Google Calendar からイベントを取得
                $results = $service->events->listEvents('primary', [
                    'timeMin' => (new \DateTime($start))->format(\DateTime::RFC3339),
                    'timeMax' => (new \DateTime($end))->format(\DateTime::RFC3339),
                    'maxResults' => 50,
                    'orderBy' => 'startTime',
                    'singleEvents' => true,
                    'showDeleted' => false,
                    'timeZone' => config('app.timezone', 'Asia/Tokyo')
                ]);

                $googleEvents = $results->getItems();

                $events = collect($googleEvents)->map(function ($event) {
                    $start = $event->getStart();
                    $end = $event->getEnd();

                    return [
                        'id' => $event->getId(),
                        'title' => $event->getSummary(),
                        'start' => $start->getDateTime() ?? $start->getDate(),
                        'end' => $end->getDateTime() ?? $end->getDate(),
                        'color' => '#FF8C00', // Orange for Google Calendar
                        'extendedProps' => [
                            'type' => 'google_calendar',
                            'description' => $event->getDescription() ?? '',
                            'location' => $event->getLocation() ?? '',
                        ]
                    ];
                });

                Log::info('Google Calendar events fetched successfully', [
                    'user_id' => $user->id,
                    'count' => $events->count()
                ]);

                return response()->json($events->values());

            } catch (Exception $e) {
                Log::error('Google Calendar API error', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                // Google Calendar エラーの場合は空配列を返す
                return response()->json([
                    'events' => [],
                    'message' => 'Failed to fetch Google Calendar events'
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to fetch Google Calendar events', [
                'user_id' => Auth::id() ?? 'guest',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Google Calendar サービス取得
     */
    private function getCalendarService($user)
    {
        try {
            $client = new Client();
            $client->setApplicationName('Okinawa Event App');
            $client->setScopes([
                'https://www.googleapis.com/auth/calendar.readonly',
                'https://www.googleapis.com/auth/calendar'
            ]);

            // Google Calendar config を読み込み
            $configPath = config_path('google-calendar-config.json');

            if (!file_exists($configPath)) {
                Log::error('Google Calendar config not found', ['path' => $configPath]);
                throw new Exception('Google Calendar config not configured');
            }

            $client->setAuthConfig($configPath);

            // ユーザーのトークンを設定
            if (!$user->google_calendar_token) {
                throw new Exception('User has no Google Calendar token');
            }

            $tokenData = json_decode($user->google_calendar_token, true);

            if (!is_array($tokenData)) {
                Log::error('Invalid token data format', ['user_id' => $user->id]);
                throw new Exception('Invalid token format');
            }

            $client->setAccessToken($tokenData);

            // トークンが期限切れの場合は自動更新
            if ($client->isAccessTokenExpired()) {
                Log::info('Google Calendar token expired, refreshing...', ['user_id' => $user->id]);

                $refreshToken = $tokenData['refresh_token'] ?? null;

                if ($refreshToken) {
                    try {
                        $client->fetchAccessTokenWithRefreshToken($refreshToken);
                        $newToken = $client->getAccessToken();

                        // DB に新しいトークンを保存
                        $user->update([
                            'google_calendar_token' => json_encode($newToken)
                        ]);

                        Log::info('Google Calendar token refreshed successfully', ['user_id' => $user->id]);
                    } catch (Exception $e) {
                        Log::error('Token refresh failed', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                } else {
                    Log::warning('No refresh token available', ['user_id' => $user->id]);
                    throw new Exception('No refresh token available');
                }
            }

            return new Calendar($client);

        } catch (Exception $e) {
            Log::error('Failed to initialize Calendar service', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 同期状態確認
     */
    public function getSyncStatus()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'connected' => false,
                    'message' => 'Not authenticated'
                ]);
            }

            $connected = (bool)$user->google_calendar_connected && !empty($user->google_calendar_token);

            Log::info('Sync status checked', [
                'user_id' => $user->id,
                'connected' => $connected
            ]);

            return response()->json([
                'connected' => $connected,
                'message' => $connected ? 'Connected' : 'Not connected'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to check sync status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}