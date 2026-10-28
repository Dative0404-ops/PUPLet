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
        // Add the account role and suspension flag to the users table.
        Schema::table('users', function (Blueprint $table) {
            // Either "student" or "admin".
            $table->string('role', 20)->default('student')->after('password');
            // Null unless the account is suspended.
            $table->timestamp('suspended_at')->nullable()->after('default_landing_page');

            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'suspended_at']);
        });
    }
};
