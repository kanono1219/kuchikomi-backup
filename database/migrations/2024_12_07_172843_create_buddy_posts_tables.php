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
    Schema::create('buddy_posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('event_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->text('message');
        $table->string('preferred_age')->nullable();
        $table->integer('max_participants');
        $table->string('meeting_point')->nullable();
        $table->enum('status', ['open', 'closed'])->default('open');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('buddy_post_participants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('buddy_post_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
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
        Schema::dropIfExists('buddy_posts_tables');
    }
};
