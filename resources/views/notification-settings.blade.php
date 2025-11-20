<div class="bg-white overflow-hidden shadow-lg rounded-xl mt-8">
    <div class="p-6">
        <h3 class="text-2xl font-bold mb-6 text-gray-800">{{ __('通知設定') }}</h3>
        
        <form action="{{ route('notification-settings.update') }}" method="POST">
            @csrf
            @method('PATCH')
            
            <div class="space-y-6">
                <!-- 通知のオン・オフ設定 -->
                <div class="flex items-center justify-between">
                    <label for="event_notifications_enabled" class="text-gray-700 font-medium">
                        イベント通知
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               name="event_notifications_enabled"
                               id="event_notifications_enabled"
                               class="sr-only peer"
                               {{ auth()->user()->event_notifications_enabled ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 
                                  peer-focus:ring-blue-300 rounded-full peer 
                                  peer-checked:after:translate-x-full peer-checked:after:border-white 
                                  after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                                  after:bg-white after:border-gray-300 after:border after:rounded-full 
                                  after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                        </div>
                    </label>
                </div>

                <!-- 通知日数設定 -->
                <div class="flex items-center justify-between">
                    <label for="notification_days_before" class="text-gray-700 font-medium">
                        イベント開催前の通知タイミング
                    </label>
                    <div class="flex items-center">
                        <select name="notification_days_before" 
                                id="notification_days_before"
                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 
                                       focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            @foreach([1, 3, 5, 7, 14, 30] as $days)
                                <option value="{{ $days }}" 
                                        {{ auth()->user()->notification_days_before == $days ? 'selected' : '' }}>
                                    {{ $days }}日前
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 
                               transition duration-300 focus:outline-none focus:ring-2 
                               focus:ring-blue-500 focus:ring-offset-2">
                    設定を保存
                </button>
            </div>
        </form>
    </div>
</div>
