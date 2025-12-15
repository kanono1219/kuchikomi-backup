<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'overview',
        'category_id',
        'location',
        'address',
        'latitude',
        'longitude',
        'start_date',
        'end_date',
        'image_url',
        'external_url',
        'google_calendar_event_id',
        'user_id',
        'venue_type',
    ];

    protected $attributes = [
        'category_id' => '1',
        'location' => '1',
        'start_date' => '2024-08-01 18:00:00',
        'end_date' => '2024-08-01 21:00:00',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected $appends = ['average_rating'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?: 0;
    }

    public function scopeWithAvgRating($query)
    {
        return $query->withAvg('reviews', 'rating');
    }

    public function scopeWithReviewsCount($query)
    {
        return $query->withCount('reviews');
    }

    public function scopePopular($query)
    {
        return $query->withAvgRating()
                     ->withReviewsCount()
                     ->orderByDesc('reviews_avg_rating')
                     ->orderByDesc('reviews_count');
    }

    public function getPaginateByLimit(int $limit_count = 5)
    {
        return $this::with('category')->orderBy('updated_at', 'DESC')->paginate($limit_count);
    }
    
    
    public function favoritedBy()
    {
    return $this->belongsToMany(User::class, 'event_favorites')->withTimestamps();
    }
    
    public function getFavoritesCountAttribute()
    {
    return $this->favorites()->count();
    }   
     public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_favorites')->withTimestamps();
    }
    public function buddyPosts()
    {
    return $this->hasMany(BuddyPost::class);
    }
}