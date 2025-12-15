<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'image_url',
        'event_notifications_enabled',
        'notification_days_before',
        'google_calendar_token',
        'google_calendar_connected',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'google_calendar_connected' => 'boolean',
    ];

    public function favoriteEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_favorites')->withTimestamps();
    }

    /**
     * ユーザーが作成した参加者募集
     */
    public function buddyPosts(): HasMany
    {
        return $this->hasMany(BuddyPost::class);
    }

    /**
     * ユーザーが参加リクエストした募集
     */
    public function joinRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            BuddyPost::class,
            'buddy_post_join_requests',
            'user_id',
            'buddy_post_id'
        )->withTimestamps();
    }

    /**
     * ユーザーが参加しているバディ募集（エイリアス）
     */
    public function participatingBuddyPosts(): BelongsToMany
    {
        return $this->belongsToMany(
            BuddyPost::class,
            'buddy_post_join_requests',
            'user_id',
            'buddy_post_id'
        )->withPivot('status')->withTimestamps();
    }

    /**
     * ユーザーが作成したイベント
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * ユーザーが作成したレビュー
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * ユーザーが参加しているチャットルーム
     */
    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(
            ChatRoom::class,
            'chat_room_users',
            'user_id',
            'chat_room_id'
        )->withTimestamps();
    }

    /**
     * ユーザーが送信したメッセージ
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * ユーザーがしたレビューコメント
     */
    public function reviewComments(): HasMany
    {
        return $this->hasMany(ReviewComment::class);
    }

    /**
     * ユーザーがしたいいね
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(
            Review::class,
            'review_likes',
            'user_id',
            'review_id'
        )->withTimestamps();
    }
}
