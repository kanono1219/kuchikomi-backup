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
        if (!Schema::hasColumn('events', 'venue_type')) {
            Schema::table('events', function (Blueprint $table) {
                $table->enum('venue_type', ['indoor', 'outdoor'])->default('indoor')->after('longitude');
            });
        }
    }
    
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('venue_type');
        });
    }
};
