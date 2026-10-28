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
        // Quizzes students take. A row is a practice quiz or a mock exam.
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            // Optional topic link: a practice quiz belongs to one topic, but a mock
            // exam draws items from every area and has none.
            $table->foreignId('topic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('practice');
            $table->string('title', 150);
            $table->unsignedSmallInteger('item_count');
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->timestamps();

            $table->index(['type', 'topic_id']);
        });

        // One row per student attempt at a quiz.
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('total_items');
            $table->unsignedSmallInteger('answered_items')->default(0);
            $table->unsignedSmallInteger('score')->default(0);
            $table->timestamp('started_at');
            // Null while the attempt is still running.
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
