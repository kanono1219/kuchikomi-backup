<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Event;
use App\Models\GoogleCalendarEvent;
use App\Models\RssFeed;
use Illuminate\Support\Facades\Log;

class MyPageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Eager load relationships to prevent N+1 queries
        $user->load([
            'buddyPosts.event',
            'buddyPosts.participants',
            'participatingBuddyPosts.event'
        ]);

        // Google Calendar 自動同期
        $syncResult = null;
        if ($user->google_calendar_connected && $user->google_calendar_token) {
            try {
                $googleCalendarController = new GoogleCalendarController();
                $syncResult = $googleCalendarController->autoSync();

                if ($syncResult['success'] && $syncResult['imported_count'] > 0) {
                    session()->flash('success', $syncResult['message']);
                } elseif (!$syncResult['success']) {
                    // エラーメッセージを表示
                    session()->flash('error', 'Google Calendar同期エラー: ' . $syncResult['message']);
                }
            } catch (\Exception $e) {
                Log::error('Auto-sync failed on mypage load', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // エラーをユーザーに表示
                session()->flash('error', 'Google Calendar自動同期に失敗しました: ' . $e->getMessage());
            }
        }

        $favoriteEvents = $user->favoriteEvents()
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('events.start_date', 'desc')
            ->paginate(10);

        // Googleカレンダーイベント数を取得
        $googleCalendarEventsCount = 0;
        if ($user->google_calendar_connected) {
            $googleCalendarEventsCount = GoogleCalendarEvent::where('user_id', $user->id)->count();
        }

        // 管理者の場合、RSS配信情報を取得
        $rssFeeds = null;
        if ($user->is_admin) {
            $rssFeeds = RssFeed::with('category')->orderBy('created_at', 'desc')->get();
        }

        return view('mypage.index', [
            'user' => $user,
            'favoriteEvents' => $favoriteEvents,
            'syncResult' => $syncResult,
            'googleCalendarEventsCount' => $googleCalendarEventsCount,
            'rssFeeds' => $rssFeeds,
        ]);
    }

    public function updateNotificationSettings(Request $request)
    {
        $request->validate([
            'notification_days_before' => 'nullable|integer|min:0|max:30',
        ]);

        $user = $request->user();
        $user->update([
            'notification_days_before' => $request->input('notification_days_before'),
        ]);

        return redirect()->route('mypage')
            ->with('success', '通知設定を更新しました');
    }
}