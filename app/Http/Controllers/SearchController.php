<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Category;
use Carbon\Carbon;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');
        $timeFilter = $request->input('time_filter');

        // 設定ファイルの確認
        $timeFilters = config('app.time_filters', [
            'current' => '開催中',
            'upcoming' => '今後の予定',
            'past' => '過去のイベント'
        ]);

        $events = Event::query();

        if (!empty($query)) {
            $events->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhereHas('category', function ($subQ) use ($query) {
                      $subQ->where('name', 'like', '%' . $query . '%');
                  })
                  ->orWhere('location', 'like', '%' . $query . '%')
                  ->orWhere('address', 'like', '%' . $query . '%');
            });
        }

        if (!empty($timeFilter) && array_key_exists($timeFilter, $timeFilters)) {
            switch ($timeFilter) {
                case 'current':
                    $events->where('start_date', '<=', Carbon::now())
                           ->where('end_date', '>=', Carbon::now());
                    break;
                case 'upcoming':
                    $events->where('start_date', '>', Carbon::now());
                    break;
                case 'past':
                    $events->where('end_date', '<', Carbon::now());
                    break;
            }
        }

        $events = $events->with('category')
                         ->latest('start_date')
                         ->paginate(10);

        return view('search_results', compact('events', 'timeFilters'));
    }
}