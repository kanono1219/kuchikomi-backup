<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>📅 カレンダーから探す</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body class="bg-gray-100">
    <x-app-layout>
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-7xl mx-auto">
                <!-- ===== ヘッダー ===== -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">📅 カレンダーから探す</h1>
                    <p class="text-gray-600">沖縄県内のイベントをカレンダーから検索できます</p>
                </div>

                <!-- ===== デバッグ情報 ===== -->
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">デバッグ情報:</strong>
                    <p class="text-sm mt-2">
                        API エンドポイント: <code class="bg-yellow-200 px-2 py-1 rounded">/calendar/events</code>
                    </p>
                    <p class="text-sm mt-1">
                        ブラウザの F12 キーでコンソールを開き、エラーを確認してください
                    </p>
                </div>

                <!-- ===== エラー・成功メッセージ ===== -->
                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">成功!</strong>
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">エラー</strong>
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <!-- ===== Google Calendar 接続状態 ===== -->
                @auth
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-blue-900">🔗 Google Calendar 連携</h3>
                            @if(Auth::user()->google_calendar_connected)
                                <p class="text-green-600 text-sm mt-1">✅ Google Calendar に接続しています</p>
                            @else
                                <p class="text-gray-600 text-sm mt-1">Google Calendar を接続するとお気に入いイベントが自動同期されます</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endauth

                <!-- ===== カレンダー表示エリア ===== -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <div id="calendar" style="min-height: 700px;"></div>
                </div>

                <!-- ===== デバッグ出力エリア ===== -->
                <div class="bg-gray-800 text-gray-100 rounded-lg p-6 mb-8 font-mono text-sm">
                    <h3 class="text-lg font-bold mb-4 text-white">📊 デバッグコンソール</h3>
                    <div id="debugOutput" class="space-y-2 max-h-64 overflow-y-auto">
                        <p class="text-blue-300">コンソール出力がここに表示されます...</p>
                    </div>
                </div>

                <!-- ===== 凡例 ===== -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">凡例</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded" style="background-color: #4ECDC4;"></div>
                            <div>
                                <p class="font-semibold text-gray-800">通常のイベント</p>
                                <p class="text-sm text-gray-600">webアプリに登録されたイベント</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded" style="background-color: #FF6B6B;"></div>
                            <div>
                                <p class="font-semibold text-gray-800">お気に入いイベント</p>
                                <p class="text-sm text-gray-600">あなたがお気に入いに追加したイベント</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded" style="background-color: #FF8C00;"></div>
                            <div>
                                <p class="font-semibold text-gray-800">Google Calendarイベント</p>
                                <p class="text-sm text-gray-600">Google Calendarから同期したイベント</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== 選択日付のイベント一覧 ===== -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        📅 <span id="selectedDate">本日</span>のイベント
                    </h2>
                    <div id="eventsList" class="space-y-4">
                        <p class="text-gray-500 text-center py-8">
                            <span class="inline-block">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                カレンダーの日付をクリックしてイベントを表示
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>

    <!-- ===== JavaScript ===== -->
    <script>
        // ★デバッグ関数★
        const debugLog = (message, data = null) => {
            const timestamp = new Date().toLocaleTimeString('ja-JP');
            const output = document.getElementById('debugOutput');
            
            let logMessage = `[${timestamp}] ${message}`;
            if (data) {
                logMessage += `: ${JSON.stringify(data, null, 2)}`;
            }
            
            const p = document.createElement('p');
            p.className = 'text-green-300';
            p.textContent = logMessage;
            output.appendChild(p);
            
            // スクロールを下に
            output.scrollTop = output.scrollHeight;
            
            // ブラウザコンソールにも出力
            console.log(message, data);
        };

        const debugError = (message, error = null) => {
            const timestamp = new Date().toLocaleTimeString('ja-JP');
            const output = document.getElementById('debugOutput');
            
            let logMessage = `[${timestamp}] ❌ ${message}`;
            if (error) {
                logMessage += `: ${error.message || error}`;
            }
            
            const p = document.createElement('p');
            p.className = 'text-red-400';
            p.textContent = logMessage;
            output.appendChild(p);
            
            output.scrollTop = output.scrollHeight;
            
            console.error(message, error);
        };

        // グローバル変数
        let calendar;

        document.addEventListener('DOMContentLoaded', function() {
            debugLog('ページロード開始');
            
            const calendarEl = document.getElementById('calendar');
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                // 基本設定
                initialView: 'dayGridMonth',
                locale: 'ja',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                height: 'auto',
                contentHeight: 'auto',
                eventDisplay: 'block',

                // ★重要★ イベント取得
                events: async function(info, successCallback, failureCallback) {
                    try {
                        debugLog('=== イベント取得開始 ===');
                        debugLog('開始日', info.start.toISOString());
                        debugLog('終了日', info.end.toISOString());

                        const allEvents = [];

                        // 1. webアプリ側のイベントを取得
                        const appUrl = `/calendar/events?start=${info.start.toISOString()}&end=${info.end.toISOString()}`;
                        debugLog('アプリイベントAPI URL', appUrl);

                        const appResponse = await fetch(appUrl);
                        debugLog('アプリイベントレスポンスステータス', appResponse.status);

                        if (!appResponse.ok) {
                            throw new Error(`HTTP error! status: ${appResponse.status}`);
                        }

                        const appEvents = await appResponse.json();
                        debugLog('アプリイベント取得数', appEvents.length);
                        allEvents.push(...appEvents);

                        // 2. Google Calendar イベントを取得（認証済みユーザーのみ）
                        @auth
                        try {
                            const googleUrl = `/calendar/google-events?start=${info.start.toISOString()}&end=${info.end.toISOString()}`;
                            debugLog('GoogleカレンダーAPI URL', googleUrl);

                            const googleResponse = await fetch(googleUrl, {
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });
                            debugLog('Googleカレンダーレスポンスステータス', googleResponse.status);

                            if (googleResponse.ok) {
                                const googleData = await googleResponse.json();

                                // レスポンスが配列の場合とオブジェクトの場合を処理
                                const googleEvents = Array.isArray(googleData) ? googleData : (googleData.events || []);

                                debugLog('Googleカレンダーイベント取得数', googleEvents.length);
                                if (googleEvents.length > 0) {
                                    allEvents.push(...googleEvents);
                                    debugLog('✅ Googleカレンダーイベント取得成功');
                                }
                            } else {
                                debugLog('⚠️ Googleカレンダーイベント取得スキップ (未接続またはエラー)');
                            }
                        } catch (googleError) {
                            debugError('Googleカレンダーイベント取得エラー (スキップ)', googleError);
                            // Google Calendar エラーは無視して続行
                        }
                        @endauth

                        debugLog('合計イベント数', allEvents.length);
                        debugLog('全イベント詳細', allEvents);

                        if (allEvents.length === 0) {
                            debugLog('⚠️ イベントが見つかりません');
                        } else {
                            debugLog('✅ イベント取得成功');
                        }

                        successCallback(allEvents);

                    } catch (error) {
                        debugError('イベント取得エラー', error);
                        failureCallback(error);
                    }
                },

                // イベントクリック時
                eventClick: function(info) {
                    debugLog('イベントクリック', info.event.title);
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },

                // 日付クリック時
                dateClick: function(info) {
                    debugLog('日付クリック', info.dateStr);
                    loadEventsForDate(info.dateStr);
                },

                // イベントマウスホバー時
                eventMouseEnter: function(info) {
                    info.el.style.cursor = 'pointer';
                    info.el.style.opacity = '0.8';
                },

                eventMouseLeave: function(info) {
                    info.el.style.opacity = '1';
                }
            });
            
            debugLog('FullCalendar 初期化中...');
            calendar.render();
            debugLog('✅ FullCalendar レンダリング完了');

            // 本日のイベントを表示
            const today = new Date().toISOString().split('T')[0];
            debugLog('本日の日付', today);
            loadEventsForDate(today);
        });

        /**
         * 指定した日付のイベント一覧を表示
         */
        async function loadEventsForDate(dateStr) {
            try {
                const date = new Date(dateStr + 'T00:00:00');
                const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
                const formattedDate = date.toLocaleDateString('ja-JP', options);

                debugLog('日付別イベント取得', dateStr);
                document.getElementById('selectedDate').textContent = formattedDate;

                // イベント取得
                const startDateTime = new Date(dateStr + 'T00:00:00').toISOString();
                const endDateTime = new Date(dateStr + 'T23:59:59').toISOString();

                const allEvents = [];

                // 1. webアプリのイベント取得
                const appUrl = `/calendar/events?start=${startDateTime}&end=${endDateTime}`;
                debugLog('日付別アプリAPI URL', appUrl);

                try {
                    const appResponse = await fetch(appUrl);
                    debugLog('日付別アプリレスポンスステータス', appResponse.status);
                    if (appResponse.ok) {
                        const appEvents = await appResponse.json();
                        debugLog('日付別アプリイベント取得完了', appEvents.length);
                        allEvents.push(...appEvents);
                    }
                } catch (error) {
                    debugError('日付別アプリイベント取得エラー', error);
                }

                // 2. Googleカレンダーのイベント取得（認証済みユーザーのみ）
                @auth
                try {
                    const googleUrl = `/calendar/google-events?start=${startDateTime}&end=${endDateTime}`;
                    debugLog('日付別GoogleカレンダーAPI URL', googleUrl);

                    const googleResponse = await fetch(googleUrl, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    debugLog('日付別Googleカレンダーレスポンスステータス', googleResponse.status);

                    if (googleResponse.ok) {
                        const googleData = await googleResponse.json();
                        const googleEvents = Array.isArray(googleData) ? googleData : (googleData.events || []);
                        debugLog('日付別Googleカレンダーイベント取得完了', googleEvents.length);
                        if (googleEvents.length > 0) {
                            allEvents.push(...googleEvents);
                        }
                    }
                } catch (error) {
                    debugError('日付別Googleカレンダーイベント取得エラー (スキップ)', error);
                }
                @endauth

                debugLog('日付別合計イベント数', allEvents.length);
                const eventsList = document.getElementById('eventsList');

                if (allEvents.length === 0) {
                    eventsList.innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-lg">この日付はイベントがありません</p>
                        </div>
                    `;
                    return;
                }

                // イベント一覧を構築
                eventsList.innerHTML = allEvents.map(event => {
                    const startTime = new Date(event.start).toLocaleTimeString('ja-JP', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    // イベントタイプによって色を変更
                    const borderColor = event.extendedProps?.type === 'google_calendar' ? 'border-orange-500' :
                                      (event.extendedProps?.isFavorited ? 'border-red-500' : 'border-blue-500');
                    const bgColor = event.extendedProps?.type === 'google_calendar' ? 'bg-orange-50' :
                                   (event.extendedProps?.isFavorited ? 'bg-red-50' : 'bg-blue-50');
                    const badge = event.extendedProps?.type === 'google_calendar' ?
                                 '<span class="inline-block bg-orange-500 text-white text-xs px-2 py-1 rounded mb-2">Googleカレンダー</span>' : '';

                    // Googleカレンダーイベントの場合はリンクなし
                    const clickHandler = event.url ? `onclick="navigateToEvent('${event.url}')"` : '';
                    const detailLink = event.url ? `
                        <div class="mt-3">
                            <a href="${event.url}" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                詳細を見る →
                            </a>
                        </div>
                    ` : '';

                    return `
                        <div class="border-l-4 ${borderColor} ${bgColor} p-4 rounded-r hover:shadow-md transition ${event.url ? 'cursor-pointer' : ''}" ${clickHandler}>
                            ${badge}
                            <h4 class="font-bold text-lg text-gray-800 mb-2">
                                ${event.title}
                            </h4>
                            <div class="space-y-1 text-sm text-gray-600">
                                ${event.extendedProps?.category ? `<p>📂 カテゴリー: ${event.extendedProps.category}</p>` : ''}
                                <p>🕐 時間: ${startTime}</p>
                                <p>📍 場所: ${event.extendedProps?.location || '未定'}</p>
                                ${event.extendedProps?.description ? `<p class="mt-2 text-gray-500">${event.extendedProps.description.substring(0, 100)}${event.extendedProps.description.length > 100 ? '...' : ''}</p>` : ''}
                            </div>
                            ${detailLink}
                        </div>
                    `;
                }).join('');

            } catch (error) {
                debugError('日付処理エラー', error);
                const eventsList = document.getElementById('eventsList');
                eventsList.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-500 text-lg">エラーが発生しました</p>
                        <p class="text-gray-400 text-sm mt-2">${error.message}</p>
                    </div>
                `;
            }
        }

        /**
         * イベントに移動
         */
        function navigateToEvent(url) {
            if (url) {
                window.location.href = url;
            }
        }
    </script>

    <!-- ===== FullCalendar カスタムスタイル ===== -->
    <style>
        .fc {
            font-family: inherit;
        }

        .fc .fc-button-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        .fc .fc-button-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .fc .fc-button-primary.fc-button-active {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .fc .fc-daygrid-day:hover {
            background-color: #f3f4f6;
            cursor: pointer;
        }

        .fc .fc-event {
            border: none;
            border-radius: 4px;
        }

        .fc .fc-event-title {
            font-weight: 600;
            padding: 4px;
            white-space: normal;
        }

        .fc .fc-col-header-cell {
            background-color: #f9fafb;
            font-weight: 600;
            border-color: #e5e7eb;
        }

        .fc .fc-daygrid-day-number {
            padding: 8px 4px;
        }

        .fc .fc-daygrid-day-frame {
            min-height: 100px;
        }

        #debugOutput {
            background-color: #1f2937;
            border-radius: 4px;
            padding: 12px;
        }
    </style>
</body>
</x-app-layout>
</html>