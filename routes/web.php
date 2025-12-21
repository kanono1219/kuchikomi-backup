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
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\RssImporterController;
use App\Http\Controllers\RssFeedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ========== ホームページ（認証不要） ==========
Route::get('/', [EventController::class, 'index'])->name('index');

// ========== 検索・カテゴリー関連（認証不要） ==========
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/categories/{category}', [CategoryController::class, 'index'])->name('categories.show');

// ========== ★カレンダー関連★（認証不要で表示・API取得可能） ==========
Route::prefix('calendar')->name('calendar.')->group(function () {
    // カレンダービュー表示（認証不要）
    Route::get('/', [CalendarController::class, 'index'])->name('index');

    // ★カレンダーAPI取得（認証不要）★
    // このルートはゲストユーザーもアクセス可能
    Route::get('/events', [CalendarController::class, 'getEvents'])->name('getEvents');

    // Google Calendar API（認証必須）
    Route::middleware('auth')->group(function () {
        Route::get('/google-events', [CalendarController::class, 'getGoogleCalendarEvents'])->name('getGoogleCalendarEvents');
        Route::get('/sync-status', [CalendarController::class, 'getSyncStatus'])->name('syncStatus');
    });
});

// ========== ダッシュボード（認証必須） ==========
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ========== ユーザープロフィール（認証必須） ==========
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ========== マイページ（認証必須） ==========
Route::middleware('auth')->group(function () {
    Route::get('/mypage', [MyPageController::class, 'index'])->name('mypage');
    Route::post('/mypage/notification-settings', [MyPageController::class, 'updateNotificationSettings'])->name('mypage.notification-settings.update');
});

// ========== イベント管理ルート ==========
Route::prefix('events')->name('events.')->group(function () {
    // イベント一覧（認証不要）
    Route::get('/', [EventController::class, 'index'])->name('index');

    // 認証が必要なイベント操作
    Route::middleware('auth')->group(function () {
        // ★イベント作成（GETが最優先）★
        Route::get('/create', [EventController::class, 'create'])->name('create');

        // イベント保存
        Route::post('/', [EventController::class, 'store'])->name('store');

        // ★イベント編集（GETが show より前）★
        Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');

        // イベント更新
        Route::put('/{event}', [EventController::class, 'update'])->name('update');

        // イベント削除
        Route::delete('/{event}', [EventController::class, 'delete'])->name('delete');

        // ========== お気に入い機能 ==========
        Route::post('/{event}/favorite', [EventController::class, 'favorite'])->name('favorite');
        Route::delete('/{event}/unfavorite', [EventController::class, 'unfavorite'])->name('unfavorite');

        // ========== バディ募集関連 ==========
        Route::prefix('{event}/buddy-posts')->name('buddy-posts.')->group(function () {
            Route::post('/', [EventController::class, 'storeBuddyPost'])->name('store');
            Route::post('/{buddyPost}/join', [EventController::class, 'joinBuddyPost'])->name('join');
            Route::delete('/{buddyPost}', [EventController::class, 'deleteBuddyPost'])->name('delete');
            Route::patch('/{buddyPost}', [EventController::class, 'updateBuddyPost'])->name('update');
            Route::post('/{buddyPost}/respond', [EventController::class, 'respondToJoinRequest'])->name('respond');
        });
    });

    // ★イベント詳細（ワイルドカード - 最後に定義）★
    Route::get('/{event}', [EventController::class, 'show'])->name('show');
});

// ========== バディポスト関連（認証必須） ==========
Route::middleware('auth')->group(function () {
    Route::post('/events/{event}/buddy-posts', [BuddyPostController::class, 'store'])->name('buddy-posts.store');
    Route::post('/events/{event}/buddy-posts/{buddyPost}/join', [BuddyPostController::class, 'join'])->name('buddy-posts.join');
    Route::delete('/events/{event}/buddy-posts/{buddyPost}', [BuddyPostController::class, 'destroy'])->name('buddy-posts.destroy');

    Route::post('/buddy-posts/{buddyPost}/chat', [ChatController::class, 'createRoom'])->name('buddy-posts.chat.create');
});

// ========== レビュー関連（認証必須） ==========
Route::middleware('auth')->prefix('reviews')->name('reviews.')->group(function () {
    Route::post('/events/{event}', [ReviewController::class, 'store'])->name('store');
    Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');
    Route::put('/{review}', [ReviewController::class, 'update'])->name('update');
    Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
});

// ========== レビューコメント関連（認証必須） ==========
Route::middleware('auth')->prefix('review-comments')->name('review-comments.')->group(function () {
    Route::post('/reviews/{review}', [ReviewCommentController::class, 'store'])->name('store');
    Route::get('/{comment}/edit', [ReviewCommentController::class, 'edit'])->name('edit');
    Route::put('/{comment}', [ReviewCommentController::class, 'update'])->name('update');
    Route::delete('/{comment}', [ReviewCommentController::class, 'destroy'])->name('destroy');
});

// ========== いいね機能（認証必須） ==========
Route::middleware('auth')->group(function () {
    Route::post('/reviews/{review}/like', [LikeController::class, 'store'])->name('likes.store');
    Route::delete('/reviews/{review}/like', [LikeController::class, 'destroy'])->name('likes.destroy');
});

// ========== チャット関連（認証必須） ==========
Route::middleware('auth')->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::get('/{chatRoom}', [ChatController::class, 'show'])->name('show');
    Route::post('/{chatRoom}/messages', [ChatController::class, 'store'])->name('store');
});

// ========== Google Calendar 統合（認証必須） ==========
Route::middleware('auth')->prefix('google-calendar')->name('google-calendar.')->group(function () {
    Route::get('/auth', [GoogleCalendarController::class, 'authenticate'])->name('authenticate');
    Route::get('/callback', [GoogleCalendarController::class, 'callback'])->name('callback');
    Route::post('/add-event/{event}', [GoogleCalendarController::class, 'addEventToCalendar'])->name('add-event');
    Route::post('/remove-event/{event}', [GoogleCalendarController::class, 'removeEventFromCalendar'])->name('remove-event');
    Route::get('/status', [GoogleCalendarController::class, 'getConnectionStatus'])->name('status');
    Route::post('/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('disconnect');
    Route::get('/events', [GoogleCalendarController::class, 'fetchEvents'])->name('events');
    Route::post('/import-event', [GoogleCalendarController::class, 'importEvent'])->name('import-event');
});

// ========== RSSインポーター（認証必須） ==========
Route::middleware('auth')->prefix('rss-importer')->name('rss-importer.')->group(function () {
    Route::get('/', [RssImporterController::class, 'index'])->name('index');
    Route::post('/preview', [RssImporterController::class, 'preview'])->name('preview');
    Route::post('/import', [RssImporterController::class, 'import'])->name('import');
});

// ========== 管理者専用ルート（管理者権限必須） ==========
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // RSS配信管理
    Route::resource('rss-feeds', RssFeedController::class);
    Route::post('/rss-feeds/{rssFeed}/fetch', [RssFeedController::class, 'fetch'])->name('rss-feeds.fetch');
});

// ========== Breeze 認証ルート ==========
require __DIR__.'/auth.php';