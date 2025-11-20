<!DOCTYPE html>
    <x-app-layout>
        <body class="bg-gray-100">
            <div class="container mx-auto px-4 py-8">
                <h1 class="text-3xl font-bold mb-6 text-gray-800">イベント一覧</h1>
                <a href="{{ Auth::check() ? '/events/create' : '/login' }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">イベントを作成する</a>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($events as $event)
                        <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <div class="p-4">
                                <h2 class="text-xl font-semibold mb-2">
                                    <a href="/events/{{ $event->id }}" class="text-blue-600 hover:text-blue-800">{{ $event->name }}</a>
                                </h2>
                                <a href="/categories/{{ $event->category->id }}" class="text-sm text-gray-600 bg-gray-200 rounded-full px-3 py-1 mb-2 inline-block">{{ $event->category->name }}</a>
                                <p class="text-gray-700 mb-4">{{ Str::limit($event->overview, 100) }}</p>
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
                    {{ $events->links() }}
                </div>
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