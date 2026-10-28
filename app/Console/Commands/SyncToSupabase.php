<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Copies local rows up into Supabase without deleting anything already there.
class SyncToSupabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync-supabase
        {--source=sqlite : The connection to read local data from}
        {--target=supabase : The Supabase connection to write into}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge local database rows up into Supabase (existing Supabase rows are kept, duplicates are skipped).';

    /**
     * Tables to copy, listed parents-first so foreign keys always resolve.
     *
     * @var list<string>
     */
    // Tables like sessions, cache, and jobs are left out: they are
    // environment-specific and would only add noise to production.
    private const TABLES = [
        'users',
        'subjects',
        'topics',
        'lessons',
        'questions',
        'quizzes',
        'study_goals',
        'lesson_progresses',
        'quiz_attempts',
        'quiz_attempt_answers',
        'question_bookmarks',
        'question_reports',
        'feedbacks',
        'settings',
        'audit_logs',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Which connections to read from and write to.
        $source = (string) $this->option('source');
        $target = (string) $this->option('target');

        // Fail fast with a readable message if Supabase cannot be reached.
        try {
            DB::connection($target)->getPdo();
        } catch (\Throwable $exception) {
            // Show the driver error so the cause (host/SSL/password) is obvious.
            $this->error("Could not connect to '{$target}': ".$exception->getMessage());

            return self::FAILURE;
        }

        // Running count of rows pushed across every table.
        $copied = 0;

        // Walk the whitelist in foreign-key-safe order.
        foreach (self::TABLES as $table) {
            // Skip tables the local database does not have yet.
            if (! Schema::connection($source)->hasTable($table)) {
                $this->line("  skip  {$table} (not present on '{$source}')");
                continue;
            }

            // Skip tables missing on Supabase so the user is told to migrate first.
            if (! Schema::connection($target)->hasTable($table)) {
                $this->warn("  skip  {$table} (not present on '{$target}' — run migrations there first)");
                continue;
            }

            // Copy this table and add its inserted rows to the running total.
            $copied += $this->syncTable($source, $target, $table);
        }

        // Move every Postgres id sequence past the highest id we just inserted,
        // otherwise the next registration would collide on the primary key.
        $this->advanceSequences($target);

        // Final summary line.
        $this->newLine();
        $this->info("Sync complete: {$copied} new row(s) written to '{$target}'.");

        return self::SUCCESS;
    }

    /**
     * Copy one table from the source connection into the target connection.
     *
     * Existing target rows are kept. insertOrIgnore compiles to
     * "on conflict do nothing" on Postgres, so primary-key or unique
     * (e.g. email) clashes are skipped instead of overwritten.
     *
     * @return int Rows actually inserted.
     */
    private function syncTable(string $source, string $target, string $table): int
    {
        // Column names available locally.
        $sourceColumns = Schema::connection($source)->getColumnListing($table);
        // Postgres column type per column, used to convert values.
        $targetTypes = $this->targetColumnTypes($target, $table);

        // Only copy columns that exist on both sides (guards against schema drift).
        $columns = array_values(array_intersect($sourceColumns, array_keys($targetTypes)));

        // Nothing shared means nothing safe to copy.
        if ($columns === []) {
            $this->warn("  skip  {$table} (no shared columns)");

            return 0;
        }

        // How many rows the target already holds, so we can report the delta.
        $before = DB::connection($target)->table($table)->count();

        // Keep batches small enough to stay well under Postgres' bind-parameter cap.
        $chunkSize = max(50, min(500, intdiv(60000, max(1, count($columns)))));

        // Read locally one row at a time so large tables never blow up memory.
        $cursor = DB::connection($source)->table($table)->select($columns)->cursor();

        // Buffer of converted rows waiting to be written.
        $buffer = [];

        foreach ($cursor as $row) {
            // Convert then queue the row.
            $buffer[] = $this->coerce((array) $row, $targetTypes);

            // Flush once the batch is full.
            if (count($buffer) >= $chunkSize) {
                // insertOrIgnore = "on conflict do nothing" on Postgres.
                DB::connection($target)->table($table)->insertOrIgnore($buffer);
                $buffer = [];
            }
        }

        // Flush any rows left in the final, partial batch.
        if ($buffer !== []) {
            DB::connection($target)->table($table)->insertOrIgnore($buffer);
        }

        // Count again to see how many rows were genuinely new.
        $inserted = DB::connection($target)->table($table)->count() - $before;

        // Report the per-table result.
        $this->line(sprintf('  %-22s %d inserted', $table, $inserted));

        return $inserted;
    }

    /**
     * Read the Postgres column types for a table so values can be cast correctly.
     *
     * @return array<string, string> Column name => lowercase data type.
     */
    private function targetColumnTypes(string $target, string $table): array
    {
        // Ask Postgres' own catalogue rather than guessing from the migrations.
        $rows = DB::connection($target)->select(
            'select column_name, data_type from information_schema.columns where table_schema = ? and table_name = ?',
            [config("database.connections.{$target}.search_path", 'public'), $table],
        );

        // Flatten into a column => type map.
        $types = [];

        foreach ($rows as $row) {
            $types[$row->column_name] = strtolower($row->data_type);
        }

        return $types;
    }

    /**
     * Cast a raw SQLite row into values Postgres will accept.
     *
     * SQLite has no real boolean or JSON column, so it stores 0/1 integers and
     * JSON text. Postgres rejects an integer for a boolean column, so those are
     * converted here.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $types
     * @return array<string, mixed>
     */
    private function coerce(array $row, array $types): array
    {
        foreach ($row as $column => $value) {
            // Nulls are valid everywhere and need no conversion.
            if ($value === null) {
                continue;
            }

            // The Postgres type of this column, if we know it.
            $type = $types[$column] ?? '';

            // SQLite stores true/false as 1/0; Postgres wants a real boolean.
            if ($type === 'boolean') {
                $row[$column] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                continue;
            }

            // JSON columns: encode only if the driver handed us a decoded array.
            if (($type === 'json' || $type === 'jsonb') && is_array($value)) {
                $row[$column] = json_encode($value);
            }
        }

        return $row;
    }

    /**
     * Push each Postgres id sequence past the largest id currently in its table.
     *
     * Without this, inserting explicit ids leaves the sequence behind, so the
     * next auto-increment insert on Supabase would raise a duplicate-key error.
     */
    private function advanceSequences(string $target): void
    {
        // Only touch tables that actually exist on the target.
        $tables = array_values(array_filter(
            self::TABLES,
            fn (string $table): bool => Schema::connection($target)->hasTable($table),
        ));

        // Nothing to do when no tables were synced.
        if ($tables === []) {
            return;
        }

        // Build a safely quoted SQL array literal from our fixed whitelist.
        $list = implode(', ', array_map(fn (string $table): string => "'{$table}'", $tables));

        // One DO block walks every table, finds its id sequence (skipping tables
        // that have none) and calls setval to the current maximum id.
        DB::connection($target)->unprepared(<<<SQL
            DO $$
            DECLARE
                t text;
                seq text;
                max_id bigint;
            BEGIN
                FOREACH t IN ARRAY ARRAY[{$list}] LOOP
                    seq := pg_get_serial_sequence('public.' || quote_ident(t), 'id');
                    IF seq IS NOT NULL THEN
                        EXECUTE format('SELECT COALESCE(MAX(id), 0) FROM public.%I', t) INTO max_id;
                        IF max_id > 0 THEN
                            PERFORM setval(seq, max_id);
                        END IF;
                    END IF;
                END LOOP;
            END $$;
        SQL);
    }
}
