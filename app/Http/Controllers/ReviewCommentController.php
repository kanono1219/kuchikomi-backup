<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewCommentController extends Controller
{
    /**
     * コンストラクタ - 認証必須
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * レビューにコメントを追加
     */
    public function store(Request $request, Review $review)
    {
        // バリデーション
        $validated = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required|max:1000',
        ], [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'body.required' => 'コメント内容を入力してください',
            'body.max' => 'コメントは1000文字以内で入力してください',
        ]);

        // コメントを作成
        $comment = $review->comments()->create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'コメントを投稿しました');
    }

    /**
     * コメントの編集画面表示
     */
    public function edit(ReviewComment $comment)
    {
        // 自分のコメントかチェック
        if ($comment->user_id !== Auth::id()) {
            abort(403, 'この操作は許可されていません。');
        }

        return view('review_comments.edit', compact('comment'));
    }

    /**
     * コメントの更新
     */
    public function update(Request $request, ReviewComment $comment)
    {
        // 自分のコメントかチェック
        if ($comment->user_id !== Auth::id()) {
            return back()->with('error', 'この操作は許可されていません。');
        }

        // バリデーション
        $validated = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required|max:1000',
        ], [
            'title.required' => 'タイトルを入力してください',
            'body.required' => 'コメント内容を入力してください',
        ]);

        // 更新
        $comment->update($validated);

        // イベント詳細ページにリダイレクト
        return redirect()
            ->route('events.show', $comment->review->event_id)
            ->with('success', 'コメントを更新しました');
    }

    /**
     * コメントの削除
     */
    public function destroy(ReviewComment $comment)
    {
        // 自分のコメントかチェック
        if ($comment->user_id !== Auth::id()) {
            return back()->with('error', 'この操作は許可されていません。');
        }

        // イベントIDを保存（削除後にリダイレクトするため）
        $eventId = $comment->review->event_id;
        
        // 削除
        $comment->delete();

        return redirect()
            ->route('events.show', $eventId)
            ->with('success', 'コメントを削除しました');
    }
}