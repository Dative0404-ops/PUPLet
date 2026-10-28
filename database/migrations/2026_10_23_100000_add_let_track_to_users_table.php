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
        // Add the exam track chosen by each user to the users table.
        Schema::table('users', function (Blueprint $table) {
            // Both are nullable, so existing users can leave them empty.
            $table->string('exam_type', 20)->nullable()->after('email');
            $table->string('specialization', 50)->nullable()->after('exam_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['exam_type', 'specialization']);
        });
    }
};
