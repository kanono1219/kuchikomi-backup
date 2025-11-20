<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    public function run()
    {
        Review::create([
            'title' => '久しぶり',
            'body' => 'コロナ で延期続きで久しぶりの開催でしたが最高でした',
            'rating' => 5,
            'user_id' => 1,
            'event_id' => 1,
        ]);

        Review::create([
            'title' => 'HY',
            'body' => '地域密着型フェスなだけあって沖縄出身アーティストばかりでした。',
            'rating' => 4,
            'user_id' => 2,
            'event_id' => 2,
        ]);
    }
}