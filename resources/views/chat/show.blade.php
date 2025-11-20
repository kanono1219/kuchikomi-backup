<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $chatRoom->buddyPost->event->name }} - チャット
            </h2>
            <a href="{{ route('chat.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                ← チャット一覧に戻る
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- チャットメッセージ表示エリア -->
                    <div class="space-y-4 mb-6">
                        @forelse($messages as $message)
                            <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%]">
                                    <div class="flex items-center mb-1 {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                                        <span class="text-sm text-gray-600">{{ $message->user->name }}</span>
                                        <span class="text-xs text-gray-400 ml-2">
                                            {{ $message->created_at->format('Y/m/d H:i') }}
                                        </span>
                                    </div>
                                    <div class="{{ $message->user_id === Auth::id() ? 'bg-blue-100' : 'bg-gray-100' }} rounded-lg p-3">
                                        <p class="text-sm text-gray-800">{{ $message->message }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500">メッセージはまだありません</p>
                        @endforelse
                    </div>

                    <!-- メッセージ入力フォーム -->
                    <form action="{{ route('chat.store', $chatRoom) }}" method="POST" class="mt-4">
                        @csrf
                        <div class="flex items-start space-x-2">
                            <textarea 
                                name="message" 
                                rows="3" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="メッセージを入力..."
                                required
                            ></textarea>
                            <button 
                                type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                送信
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>