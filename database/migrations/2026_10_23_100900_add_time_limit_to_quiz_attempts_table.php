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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // The limit that applied when this attempt started, copied from the
            // quiz or from the student's preference so later changes cannot move
            // a deadline that is already running. Null means untimed.
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('time_limit_minutes');
        });
    }
};
