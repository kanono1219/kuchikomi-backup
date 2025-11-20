<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    
    <form method="POST" action="{{ route('login') }}">
        @csrf
        
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        
        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        
        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>
        
        <!-- Login Actions -->
        <div class="flex items-center justify-end mt-4">
            <a class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800" href="/">
                戻る
            </a>
            
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ml-4" href="{{ route('password.request') }}">
                    パスワードを忘れた方
                </a>
            @endif
            
            <x-primary-button class="ml-3">
                ログイン
            </x-primary-button>
        </div>
    </form>

    <!-- ★新規追加：目立つ新規登録セクション★ -->
    <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="bg-blue-50 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-700 mb-3">
                <span class="font-semibold">初めての方へ</span>
            </p>
            <p class="text-xs text-gray-600 mb-4">
                アカウントをお持ちでない方は、新規登録からご利用ください
            </p>
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full sm:w-auto">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                新規アカウント作成
            </a>
        </div>
    </div>
</x-guest-layout>