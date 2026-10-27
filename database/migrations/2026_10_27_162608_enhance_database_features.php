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
        // 1. Add a useful index for filtering attempt results (correct/incorrect)
        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->index(['quiz_attempt_id', 'is_correct'], 'idx_attempt_results');
        });

        // 2. Create an audit table for system-level actions
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index();
            $table->string('table_name', 50);
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 3. Implement Postgres-specific Function and Trigger
        // This will fire when a question is verified by an administrator.
        if (DB::getDriverName() === 'pgsql') {
            // Function to log verification events
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

            // Trigger that calls the function
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
