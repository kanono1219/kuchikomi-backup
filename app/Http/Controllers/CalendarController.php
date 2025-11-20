<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * カレンダー表示
     */
    public function index()
    {
        return view('calendar.index');
    }

    /**
     * カテゴリーの色マッピングを定義
     */
    private function getCategoryColors()
    {
        return [
            '祭り' => '#FF6B6B',           // 赤
            '音楽イベント' => '#4ECDC4',   // 青緑
            '展示会' => '#FFD93D',         // 黄色
            'スポーツイベント' => '#6BCB77', // 緑
            '式典' => '#9D84B7',           // 紫
            'その他' => '#3B82F6',         // デフォルト青
        ];
    }

    /**
     * カレンダー用のイベント一覧をJSON形式で返す
     */
    public function getEvents(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        // 指定期間のイベントを取得（カテゴリー情報も含める）
        $events = Event::whereBetween('start_date', [$start, $end])
            ->with('category')
            ->select('id', 'name', 'start_date as start', 'end_date as end', 'location', 'category_id')
            ->get();

        $colors = $this->getCategoryColors();

        // FullCalendar用のフォーマットに変換
        $calendarEvents = $events->map(function ($event) use ($colors) {
            // カテゴリー名から色を取得
            $categoryName = $event->category ? $event->category->name : 'その他';
            $color = $colors[$categoryName] ?? '#3B82F6';

            // お気に入りならオレンジ色に変更
            if (Auth::check() && Auth::user()->favoriteEvents()->where('event_id', $event->id)->exists()) {
                $color = '#FF8C00'; // オレンジ（お気に入り）
            }

            return [
                'id' => $event->id,
                'title' => $event->name,
                'start' => $event->start,
                'end' => $event->end,
                'location' => $event->location,
                'url' => route('events.show', $event->id),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#FFFFFF',
            ];
        });

        return response()->json($calendarEvents);
    }

    /**
     * 特定日付のイベント一覧を取得
     * 時間を考慮した日付比較を実施
     */
    public function getEventsByDate(Request $request)
    {
        $date = $request->query('date');

        // 日付文字列を使用して、その日の開始から終了までのイベントを取得
        $events = Event::whereDate('start_date', $date)
            ->with('category', 'user')
            ->withAvgRating()
            ->withReviewsCount()
            ->withCount('favorites')
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json($events);
    }
}