<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('google_calendar_connected')->default(false)->after('notification_days_before');
            $table->longText('google_calendar_token')->nullable()->after('google_calendar_connected');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('google_calendar_event_id')->nullable()->after('external_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_calendar_connected', 'google_calendar_token']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('google_calendar_event_id');
        });
    }
};