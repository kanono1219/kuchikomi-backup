<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>イベント検索結果</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&display=swap" rel="stylesheet">
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
            <!-- ヘッダーセクション -->
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                    🔍 イベント検索結果
                </h1>
                <p class="text-gray-600 text-lg">{{ $events->total() }}件のイベントが見つかりました</p>
            </div>

            <!-- 検索条件の表示 -->
            <div class="mb-8 bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    検索条件
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <span class="font-semibold text-gray-700 mr-2">🔑 キーワード:</span>
                        <span class="text-gray-600">{{ request('query') ?: 'すべて' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="font-semibold text-gray-700 mr-2">📅 期間:</span>
                        <span class="text-gray-600">
                            @php
                                $timeFilters = [
                                    '' => 'すべて',
                                    'current' => '開催中',
                                    'upcoming' => '今後の予定',
                                    'past' => '過去のイベント'
                                ];
                            @endphp
                            {{ $timeFilters[request('time_filter')] ?? 'すべて' }}
                        </span>
                    </div>
                </div>
                @auth
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="{{ route('events.create') }}" class="btn-primary text-white font-bold py-3 px-6 rounded-lg inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            イベントを作成する
                        </a>
                    </div>
                @else
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="{{ route('login') }}" class="btn-primary text-white font-bold py-3 px-6 rounded-lg inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            ログインしてイベントを作成
                        </a>
                    </div>
                @endauth
            </div>

            @if($events->isEmpty())
                <!-- 検索結果なし -->
                <div class="text-center py-16">
                    <svg class="w-24 h-24 mx-auto mb-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-700 mb-2">検索結果が見つかりませんでした</h3>
                    <p class="text-gray-500 mb-6">別のキーワードで検索してみてください</p>
                    <a href="{{ route('index') }}" class="btn-primary text-white font-bold py-3 px-6 rounded-lg inline-block">
                        すべてのイベントを見る
                    </a>
                </div>
            @else
                <!-- 検索結果一覧 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                    @foreach ($events as $event)
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
                            </div>

                            <!-- コンテンツ -->
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-3 line-clamp-2 hover:text-blue-600 transition">
                                    <a href="/events/{{ $event->id }}">
                                        @if(function_exists('highlightSearchTerm'))
                                            {!! highlightSearchTerm($event->name, request('query')) !!}
                                        @else
                                            {{ $event->name }}
                                        @endif
                                    </a>
                                </h3>

                                <!-- カテゴリーと評価 -->
                                <div class="flex items-center justify-between mb-4">
                                    <a href="/categories/{{ $event->category->id }}" class="category-tag bg-blue-100 text-blue-700">
                                        @if(function_exists('highlightSearchTerm'))
                                            {!! highlightSearchTerm($event->category->name, request('query')) !!}
                                        @else
                                            {{ $event->category->name }}
                                        @endif
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
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                        @if(function_exists('highlightSearchTerm'))
                                            {!! highlightSearchTerm($event->location ?? '場所未定', request('query')) !!}
                                        @else
                                            {{ $event->location ?? '場所未定' }}
                                        @endif
                                    </p>
                                    <p class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $event->start_date->format('Y/m/d H:i') }}
                                    </p>
                                </div>

                                <!-- 概要 -->
                                <p class="text-gray-600 text-sm line-clamp-2 mb-4">
                                    @if(function_exists('highlightSearchTerm'))
                                        {!! Str::limit(highlightSearchTerm($event->overview ?? '', request('query')), 100) !!}
                                    @else
                                        {{ Str::limit($event->overview ?? '', 100) }}
                                    @endif
                                </p>

                                <!-- アクションボタン -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <a href="/events/{{ $event->id }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm flex items-center">
                                        詳細を見る
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                    @auth
                                        @if($event->user_id === Auth::id())
                                            <form action="/events/{{ $event->id }}" id="form_{{ $event->id }}" method="post" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" onclick="deleteEvent({{ $event->id }})" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-xs transition">
                                                    削除
                                                </button>
                                            </form>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- ページネーション -->
                <div class="mt-8">
                    {{ $events->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

        <script>
            function deleteEvent(id) {
                'use strict'
                if (confirm('削除すると復元できません。\n本当に削除しますか？')) {
                    document.getElementById(`form_${id}`).submit();
                }
            }
        </script>
    </body>
</x-app-layout>
</html>
