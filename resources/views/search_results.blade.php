<!DOCTYPE html>
<x-app-layout>
    <body class="bg-gray-100">
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">イベント検索結果</h1>
            <!-- 検索条件の表示 -->
            <div class="mb-6 bg-white shadow-md rounded-lg p-4">
                <p class="text-lg font-semibold">検索条件：</p>
                <p>キーワード: {{ request('query') ?: 'なし' }}</p>
                <p>期間: 
                    @php
                        $timeFilters = [
                            '' => 'すべて',
                            'current' => '開催中',
                            'upcoming' => '今後の予定',
                            'past' => '過去のイベント'
                        ];
                    @endphp
                    {{ $timeFilters[request('time_filter')] ?? 'すべて' }}
                </p>
            </div>
            <a href="{{ Auth::check() ? '/events/create' : '/login' }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">イベントを作成する</a>
            @if($events->isEmpty())
                <p class="text-gray-700 text-lg">検索条件に一致するイベントが見つかりませんでした。</p>
            @else
                <p class="text-gray-700 mb-4">{{ $events->total() }}件のイベントが見つかりました。</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($events as $event)
                        <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <div class="p-4">
                                <h2 class="text-xl font-semibold mb-2">
                                    <a href="/events/{{ $event->id }}" class="text-blue-600 hover:text-blue-800">
                                        @if(function_exists('highlightSearchTerm'))
                                            {!! highlightSearchTerm($event->name, request('query')) !!}
                                        @else
                                            {{ $event->name }}
                                        @endif
                                    </a>
                                </h2>
                                <a href="/categories/{{ $event->category->id }}" class="text-sm text-gray-600 bg-gray-200 rounded-full px-3 py-1 mb-2 inline-block">
                                    @if(function_exists('highlightSearchTerm'))
                                        {!! highlightSearchTerm($event->category->name, request('query')) !!}
                                    @else
                                        {{ $event->category->name }}
                                    @endif
                                </a>
                                <p class="text-gray-700 mb-2">
                                    開催場所: 
                                    @if(function_exists('highlightSearchTerm'))
                                        {!! highlightSearchTerm($event->location, request('query')) !!}
                                    @else
                                        {{ $event->location }}
                                    @endif
                                </p>
                                <p class="text-gray-700 mb-2">
                                    開催期間: {{ $event->start_date->format('Y/m/d H:i') }} - {{ $event->end_date->format('Y/m/d H:i') }}
                                </p>
                                <p class="text-gray-700 mb-4">
                                    @if(function_exists('highlightSearchTerm'))
                                        {!! Str::limit(highlightSearchTerm($event->overview, request('query')), 100) !!}
                                    @else
                                        {{ Str::limit($event->overview, 100) }}
                                    @endif
                                </p>
                                @auth
                                    @if($event->user_id === Auth::id())
                                        <form action="/events/{{ $event->id }}" id="form_{{ $event->id }}" method="post" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="deleteEvent({{ $event->id }})" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm">削除</button> 
                                        </form>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">
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