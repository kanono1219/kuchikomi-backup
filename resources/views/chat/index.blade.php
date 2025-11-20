<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            チャット一覧
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @forelse($chatRooms as $chatRoom)
                        <div class="mb-4 last:mb-0">
                            <a href="{{ route('chat.show', $chatRoom) }}" 
                               class="block bg-white p-4 rounded-lg border border-gray-200 hover:border-blue-500 transition-colors duration-200">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ $chatRoom->buddyPost->event->name }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $chatRoom->buddyPost->title }}
                                        </p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span>投稿者: {{ $chatRoom->buddyPost->user->name }}</span>
                                            <span class="mx-2">•</span>
                                            <span>{{ $chatRoom->created_at->format('Y/m/d H:i') }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        @if($chatRoom->messages->isNotEmpty())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                メッセージあり
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                新規チャット
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($chatRoom->messages->isNotEmpty())
                                    <div class="mt-3 p-3 bg-gray-50 rounded text-sm text-gray-600">
                                        <span class="font-medium">
                                            {{ $chatRoom->messages->first()->user->name }}:
                                        </span>
                                        {{ Str::limit($chatRoom->messages->first()->message, 100) }}
                                    </div>
                                @endif
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-lg">チャットルームがありません</p>
                            <p class="mt-2 text-sm">バディ募集に参加するとチャットができるようになります</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>