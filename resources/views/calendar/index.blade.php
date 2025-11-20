<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>イベントカレンダー</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body class="bg-gray-100">
    <x-app-layout>
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-7xl">
                <!-- ヘッダー -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">イベントカレンダー</h1>
                    <p class="text-gray-600">沖縄県内のイベントをカレンダーから探す</p>
                </div>

                <!-- カレンダー表示エリア -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <div id="calendar" class="mb-6"></div>
                </div>

                <!-- 凡例 -->
                <div class="bg-white rounded-lg shadow p-4 mb-8">
                    <h3 class="font-bold text-gray-800 mb-3">凡例</h3>
                    <div class="flex gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-blue-500 rounded"></div>
                            <span class="text-gray-700">通常のイベント</span>
                        </div>
                        @auth
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-red-500 rounded"></div>
                                <span class="text-gray-700">お気に入りイベント</span>
                            </div>
                        @endauth
                    </div>
                </div>

                <!-- 選択日付のイベント一覧 -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        <span id="selectedDate">本日</span>のイベント
                    </h2>
                    <div id="eventsList" class="space-y-4">
                        <p class="text-gray-500 text-center py-8">日付をクリックしてイベントを表示</p>
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'ja',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek'
                },
                height: 'auto',
                events: {
                    url: '{{ route("calendar.getEvents") }}',
                    failure: function() {
                        alert('イベント情報の読み込みに失敗しました');
                    }
                },
                eventClick: function(info) {
                    window.location.href = info.event.url;
                },
                dateClick: function(info) {
                    loadEventsForDate(info.dateStr);
                },
                eventDidMount: function(info) {
                    info.el.style.cursor = 'pointer';
                    info.el.addEventListener('mouseenter', function() {
                        this.style.opacity = '0.8';
                    });
                    info.el.addEventListener('mouseleave', function() {
                        this.style.opacity = '1';
                    });
                }
            });
            
            calendar.render();

            const today = new Date().toISOString().split('T')[0];
            loadEventsForDate(today);
        });

        function loadEventsForDate(dateStr) {
            const date = new Date(dateStr);
            const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
            const formattedDate = date.toLocaleDateString('ja-JP', options);
            
            document.getElementById('selectedDate').textContent = formattedDate;

            fetch(`{{ route("calendar.getEventsByDate") }}?date=${dateStr}`)
                .then(response => response.json())
                .then(events => {
                    const eventsList = document.getElementById('eventsList');
                    
                    if (events.length === 0) {
                        eventsList.innerHTML = '<p class="text-gray-500 text-center py-8">この日付はイベントがありません</p>';
                        return;
                    }

                    eventsList.innerHTML = events.map(event => `
                        <div class="border-l-4 border-blue-500 bg-gray-50 p-4 rounded-r-lg hover:shadow-md transition">
                            <h3 class="font-bold text-lg text-gray-800 mb-2">
                                <a href="/events/${event.id}" class="text-blue-600 hover:text-blue-800">
                                    ${event.name}
                                </a>
                            </h3>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>カテゴリー:</strong> ${event.category ? event.category.name : 'N/A'}</p>
                                <p><strong>場所:</strong> ${event.location || 'N/A'}</p>
                                <p><strong>開始:</strong> ${formatDateTime(event.start_date)}</p>
                                <p><strong>終了:</strong> ${formatDateTime(event.end_date)}</p>
                                ${event.reviews_avg_rating ? `<p><strong>評価:</strong> ⭐ ${event.reviews_avg_rating.toFixed(1)} (${event.reviews_count}件)</p>` : ''}
                            </div>
                            <a href="/events/${event.id}" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                詳細を見る
                            </a>
                        </div>
                    `).join('');
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('eventsList').innerHTML = '<p class="text-red-500">エラーが発生しました</p>';
                });
        }

        function formatDateTime(dateTimeStr) {
            const date = new Date(dateTimeStr);
            return date.toLocaleString('ja-JP', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>