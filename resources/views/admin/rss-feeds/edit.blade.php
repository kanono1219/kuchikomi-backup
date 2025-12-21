<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('RSS配信の編集') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-lg rounded-xl">
                <div class="p-6">
                    <form action="{{ route('admin.rss-feeds.update', $rssFeed) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <!-- 名前 -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">名前 *</label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       value="{{ old('name', $rssFeed->name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- URL -->
                            <div>
                                <label for="url" class="block text-sm font-medium text-gray-700">RSS URL *</label>
                                <input type="url"
                                       name="url"
                                       id="url"
                                       value="{{ old('url', $rssFeed->url) }}"
                                       placeholder="https://example.com/feed.rss"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       required>
                                @error('url')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- カテゴリー -->
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700">デフォルトカテゴリー</label>
                                <select name="category_id"
                                        id="category_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- 選択してください --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                                {{ old('category_id', $rssFeed->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 取得間隔 -->
                            <div>
                                <label for="fetch_interval" class="block text-sm font-medium text-gray-700">取得間隔（秒）</label>
                                <input type="number"
                                       name="fetch_interval"
                                       id="fetch_interval"
                                       value="{{ old('fetch_interval', $rssFeed->fetch_interval) }}"
                                       min="60"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">デフォルト: 3600秒（1時間）</p>
                                @error('fetch_interval')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 有効/無効 -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox"
                                           name="is_active"
                                           value="1"
                                           {{ old('is_active', $rssFeed->is_active) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">有効にする</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <a href="{{ route('admin.rss-feeds.index') }}"
                               class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md transition duration-200">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-200">
                                更新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
