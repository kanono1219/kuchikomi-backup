<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleCalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_event_id',
        'name',
        'description',
        'location',
        'start_date',
        'end_date',
        'html_link',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * このイベントの所有者
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
