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
        Schema::create('review_comments', function (Blueprint $table) {
            $table->id(); // 自動増分のプライマリキー
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // usersテーブルへの外部キー
            $table->foreignId('review_id')->constrained('reviews')->onDelete('cascade'); // reviewsテーブルへの外部キー
            $table->string('title', 255); // コメントのタイトル
            $table->text('body'); // コメントの内容
            $table->timestamps(); // ← created_at と updated_at を追加
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('review_comments'); // ← タイプミス修正（coments → comments）
    }
};