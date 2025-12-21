<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RssFeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'category_id',
        'is_active',
        'last_fetched_at',
        'fetch_interval',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_fetched_at' => 'datetime',
    ];

    /**
     * RSS配信が属するカテゴリー
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 次回取得可能かどうかを判定
     */
    public function canFetch(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_fetched_at) {
            return true;
        }

        return $this->last_fetched_at->addSeconds($this->fetch_interval)->isPast();
    }
}
