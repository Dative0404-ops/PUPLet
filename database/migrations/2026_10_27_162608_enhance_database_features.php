<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Faster lookups when counting correct and incorrect answers in an attempt.
        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->index(['quiz_attempt_id', 'is_correct'], 'idx_attempt_results');
        });

        // Audit log of important system actions, one row per logged event.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index();
            $table->string('table_name', 50);
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('user_id')->nullable();
            // Extra event details, stored as JSON.
            $table->jsonb('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Postgres only: a function and trigger that write an audit row when an
        // administrator verifies a question. The check keeps the migration safe on
        // other database drivers such as SQLite.
        if (DB::getDriverName() === 'pgsql') {
            // Logs an audit row when a question goes from unverified to verified.
            DB::unprepared("
                CREATE OR REPLACE FUNCTION log_question_verification()
                RETURNS TRIGGER AS $$
                BEGIN
                    IF (OLD.verified_at IS NULL AND NEW.verified_at IS NOT NULL) THEN
                        INSERT INTO audit_logs (event_type, table_name, record_id, user_id, payload)
                        VALUES (
                            'question_verified',
                            'questions',
                            NEW.id,
                            NEW.reviewed_by,
                            jsonb_build_object('verified_at', NEW.verified_at, 'reviewer_id', NEW.reviewed_by)
                        );
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ");

            // Runs the function above after each question update.
            DB::unprepared("
                CREATE TRIGGER trg_question_verified
                AFTER UPDATE ON questions
                FOR EACH ROW
                EXECUTE FUNCTION log_question_verification();
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the Postgres trigger and function before dropping their table.
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared("DROP TRIGGER IF EXISTS trg_question_verified ON questions;");
            DB::unprepared("DROP FUNCTION IF EXISTS log_question_verification();");
        }

        Schema::dropIfExists('audit_logs');

        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->dropIndex('idx_attempt_results');
        });
    }
};
