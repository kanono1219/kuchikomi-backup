<nav x-data="{ 
    open: false,
    weather: {
        current: { temp: '', description: '', icon: '' },
        forecast: [],
        loading: true
    },
    formatDate(dateStr) {
        const date = new Date(dateStr);
        const month = date.getMonth() + 1;
        const day = date.getDate();
        return `${month}/${day}`;
    },
    async fetchWeather() {
        const city = 'Okinawa,JP';
        const apiKey = '{{ config('services.openweather.key') }}';
        
        try {
            const currentResponse = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric&lang=ja`);
            const currentData = await currentResponse.json();
            
            const forecastResponse = await fetch(`https://api.openweathermap.org/data/2.5/forecast?q=${city}&appid=${apiKey}&units=metric&lang=ja`);
            const forecastData = await forecastResponse.json();
            
            // 日付ごとに最初のデータのみを抽出（12時のデータを優先）
            const dailyForecasts = [];
            const seenDates = new Set();
            
            for (const item of forecastData.list) {
                const date = new Date(item.dt * 1000);
                const dateString = date.toDateString();
                if (!seenDates.has(dateString) && dailyForecasts.length < 5) {
                    seenDates.add(dateString);
                    dailyForecasts.push({
                        date: date,
                        temp: Math.round(item.main.temp),
                        icon: item.weather[0].icon,
                        description: item.weather[0].description
                    });
                }
            }

            this.weather = {
                current: {
                    temp: Math.round(currentData.main.temp),
                    description: currentData.weather[0].description,
                    icon: currentData.weather[0].icon
                },
                forecast: dailyForecasts,
                loading: false
            };
        } catch (error) {
            console.error('天気データの取得に失敗しました:', error);
            this.weather.loading = false;
        }
    }
}" 
x-init="fetchWeather(); setInterval(fetchWeather, 1800000)"
class="bg-white border-b border-gray-100">
    <!-- プライマリナビゲーションメニュー -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- ロゴ -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('index') }}">
                        <img src="{{ asset('images/kensho.jpg') }}" alt="Logo" class="block h-9 w-auto">
                    </a>
                </div>

                <!-- ナビゲーションリンク -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('index')" :active="request()->routeIs('index')">
                        {{ __('Top') }}
                    </x-nav-link>
                </div>

                <!-- デスクトップ天気情報 -->
                <div class="hidden lg:flex lg:items-center lg:ml-6">
                    <div class="flex items-center bg-gray-50 rounded-lg px-3 py-2 shadow-sm" x-show="!weather.loading">
                        <!-- 現在の天気 -->
                        <div class="flex items-center border-r border-gray-200 pr-3">
                            <span class="text-sm font-medium text-gray-700">沖縄</span>
                            <div class="flex items-center ml-2">
                                <img :src="'https://openweathermap.org/img/wn/' + weather.current.icon + '.png'"
                                     class="w-10 h-10" :alt="weather.current.description">
                                <div class="flex flex-col ml-1">
                                    <span class="text-xl font-medium text-gray-700" x-text="weather.current.temp + '°'"></span>
                                    <span class="text-xs text-gray-600" x-text="weather.current.description"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 5日間予報 -->
                        <div class="flex space-x-3 pl-3">
                            <template x-for="(forecast, index) in weather.forecast" :key="index">
                                <div class="flex flex-col items-center px-1">
                                    <span class="text-xs text-gray-600" x-text="formatDate(forecast.date)"></span>
                                    <img :src="'https://openweathermap.org/img/wn/' + forecast.icon + '.png'"
                                         class="w-8 h-8" :alt="forecast.description">
                                    <span class="text-sm font-medium text-gray-700" x-text="forecast.temp + '°'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <!-- ローディング表示 -->
                    <div x-show="weather.loading" class="flex items-center space-x-2 text-gray-500 px-3">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">読み込み中...</span>
                    </div>
                </div>
            </div>

            <!-- モバイル天気情報 -->
            <div class="lg:hidden flex items-center mr-4">
                <div class="bg-gray-50 rounded-lg px-3 py-2 shadow-sm" x-show="!weather.loading">
                    <div class="flex items-center">
                        <span class="text-xs font-medium text-gray-700 mr-2">沖縄</span>
                        <img :src="'https://openweathermap.org/img/wn/' + weather.current.icon + '.png'"
                             class="w-8 h-8" :alt="weather.current.description">
                        <span class="text-lg font-medium text-gray-700" x-text="weather.current.temp + '°'"></span>
                    </div>
                </div>
                <div x-show="weather.loading" class="flex items-center">
                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>

            <!-- 拡張検索バー -->
            <div class="flex items-center">
                <form method="GET" action="{{ route('search') }}" class="flex space-x-2">
                    <input type="text" name="query" placeholder="イベント名、カテゴリー、場所で検索" 
                           class="w-64 border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                           value="{{ request('query') }}">
                    <select name="time_filter" class="border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="" {{ request('time_filter') == '' ? 'selected' : '' }}>全期間</option>
                        <option value="current" {{ request('time_filter') == 'current' ? 'selected' : '' }}>開催中</option>
                        <option value="upcoming" {{ request('time_filter') == 'upcoming' ? 'selected' : '' }}>今後の予定</option>
                        <option value="past" {{ request('time_filter') == 'past' ? 'selected' : '' }}>過去のイベント</option>
                    </select>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        検索
                    </button>
                </form>
            </div>

            <!-- 設定ドロップダウン -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @if (Auth::check())
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ml-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('mypage')">
                                {{ __('マイページ') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('プロフィール') }}
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('rss-importer.index')">
                                {{ __('RSSインポーター') }}
                            </x-dropdown-link>

                            <!-- 認証 -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('ログアウト') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">ログイン</a>
                    <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">登録</a>
                @endif
            </div>

            <!-- ハンバーガーメニュー -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- モバイルメニュー -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('index')" :active="request()->routeIs('index')">
                {{ __('Top') }}
            </x-responsive-nav-link>
            
            <!-- モバイル詳細天気情報 -->
            <div class="px-4 py-2">
                <div class="bg-gray-50 rounded-lg p-4 shadow-sm" x-show="!weather.loading">
                    <!-- 現在の天気 -->
                    <div class="flex items-center justify-center mb-3 pb-3 border-b border-gray-200">
                        <span class="text-sm font-medium text-gray-700 mr-2">沖縄</span>
                        <img :src="'https://openweathermap.org/img/wn/' + weather.current.icon + '@2x.png'"
                             class="w-12 h-12" :alt="weather.current.description">
                        <div class="flex flex-col ml-2">
                            <span class="text-2xl font-medium text-gray-700" x-text="weather.current.temp + '°'"></span>
                            <span class="text-sm text-gray-600" x-text="weather.current.description"></span>
                        </div>
                    </div>


                    <!-- 5日間予報 -->
                    <div class="flex flex-wrap justify-center gap-3">
                        <template x-for="(forecast, index) in weather.forecast" :key="index">
                            <div class="flex flex-col items-center p-2 bg-white bg-opacity-50 rounded-lg">
                                <span class="text-xs text-gray-600" x-text="formatDate(forecast.date)"></span>
                                <img :src="'http://openweathermap.org/img/wn/' + forecast.icon + '.png'"
                                     class="w-8 h-8" :alt="forecast.description">
                                <span class="text-sm font-medium text-gray-700" x-text="forecast.temp + '°'"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div x-show="weather.loading" class="text-sm text-gray-500 text-center">
                    天気情報を読み込み中...
                </div>
            </div>
        </div>

        <!-- レスポンシブ設定オプション -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            @if (Auth::check())
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('mypage')">
                        {{ __('マイページ') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('プロフィール') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('rss-importer.index')">
                        {{ __('RSSインポーター') }}
                    </x-responsive-nav-link>

                    <!-- 認証 -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('ログアウト') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="px-4">
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">ログイン</a>
                    <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">登録</a>
                </div>
            @endif
        </div>
    </div>
</nav>