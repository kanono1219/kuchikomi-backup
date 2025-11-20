<?php
namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use DateTime;

class EventSeeder extends Seeder
{
    public function run()
    {
        DB::table('events')->insert([
            [
                'name' => '豊見城祭り',
                'category_id' => 1,
                'overview' => '豊見城市内のお祭り。地元の特産品の販売や伝統芸能のステージ、花火大会など、家族で楽しめるイベントが盛りだくさん。',
                'location' => '美らSUNビーチ',
                'address' => '沖縄県豊見城市字豊見城236',
                'venue_type' => 'outdoor',
                'latitude' => 26.1931,
                'longitude' => 127.6682,
                'start_date' => '2024-08-01 18:00:00',
                'end_date' => '2024-08-01 21:00:00',
                'image_url' => 'https://example.com/images/tomigusuku-festival.jpg',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HY SKY Fes',
                'category_id' => 2,
                'overview' => '沖縄を代表するバンドHY主催の野外音楽フェス。県内外のアーティストが集結し、沖縄の青空の下で音楽を楽しめる。',
                'location' => '沖縄総合運動公園 多目的広場',
                'address' => '沖縄県沖縄市比屋根5-3-1',
                'venue_type' => 'outdoor',
                'latitude' => 26.3335,
                'longitude' => 127.7947,
                'start_date' => '2024-10-01 10:00:00',
                'end_date' => '2024-10-01 17:00:00',
                'image_url' => 'https://example.com/images/hy-sky-fes.jpg',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '沖国際祭',
                'category_id' => 1,
                'overview' => '沖縄国際大学の学園祭。学生による模擬店やステージパフォーマンス、著名人のトークショーなど、多彩なイベントを開催。',
                'location' => '沖縄国際大学',
                'address' => '沖縄県宜野湾市宜野湾2-6-1',
                'venue_type' => 'indoor',
                'latitude' => 26.2528,
                'longitude' => 127.7566,
                'start_date' => '2024-11-20 10:00:00',
                'end_date' => '2024-11-20 21:00:00',
                'image_url' => 'https://example.com/images/okiu-festival.jpg',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '首里城祭',
                'category_id' => 1,
                'overview' => '琉球王国の伝統と文化を体験できる祭り。王朝行列や伝統芸能の上演、古式に則った儀式の再現などが行われる。',
                'location' => '首里城公園',
                'address' => '沖縄県那覇市首里金城町1-2',
                'venue_type' => 'outdoor',
                'latitude' => 26.2166,
                'longitude' => 127.7190,
                'start_date' => '2024-11-01 09:00:00',
                'end_date' => '2024-11-03 21:00:00',
                'image_url' => 'https://example.com/images/shuri-castle-festival.jpg',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '沖縄国際映画祭',
                'category_id' => 3,
                'overview' => '国内外の優れた映画作品の上映や、映画製作者によるトークイベント、ワークショップなどを開催する映画の祭典。',
                'location' => '沖縄コンベンションセンター',
                'address' => '沖縄県宜野湾市真志喜4-3-1',
                'venue_type' => 'indoor',
                'latitude' => 26.2817,
                'longitude' => 127.7277,
                'start_date' => '2024-09-15 10:00:00',
                'end_date' => '2024-09-20 22:00:00',
                'image_url' => 'https://example.com/images/okinawa-film-festival.jpg',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}