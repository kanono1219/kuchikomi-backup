<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id(); // 自動増分のプライマリキー
            $table->string('title', 255); // 口コミタイトル
            $table->text('body'); // 口コミ内容
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 投稿者のID。usersテーブルへの外部キー
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade'); // イベントID。eventsテーブルへの外部キー
            $table->unsignedTinyInteger('rating'); // 5段階評価
            $table->timestamps(); // created_atとupdated_atカラム
            $table->softDeletes(); // 論理削除のためのdeleted_atカラム
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};