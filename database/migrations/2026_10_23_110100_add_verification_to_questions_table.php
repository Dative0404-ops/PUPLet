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
        // Add review and verification details to the questions table.
        Schema::table('questions', function (Blueprint $table) {
            // Either "draft", "published" or "archived".
            $table->string('status', 20)->default('published')->after('explanation');
            $table->text('reference')->nullable()->after('status');
            $table->string('source', 150)->nullable()->after('reference');
            // The reviewer who checked the question; kept as null if that user is deleted.
            $table->foreignId('reviewed_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
            // Null until the question is verified.
            $table->timestamp('verified_at')->nullable()->after('reviewed_by');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'reference', 'source', 'reviewed_by', 'verified_at']);
        });
    }
};
