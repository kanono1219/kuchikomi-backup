<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Event;

class MyPageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $favoriteEvents = $user->favoriteEvents()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('events.start_date', 'desc')
            ->paginate(10);

        return view('mypage.index', [
            'user' => $user,
            'favoriteEvents' => $favoriteEvents,
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