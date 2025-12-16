<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Event</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Noto Sans JP', sans-serif;
        }
        .event-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: rgba(59, 130, 246, 0.5);
        }
        .section-title {
            position: relative;
            display: inline-block;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .weather-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 20px;
            border-radius: 50px;
            color: white;
            display: inline-block;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .category-tag {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .category-tag:hover {
            transform: scale(1.05);
        }
        .rating-stars {
            color: #fbbf24;
            font-size: 1.1rem;
        }
        .fc {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .fc .fc-daygrid-day:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
    </style>
</head>
<x-app-layout>
    <body class="font-sans">
        <div class="container mx-auto px-4 py-12 max-w-7xl">
            <!-- フラッシュメッセージ -->
            @if (session('success'))
                <div class="mb-8 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow-md flex items-center justify-between animate-fade-in">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-8 bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg shadow-md flex items-center justify-between animate-fade-in">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            @endif

            <!-- ヘッダーセクション -->
            <div class="text-center mb-16">
                <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                    沖縄県のイベント
                </h1>
                <p class="text-gray-600 text-lg">あなたの興味に合ったイベントを見つけよう</p>
            </div>

            <!-- 天気情報セクション -->
            <div class="mb-12 text-center">
                @if($weatherData['weather'] === 'sunny')
                    <div class="inline-block weather-badge">
                        🌞 天気が良いので外のイベントがおすすめ！
                        @if($weatherData['temp'])
                            <span class="ml-2 font-semibold">気温: {{ $weatherData['temp'] }}℃</span>
                        @endif
                    </div>
                @elseif($weatherData['weather'] === 'cloudy')
                    <div class="inline-block weather-badge">
                        ☁️ 曇りなので室内イベントがおすすめ！
                        @if($weatherData['temp'])
                            <span class="ml-2 font-semibold">気温: {{ $weatherData['temp'] }}℃</span>
                        @endif
                    </div>
                @else
                    <div class="inline-block weather-badge">
                        🌧️ 雨の日は室内イベントがおすすめ！
                        @if($weatherData['temp'])
                            <span class="ml-2 font-semibold">気温: {{ $weatherData['temp'] }}℃</span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- 今日のおすすめイベント -->
            <div class="mb-16">
                <h2 class="section-title mb-8">今日のおすすめイベント</h2>
                
                @if($recommendedEvents->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($recommendedEvents as $event)
                            <div class="event-card rounded-xl overflow-hidden shadow-lg">
                                <!-- 画像 -->
                                <div class="relative h-48 overflow-hidden bg-gradient-to-br from-gray-200 to-gray-300">
                                    @if($event->image_url)
                                        <img src="{{ $event->image_url }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100">
                                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <!-- リボン -->
                                    <div class="absolute top-0 right-0 bg-red-500 text-white px-4 py-2 rounded-bl-lg font-bold text-sm">
                                        本日開催
                                    </div>
                                </div>

                                <!-- コンテンツ -->
                                <div class="p-6">
                                    <h3 class="text-xl font-bold text-gray-800 mb-3 line-clamp-2 hover:text-blue-600 transition">
                                        <a href="/events/{{ $event->id }}">{{ $event->name }}</a>
                                    </h3>

                                    <!-- カテゴリーと評価 -->
                                    <div class="flex items-center justify-between mb-4">
                                        <a href="/categories/{{ $event->category->id }}" class="category-tag bg-blue-100 text-blue-700">
                                            {{ $event->category->name }}
                                        </a>
                                        <div class="flex items-center gap-1">
                                            <span class="rating-stars">★</span>
                                            <span class="text-sm font-semibold text-gray-700">
                                                @if($event->reviews_avg_rating)
                                                    {{ number_format($event->reviews_avg_rating, 1) }}
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-500">({{ $event->reviews_count ?? 0 }})</span>
                                        </div>
                                    </div>

                                    <!-- 詳細情報 -->
                                    <div class="space-y-2 mb-4 text-sm text-gray-600">
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                                            {{ $event->venue_type === 'indoor' ? '室内' : '屋外' }}
                                        </p>
                                        <p class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                                            {{ $event->location }}
                                        </p>
                                    </div>

                                    <p class="text-gray-600 text-sm line-clamp-2 mb-4">{{ $event->overview }}</p>

                                    <!-- ボタン -->
                                    <div class="flex gap-3">
                                        <a href="/events/{{ $event->id }}" class="flex-1 btn-primary text-white font-semibold py-2 px-4 rounded-lg text-center">
                                            詳細を見る
                                        </a>
                                        @auth
                                            @if(Auth::user()->favoriteEvents->contains($event))
                                                <form action="{{ route('events.unfavorite', $event) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="favorite-button text-red-500 hover:text-red-700 p-2">
                                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('events.favorite', $event) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="favorite-button text-gray-400 hover:text-red-500 p-2 transition">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl p-12 text-center shadow-lg">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-gray-500 text-lg">今日はイベントがありません</p>
                    </div>
                @endif
            </div>

            <!-- イベント作成ボタン -->
            <div class="mb-8 text-center">
                @auth
                    <a href="/events/create" class="btn-primary text-white font-bold py-3 px-8 rounded-full inline-block text-lg">
                        ✨ イベントを作成する
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary text-white font-bold py-3 px-8 rounded-full inline-block text-lg">
                        ✨ イベントを作成する
                    </a>
                @endauth
            </div>

            <!-- RSS配信でイベントを作成 -->
            @auth
            <div class="mb-16">
                <div class="max-w-3xl mx-auto bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl shadow-lg p-8 border-2 border-orange-200">
                    <div class="flex items-start gap-6">
                        <!-- アイコン -->
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-amber-500 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7m-6 0a1 1 0 11-2 0 1 1 0 012 0z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- コンテンツ -->
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">
                                📡 RSS配信からイベントをインポート
                            </h3>
                            <p class="text-gray-600 mb-4 leading-relaxed">
                                外部サイトのRSS/AtomフィードURLを入力するだけで、イベント情報を一括で取得・登録できます。手動での入力作業を大幅に削減！
                            </p>

                            <div class="flex flex-wrap gap-3 mb-4">
                                <span class="inline-flex items-center gap-1 text-sm bg-white px-3 py-1 rounded-full text-gray-700 shadow-sm">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    RSS 2.0対応
                                </span>
                                <span class="inline-flex items-center gap-1 text-sm bg-white px-3 py-1 rounded-full text-gray-700 shadow-sm">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Atom対応
                                </span>
                                <span class="inline-flex items-center gap-1 text-sm bg-white px-3 py-1 rounded-full text-gray-700 shadow-sm">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    重複チェック
                                </span>
                                <span class="inline-flex items-center gap-1 text-sm bg-white px-3 py-1 rounded-full text-gray-700 shadow-sm">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    プレビュー機能
                                </span>
                            </div>

                            <a href="{{ route('rss-importer.index') }}"
                               class="inline-flex items-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold py-3 px-6 rounded-full hover:from-orange-600 hover:to-amber-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                RSSインポーターを開く
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endauth

            <!-- カレンダーセクション -->
            <div class="mb-16">
                <h2 class="section-title mb-8">📅 カレンダーから探す</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
                        <div id="calendar"></div>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-800 mb-4">📌 イベント種類</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50">
                                    <div class="w-3 h-3 rounded" style="background-color: #4ECDC4;"></div>
                                    <span class="text-gray-700">🎪 webアプリイベント</span>
                                </div>
                                <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50">
                                    <div class="w-3 h-3 rounded" style="background-color: #FF6B6B;"></div>
                                    <span class="text-gray-700">❤️ お気に入いイベント</span>
                                </div>
                                @auth
                                    @if(auth()->user()->google_calendar_connected)
                                    <div class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 border-t mt-3 pt-3">
                                        <div class="w-3 h-3 rounded" style="background-color: #FF8C00;"></div>
                                        <span class="text-gray-700 font-semibold">📅 Google Calendar</span>
                                    </div>
                                    @endif
                                @endauth
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <span id="selectedDate">本日</span>のイベント
                            </h3>
                            <div id="eventsList" class="space-y-3 max-h-96 overflow-y-auto">
                                <p class="text-gray-500 text-center py-4 text-sm">日付をクリック</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

            <!-- 最新のイベント -->
            <div class="mb-16">
                <h2 class="section-title mb-8">🆕 最新のイベント</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($latestEvents as $event)
                        <div class="event-card rounded-xl overflow-hidden shadow-lg">
                            <div class="relative h-40 bg-gradient-to-br from-gray-200 to-gray-300">
                                @if($event->image_url)
                                    <img src="{{ $event->image_url }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2 hover:text-blue-600">
                                    <a href="/events/{{ $event->id }}">{{ $event->name }}</a>
                                </h3>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="category-tag bg-purple-100 text-purple-700">{{ $event->category->name }}</span>
                                    <div class="flex items-center gap-1">
                                        <span class="rating-stars">★</span>
                                        <span class="text-sm font-semibold">
                                            @if($event->reviews_avg_rating)
                                                {{ number_format($event->reviews_avg_rating, 1) }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </span>
                                        <span class="text-xs text-gray-500">({{ $event->reviews_count ?? 0 }})</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $event->overview }}</p>
                                <a href="/events/{{ $event->id }}" class="inline-block btn-primary text-white font-semibold py-2 px-4 rounded-lg text-sm">
                                    詳細 →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 人気のイベント -->
            <div class="mb-16">
                <h2 class="section-title mb-8">⭐ 人気のイベント</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($popularEvents as $event)
                        <div class="event-card rounded-xl overflow-hidden shadow-lg">
                            <div class="relative h-40 bg-gradient-to-br from-gray-200 to-gray-300">
                                @if($event->image_url)
                                    <img src="{{ $event->image_url }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2 hover:text-blue-600">
                                    <a href="/events/{{ $event->id }}">{{ $event->name }}</a>
                                </h3>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="category-tag bg-pink-100 text-pink-700">{{ $event->category->name }}</span>
                                    <div class="flex items-center gap-1">
                                        <span class="rating-stars">★</span>
                                        <span class="text-sm font-semibold">
                                            @if($event->reviews_avg_rating)
                                                {{ number_format($event->reviews_avg_rating, 1) }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </span>
                                        <span class="text-xs text-gray-500">({{ $event->reviews_count ?? 0 }})</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $event->overview }}</p>
                                <a href="/events/{{ $event->id }}" class="inline-block btn-primary text-white font-semibold py-2 px-4 rounded-lg text-sm">
                                    詳細 →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const calendarEl = document.getElementById('calendar');
                
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'ja',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek'
                    },
                    height: 'auto',
                    // ★修正★ webアプリイベント取得
                    events: {
                        url: '{{ route("calendar.getEvents") }}',
                    },
                    eventClick: function(info) {
                        if (info.event.url) {
                            window.location.href = info.event.url;
                        }
                    },
                    dateClick: function(info) {
                        loadEventsForDate(info.dateStr);
                    },
                    eventDidMount: function(info) {
                        info.el.style.cursor = 'pointer';
                    }
                });
                
                calendar.render();
                const today = new Date().toISOString().split('T')[0];
                loadEventsForDate(today);
            });

            function loadEventsForDate(dateStr) {
                const date = new Date(dateStr);
                const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
                const formattedDate = date.toLocaleDateString('ja-JP', options);
                
                document.getElementById('selectedDate').textContent = formattedDate;

                const startDateTime = new Date(dateStr + 'T00:00:00').toISOString();
                const endDateTime = new Date(dateStr + 'T23:59:59').toISOString();

                // webアプリイベント取得
                fetch(`{{ route("calendar.getEvents") }}?start=${startDateTime}&end=${endDateTime}`)
                    .then(response => response.json())
                    .then(events => {
                        const eventsList = document.getElementById('eventsList');
                        
                        if (events.length === 0) {
                            eventsList.innerHTML = '<p class="text-gray-500 text-center py-4 text-sm">この日付はイベントがありません</p>';
                            return;
                        }

                        eventsList.innerHTML = events.map(event => `
                            <div class="border-l-4 border-blue-500 bg-gradient-to-r from-gray-50 to-transparent p-3 rounded-r hover:shadow-md transition">
                                <h4 class="font-bold text-sm text-gray-800 mb-1">
                                    <a href="/events/${event.id}" class="text-blue-600 hover:text-blue-800 transition">
                                        ${event.title}
                                    </a>
                                </h4>
                                <p class="text-xs text-gray-600">${event.extendedProps?.category || 'N/A'}</p>
                                <p class="text-xs text-gray-600">📍 ${event.extendedProps?.location || '場所未定'}</p>
                                <p class="text-xs text-gray-600">🕐 ${formatTime(event.start)}</p>
                            </div>
                        `).join('');
                    })
                    .catch(error => console.error('Error:', error));
            }

            function formatTime(dateTimeStr) {
                const date = new Date(dateTimeStr);
                return date.toLocaleString('ja-JP', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        </script>
    </body>
</x-app-layout>