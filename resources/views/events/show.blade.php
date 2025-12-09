<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $event->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
</head>
<x-app-layout>
    <div class="py-8">
        <div class="container mx-auto px-4 max-w-4xl">
            <!-- 成功・エラーメッセージの表示 -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <h1 class="text-4xl font-bold mb-8 text-gray-800 border-b-2 pb-2">{{ $event->name }}</h1>
            
            <!-- イベント画像とのセクション -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
                @if($event->image_path)
                    <img src="{{ asset('storage/' . $event->image_path) }}" alt="{{ $event->name }}" class="w-full h-80 object-cover">
                @elseif($event->image_url)
                    <img src="{{ $event->image_url }}" alt="{{ $event->name }}" class="w-full h-80 object-cover">
                @else
                    <div class="w-full h-80 bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-500 text-lg">画像がありません</span>
                    </div>
                @endif
                
                <div class="p-6">
                    <span class="inline-block bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full mb-4">{{ $event->category->name }}</span>
                    
                    <p class="text-gray-700 mb-6 text-lg leading-relaxed">{{ $event->overview }}</p>
                    
                    <div class="text-sm text-gray-600 space-y-2">
                        <p class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            開催場所: {{ $event->location }}
                        </p>
                        <p class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            住所: {{ $event->address ?? '住所情報がありません' }}
                        </p>
                        <p class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            開始日時: {{ $event->start_date->format('Y年m月d日 H:i') }}
                        </p>
                        <p class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            終了日時: {{ $event->end_date->format('Y年m月d日 H:i') }}
                        </p>
                        @if($event->external_url)
                            <p class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                <a href="{{ $event->external_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 transition duration-300">
                                    イベント公式サイト
                                </a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- 地図表示 -->
            <div id="map" class="w-full h-64 mb-8 rounded-lg shadow-md"></div>
            
            <!-- ========== ★ 修正版：お気に入いボタン ★ ========== -->
            <div class="mt-6 mb-8">
                @auth
                    <form action="{{ route('events.favorite', $event) }}" method="POST" class="inline favorite-form">
                        @csrf
                        <button type="submit" class="favorite-button {{ $isFavorited ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600' }} text-white font-bold py-3 px-6 rounded-lg transition duration-300 flex items-center shadow-md hover:shadow-lg">
                            <svg class="w-6 h-6 mr-2" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <span class="favorite-text">{{ $isFavorited ? '💔 お気に入いを解除' : '❤️ お気に入いに追加' }}</span>
                            <span class="favorite-count ml-2 bg-white bg-opacity-30 px-2 py-1 rounded">{{ $event->favorites_count }}</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300 flex items-center shadow-md hover:shadow-lg inline-block">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        ログインしてお気に入いに追加
                    </a>
                @endauth
            </div>

            @auth
                @if($event->user_id === Auth::id())
                    <div class="flex space-x-4 mb-8">
                        <a href="/events/{{ $event->id }}/edit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">編集</a>
                        <form action="/events/{{ $event->id }}" id="delete_form" method="post">
                            @csrf
                            @method('DELETE')
                            <button type="button" onclick="deleteEvent()" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-300">削除</button>
                        </form>
                    </div>
                @endif
            @endauth
            
            <!-- 参加者募集セクション -->
            <div class="mt-12 mb-8">
                <h2 class="text-3xl font-bold mb-6 text-gray-800 border-b-2 pb-2">参加者募集</h2>
                
                @auth
                    <form action="/events/{{ $event->id }}/buddy-posts" method="POST" class="mb-8 bg-white shadow-md rounded px-8 pt-6 pb-8">
                        @csrf
                        <div class="mb-4">
                            <label for="title" class="block text-gray-700 text-sm font-bold mb-2">募集タイトル</label>
                            <input type="text" name="title" id="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required placeholder="例：一緒に参加してくれる方募集！">
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="block text-gray-700 text-sm font-bold mb-2">募集メッセージ</label>
                            <textarea name="message" id="message" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required placeholder="募集の詳細を記入してください"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="preferred_age" class="block text-gray-700 text-sm font-bold mb-2">希望年齢層</label>
                                <select name="preferred_age" id="preferred_age" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">指定なし</option>
                                    <option value="10-20">10代〜20代</option>
                                    <option value="20-30">20代〜30代</option>
                                    <option value="30-40">30代〜40代</option>
                                    <option value="40-50">40代〜50代</option>
                                    <option value="50+">50代以上</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="max_participants" class="block text-gray-700 text-sm font-bold mb-2">募集人数</label>
                                <select name="max_participants" id="max_participants" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    @for($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}">{{ $i }}人</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="meeting_point" class="block text-gray-700 text-sm font-bold mb-2">集合場所</label>
                            <input type="text" name="meeting_point" id="meeting_point" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="例:イベント会場入口前">
                        </div>
                        
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">募集を投稿</button>
                    </form>
                @else
                    <p class="mb-8 text-lg">参加者を募集するには<a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 underline">ログイン</a>してください。</p>
                @endauth
            
                <!-- 募集一覧 -->
                <div class="space-y-6">
                    @forelse($event->buddyPosts as $post)
                        <div class="bg-white shadow-md rounded-lg p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold">{{ $post->title }}</h3>
                                    <p class="text-sm text-gray-600">投稿者: {{ $post->user->name }}</p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $post->status === 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $post->status === 'open' ? '募集中' : '募集終了' }}
                                </span>
                            </div>
            
                            <p class="text-gray-700 mb-4">{{ $post->message }}</p>
            
                            <div class="grid grid-cols-2 gap-4 mb-4 text-sm text-gray-600">
                                @if($post->preferred_age)
                                    <div>
                                        <span class="font-semibold">希望年齢層:</span>
                                        <span>{{ $post->preferred_age }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="font-semibold">募集人数:</span>
                                    <span>{{ $post->participants->count() }}/{{ $post->max_participants }}人</span>
                                </div>
                                @if($post->meeting_point)
                                    <div>
                                        <span class="font-semibold">集合場所:</span>
                                        <span>{{ $post->meeting_point }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="font-semibold">投稿日時:</span>
                                    <span>{{ $post->created_at->format('Y/m/d H:i') }}</span>
                                </div>
                            </div>
            
                            @auth
                                @if($post->status === 'open' && $post->user_id !== Auth::id() && !$post->participants->contains(Auth::id()))
                                    <form action="/events/{{ $event->id }}/buddy-posts/{{ $post->id }}/join" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                                            参加リクエスト
                                        </button>
                                    </form>
                                @elseif($post->participants->contains(Auth::id()))
                                    <span class="inline-block bg-gray-100 text-gray-700 font-bold py-2 px-4 rounded">
                                        参加リクエスト済み
                                    </span>
                                @endif
            
                                @if($post->user_id === Auth::id())
                                    <form action="/events/{{ $event->id }}/buddy-posts/{{ $post->id }}" method="POST" class="inline ml-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-300" onclick="return confirm('この募集を削除してもよろしいですか？')">
                                            削除
                                        </button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    @empty
                        <p class="text-gray-600 text-center py-8">現在募集はありません。最初の募集を投稿してみましょう！</p>
                    @endforelse
                </div>
            </div>
            
            <!-- レビューセクション -->
            <div class="mt-12">
                <h2 class="text-3xl font-bold mb-6 text-gray-800 border-b-2 pb-2">口コミ・レビュー</h2>
                
                @auth
                    <form action="/reviews/events/{{ $event->id }}" method="POST" class="mb-8 bg-white shadow-md rounded px-8 pt-6 pb-8">
                        @csrf
                        <div class="mb-4">
                            <label for="review_title" class="block text-gray-700 text-sm font-bold mb-2">タイトル</label>
                            <input type="text" name="title" id="review_title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <div class="mb-4">
                            <label for="review_body" class="block text-gray-700 text-sm font-bold mb-2">本文</label>
                            <textarea name="body" id="review_body" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="rating" class="block text-gray-700 text-sm font-bold mb-2">評価</label>
                            <select name="rating" id="rating" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="5">5 - 最高</option>
                                <option value="4">4 - 良い</option>
                                <option value="3">3 - 普通</option>
                                <option value="2">2 - イマイチ</option>
                                <option value="1">1 - 悪い</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">レビューを投稿</button>
                    </form>
                @else
                    <p class="mb-8 text-lg">レビューを投稿するには<a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 underline">ログイン</a>してください。</p>
                @endauth

                @if($reviews->count() > 0)
                    @foreach($reviews as $review)
                        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                            <!-- レビュー本体 -->
                            <div class="flex items-start mb-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                        {{ substr($review->user->name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $review->title }}</h4>
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                            <span class="ml-2 text-sm text-gray-600">{{ $review->rating }}/5</span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ $review->user->name }} • {{ $review->created_at->diffForHumans() }}</p>
                                    <p class="text-gray-700 mt-3">{{ $review->body }}</p>
                                    
                                    <!-- いいねボタンとコメントボタン -->
                                    <div class="flex items-center mt-4 space-x-4">
                                        @auth
                                            <form action="{{ $review->likes()->where('user_id', Auth::id())->exists() ? route('likes.destroy', $review) : route('likes.store', $review) }}" method="POST" class="inline">
                                                @csrf
                                                @if($review->likes()->where('user_id', Auth::id())->exists())
                                                    @method('DELETE')
                                                @endif
                                                <button type="submit" class="flex items-center text-sm {{ $review->likes()->where('user_id', Auth::id())->exists() ? 'text-red-600' : 'text-gray-600' }} hover:text-red-600 transition">
                                                    <svg class="w-5 h-5 mr-1" fill="{{ $review->likes()->where('user_id', Auth::id())->exists() ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                    </svg>
                                                    <span>{{ $review->likes->count() }}</span>
                                                </button>
                                            </form>
                                        @else
                                            <span class="flex items-center text-sm text-gray-600">
                                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                </svg>
                                                <span>{{ $review->likes->count() }}</span>
                                            </span>
                                        @endauth
                                        
                                        <button onclick="toggleComments({{ $review->id }})" class="flex items-center text-sm text-gray-600 hover:text-blue-600 transition">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                            <span>コメント ({{ $review->comments_count ?? $review->comments->count() }})</span>
                                        </button>

                                        @auth
                                            @if(Auth::id() === $review->user_id)
                                                <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" onclick="return confirm('本当に削除しますか？')" class="text-sm text-red-600 hover:text-red-800">削除</button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                            
                            <!-- コメントセクション（最初は非表示） -->
                            <div id="comments-{{ $review->id }}" class="mt-6 pl-16 hidden">
                                <h5 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">コメント</h5>
                                
                                <!-- コメント一覧 -->
                                @forelse($review->comments as $comment)
                                    <div class="bg-gray-50 rounded-lg p-4 mb-3 border-l-4 border-blue-500">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <span class="font-semibold text-gray-900">{{ $comment->user->name }}</span>
                                                    <span class="mx-2 text-gray-400">•</span>
                                                    <span class="text-sm text-gray-600">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                                <h6 class="font-medium text-gray-800 mt-2">{{ $comment->title }}</h6>
                                                <p class="text-gray-700 mt-1">{{ $comment->body }}</p>
                                            </div>
                                            
                                            @auth
                                                @if(Auth::id() === $comment->user_id)
                                                    <div class="flex space-x-2 ml-4">
                                                        <form action="{{ route('review-comments.destroy', $comment) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" onclick="return confirm('本当に削除しますか？')" class="text-red-600 hover:text-red-800 text-sm">
                                                                削除
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            @endauth
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-gray-500 text-sm py-4">まだコメントがありません。最初のコメントを投稿してみましょう！</p>
                                @endforelse
                                
                                <!-- コメント投稿フォーム -->
                                @auth
                                    <div class="mt-6 bg-white border-2 border-gray-200 rounded-lg p-4">
                                        <h6 class="font-semibold text-gray-800 mb-3">コメントを投稿</h6>
                                        <form action="{{ route('review-comments.store', $review) }}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <input type="text" 
                                                       name="title" 
                                                       placeholder="コメントタイトル" 
                                                       required
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                            </div>
                                            <div class="mb-3">
                                                <textarea name="body" 
                                                          rows="3" 
                                                          placeholder="コメント内容を入力してください（最大1000文字）" 
                                                          required
                                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"></textarea>
                                            </div>
                                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200 shadow-md">
                                                コメントする
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600 mt-4 bg-gray-50 p-3 rounded border border-gray-200">
                                        コメントするには<a href="{{ route('login') }}" class="text-blue-600 hover:underline font-semibold">ログイン</a>してください
                                    </p>
                                @endauth
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="mt-8">
                        {{ $reviews->appends(request()->query())->links() }}
                    </div>
                @else
                    <p class="text-lg text-gray-600">まだレビューがありません。</p>
                @endif
            </div>
            
            <a href="/" class="inline-block mt-8 text-blue-600 hover:text-blue-800 transition duration-300">← イベント一覧に戻る</a>
        </div>
    </div>

    <!-- ========== ★ 修正版JavaScript ★ ========== -->
    <script>
        // ★ お気に入いボタンの AJAX 処理
        document.addEventListener('DOMContentLoaded', function() {
            const favoriteForm = document.querySelector('.favorite-form');
            if (favoriteForm) {
                favoriteForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const url = this.action;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    const favoriteBtn = document.querySelector('.favorite-button');
                    const favoriteText = document.querySelector('.favorite-text');
                    const favoriteCount = document.querySelector('.favorite-count');
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // ボタンの状態を更新
                            if (data.isFavorited) {
                                // お気に入い追加状態
                                favoriteBtn.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                                favoriteBtn.classList.add('bg-red-500', 'hover:bg-red-600');
                                favoriteText.textContent = '💔 お気に入いを解除';
                                favoriteBtn.querySelector('svg').setAttribute('fill', 'currentColor');
                            } else {
                                // お気に入い解除状態
                                favoriteBtn.classList.remove('bg-red-500', 'hover:bg-red-600');
                                favoriteBtn.classList.add('bg-blue-500', 'hover:bg-blue-600');
                                favoriteText.textContent = '❤️ お気に入いに追加';
                                favoriteBtn.querySelector('svg').setAttribute('fill', 'none');
                            }
                            
                            // 個数を更新
                            favoriteCount.textContent = data.favoritesCount;
                            
                            // 成功メッセージを表示
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message || 'エラーが発生しました', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('エラーが発生しました', 'error');
                    });
                });
            }
        });

        /**
         * 通知メッセージを表示
         */
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                background-color: ${type === 'success' ? '#10B981' : '#EF4444'};
                color: white;
                font-weight: bold;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideIn 0.3s ease-in-out;
            `;

            document.body.appendChild(notification);

            // 3秒後に削除
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // アニメーション定義
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        function deleteEvent() {
            'use strict'
            if (confirm('削除すると復元できません。\n本当に削除しますか？')) {
                document.getElementById('delete_form').submit();
            }
        }

        // コメントセクションの表示/非表示を切り替え
        function toggleComments(reviewId) {
            const commentsSection = document.getElementById(`comments-${reviewId}`);
            commentsSection.classList.toggle('hidden');
        }

        function initMap() {
            var eventLocation = {
                lat: {{ $event->latitude ?? 26.2124 }},
                lng: {{ $event->longitude ?? 127.6809 }}
            };

            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: eventLocation
            });

            var marker = new google.maps.Marker({
                position: eventLocation,
                map: map,
                title: '{{ $event->name }}'
            });

            var infoWindow = new google.maps.InfoWindow({
                content: '<div><strong>{{ $event->name }}</strong><br>{{ $event->address ?? "住所情報がありません" }}</div>'
            });

            marker.addListener('click', function() {
                infoWindow.open(map, marker);
            });
        }

        function loadMapScript() {
            var script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initMap';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        document.addEventListener('DOMContentLoaded', loadMapScript);
    </script>
</x-app-layout>
</html>