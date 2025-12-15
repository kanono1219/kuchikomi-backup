<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categories')->insert([
            ['name' => '祭り', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '音楽イベント', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '展示会', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'スポーツイベント', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '式典', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'RSS配信', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}