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
        // LET subjects, grouped by area (gen_ed, prof_ed, specialization).
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('area', 20)->index();
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Two subjects in the same area cannot share a name.
            $table->unique(['area', 'name']);
        });

        // Topics inside a subject.
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            // Deleting a subject also deletes its topics.
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Two topics in the same subject cannot share a name.
            $table->unique(['subject_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
        Schema::dropIfExists('subjects');
    }
};
