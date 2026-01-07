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

            <!-- RSS配信でイベントを作成（管理者のみ） -->
            @if(auth()->check() && auth()->user()->is_admin)
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
            @endif

            <!-- カレンダーセクション -->
            <div class="mb-16">
                <h2 class="section-title mb-8">📅 カレンダーから探す</h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
                        <div id="calendar"></div>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="font-bold text-lg text-gray-800 mb-4">📌 凡例</h3>

                            <!-- カテゴリー別の色 -->
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-gray-600 mb-2">カテゴリー別</h4>
                                <div class="grid grid-cols-1 gap-2 text-sm">
                                    <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                        <div class="w-3 h-3 rounded" style="background-color: #9C27B0;"></div>
                                        <span class="text-gray-700">祭り</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                        <div class="w-3 h-3 rounded" style="background-color: #E91E63;"></div>
                                        <span class="text-gray-700">音楽イベント</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                        <div class="w-3 h-3 rounded" style="background-color: #2196F3;"></div>
                                        <span class="text-gray-700">展示会</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                        <div class="w-3 h-3 rounded" style="background-color: #4CAF50;"></div>
                                        <span class="text-gray-700">スポーツイベント</span>
                                    </div>
                                    <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                        <div class="w-3 h-3 rounded" style="background-color: #FF9800;"></div>
                                        <span class="text-gray-700">式典</span>
                                    </div>
                                    @if(auth()->check() && auth()->user()->is_admin)
                                    <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                        <div class="w-3 h-3 rounded" style="background-color: #607D8B;"></div>
                                        <span class="text-gray-700">RSS配信</span>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- 特別な表示 -->
                            <div class="border-t pt-3">
                                <h4 class="text-sm font-semibold text-gray-600 mb-2">特別な表示</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                        <div class="w-3 h-3 rounded" style="background-color: #FF6B6B;"></div>
                                        <span class="text-gray-700">❤️ お気に入い</span>
                                    </div>
                                    @auth
                                        @if(auth()->user()->google_calendar_connected)
                                        <div class="flex items-center gap-2 p-1.5 rounded hover:bg-gray-50">
                                            <div class="w-3 h-3 rounded" style="background-color: #FF8C00;"></div>
                                            <span class="text-gray-700">📅 Google Calendar</span>
                                        </div>
                                        @endif
                                    @endauth
                                </div>
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
            // ★デバッグ関数★
            const debugLog = (message, data = null) => {
                const timestamp = new Date().toLocaleTimeString('ja-JP');
                console.log(`[${timestamp}] ${message}`, data || '');
            };

            const debugError = (message, error = null) => {
                const timestamp = new Date().toLocaleTimeString('ja-JP');
                console.error(`[${timestamp}] ❌ ${message}`, error || '');
            };

            // グローバル変数
            let calendar;

            document.addEventListener('DOMContentLoaded', function() {
                debugLog('ページロード開始');

                const calendarEl = document.getElementById('calendar');

                calendar = new FullCalendar.Calendar(calendarEl, {
                    // 基本設定
                    initialView: 'dayGridMonth',
                    locale: 'ja',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek'
                    },
                    height: 'auto',
                    contentHeight: 'auto',
                    eventDisplay: 'block',

                    // ★重要★ イベント取得
                    events: async function(info, successCallback, failureCallback) {
                        try {
                            debugLog('=== イベント取得開始 ===');
                            debugLog('開始日', info.start.toISOString());
                            debugLog('終了日', info.end.toISOString());

                            const allEvents = [];

                            // 1. webアプリ側のイベントを取得
                            const appUrl = `/calendar/events?start=${info.start.toISOString()}&end=${info.end.toISOString()}`;
                            debugLog('アプリイベントAPI URL', appUrl);

                            const appResponse = await fetch(appUrl);
                            debugLog('アプリイベントレスポンスステータス', appResponse.status);

                            if (!appResponse.ok) {
                                throw new Error(`HTTP error! status: ${appResponse.status}`);
                            }

                            const appEvents = await appResponse.json();
                            debugLog('アプリイベント取得数', appEvents.length);
                            allEvents.push(...appEvents);

                            // 2. Google Calendar 予定を取得（認証済みユーザーのみ）
                            @auth
                            try {
                                const googleUrl = `/calendar/google-events?start=${info.start.toISOString()}&end=${info.end.toISOString()}`;
                                debugLog('🔍 GoogleカレンダーAPI URL', googleUrl);

                                const googleResponse = await fetch(googleUrl, {
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                });
                                debugLog('📡 Googleカレンダーレスポンスステータス', googleResponse.status);

                                if (googleResponse.ok) {
                                    const googleData = await googleResponse.json();
                                    debugLog('📦 Googleカレンダー生データ', googleData);

                                    // レスポンスが配列の場合とオブジェクトの場合を処理
                                    const googleEvents = Array.isArray(googleData) ? googleData : (googleData.events || []);

                                    debugLog('📊 Googleカレンダー予定取得数', googleEvents.length);

                                    // length が 0 でも処理を続行
                                    if (googleEvents.length === 0) {
                                        debugLog('⚠️ Googleカレンダー予定が0件です');
                                    } else {
                                        allEvents.push(...googleEvents);
                                        debugLog('✅ Googleカレンダー予定をカレンダーに追加しました', googleEvents.length + '件');
                                    }
                                } else {
                                    const errorText = await googleResponse.text();
                                    debugError('❌ Googleカレンダー予定取得エラー', {
                                        status: googleResponse.status,
                                        statusText: googleResponse.statusText,
                                        body: errorText
                                    });
                                }
                            } catch (googleError) {
                                debugError('💥 Googleカレンダー予定取得で例外発生', {
                                    message: googleError.message,
                                    stack: googleError.stack
                                });
                            }
                            @endauth

                            debugLog('合計イベント数（重複除去前）', allEvents.length);

                            // ★重複除去★ タイトルと開始時刻が同じイベントは1つだけ残す
                            const uniqueEvents = [];
                            const eventKeys = new Set();

                            allEvents.forEach(event => {
                                // イベントの一意キーを作成（タイトル + 開始時刻）
                                const key = `${event.title}_${event.start}`;

                                if (!eventKeys.has(key)) {
                                    eventKeys.add(key);
                                    uniqueEvents.push(event);
                                } else {
                                    debugLog('重複イベントを除外', {
                                        title: event.title,
                                        start: event.start,
                                        type: event.extendedProps?.type
                                    });
                                }
                            });

                            debugLog('合計イベント数（重複除去後）', uniqueEvents.length);
                            debugLog('全イベント詳細', uniqueEvents);

                            if (uniqueEvents.length === 0) {
                                debugLog('⚠️ イベントが見つかりません');
                            } else {
                                debugLog('✅ イベント取得成功');
                            }

                            successCallback(uniqueEvents);

                        } catch (error) {
                            debugError('イベント取得エラー', error);
                            failureCallback(error);
                        }
                    },

                    // イベントクリック時
                    eventClick: function(info) {
                        debugLog('イベントクリック', info.event.title);
                        if (info.event.url) {
                            window.location.href = info.event.url;
                        }
                    },

                    // 日付クリック時
                    dateClick: function(info) {
                        debugLog('日付クリック', info.dateStr);
                        loadEventsForDate(info.dateStr);
                    },

                    // イベントマウスホバー時
                    eventMouseEnter: function(info) {
                        info.el.style.cursor = 'pointer';
                        info.el.style.opacity = '0.8';
                    },

                    eventMouseLeave: function(info) {
                        info.el.style.opacity = '1';
                    }
                });

                debugLog('FullCalendar 初期化中...');
                calendar.render();
                debugLog('✅ FullCalendar レンダリング完了');

                // 本日のイベントを表示
                const today = new Date().toISOString().split('T')[0];
                debugLog('本日の日付', today);
                loadEventsForDate(today);
            });

            /**
             * 指定した日付のイベント一覧を表示
             */
            async function loadEventsForDate(dateStr) {
                try {
                    const date = new Date(dateStr + 'T00:00:00');
                    const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
                    const formattedDate = date.toLocaleDateString('ja-JP', options);

                    debugLog('日付別イベント取得', dateStr);
                    document.getElementById('selectedDate').textContent = formattedDate;

                    // イベント取得
                    const startDateTime = new Date(dateStr + 'T00:00:00').toISOString();
                    const endDateTime = new Date(dateStr + 'T23:59:59').toISOString();

                    const allEvents = [];

                    // 1. webアプリのイベント取得
                    const appUrl = `/calendar/events?start=${startDateTime}&end=${endDateTime}`;
                    debugLog('日付別アプリAPI URL', appUrl);

                    try {
                        const appResponse = await fetch(appUrl);
                        debugLog('日付別アプリレスポンスステータス', appResponse.status);
                        if (appResponse.ok) {
                            const appEvents = await appResponse.json();
                            debugLog('日付別アプリイベント取得完了', appEvents.length);
                            allEvents.push(...appEvents);
                        }
                    } catch (error) {
                        debugError('日付別アプリイベント取得エラー', error);
                    }

                    // 2. Googleカレンダーのイベント取得（認証済みユーザーのみ）
                    @auth
                    try {
                        const googleUrl = `/calendar/google-events?start=${startDateTime}&end=${endDateTime}`;
                        debugLog('日付別GoogleカレンダーAPI URL', googleUrl);

                        const googleResponse = await fetch(googleUrl, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        debugLog('日付別Googleカレンダーレスポンスステータス', googleResponse.status);

                        if (googleResponse.ok) {
                            const googleData = await googleResponse.json();
                            const googleEvents = Array.isArray(googleData) ? googleData : (googleData.events || []);
                            debugLog('日付別Googleカレンダーイベント取得完了', googleEvents.length);
                            if (googleEvents.length > 0) {
                                allEvents.push(...googleEvents);
                            }
                        }
                    } catch (error) {
                        debugError('日付別Googleカレンダーイベント取得エラー (スキップ)', error);
                    }
                    @endauth

                    debugLog('日付別合計イベント数', allEvents.length);
                    const eventsList = document.getElementById('eventsList');

                    if (allEvents.length === 0) {
                        eventsList.innerHTML = `
                            <div class="text-center py-8">
                                <p class="text-gray-500 text-lg">この日付はイベントがありません</p>
                            </div>
                        `;
                        return;
                    }

                    // イベント一覧を構築
                    eventsList.innerHTML = allEvents.map(event => {
                        const startTime = new Date(event.start).toLocaleTimeString('ja-JP', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        // イベントタイプによって色を変更
                        const borderColor = event.extendedProps?.type === 'google_calendar' ? 'border-orange-500' :
                                          (event.extendedProps?.isFavorited ? 'border-red-500' : 'border-blue-500');
                        const bgColor = event.extendedProps?.type === 'google_calendar' ? 'bg-orange-50' :
                                       (event.extendedProps?.isFavorited ? 'bg-red-50' : 'bg-blue-50');
                        const badge = event.extendedProps?.type === 'google_calendar' ?
                                     '<span class="inline-block bg-orange-500 text-white text-xs px-2 py-1 rounded mb-2">Googleカレンダー予定</span>' : '';

                        // Googleカレンダーイベントの場合はリンクなし
                        const clickHandler = event.url ? `onclick="navigateToEvent('${event.url}')"` : '';
                        const detailLink = event.url ? `
                            <div class="mt-3">
                                <a href="${event.url}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                    詳細を見る →
                                </a>
                            </div>
                        ` : '';

                        return `
                            <div class="border-l-4 ${borderColor} ${bgColor} p-4 rounded-r hover:shadow-md transition ${event.url ? 'cursor-pointer' : ''}" ${clickHandler}>
                                ${badge}
                                <h4 class="font-bold text-lg text-gray-800 mb-2">
                                    ${event.title}
                                </h4>
                                <div class="space-y-1 text-sm text-gray-600">
                                    ${event.extendedProps?.category ? `<p>📂 カテゴリー: ${event.extendedProps.category}</p>` : ''}
                                    <p>🕐 時間: ${startTime}</p>
                                    <p>📍 場所: ${event.extendedProps?.location || '未定'}</p>
                                    ${event.extendedProps?.description ? `<p class="mt-2 text-gray-500">${event.extendedProps.description.substring(0, 100)}${event.extendedProps.description.length > 100 ? '...' : ''}</p>` : ''}
                                </div>
                                ${detailLink}
                            </div>
                        `;
                    }).join('');

                } catch (error) {
                    debugError('日付処理エラー', error);
                    const eventsList = document.getElementById('eventsList');
                    eventsList.innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-red-500 text-lg">エラーが発生しました</p>
                            <p class="text-gray-400 text-sm mt-2">${error.message}</p>
                        </div>
                    `;
                }
            }

            /**
             * イベントに移動
             */
            function navigateToEvent(url) {
                if (url) {
                    window.location.href = url;
                }
            }
        </script>
    </body>
</x-app-layout>