<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Googleカレンダーからイベントをインポート') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Googleカレンダーのイベント一覧</h3>
                        <p class="text-sm text-gray-600">6ヶ月前から6ヶ月後までのイベントを表示しています。インポートしたいイベントを選択してください。</p>
                    </div>

                    @if(count($googleEvents) === 0)
                        <div class="text-center py-8 text-gray-500">
                            <p>Googleカレンダーにイベントが見つかりませんでした。</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($googleEvents as $googleEvent)
                                @php
                                    $start = $googleEvent->getStart()->getDateTime() ?: $googleEvent->getStart()->getDate();
                                    $end = $googleEvent->getEnd()->getDateTime() ?: $googleEvent->getEnd()->getDate();
                                    $summary = $googleEvent->getSummary() ?: '(タイトルなし)';
                                    $description = $googleEvent->getDescription() ?: '';
                                    $location = $googleEvent->getLocation() ?: '';
                                    $eventId = $googleEvent->getId();

                                    // 既にインポート済みか確認
                                    $isImported = \App\Models\GoogleCalendarEvent::where('google_event_id', $eventId)
                                        ->where('user_id', $user->id)
                                        ->exists();
                                @endphp

                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition {{ $isImported ? 'bg-gray-100 opacity-60' : '' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-lg mb-2">{{ $summary }}</h4>

                                            <div class="space-y-1 text-sm text-gray-600">
                                                <div class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <span>
                                                        @if(str_contains($start, 'T'))
                                                            {{ \Carbon\Carbon::parse($start)->format('Y年m月d日 H:i') }}
                                                        @else
                                                            {{ \Carbon\Carbon::parse($start)->format('Y年m月d日') }} (終日)
                                                        @endif
                                                    </span>
                                                </div>

                                                @if($location)
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                        <span>{{ Str::limit($location, 50) }}</span>
                                                    </div>
                                                @endif

                                                @if($description)
                                                    <div class="mt-2">
                                                        <p class="text-gray-700">{{ Str::limit($description, 100) }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="ml-4">
                                            @if($isImported)
                                                <span class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-500 bg-white cursor-not-allowed">
                                                    ✓ インポート済み
                                                </span>
                                            @else
                                                <button
                                                    onclick="importEvent('{{ $eventId }}')"
                                                    class="import-btn inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                                                >
                                                    インポート
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6">
                        <a href="{{ route('mypage') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            マイページに戻る
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function importEvent(eventId) {
            if (!confirm('このイベントをインポートしますか？')) {
                return;
            }

            // ボタンを無効化
            event.target.disabled = true;
            event.target.textContent = 'インポート中...';

            fetch('{{ route('google-calendar.import-event') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    google_event_id: eventId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // ページをリロードして状態を更新
                } else {
                    alert('エラー: ' + data.message);
                    event.target.disabled = false;
                    event.target.textContent = 'インポート';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
                event.target.disabled = false;
                event.target.textContent = 'インポート';
            });
        }
    </script>
</x-app-layout>
