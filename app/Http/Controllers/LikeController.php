<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Review $review)
    {
        $user = Auth::user();
        
        if (!$review->likes()->where('user_id', $user->id)->exists()) {
            $like = new Like();
            $like->user_id = $user->id;
            $review->likes()->save($like);
        }

        return redirect()->back();
    }

    public function destroy(Review $review)
    {
        $user = Auth::user();
        
        $like = $review->likes()->where('user_id', $user->id)->first();
        
        if ($like) {
            $like->delete();
        }

        return redirect()->back();
    }
}