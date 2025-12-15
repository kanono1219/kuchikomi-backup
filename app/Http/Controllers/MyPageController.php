<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Event;
use Illuminate\Support\Facades\Log;

class MyPageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Google Calendar 自動同期
        $syncResult = null;
        if ($user->google_calendar_connected && $user->google_calendar_token) {
            try {
                $googleCalendarController = new GoogleCalendarController();
                $syncResult = $googleCalendarController->autoSync();

                if ($syncResult['success'] && $syncResult['imported_count'] > 0) {
                    session()->flash('success', $syncResult['message']);
                }
            } catch (\Exception $e) {
                Log::warning('Auto-sync failed on mypage load', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // エラーは無視してページを表示
            }
        }

        $favoriteEvents = $user->favoriteEvents()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('events.start_date', 'desc')
            ->paginate(10);

        return view('mypage.index', [
            'user' => $user,
            'favoriteEvents' => $favoriteEvents,
            'syncResult' => $syncResult,
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