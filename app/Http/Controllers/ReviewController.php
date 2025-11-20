<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{   public function destroy(Review $review)
    {
    if (auth()->id() !== $review->user_id) {
        return redirect()->back()->with('error', '他のユーザーのレビューは削除できません。');
    }

    $review->delete();
    return redirect()->back()->with('success', 'レビューが削除されました。');
    }
    public function store(Request $request, Event $event)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|max:255',
                'body' => 'required',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            $review = new Review([
                'title' => $validatedData['title'],
                'body' => $validatedData['body'],
                'rating' => $validatedData['rating'],
                'event_id' => $event->id,
                'user_id' => auth()->id(),
            ]);

            $event->reviews()->save($review);

            Log::info('Review saved successfully', ['review_id' => $review->id, 'event_id' => $event->id]);

            return redirect()->back()->with('success', 'レビューが投稿されました。');
        } catch (\Exception $e) {
            Log::error('Failed to save review', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'レビューの投稿に失敗しました。')->withInput();
        }
    }
}