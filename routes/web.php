<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewCommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\BuddyPostController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\Mypage\NotificationSettingsController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\RssImporterController;
use App\Http\Controllers\GoogleCalendarController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ホーム画面は認証が終わっていないユーザーも見れるようにする
Route::get('/', [EventController::class, 'index'])->name('index');

Route::get('/api/weather', [WeatherController::class, 'getWeather'])->name('weather.get');

Route::middleware(['auth'])->prefix('mypage')->name('mypage.')->group(function () {
    Route::patch('/notification-settings', [NotificationSettingsController::class, 'update'])
        ->name('notification-settings.update');
});

Route::middleware(['auth'])->group(function () {
    // バディ投稿基本操作
    Route::post('/events/{event}/buddy-posts', [BuddyPostController::class, 'store'])->name('buddy-posts.store');
    Route::post('/events/{event}/buddy-posts/{buddyPost}/join', [BuddyPostController::class, 'join'])->name('buddy-posts.join');
    Route::delete('/events/{event}/buddy-posts/{buddyPost}', [BuddyPostController::class, 'destroy'])->name('buddy-posts.destroy');
    
    // チャット関連のルート
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', function() {
            \Log::info('Chat index route hit');
            return app(ChatController::class)->index();
        })->name('index');
        Route::get('/{chatRoom}', [ChatController::class, 'show'])->name('show');
        Route::post('/{chatRoom}/messages', [ChatController::class, 'store'])->name('store');
    });
    
    // バディポスト関連のチャットルーム作成
    Route::post('/buddy-posts/{buddyPost}/chat', [ChatController::class, 'createRoom'])
        ->name('buddy-posts.chat.create');

    // イベント関連のルート
    Route::prefix('events')->group(function () {
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/create', [EventController::class, 'create'])->name('create');
        Route::get('/{event}', [EventController::class, 'show'])->name('events.show');
        Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventController::class, 'delete'])->name('delete');

        // バディ募集関連のルート
        Route::prefix('{event}/buddy-posts')->group(function () {
            Route::post('/', [EventController::class, 'storeBuddyPost'])
                ->name('events.buddy-posts.store');
            Route::post('/{buddyPost}/join', [EventController::class, 'joinBuddyPost'])
                ->name('events.buddy-posts.join');
            Route::delete('/{buddyPost}', [EventController::class, 'deleteBuddyPost'])
                ->name('events.buddy-posts.delete');
            Route::patch('/{buddyPost}', [EventController::class, 'updateBuddyPost'])
                ->name('events.buddy-posts.update');
            Route::post('/{buddyPost}/respond', [EventController::class, 'respondToJoinRequest'])
                ->name('events.buddy-posts.respond');
        });

        // イベントお気に入り
        Route::post('/{event}/favorite', [EventController::class, 'favorite'])->name('events.favorite');
        Route::delete('/{event}/unfavorite', [EventController::class, 'unfavorite'])->name('events.unfavorite');
    });

    // レビュー関連のルート
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::post('/events/{event}', [ReviewController::class, 'store'])->name('store');
        Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');
        Route::put('/{review}', [ReviewController::class, 'update'])->name('update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
    });

    // レビューコメント関連のルート
    Route::prefix('review-comments')->name('review-comments.')->group(function () {
        Route::post('/reviews/{review}', [ReviewCommentController::class, 'store'])
            ->name('store');
        Route::get('/{comment}/edit', [ReviewCommentController::class, 'edit'])
            ->name('edit');
        Route::put('/{comment}', [ReviewCommentController::class, 'update'])
            ->name('update');
        Route::delete('/{comment}', [ReviewCommentController::class, 'destroy'])
            ->name('destroy');
    });

    // いいね関連のルート
    Route::post('/reviews/{review}/like', [LikeController::class, 'store'])->name('likes.store');
    Route::delete('/reviews/{review}/like', [LikeController::class, 'destroy'])->name('likes.destroy');

    // マイページ
    Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage');

    // ★RSS Importer 関連のルート★
    Route::prefix('rss-importer')->name('rss-importer.')->group(function () {
        Route::get('/', [RssImporterController::class, 'index'])->name('index');
        Route::post('/preview', [RssImporterController::class, 'preview'])->name('preview');
        Route::post('/import', [RssImporterController::class, 'import'])->name('import');
    });

    // ★Google Calendar 関連のルート（新規追加）★
    Route::prefix('google-calendar')->name('google-calendar.')->group(function () {
        Route::get('/auth', [GoogleCalendarController::class, 'authenticate'])
            ->name('authenticate');
        Route::get('/callback', [GoogleCalendarController::class, 'callback'])
            ->name('callback');
        Route::post('/add-event/{event}', [GoogleCalendarController::class, 'addEventToCalendar'])
            ->name('add-event');
        Route::post('/remove-event/{event}', [GoogleCalendarController::class, 'removeEventFromCalendar'])
            ->name('remove-event');
        Route::get('/status', [GoogleCalendarController::class, 'getConnectionStatus'])
            ->name('status');
        Route::post('/disconnect', [GoogleCalendarController::class, 'disconnect'])
            ->name('disconnect');
    });
});

// 検索とカテゴリー関連のルート
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/categories/{category}', [CategoryController::class, 'index'])->middleware('auth');

// ダッシュボード
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// プロフィール関連のルート
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// カレンダー関連のルート
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::get('/calendar/events', [CalendarController::class, 'getEvents'])->name('calendar.getEvents');
Route::get('/calendar/events-by-date', [CalendarController::class, 'getEventsByDate'])->name('calendar.getEventsByDate');

require __DIR__.'/auth.php';