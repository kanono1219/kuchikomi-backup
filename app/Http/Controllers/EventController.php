<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\BuddyPost;
use Cloudinary;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
        Paginator::useBootstrap();
    }

    private function getWeatherData()
    {
        $apiKey = env('OPENWEATHERMAP_API_KEY');
        // 沖縄県那覇市の緯度経度
        $lat = 26.2124;
        $lon = 127.6809;
        
        try {
            $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $apiKey,
                'units' => 'metric', // 摂氏温度で取得
                'lang' => 'ja'      // 日本語で取得
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'weather' => $this->determineWeatherStatus($data['weather'][0]['id']),
                    'temp' => round($data['main']['temp']), // 温度を四捨五入
                    'description' => $data['weather'][0]['description']
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Weather API Error: ' . $e->getMessage());
        }
        
        return [
            'weather' => 'rainy',
            'temp' => null,
            'description' => '天気情報を取得できません'
        ];
    }

    private function determineWeatherStatus($weatherId)
    {
        if ($weatherId == 800) {
            return 'sunny';
        } elseif ($weatherId >= 801 && $weatherId <= 804) {
            return 'cloudy';
        }
        return 'rainy';
    }

    public function index()
    {
        $weatherData = $this->getWeatherData();
        \Log::info('Weather Data:', $weatherData);

        // ★修正：今日のイベント（開催日が今日のもののみ）
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        
        $recommendedEvents = Event::whereDate('start_date', '>=', $today)
            ->whereDate('start_date', '<', $tomorrow)
            ->with('category')
            ->withAvgRating()
            ->withReviewsCount()
            ->withCount('favorites')
            ->orderBy('start_date', 'asc')
            ->limit(6)
            ->get();
    
        // 最新のイベント
        $latestEvents = Event::with('category')
            ->withAvgRating()
            ->withReviewsCount()
            ->withCount('favorites')
            ->latest()
            ->take(10)
            ->get();
    
        // 人気のイベント（評価が高い順）
        $popularEvents = Event::with('category')
            ->withAvgRating()
            ->withReviewsCount()
            ->withCount('favorites')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->take(10)
            ->get();
    
        return view('events.index', compact('recommendedEvents', 'latestEvents', 'popularEvents', 'weatherData'));
    }

    public function show(Event $event)
    {
        // イベント情報、カテゴリー、ユーザー、バディ募集情報をロード
        $event->load([
            'category', 
            'user',
            'buddyPosts' => function($query) {
                $query->with(['user', 'participants'])
                      ->orderBy('created_at', 'desc');
            }
        ]);
        
        // コメントとコメント数も一緒に取得
        $reviews = $event->reviews()
            ->with(['user', 'likes', 'comments.user'])
            ->withCount('comments')
            ->latest()
            ->paginate(10);
        
        if (!$event->latitude || !$event->longitude) {
            $event->latitude = 26.2124;
            $event->longitude = 127.6809;
        }
    
        $isFavorited = Auth::check() ? Auth::user()->favoriteEvents()->where('event_id', $event->id)->exists() : false;
        $event->loadCount('favorites');
        
        $userParticipatingPosts = collect([]);
        if (Auth::check()) {
            $userParticipatingPosts = $event->buddyPosts()
                ->whereHas('participants', function($query) {
                    $query->where('user_id', Auth::id());
                })
                ->pluck('id');
        }
    
        return view('events.show', compact('event', 'reviews', 'isFavorited', 'userParticipatingPosts'));
    }

    public function create(Category $category)
    {
        return view('events.create')->with(['categories' => $category->get()]);
    }

    public function store(EventRequest $request)
    {
        $input = $request->validated()['event'];
        $input['user_id'] = Auth::id();
    
        if ($request->hasFile('image')) {
            $image_url = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();
            $input['image_url'] = $image_url;
        }
    
        $event = Event::create($input);
    
        return redirect()->route('events.show', $event)->with('success', 'イベントが作成されました。');
    }

    public function edit(Event $event, Category $category)
    {
        if ($event->user_id !== Auth::id()) {
            return redirect('/events/' . $event->id);
        }
        
        $categories = $category->get();
        return view('events.edit')->with(['event' => $event, 'categories' => $categories]);
    }

    public function update(EventRequest $request, Event $event)
    {
        if ($event->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    
        $input_event = $request->validated()['event'];
    
        if($request->file('image')){
            $image_url = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();
            $input_event['image_url'] = $image_url;
        }
    
        $event->fill($input_event)->save();
    
        return redirect()->route('events.show', $event)->with('success', 'イベントが更新されました。');
    }

    public function delete(Event $event)
    {
        if ($event->user_id !== Auth::id()) {
            return redirect('/events/' . $event->id);
        }
        
        $event->delete();
        return redirect('/')->with('success', 'イベントが削除されました。');
    }

    public function favorite(Request $request, Event $event)
    {
        $user = Auth::user();
        $isFavorited = $user->favoriteEvents()->toggle($event->id);
        $event->loadCount('favorites');
        
        // お気に入りに追加された場合、Google カレンダーに追加
        if (!empty($isFavorited['attached'])) {
            // Google カレンダーに追加（接続している場合のみ）
            if ($user->google_calendar_connected && $user->google_calendar_token) {
                try {
                    $googleCalendarController = new \App\Http\Controllers\GoogleCalendarController();
                    $googleCalendarController->addEventToCalendar($event);
                } catch (\Exception $e) {
                    \Log::warning('Failed to add event to Google Calendar: ' . $e->getMessage());
                    // エラーになってもお気に入り登録は続行
                }
            }
        } else {
            // お気に入りから削除された場合、Google カレンダーからも削除
            if ($event->google_calendar_event_id) {
                try {
                    $googleCalendarController = new \App\Http\Controllers\GoogleCalendarController();
                    $googleCalendarController->removeEventFromCalendar($event);
                } catch (\Exception $e) {
                    \Log::warning('Failed to remove event from Google Calendar: ' . $e->getMessage());
                }
            }
        }
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'isFavorited' => !empty($isFavorited['attached']),
                'favoritesCount' => $event->favorites_count
            ]);
        }
    
        return back();
    }
    
    public function unfavorite(Request $request, Event $event)
    {
        return $this->favorite($request, $event);
    }

    // バディ募集関連の新規メソッド
    public function storeBuddyPost(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'message' => 'required',
            'preferred_age' => 'nullable|string',
            'max_participants' => 'required|integer|min:1|max:10',
            'meeting_point' => 'nullable|string|max:255',
        ]);

        $event->buddyPosts()->create([
            'user_id' => Auth::id(),
            ...$validated
        ]);

        return back()->with('success', '募集を投稿しました！');
    }

    public function joinBuddyPost(Event $event, BuddyPost $buddyPost)
    {
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
    }

    public function deleteBuddyPost(Event $event, BuddyPost $buddyPost)
    {
        if ($buddyPost->user_id !== Auth::id()) {
            return back()->with('error', 'この操作は許可されていません。');
        }

        $buddyPost->delete();

        return back()->with('success', '募集を削除しました。');
    }

    public function updateBuddyPost(Request $request, Event $event, BuddyPost $buddyPost)
    {
        if ($buddyPost->user_id !== Auth::id()) {
            return back()->with('error', 'この操作は許可されていません。');
        }

        $validated = $request->validate([
            'status' => 'required|in:open,closed',
        ]);

        $buddyPost->update($validated);

        return back()->with('success', '募集を更新しました。');
    }

    public function respondToJoinRequest(Event $event, BuddyPost $buddyPost, Request $request)
    {
        if ($buddyPost->user_id !== Auth::id()) {
            return back()->with('error', 'この操作は許可されていません。');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:approved,rejected',
        ]);

        $buddyPost->participants()->updateExistingPivot($request->user_id, [
            'status' => $validated['status']
        ]);

        $statusText = $validated['status'] === 'approved' ? '承認' : '拒否';
        return back()->with('success', "参加リクエストを{$statusText}しました。");
    }
}