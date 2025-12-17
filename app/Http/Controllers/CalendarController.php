<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GoogleCalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class CalendarController extends Controller
{
    /**
     * カテゴリーごとの色マッピング
     */
    private function getCategoryColor($categoryName, $isFavorited = false)
    {
        $colors = [
            '祭り' => '#FF6B6B',          // 赤
            '音楽イベント' => '#9B59B6',   // 紫
            '展示会' => '#3498DB',         // 青
            'スポーツイベント' => '#2ECC71', // 緑
            '式典' => '#F39C12',           // オレンジ
            'RSS配信' => '#95A5A6',        // グレー
        ];

        // お気に入りの場合は少し濃い色にする
        if ($isFavorited) {
            $favoritedColors = [
                '祭り' => '#E74C3C',
                '音楽イベント' => '#8E44AD',
                '展示会' => '#2980B9',
                'スポーツイベント' => '#27AE60',
                '式典' => '#D68910',
                'RSS配信' => '#7F8C8D',
            ];
            return $favoritedColors[$categoryName] ?? '#E74C3C';
        }

        return $colors[$categoryName] ?? '#4ECDC4'; // デフォルトはティール
    }

    /**
     * カレンダービュー表示（認証不要）
     */
    public function index()
    {
        Log::info('Calendar index page loaded');

        // Google Calendar 自動同期（認証済みユーザーのみ）
        $user = Auth::user();
        if ($user && $user->google_calendar_connected && $user->google_calendar_token) {
            try {
                $googleCalendarController = new GoogleCalendarController();
                $syncResult = $googleCalendarController->autoSync();

                if ($syncResult['success'] && $syncResult['imported_count'] > 0) {
                    session()->flash('success', $syncResult['message']);
                }
            } catch (Exception $e) {
                Log::error('Auto-sync failed on calendar page load', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

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

                    // カテゴリー名を取得
                    $categoryName = $event->category->name ?? '';

                    // イベントデータを FullCalendar フォーマットに変換
                    return [
                        'id' => (string)$event->id,
                        'title' => $event->name,
                        'start' => $event->start_date ? Carbon::parse($event->start_date)->format('Y-m-d\TH:i:s') : null,
                        'end' => $event->end_date ? Carbon::parse($event->end_date)->format('Y-m-d\TH:i:s') : null,
                        'color' => $this->getCategoryColor($categoryName, $isFavorited),
                        'extendedProps' => [
                            'type' => 'app_event',
                            'category' => $categoryName,
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
     * Google Calendar イベント取得（ローカルDBから）（認証必須）
     */
    public function getGoogleCalendarEvents(Request $request)
    {
        try {
            $user = Auth::user();

            // ユーザーが認証されていない場合
            if (!$user) {
                Log::warning('User not authenticated for Google Calendar events');
                return response()->json([]);
            }

            // ユーザーが Google Calendar に接続していない場合
            if (!$user->google_calendar_connected || !$user->google_calendar_token) {
                Log::info('User not connected to Google Calendar', ['user_id' => $user->id]);
                return response()->json([]);
            }

            $start = $request->get('start');
            $end = $request->get('end');

            if (!$start || !$end) {
                Log::warning('Missing required parameters for Google Calendar events', ['start' => $start, 'end' => $end]);
                return response()->json([]);
            }

            Log::info('Fetching Google Calendar events from local DB', [
                'user_id' => $user->id,
                'start' => $start,
                'end' => $end
            ]);

            try {
                $startDate = new \DateTime($start);
                $endDate = new \DateTime($end);
            } catch (Exception $e) {
                Log::error('Invalid date format for Google Calendar events', ['start' => $start, 'end' => $end, 'error' => $e->getMessage()]);
                return response()->json([]);
            }

            // ローカルDBからGoogleカレンダーの予定を取得
            // まず、全レコード数を確認
            $totalCount = GoogleCalendarEvent::where('user_id', $user->id)->count();
            Log::info('Total Google Calendar events in DB', [
                'user_id' => $user->id,
                'total_count' => $totalCount
            ]);

            $googleEvents = GoogleCalendarEvent::where('user_id', $user->id)
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
                ->get();

            Log::info('Google Calendar events filtered by date range', [
                'user_id' => $user->id,
                'filtered_count' => $googleEvents->count(),
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]);

            $googleEvents = $googleEvents->map(function ($event) {
                return [
                    'id' => 'gc_' . $event->id, // Prefix to distinguish from app events
                    'title' => $event->name,
                    'start' => $event->start_date ? Carbon::parse($event->start_date)->format('Y-m-d\TH:i:s') : null,
                    'end' => $event->end_date ? Carbon::parse($event->end_date)->format('Y-m-d\TH:i:s') : null,
                    'color' => '#FF8C00', // Orange for Google Calendar
                    'extendedProps' => [
                        'type' => 'google_calendar',
                        'description' => $event->description ?? '',
                        'location' => $event->location ?? '',
                        'html_link' => $event->html_link ?? '',
                    ]
                ];
            });

            Log::info('Google Calendar events mapped successfully', [
                'user_id' => $user->id,
                'count' => $googleEvents->count()
            ]);

            return response()->json($googleEvents->values());

        } catch (Exception $e) {
            Log::error('Failed to fetch Google Calendar events from local DB', [
                'user_id' => Auth::id() ?? 'guest',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([]);
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