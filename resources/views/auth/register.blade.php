<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        
        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        
        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                          type="password"
                          name="password"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        
        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                          type="password"
                          name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        
        <!-- Return and Register Actions -->
        <div class="flex items-center justify-end mt-4">
            <a class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800 underline" href="/">
                戻る
            </a>
            
            <x-primary-button class="ml-4">
                登録
            </x-primary-button>
        </div>
    </form>

    <!-- ★ログインページへの目立つセクション★ -->
    <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="bg-gray-100 rounded-lg p-4 text-center border-2 border-gray-300">
            <p class="text-sm text-gray-800 mb-3">
                <span class="font-semibold">既にアカウントをお持ちの方</span>
            </p>
            <p class="text-xs text-gray-600 mb-4">
                こちらからログインしてご利用ください
            </p>
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full sm:w-auto shadow-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                ログインページへ
            </a>
        </div>
    </div>
</x-guest-layout>