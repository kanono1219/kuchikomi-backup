<?php

// database/migrations/[timestamp]_add_notification_settings_to_users_table.php
namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('event_notifications_enabled')->default(true);
            $table->integer('notification_days_before')->default(7);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['event_notifications_enabled', 'notification_days_before']);
        });
    }
};
