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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function favoriteEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_favorites')->withTimestamps();
    }

    // 追加するリレーション
    
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
    public function participatingBuddyPosts(): BelongsToMany
    {
        return $this->belongsToMany(BuddyPost::class, 'buddy_post_participants')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * 参加承認された募集のみを取得
     */
    public function approvedBuddyPosts(): BelongsToMany
    {
        return $this->participatingBuddyPosts()
            ->wherePivot('status', 'approved');
    }

    /**
     * 保留中の参加リクエストを取得
     */
    public function pendingBuddyPosts(): BelongsToMany
    {
        return $this->participatingBuddyPosts()
            ->wherePivot('status', 'pending');
    }
}