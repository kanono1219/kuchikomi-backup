<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'body',
        'user_id',
        'event_id',
        'rating',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the user that wrote the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event that was reviewed.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the comments for the review.
     */
    public function comments()
    {
        return $this->hasMany(ReviewComment::class);
    }

    /**
     * Get the likes for the review.
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}