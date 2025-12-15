<!-- resources/views/mypage/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('マイページ') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 通知設定セクション -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl mb-8">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-6 text-gray-800">{{ __('通知設定') }}</h3>
                    
                    <form action="{{ route('mypage.notification-settings.update') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="space-y-6">
                            <!-- 通知のオン・オフ設定 -->
                            <div class="space-y-4">
                                <label class="text-gray-700 font-medium block mb-2">
                                    イベント開催通知
                                </label>
                                <div class="flex space-x-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" 
                                               name="event_notifications_enabled" 
                                               value="1"
                                               {{ auth()->user()->event_notifications_enabled ? 'checked' : '' }}
                                               class="form-radio h-4 w-4 text-blue-600">
                                        <span class="ml-2">通知を受け取る</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" 
                                               name="event_notifications_enabled" 
                                               value="0"
                                               {{ !auth()->user()->event_notifications_enabled ? 'checked' : '' }}
                                               class="form-radio h-4 w-4 text-blue-600">
                                        <span class="ml-2">通知を受け取らない</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 通知日数設定 -->
                            <div class="notification-days">
                                <div class="flex items-center justify-between">
                                    <label class="text-gray-700 font-medium">通知タイミング</label>
                                    <div class="flex items-center">
                                        <select name="notification_days_before" 
                                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-300">
                                            @foreach([1, 3, 5, 7, 14, 30] as $days)
                                                <option value="{{ $days }}" 
                                                        {{ auth()->user()->notification_days_before == $days ? 'selected' : '' }}>
                                                    {{ $days }}日前
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="ml-2 text-sm text-gray-500">に通知</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                設定を保存
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Google Calendar連携セクション -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl mb-8">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-6 text-gray-800">{{ __('Google Calendar 連携') }}</h3>

                    @if(auth()->user()->google_calendar_connected)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-green-800 font-medium">Google Calendarに接続済み</span>
                            </div>
                            <p class="text-sm text-green-700 mt-2">お気に入りしたイベントは自動的にGoogleカレンダーに追加されます。</p>
                        </div>

                        <div class="flex space-x-4">
                            <a href="{{ route('google-calendar.events') }}"
                               class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition duration-200 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Googleカレンダーからイベントをインポート
                            </a>

                            <form action="{{ route('google-calendar.disconnect') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        onclick="return confirm('Google Calendar との接続を解除しますか？')"
                                        class="bg-red-600 text-white px-6 py-3 rounded-md hover:bg-red-700 transition duration-200">
                                    接続を解除
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                            <p class="text-gray-700 mb-2">Google Calendarと連携して、お気に入りイベントを自動的にカレンダーに追加できます。</p>
                            <ul class="text-sm text-gray-600 list-disc list-inside space-y-1">
                                <li>お気に入りに追加すると自動的にGoogleカレンダーに登録</li>
                                <li>Googleカレンダーのイベントをこのアプリにインポート</li>
                                <li>すべてのデバイスでイベントを同期</li>
                            </ul>
                        </div>

                        <a href="{{ route('google-calendar.authenticate') }}"
                           class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition duration-200 inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Google Calendar と連携する
                        </a>
                    @endif
                </div>
            </div>

            <!-- 参加者募集の管理 -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl mb-8">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-6 text-gray-800">{{ __('参加者募集の管理') }}</h3>
            
                    <!-- 投稿した募集 -->
                    <div class="mb-8">
                        <h4 class="text-xl font-semibold mb-4 text-gray-700 flex items-center">
                            <span>投稿した募集</span>
                            <span class="ml-2 text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                {{ auth()->user()->buddyPosts->count() }}件
                            </span>
                        </h4>
            
                        @forelse(auth()->user()->buddyPosts as $post)
                            <div class="border rounded-lg p-4 mb-4 hover:bg-gray-50 transition duration-200">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h5 class="font-semibold text-lg text-gray-800">{{ $post->title }}</h5>
                                        <a href="{{ route('events.show', $post->event_id) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                            {{ $post->event->name }}
                                        </a>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-sm {{ $post->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $post->status === 'open' ? '募集中' : '募集終了' }}
                                    </span>
                                </div>
            
                                @if($post->participants->isNotEmpty())
                                    <div class="border-t pt-4 mt-4">
                                        <h6 class="font-medium mb-3">参加リクエスト一覧</h6>
                                        <div class="space-y-2">
                                            @foreach($post->participants as $participant)
                                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                                                    <div>
                                                        <span class="font-medium">{{ $participant->name }}</span>
                                                        <span class="ml-2 text-sm px-2 py-1 rounded-full
                                                            @if($participant->pivot->status === 'pending')
                                                                bg-yellow-100 text-yellow-800
                                                            @elseif($participant->pivot->status === 'approved')
                                                                bg-green-100 text-green-800
                                                            @else
                                                                bg-red-100 text-red-800
                                                            @endif">
                                                            {{ $participant->pivot->status === 'pending' ? '保留中' : 
                                                               ($participant->pivot->status === 'approved' ? '承認済み' : '拒否済み') }}
                                                        </span>

                                                        <!-- チャットボタン - statusによる条件分岐を削除 -->
                                                        <form action="{{ route('buddy-posts.chat.create', $post) }}" method="POST" class="inline ml-2">
                                                            @csrf
                                                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition duration-200 inline-flex items-center">
                                                                <svg xmlns="http://www.w3.org/2000/svg" 
                                                                     class="h-4 w-4 mr-1" 
                                                                     fill="none" 
                                                                     viewBox="0 0 24 24" 
                                                                     stroke="currentColor">
                                                                    <path stroke-linecap="round" 
                                                                          stroke-linejoin="round" 
                                                                          stroke-width="2" 
                                                                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                                </svg>
                                                                チャット
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                   @if($participant->pivot->status === 'pending')
                                                        <div class="flex space-x-2">
                                                            <form action="{{ route('events.buddy-posts.respond', [
                                                                    'event' => $post->event_id, 
                                                                    'buddyPost' => $post->id
                                                                ]) }}" method="POST" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{ $participant->id }}">
                                                                <input type="hidden" name="status" value="approved">
                                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition duration-200">
                                                                    承認
                                                                </button>
                                                            </form>
                                                            <form action="{{ route('events.buddy-posts.respond', [
                                                                    'event' => $post->event_id, 
                                                                    'buddyPost' => $post->id
                                                                ]) }}" method="POST" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{ $participant->id }}">
                                                                <input type="hidden" name="status" value="rejected">
                                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition duration-200">
                                                                    拒否
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">まだ募集を投稿していません</p>
                        @endforelse
                    </div>
            
                    <!-- 参加リクエストした募集 -->
                    <div>
                        <h4 class="text-xl font-semibold mb-4 text-gray-700 flex items-center">
                            <span>参加リクエストした募集</span>
                            <span class="ml-2 text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                {{ auth()->user()->participatingBuddyPosts->count() }}件
                            </span>
                        </h4>
            
                        @forelse(auth()->user()->participatingBuddyPosts as $post)
                            <div class="border rounded-lg p-4 mb-4 hover:bg-gray-50 transition duration-200">
                                <!-- 投稿の基本情報 -->
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h5 class="font-semibold text-lg text-gray-800">{{ $post->title }}</h5>
                                        <a href="{{ route('events.show', $post->event_id) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                            {{ $post->event->name }}
                                        </a>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-sm
                                        @if($post->pivot->status === 'pending')
                                            bg-yellow-100 text-yellow-800
                                        @elseif($post->pivot->status === 'approved')
                                            bg-green-100 text-green-800
                                        @else
                                            bg-red-100 text-red-800
                                        @endif">
                                        {{ $post->pivot->status === 'pending' ? '保留中' : 
                                           ($post->pivot->status === 'approved' ? '承認済み' : '拒否済み') }}
                                    </span>
                                </div>

                                <!-- アクションボタン -->
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('events.show', $post->event_id) }}" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm transition duration-200">
                                        詳細を見る
                                    </a>
                                    
                                    <!-- チャットボタン - statusによる条件分岐を削除 -->
                                    <form action="{{ route('buddy-posts.chat.create', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm transition duration-200 inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            チャット
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">まだ参加リクエストしていません</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- お気に入りイベントセクション -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-6 text-gray-800">{{ __('お気に入りイベント') }}</h3>
                    
                    @if($favoriteEvents->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            @foreach($favoriteEvents as $event)
                                <div class="bg-white shadow-lg rounded-xl overflow-hidden transition duration-300 ease-in-out transform hover:-translate-y-2 hover:shadow-2xl">
                                    @if($event->image_url)
                                        <img src="{{ $event->image_url }}" alt="{{ $event->name }}" class="w-full h-56 object-cover">
                                    @else
                                        <div class="w-full h-56 bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center">
                                            <span class="text-gray-400">No Image</span>
                                        </div>
                                    @endif
                                    
                                    <div class="p-6">
                                        <h4 class="text-xl font-semibold mb-3">
                                            <a href="/events/{{ $event->id }}" class="text-gray-800 hover:text-blue-600 transition duration-300">
                                                {{ $event->name }}
                                            </a>
                                        </h4>

                                        <div class="flex items-center mb-3">
                                            <a href="/categories/{{ $event->category->id }}" 
                                               class="text-sm text-blue-600 bg-blue-100 rounded-full px-3 py-1 mr-2 hover:bg-blue-200 transition duration-300">
                                                {{ $event->category->name }}
                                            </a>
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                                <span class="ml-1 text-sm font-medium text-gray-600">
                                                    {{ number_format($event->average_rating, 1) }} ({{ $event->reviews_count }}件)
                                                </span>
                                            </div>
                                        </div>

                                        <div class="text-sm text-gray-600 mb-2">
                                            <p>開催期間：</p>
                                            <p>{{ $event->start_date->format('Y年m月d日 H:i') }} ～</p>
                                            <p>{{ $event->end_date->format('Y年m月d日 H:i') }}</p>
                                        </div>
                                        
                                        @if($event->location)
                                            <p class="text-gray-600 mb-2">{{ $event->location }}</p>
                                        @endif

                                        <p class="text-sm text-gray-600 mt-3 line-clamp-3">{{ $event->overview }}</p>

                                        <div class="mt-4 flex justify-between items-center">
                                            <a href="{{ route('events.show', $event) }}" 
                                               class="text-blue-600 hover:text-blue-800 transition duration-300">
                                                詳細を見る
                                            </a>
                                            <form action="{{ route('events.unfavorite', $event) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-500 hover:text-red-700"
                                                        onclick="return confirm('お気に入りから削除してもよろしいですか？')">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" 
                                                              d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" 
                                                              clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6">
                            {{ $favoriteEvents->links() }}
                        </div>
                    @else
                        <p class="text-gray-500">{{ __('お気に入りに登録したイベントはありません。') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const radioButtons = document.querySelectorAll('input[name="event_notifications_enabled"]');
        const notificationDays = document.querySelector('.notification-days');

        function updateNotificationDays() {
            const isEnabled = document.querySelector('input[name="event_notifications_enabled"]:checked').value === '1';
            notificationDays.classList.toggle('opacity-50', !isEnabled);
            notificationDays.classList.toggle('pointer-events-none', !isEnabled);
        }

        radioButtons.forEach(radio => {
            radio.addEventListener('change', updateNotificationDays);
        });

        // 初期状態の設定
        updateNotificationDays();
    });
    </script>
</x-app-layout>