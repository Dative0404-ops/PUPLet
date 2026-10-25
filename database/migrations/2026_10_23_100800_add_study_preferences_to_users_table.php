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
            // The item target the student aims for each day.
            $table->unsignedSmallInteger('daily_target_items')->default(50)->after('specialization');

            // Minutes for a practice quiz. Null follows each quiz's own limit,
            // 0 means the student always practices untimed.
            $table->unsignedSmallInteger('practice_time_limit_minutes')->nullable()->after('daily_target_items');

            // Where the student lands after signing in.
            $table->string('default_landing_page', 20)->default('dashboard')->after('practice_time_limit_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'daily_target_items',
                'practice_time_limit_minutes',
                'default_landing_page',
            ]);
        });
    }
};
