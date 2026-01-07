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

                    // カテゴリーに応じた色を決定
                    $categoryColor = $this->getCategoryColor($event->category->name ?? '');

                    // お気に入りの場合は赤色、それ以外はカテゴリー色
                    $eventColor = $isFavorited ? '#FF6B6B' : $categoryColor;

                    // イベントデータを FullCalendar フォーマットに変換
                    return [
                        'id' => (string)$event->id,
                        'title' => $event->name,
                        'start' => $event->start_date ? Carbon::parse($event->start_date)->format('Y-m-d\TH:i:s') : null,
                        'end' => $event->end_date ? Carbon::parse($event->end_date)->format('Y-m-d\TH:i:s') : null,
                        'backgroundColor' => $eventColor,
                        'borderColor' => $eventColor,
                        'textColor' => '#FFFFFF',
                        'extendedProps' => [
                            'type' => 'app_event',
                            'category' => $event->category->name ?? '',
                            'categoryColor' => $categoryColor,
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
                // Carbon を使って日付を解析（タイムゾーンを考慮）
                $startDate = Carbon::parse($start);
                $endDate = Carbon::parse($end);
            } catch (Exception $e) {
                Log::error('Invalid date format for Google Calendar events', ['start' => $start, 'end' => $end, 'error' => $e->getMessage()]);
                return response()->json([]);
            }

            // デバッグ: 全レコード数を確認
            $totalCount = GoogleCalendarEvent::where('user_id', $user->id)->count();
            Log::info('Total Google Calendar events in DB', [
                'user_id' => $user->id,
                'total_count' => $totalCount
            ]);

            // デバッグ: 最初の5件のイベントの日付を確認
            if ($totalCount > 0) {
                $sampleEvents = GoogleCalendarEvent::where('user_id', $user->id)
                    ->orderBy('start_date', 'asc')
                    ->take(5)
                    ->get(['id', 'name', 'start_date', 'end_date']);

                Log::info('Sample events from DB', [
                    'user_id' => $user->id,
                    'sample_events' => $sampleEvents->map(function($e) {
                        return [
                            'id' => $e->id,
                            'name' => $e->name,
                            'start' => $e->start_date->format('Y-m-d H:i:s'),
                            'end' => $e->end_date->format('Y-m-d H:i:s'),
                        ];
                    })->toArray()
                ]);
            }

            // ★重要★ webアプリのイベントで、Googleカレンダーに追加されているものを取得
            // これらのイベントは重複を避けるため除外する
            $webAppGoogleEventIds = Event::where('user_id', $user->id)
                ->whereNotNull('google_calendar_event_id')
                ->pluck('google_calendar_event_id')
                ->filter()  // null値を除外
                ->toArray();

            Log::info('Web app events in Google Calendar (will be excluded)', [
                'user_id' => $user->id,
                'count' => count($webAppGoogleEventIds),
                'event_ids' => $webAppGoogleEventIds
            ]);

            // デバッグ: 除外される前の全件数
            $totalBeforeFilter = GoogleCalendarEvent::where('user_id', $user->id)
                ->where('end_date', '>=', $startDate)
                ->where('start_date', '<=', $endDate)
                ->count();

            Log::info('Google Calendar events before filtering', [
                'user_id' => $user->id,
                'count_before_filter' => $totalBeforeFilter
            ]);

            // ★修正★ Googleカレンダーの個人的予定のみを取得（Webアプリのイベントと重複するものは除外）
            $googleEventsQuery = GoogleCalendarEvent::where('user_id', $user->id)
                ->where('end_date', '>=', $startDate)     // イベント終了日が検索開始日以降
                ->where('start_date', '<=', $endDate);    // イベント開始日が検索終了日以前

            // Webアプリのイベントと重複するものを除外
            if (count($webAppGoogleEventIds) > 0) {
                $googleEventsQuery->whereNotIn('google_event_id', $webAppGoogleEventIds);
            }

            $googleEvents = $googleEventsQuery->orderBy('start_date', 'asc')->get();

            Log::info('Google Calendar events after filtering duplicates', [
                'user_id' => $user->id,
                'count_before_filter' => $totalBeforeFilter,
                'count_after_filter' => $googleEvents->count(),
                'excluded_count' => $totalBeforeFilter - $googleEvents->count(),
                'requested_start' => $startDate->toDateTimeString(),
                'requested_end' => $endDate->toDateTimeString(),
                'remaining_event_ids' => $googleEvents->pluck('google_event_id')->toArray(),
                'remaining_event_names' => $googleEvents->pluck('name')->toArray()
            ]);

            // FullCalendar フォーマットに変換
            $googleEvents = $googleEvents->map(function ($event) {
                return [
                    'id' => 'gc_' . $event->id, // Prefix to distinguish from app events
                    'title' => $event->name,
                    'start' => $event->start_date ? $event->start_date->format('Y-m-d\TH:i:s') : null,
                    'end' => $event->end_date ? $event->end_date->format('Y-m-d\TH:i:s') : null,
                    'backgroundColor' => '#FF8C00', // Orange for Google Calendar
                    'borderColor' => '#FF8C00',
                    'textColor' => '#FFFFFF',
                    'extendedProps' => [
                        'type' => 'google_calendar',
                        'description' => $event->description ?? '',
                        'location' => $event->location ?? '',
                        'html_link' => $event->html_link ?? '',
                    ]
                ];
            });

            Log::info('Google Calendar events mapped to FullCalendar format', [
                'user_id' => $user->id,
                'final_count' => $googleEvents->count()
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

    /**
     * カテゴリー名に応じた色を取得
     */
    private function getCategoryColor($categoryName)
    {
        $colors = [
            '祭り' => '#9C27B0',        // 紫色
            '音楽イベント' => '#E91E63',  // ピンク色
            '展示会' => '#2196F3',      // 青色
            'スポーツイベント' => '#4CAF50', // 緑色
            '式典' => '#FF9800',        // オレンジ色
            'RSS配信' => '#607D8B',     // グレー色
        ];

        return $colors[$categoryName] ?? '#4ECDC4'; // デフォルトはティール色
    }
}