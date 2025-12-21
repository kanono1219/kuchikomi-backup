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
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // RSS配信の名前
            $table->string('url'); // RSS配信のURL
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null'); // デフォルトカテゴリー
            $table->boolean('is_active')->default(true); // 有効/無効
            $table->timestamp('last_fetched_at')->nullable(); // 最後に取得した日時
            $table->integer('fetch_interval')->default(3600); // 取得間隔（秒）デフォルト1時間
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rss_feeds');
    }
};
