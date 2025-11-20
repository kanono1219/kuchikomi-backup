<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'buddy_post_id',
    ];

    public function buddyPost(): BelongsTo
    {
        return $this->belongsTo(BuddyPost::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function participants()
    {
        return $this->hasManyThrough(
            User::class,
            BuddyPostParticipant::class,
            'buddy_post_id', // 中間テーブルの外部キー
            'id',           // usersテーブルのローカルキー
            'buddy_post_id',// chat_roomsテーブルの外部キー
            'user_id'       // 中間テーブルのローカルキー
        );
    }
}