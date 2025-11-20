<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>インポートプレビュー</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<x-app-layout>
    <body class="bg-gray-50">
        <div class="container mx-auto px-4 py-12 max-w-5xl">
            <!-- ヘッダー -->
            <div class="mb-8">
                <a href="{{ route('rss-importer.index') }}" class="text-blue-600 hover:text-blue-800">← 戻る</a>
                <h1 class="text-4xl font-bold text-gray-800 mt-4 mb-2">インポートプレビュー</h1>
                <p class="text-gray-600">{{ count($events) }} 件のイベントが見つかりました</p>
            </div>

            <!-- インポートフォーム -->
            <form action="{{ route('rss-importer.import') }}" method="POST" class="bg-white rounded-lg shadow-lg p-8">
                @csrf

                <!-- 選択ツール -->
                <div class="mb-8 flex gap-3 pb-6 border-b">
                    <button type="button" onclick="selectAll()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        すべて選択
                    </button>
                    <button type="button" onclick="deselectAll()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
                        すべて解除
                    </button>
                    <span class="ml-auto text-gray-600">
                        選択中: <span id="count">0</span>件
                    </span>
                </div>

                <!-- イベント一覧 -->
                <div class="space-y-4 mb-8">
                    @foreach($events as $index => $event)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-start gap-4">
                                <!-- チェックボックス -->
                                <input 
                                    type="checkbox" 
                                    name="events[]" 
                                    value="{{ json_encode($event) }}"
                                    class="event-checkbox mt-1"
                                    onchange="updateCount()"
                                >

                                <!-- イベント情報 -->
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-800 text-lg mb-2">{{ $event['name'] }}</h3>
                                    
                                    <div class="text-sm text-gray-600 space-y-1 mb-3">
                                        <p><strong>開始:</strong> {{ \Carbon\Carbon::parse($event['start_date'])->format('Y年m月d日 H:i') }}</p>
                                        <p><strong>場所:</strong> {{ $event['location'] }}</p>
                                        <p><strong>カテゴリー:</strong> <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded">{{ \App\Models\Category::find($event['category_id'])->name ?? 'その他' }}</span></p>
                                    </div>

                                    <p class="text-gray-700 text-sm line-clamp-2 mb-3">{{ $event['overview'] }}</p>

                                    @if($event['external_url'])
                                        <a href="{{ $event['external_url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                            ソースを見る →
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- ボタン -->
                <div class="flex gap-4">
                    <a href="{{ route('rss-importer.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        キャンセル
                    </a>
                    <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        ✓ 選択したイベントをインポート
                    </button>
                </div>
            </form>
        </div>

        <script>
            function updateCount() {
                const count = document.querySelectorAll('.event-checkbox:checked').length;
                document.getElementById('count').textContent = count;
            }

            function selectAll() {
                document.querySelectorAll('.event-checkbox').forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateCount();
            }

            function deselectAll() {
                document.querySelectorAll('.event-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateCount();
            }

            // 初期化
            updateCount();
        </script>
    </body>
</x-app-layout>