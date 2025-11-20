<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>イベントをインポート</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<x-app-layout>
    <body class="bg-gray-50">
        <div class="container mx-auto px-4 py-12 max-w-2xl">
            <!-- ヘッダー -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-800 mb-4">
                    🚀 RSS/iCalendar からイベントをインポート
                </h1>
                <p class="text-gray-600 text-lg">
                    Webサイトやブログから自動的にイベント情報を取得できます
                </p>
            </div>

            <!-- エラーメッセージ -->
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <!-- フォーム -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">フィードURLを入力</h2>
                
                <form action="{{ route('rss-importer.preview') }}" method="POST">
                    @csrf
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-3">RSS/iCalendar フィードURL</label>
                        <div class="flex gap-2">
                            <input 
                                type="url" 
                                name="feed_url" 
                                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                placeholder="https://example.com/feed" 
                                required
                            >
                            <button 
                                type="submit" 
                                class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
                            >
                                プレビュー
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">RSS 2.0、Atom、iCalendar 形式に対応しています</p>
                    </div>
                </form>
            </div>
        </div>
    </body>
</x-app-layout>