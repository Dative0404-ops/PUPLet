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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            // Practice quizzes belong to one topic; a mock examination draws
            // its items from every area, so it has no single topic.
            $table->foreignId('topic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('practice');
            $table->string('title', 150);
            $table->unsignedSmallInteger('item_count');
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->timestamps();

            $table->index(['type', 'topic_id']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('total_items');
            $table->unsignedSmallInteger('answered_items')->default(0);
            $table->unsignedSmallInteger('score')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quizzes');
    }
};
