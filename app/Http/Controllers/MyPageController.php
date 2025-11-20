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
            ->withAvgRating()
            ->withReviewsCount()
            ->orderBy('events.start_date', 'desc')
            ->paginate(10);

        return view('mypage.index', [
            'user' => $user,
            'favoriteEvents' => $favoriteEvents,
        ]);
    }
}