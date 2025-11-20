<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'review_id',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the user that owns the like.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the review that was liked.
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Check if a user has liked a specific review.
     *
     * @param int $userId
     * @param int $reviewId
     * @return bool
     */
    public static function hasLiked($userId, $reviewId)
    {
        return static::where('user_id', $userId)
                     ->where('review_id', $reviewId)
                     ->exists();
    }
}