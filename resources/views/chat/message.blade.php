<div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
    @if($message->user_id !== Auth::id())
        <div class="flex-shrink-0 mr-3">
            @if($message->user->image_url)
                <img src="{{ $message->user->image_url }}" 
                     alt="{{ $message->user->name }}" 
                     class="h-8 w-8 rounded-full">
            @else
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <span class="text-sm text-gray-600">
                        {{ substr($message->user->name, 0, 1) }}
                    </span>
                </div>
            @endif
        </div>
    @endif

    <div class="flex max-w-xs lg:max-w-md {{ $message->user_id === Auth::id() ? 'flex-row-reverse' : 'flex-row' }} items-end gap-2">
        <div class="rounded-2xl px-4 py-2 {{ $message->user_id === Auth::id() 
            ? 'bg-blue-600 text-white' 
            : 'bg-white border border-gray-200 text-gray-900' }}">
            <p class="text-sm">{{ $message->message }}</p>
            <div class="text-xs mt-1 {{ $message->user_id === Auth::id() ? 'text-blue-100' : 'text-gray-500' }}">
                {{ $message->created_at->format('H:i') }}
                @if($message->user_id === Auth::id() && $message->is_read)
                    <span class="ml-1">既読</span>
                @endif
            </div>
        </div>
    </div>
</div>