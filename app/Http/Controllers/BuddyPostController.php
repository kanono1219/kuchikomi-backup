<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\BuddyPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class BuddyPostController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'message' => 'required',
            'preferred_age' => 'nullable|string',
            'max_participants' => 'required|integer|min:1|max:10',
            'meeting_point' => 'nullable|string|max:255',
        ]);

        $event->buddyPosts()->create([
            'user_id' => auth()->id(),
            ...$validated
        ]);

        return back()->with('success', '募集を投稿しました！');
    }

    public function join(Event $event, BuddyPost $buddyPost)
    {
        try {
            if ($buddyPost->status === 'closed') {
                return back()->with('error', 'この募集は終了しています。');
            }

            if ($buddyPost->participants->contains(Auth::id())) {
                return back()->with('error', '既に参加リクエスト済みです。');
            }

            if ($buddyPost->participants()->count() >= $buddyPost->max_participants) {
                return back()->with('error', '募集人数が上限に達しました。');
            }

            $buddyPost->participants()->attach(Auth::id(), [
                'status' => 'pending'
            ]);

            return back()->with('success', '参加リクエストを送信しました！');
        } catch (Exception $e) {
            return back()->with('error', '参加リクエストの送信に失敗しました。');
        }
    }

    // 他のメソッドも同様に実装
}