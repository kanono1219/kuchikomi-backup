<?php

namespace App\Http\Controllers;

use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\BuddyPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    public function __construct()
    {
        \Log::info('ChatController constructed');
        $this->middleware('auth');
    }

    public function index()
    {
        \Log::info('====== Starting Chat Index Method ======');
        $user_id = Auth::id();
        \Log::info('Current user ID: ' . $user_id);
    
        try {
            $chatRooms = ChatRoom::where(function($query) use ($user_id) {
                $query->whereHas('buddyPost', function($q) use ($user_id) {
                    $q->where('user_id', $user_id)
                      ->orWhereHas('participants', function($p) use ($user_id) {
                          $p->where('user_id', $user_id);
                      });
                });
            })
            ->with([
                'buddyPost.event',
                'buddyPost.user',
                'messages' => function($query) {
                    $query->latest()->take(1)->with('user');
                }
            ])
            ->get();
    
            // データの詳細をログに出力
            foreach ($chatRooms as $room) {
                \Log::info("===== Room Details =====");
                \Log::info("Room ID: " . $room->id);
                \Log::info("BuddyPost exists: " . ($room->buddyPost ? 'yes' : 'no'));
                if ($room->buddyPost) {
                    \Log::info("Event exists: " . ($room->buddyPost->event ? 'yes' : 'no'));
                    \Log::info("Event name: " . ($room->buddyPost->event->name ?? 'null'));
                    \Log::info("BuddyPost title: " . ($room->buddyPost->title ?? 'null'));
                }
            }
    
            // ビューに渡す前にデータの存在を確認
            \Log::info('View data:', [
                'chatRooms count' => $chatRooms->count(),
                'has data' => $chatRooms->isNotEmpty() ? 'yes' : 'no'
            ]);
    
            return view('chat.index', compact('chatRooms'));
    
        } catch (\Exception $e) {
            \Log::error('Error in chat index: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'チャットルームの取得中にエラーが発生しました。');
        }
    }

    public function show(ChatRoom $chatRoom)
    {
        \Log::info('Showing chat room ID: ' . $chatRoom->id);
        
        // チャットルームへのアクセス権確認
        if (!$this->canAccessChatRoom($chatRoom)) {
            \Log::error('Access denied to chat room: ' . $chatRoom->id);
            abort(403, 'このチャットルームにアクセスする権限がありません。');
        }
    
        try {
            // メッセージを取得
            $messages = $chatRoom->messages()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();
    
            \Log::info('Found messages: ' . $messages->count());
    
            // 未読メッセージを既読に更新
            $chatRoom->messages()
                ->where('user_id', '!=', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
    
            return view('chat.show', compact('chatRoom', 'messages'));
            
        } catch (\Exception $e) {
            \Log::error('Error in chat show: ' . $e->getMessage());
            throw $e;
        }
    }

    public function store(Request $request, ChatRoom $chatRoom)
    {
        if (!$this->canAccessChatRoom($chatRoom)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $chatRoom->messages()->create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
        ]);

        $message->load('user');

        if ($request->ajax()) {
            return response()->json([
                'message' => view('chat.message', compact('message'))->render(),
            ]);
        }

        return back();
    }

    public function createRoom(BuddyPost $buddyPost)
    {
        \Log::info('Creating chat room for buddy post ID: ' . $buddyPost->id);
        \Log::info('Current user ID: ' . Auth::id());
        \Log::info('Buddy post owner ID: ' . $buddyPost->user_id);

        $isParticipant = $buddyPost->participants()
            ->where('user_id', Auth::id())
            ->exists();

        \Log::info('Is participant: ' . ($isParticipant ? 'yes' : 'no'));

        $chatRoom = ChatRoom::firstOrCreate([
            'buddy_post_id' => $buddyPost->id,
        ]);

        \Log::info('Chat room created/found with ID: ' . $chatRoom->id);

        return redirect()->route('chat.show', $chatRoom);
    }

    private function canAccessChatRoom(ChatRoom $chatRoom): bool
    {
        $buddyPost = $chatRoom->buddyPost;
        
        if ($buddyPost->user_id === Auth::id()) {
            return true;
        }

        return $buddyPost->participants()
            ->where('user_id', Auth::id())
            ->exists();
    }
}