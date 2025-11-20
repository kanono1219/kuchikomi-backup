<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuddyPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'user_id',
        'title',
        'message',
        'preferred_age',
        'max_participants',
        'meeting_point',
        'status'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'buddy_post_participants')
            ->withPivot('status')
            ->withTimestamps();
    }
}
