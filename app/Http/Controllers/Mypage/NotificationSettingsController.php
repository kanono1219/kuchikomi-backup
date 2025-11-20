<?php

namespace App\Http\Controllers\Mypage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'event_notifications_enabled' => 'required|boolean',
            'notification_days_before' => 'required|integer|min:1|max:30',
        ]);

        auth()->user()->update([
            'event_notifications_enabled' => $validated['event_notifications_enabled'],
            'notification_days_before' => $validated['notification_days_before'],
        ]);

        return redirect()->back()->with('success', '通知設定を更新しました');
    }
}