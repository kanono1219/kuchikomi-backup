<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\BuddyPost;
use Illuminate\Http\Request;

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

    // 他のメソッドも同様に実装
}