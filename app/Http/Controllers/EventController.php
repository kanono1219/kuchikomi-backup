<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\BuddyPost;
use App\Models\Category;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Cloudinary;
use Exception;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * 天気情報を取得
     */
    private function getWeatherData()
    {
        try {
            $apiKey = env('OPENWEATHER_API_KEY');
            
            if (!$apiKey) {
                Log::warning('OPENWEATHER_API_KEY is not set');
                return $this->getDefaultWeatherData();
            }

            $lat = env('OPENWEATHER_LAT', '26.2124');
            $lon = env('OPENWEATHER_LON', '127.6809');
            $lang = 'ja';
            $units = 'metric';

            $cacheKey = 'weather_data_' . md5($lat . $lon);
            $cacheDuration = env('WEATHER_CACHE_DURATION', 1800);

            // キャッシュから取得
            $cachedWeather = Cache::get($cacheKey);
            if ($cachedWeather && is_array($cachedWeather)) {
                return $cachedWeather;
            }

            $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&lang={$lang}&units={$units}";

            $response = @file_get_contents($url);
            
            if ($response === false) {
                Log::warning('Failed to fetch weather data from API');
                return $this->getDefaultWeatherData();
            }

            $data = json_decode($response, true);

            if ($data && isset($data['main']) && isset($data['weather'])) {
                $weatherData = [
                    'temp' => isset($data['main']['temp']) ? round($data['main']['temp']) : null,
                    'feels_like' => isset($data['main']['feels_like']) ? round($data['main']['feels_like']) : null,
                    'temp_min' => isset($data['main']['temp_min']) ? round($data['main']['temp_min']) : null,
                    'temp_max' => isset($data['main']['temp_max']) ? round($data['main']['temp_max']) : null,
                    'humidity' => $data['main']['humidity'] ?? null,
                    'pressure' => $data['main']['pressure'] ?? null,
                    'description' => $data['weather'][0]['description'] ?? '',
                    'weather' => $this->mapWeatherCondition($data['weather'][0]['main'] ?? 'Clouds'),
                    'wind_speed' => $data['wind']['speed'] ?? 0,
                    'clouds' => $data['clouds']['all'] ?? 0,
                ];

                // キャッシュに保存
                Cache::put($cacheKey, $weatherData, $cacheDuration);

                return $weatherData;
            }

            return $this->getDefaultWeatherData();
        } catch (Exception $e) {
            Log::warning('Failed to fetch weather data', [
                'error' => $e->getMessage()
            ]);

            return $this->getDefaultWeatherData();
        }
    }

    /**
     * 天気の条件をマッピング
     */
    private function mapWeatherCondition($condition)
    {
        $conditionMap = [
            'Clear' => 'sunny',
            'Clouds' => 'cloudy',
            'Rain' => 'rainy',
            'Drizzle' => 'rainy',
            'Thunderstorm' => 'rainy',
            'Snow' => 'snowy',
            'Mist' => 'cloudy',
            'Smoke' => 'cloudy',
            'Haze' => 'cloudy',
            'Dust' => 'cloudy',
            'Fog' => 'cloudy',
            'Sand' => 'cloudy',
            'Ash' => 'cloudy',
            'Squall' => 'rainy',
            'Tornado' => 'rainy',
        ];

        return $conditionMap[$condition] ?? 'cloudy';
    }

    /**
     * デフォルトの天気データを返す
     */
    private function getDefaultWeatherData()
    {
        return [
            'temp' => null,
            'feels_like' => null,
            'temp_min' => null,
            'temp_max' => null,
            'humidity' => null,
            'pressure' => null,
            'description' => '天気情報を取得できません',
            'weather' => 'cloudy',
            'wind_speed' => 0,
            'clouds' => 0,
        ];
    }

    /**
     * 天気情報に基づいておすすめイベントを取得
     */
    private function getRecommendedEvents($weatherData)
    {
        try {
            $today = Carbon::now()->startOfDay();
            $todayEnd = Carbon::now()->endOfDay();

            $query = Event::with('category')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->where('deleted_at', null)
                ->whereBetween('start_date', [$today, $todayEnd]);

            // 天気に基づいてフィルタリング
            if ($weatherData['weather'] === 'sunny') {
                // 晴れの日は屋外イベントをおすすめ
                $query->where(function($q) {
                    $q->where('location', 'like', '%屋外%')
                      ->orWhere('location', 'like', '%公園%')
                      ->orWhere('location', 'like', '%ビーチ%');
                });
            } elseif ($weatherData['weather'] === 'rainy' || $weatherData['weather'] === 'snowy') {
                // 雨や雪の日は室内イベントをおすすめ
                $query->where(function($q) {
                    $q->where('location', 'like', '%室内%')
                      ->orWhere('location', 'like', '%館%')
                      ->orWhere('location', 'like', '%ホール%');
                });
            }

            return $query->limit(6)->get();
        } catch (Exception $e) {
            Log::warning('Failed to get recommended events', [
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * 最新のイベントを取得
     */
    private function getLatestEvents()
    {
        try {
            return Event::with('category')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->where('deleted_at', null)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (Exception $e) {
            Log::warning('Failed to get latest events', [
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * 人気のイベントを取得（評価が高い順）
     */
    private function getPopularEvents()
    {
        try {
            return Event::with('category')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->where('deleted_at', null)
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->limit(10)
                ->get();
        } catch (Exception $e) {
            Log::warning('Failed to get popular events', [
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * イベント一覧を表示
     */
    public function index()
    {
        try {
            // Google Calendar 自動同期（認証済みユーザーのみ）
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->google_calendar_connected && $user->google_calendar_token) {
                    try {
                        $googleCalendarController = new GoogleCalendarController();
                        $syncResult = $googleCalendarController->autoSync();

                        if ($syncResult['success'] && $syncResult['imported_count'] > 0) {
                            session()->flash('success', $syncResult['message']);
                        }
                    } catch (Exception $e) {
                        Log::warning('Auto-sync failed on events index', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                        // エラーは無視してページを表示
                    }
                }
            }

            $events = Event::with('category')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->where('deleted_at', null)
                ->paginate(12);

            // 天気情報を取得
            $weatherData = $this->getWeatherData();

            // 今日のおすすめイベントを取得
            $recommendedEvents = $this->getRecommendedEvents($weatherData);

            // 最新のイベントを取得
            $latestEvents = $this->getLatestEvents();

            // 人気のイベントを取得
            $popularEvents = $this->getPopularEvents();

            return view('events.index', compact('events', 'weatherData', 'recommendedEvents', 'latestEvents', 'popularEvents'));
        } catch (Exception $e) {
            Log::error('Error in events.index', [
                'error' => $e->getMessage()
            ]);

            // エラーが発生してもデフォルト値で表示
            return view('events.index', [
                'events' => Event::with('category')
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->where('deleted_at', null)
                    ->paginate(12),
                'weatherData' => $this->getDefaultWeatherData(),
                'recommendedEvents' => collect(),
                'latestEvents' => collect(),
                'popularEvents' => collect()
            ]);
        }
    }

    /**
     * イベント詳細を表示
     */
    public function show(Event $event)
    {
        try {
            // イベント情報をロード
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
            
            // デフォルト位置を設定
            if (!$event->latitude || !$event->longitude) {
                $event->latitude = 26.2124;
                $event->longitude = 127.6809;
            }
        
            // お気に入いフラグを取得
            $isFavorited = false;
            if (Auth::check()) {
                $isFavorited = Auth::user()->favoriteEvents()
                    ->where('event_id', $event->id)
                    ->exists();
            }

            $event->loadCount('favorites');
            
            // ユーザーが参加しているバディ募集を取得
            $userParticipatingPosts = collect([]);
            if (Auth::check()) {
                $userParticipatingPosts = $event->buddyPosts()
                    ->whereHas('participants', function($query) {
                        $query->where('user_id', Auth::id());
                    })
                    ->pluck('id');
            }
        
            return view('events.show', compact('event', 'reviews', 'isFavorited', 'userParticipatingPosts'));
        } catch (Exception $e) {
            Log::error('Error in events.show', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('events.index')
                ->with('error', 'イベント詳細の読み込みに失敗しました');
        }
    }

    /**
     * イベント作成フォームを表示
     */
    public function create()
    {
        try {
            $categories = Category::all();
            return view('events.create', compact('categories'));
        } catch (Exception $e) {
            Log::error('Error in events.create', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'フォームの読み込みに失敗しました');
        }
    }

    /**
     * ★修正版★ イベントを保存（create.blade.php のネスト構造に対応）
     */
    public function store(Request $request)
    {
        try {
            Log::info('Event store request received', [
                'user_id' => Auth::id(),
                'all_input' => $request->all()
            ]);

            // ★修正★ バリデーション（create.blade.php の event[*] ネスト構造に対応）
            $validated = $request->validate([
                'event.name'          => 'required|string|max:255',
                'event.overview'      => 'required|string|max:1000',
                'event.category_id'   => 'required|exists:categories,id',
                'event.location'      => 'required|string|max:255',
                'event.address'       => 'nullable|string|max:255',
                'event.venue_type'    => 'nullable|in:indoor,outdoor',
                'event.latitude'      => 'nullable|numeric',
                'event.longitude'     => 'nullable|numeric',
                'event.external_url'  => 'nullable|url',
                'event.start_date'    => 'required|date_format:Y-m-d\TH:i|after:now',
                'event.end_date'      => 'required|date_format:Y-m-d\TH:i|after:event.start_date',
                'image'               => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'event.name.required' => 'イベント名は必須です',
                'event.overview.required' => '概要は必須です',
                'event.category_id.required' => 'カテゴリーは必須です',
                'event.category_id.exists' => '指定されたカテゴリーが見つかりません',
                'event.location.required' => '場所は必須です',
                'event.start_date.required' => '開始日時は必須です',
                'event.start_date.date_format' => '開始日時の形式が正しくありません',
                'event.start_date.after' => '開始日時は現在より後でなければなりません',
                'event.end_date.required' => '終了日時は必須です',
                'event.end_date.date_format' => '終了日時の形式が正しくありません',
                'event.end_date.after' => '終了日時は開始日時より後でなければなりません',
                'image.image' => '画像ファイルをアップロードしてください',
                'image.max' => '画像は2MB以下である必要があります',
            ]);

            // ネストされたデータをフラット化
            $eventData = $validated['event'];
            $eventData['user_id'] = Auth::id();

            // datetime-local 形式 (Y-m-d\TH:i) を Y-m-d H:i に変換
            if (isset($eventData['start_date'])) {
                $eventData['start_date'] = str_replace('T', ' ', $eventData['start_date']);
            }
            if (isset($eventData['end_date'])) {
                $eventData['end_date'] = str_replace('T', ' ', $eventData['end_date']);
            }

            // 画像をアップロード
            if ($request->hasFile('image')) {
                try {
                    $image_url = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();
                    $eventData['image_url'] = $image_url;
                    Log::info('Image uploaded successfully', ['url' => $image_url]);
                } catch (Exception $e) {
                    Log::error('Image upload failed', ['error' => $e->getMessage()]);
                    // 画像エラーはスキップして続行
                }
            }

            Log::info('Creating event with validated data', [
                'event_data' => $eventData
            ]);

            $event = Event::create($eventData);

            Log::info('Event created successfully', [
                'event_id' => $event->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('events.show', $event)
                ->with('success', 'イベントが作成されました🎉');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // バリデーションエラーをログに記録
            Log::warning('Event store validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors()
            ]);

            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'イベント作成に失敗しました。入力内容を確認してください。');

        } catch (Exception $e) {
            Log::error('Error in events.store', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'イベント作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * イベント編集フォームを表示
     */
    public function edit(Event $event)
    {
        try {
            if ($event->user_id !== Auth::id()) {
                return redirect()->route('events.show', $event)
                    ->with('error', 'このイベントは編集できません');
            }

            $categories = Category::all();
            return view('events.edit', compact('event', 'categories'));
        } catch (Exception $e) {
            Log::error('Error in events.edit', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'フォームの読み込みに失敗しました');
        }
    }

    /**
     * イベントを更新
     */
    public function update(Request $request, Event $event)
    {
        try {
            if ($event->user_id !== Auth::id()) {
                return redirect()->route('events.show', $event)
                    ->with('error', 'このイベントは更新できません');
            }

            $validated = $request->validate([
                'event.name'          => 'required|string|max:255',
                'event.overview'      => 'required|string|max:1000',
                'event.category_id'   => 'required|exists:categories,id',
                'event.location'      => 'required|string|max:255',
                'event.start_date'    => 'required|date_format:Y-m-d\TH:i',
                'event.end_date'      => 'required|date_format:Y-m-d\TH:i|after:event.start_date',
                'image'               => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // ネストされたデータをフラット化
            $eventData = $validated['event'];

            // datetime-local 形式を変換
            if (isset($eventData['start_date'])) {
                $eventData['start_date'] = str_replace('T', ' ', $eventData['start_date']);
            }
            if (isset($eventData['end_date'])) {
                $eventData['end_date'] = str_replace('T', ' ', $eventData['end_date']);
            }

            // 画像をアップロード
            if ($request->hasFile('image')) {
                try {
                    $image_url = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();
                    $eventData['image_url'] = $image_url;
                } catch (Exception $e) {
                    Log::error('Image upload failed', ['error' => $e->getMessage()]);
                    // 画像エラーはスキップして続行
                }
            }

            $event->update($eventData);

            Log::info('Event updated successfully', [
                'event_id' => $event->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('events.show', $event)
                ->with('success', 'イベントが更新されました');
        } catch (Exception $e) {
            Log::error('Error in events.update', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'イベント更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * イベントを削除
     */
    public function delete(Event $event)
    {
        try {
            if ($event->user_id !== Auth::id()) {
                return redirect()->route('events.show', $event)
                    ->with('error', 'このイベントは削除できません');
            }

            $event->delete();

            Log::info('Event deleted successfully', [
                'event_id' => $event->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('events.index')
                ->with('success', 'イベントが削除されました');
        } catch (Exception $e) {
            Log::error('Error in events.destroy', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'イベント削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * イベントをお気に入いに追加/削除
     */
    public function favorite(Request $request, Event $event)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ログインしてください'
                ], 401);
            }

            // toggle() でお気に入い状態を切り替え
            $toggleResult = $user->favoriteEvents()->toggle($event->id);

            // toggle() の戻り値を解析
            $wasFavorited = !empty($toggleResult['attached']);

            Log::info('Favorite toggled', [
                'user_id' => $user->id,
                'event_id' => $event->id,
                'is_favorited' => $wasFavorited
            ]);

            // Googleカレンダーに自動追加/削除
            $googleCalendarMessage = '';
            if ($user->google_calendar_connected && $user->google_calendar_token) {
                try {
                    if ($wasFavorited) {
                        // お気に入り追加時：Googleカレンダーにも追加
                        $googleCalendarController = new GoogleCalendarController();
                        $response = $googleCalendarController->addEventToCalendar($event);
                        $responseData = $response->getData();

                        if ($responseData->success ?? false) {
                            $googleCalendarMessage = ' & Googleカレンダーに追加されました';
                            Log::info('Event auto-added to Google Calendar', [
                                'event_id' => $event->id,
                                'user_id' => $user->id
                            ]);
                        }
                    } else {
                        // お気に入り削除時：Googleカレンダーからも削除
                        if ($event->google_calendar_event_id) {
                            $googleCalendarController = new GoogleCalendarController();
                            $response = $googleCalendarController->removeEventFromCalendar($event);
                            $responseData = $response->getData();

                            if ($responseData->success ?? false) {
                                $googleCalendarMessage = ' & Googleカレンダーから削除されました';
                                Log::info('Event auto-removed from Google Calendar', [
                                    'event_id' => $event->id,
                                    'user_id' => $user->id
                                ]);
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('Google Calendar auto-sync failed', [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    // Googleカレンダー連携のエラーは無視（お気に入り自体は成功）
                }
            }

            // 最新のお気に入い数を取得
            $event->load('favorites');
            $favoritesCount = $event->favorites ? $event->favorites->count() : 0;

            $message = $wasFavorited
                ? '❤️ お気に入いに追加しました' . $googleCalendarMessage
                : '💔 お気に入いから削除しました' . $googleCalendarMessage;

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'isFavorited' => $wasFavorited,
                    'favoritesCount' => $favoritesCount,
                    'message' => $message
                ]);
            }

            return redirect()->route('events.show', $event)
                ->with('success', $message);

        } catch (Exception $e) {
            Log::error('Error toggling favorite', [
                'event_id' => $event->id,
                'user_id' => Auth::id() ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'エラーが発生しました'
                ], 500);
            }

            return redirect()->route('events.show', $event)
                ->with('error', 'エラーが発生しました');
        }
    }

    /**
     * バディ募集を投稿
     */
    public function storeBuddyPost(Request $request, Event $event)
    {
        try {
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

            return back()->with('success', 'バディ募集を投稿しました！');
        } catch (Exception $e) {
            Log::error('Error storing buddy post', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'バディ募集の投稿に失敗しました');
        }
    }

    /**
     * バディ募集に参加
     */
    public function joinBuddyPost(Event $event, BuddyPost $buddyPost)
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
            Log::error('Error joining buddy post', [
                'buddy_post_id' => $buddyPost->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '参加リクエストに失敗しました');
        }
    }

    /**
     * バディ募集を削除
     */
    public function deleteBuddyPost(Event $event, BuddyPost $buddyPost)
    {
        try {
            if ($buddyPost->user_id !== Auth::id()) {
                return back()->with('error', 'この操作は許可されていません。');
            }

            $buddyPost->delete();

            return back()->with('success', '募集を削除しました。');
        } catch (Exception $e) {
            Log::error('Error deleting buddy post', [
                'buddy_post_id' => $buddyPost->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '募集削除に失敗しました');
        }
    }

    /**
     * バディ募集を更新
     */
    public function updateBuddyPost(Request $request, Event $event, BuddyPost $buddyPost)
    {
        try {
            if ($buddyPost->user_id !== Auth::id()) {
                return back()->with('error', 'この操作は許可されていません。');
            }

            $validated = $request->validate([
                'status' => 'required|in:open,closed',
            ]);

            $buddyPost->update($validated);

            return back()->with('success', '募集を更新しました。');
        } catch (Exception $e) {
            Log::error('Error updating buddy post', [
                'buddy_post_id' => $buddyPost->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '募集更新に失敗しました');
        }
    }

    /**
     * バディ募集への参加リクエストに対応
     */
    public function respondToJoinRequest(Event $event, BuddyPost $buddyPost, Request $request)
    {
        try {
            if ($buddyPost->user_id !== Auth::id()) {
                return back()->with('error', 'この操作は許可されていません。');
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'status' => 'required|in:approved,rejected',
            ]);

            $buddyPost->participants()->updateExistingPivot($validated['user_id'], [
                'status' => $validated['status']
            ]);

            $statusText = $validated['status'] === 'approved' ? '承認' : '拒否';
            return back()->with('success', "参加リクエストを{$statusText}しました。");
        } catch (Exception $e) {
            Log::error('Error responding to join request', [
                'buddy_post_id' => $buddyPost->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '対応に失敗しました');
        }
    }
}