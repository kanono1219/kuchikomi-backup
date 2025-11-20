<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewComment extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     */
    protected $fillable = [
        'user_id',
        'review_id',
        'title',
        'body',
    ];

    /**
     * コメントを投稿したユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * コメントが付いているレビュー
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}