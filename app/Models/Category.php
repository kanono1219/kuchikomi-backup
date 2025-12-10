<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    //「1対多」の関係なので'posts'と複数形に
    public function events()   
    {
        return $this->hasMany(Event::class);  
    }
    public function getByCategory(int $limit_count = 12)
    {
        return $this->events()
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('updated_at', 'DESC')
            ->paginate($limit_count);
    }
}
