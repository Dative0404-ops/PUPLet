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
        // Review lessons, one row per lesson.
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            // Deleting a topic also deletes its lessons.
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('summary')->nullable();
            // Key terms, stored as JSON.
            $table->json('key_terms')->nullable();
            // Worked examples, stored as JSON.
            $table->json('examples')->nullable();
            $table->text('study_notes')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(10);
            $table->timestamps();
        });

        // How far each student has read a lesson.
        Schema::create('lesson_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            // Percent read, from 0 to 100.
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // One progress row per student per lesson.
            $table->unique(['user_id', 'lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_progresses');
        Schema::dropIfExists('lessons');
    }
};
