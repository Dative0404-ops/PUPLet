<?php

// Declares the PHP namespace so Laravel can autoload this class as App\Support\AdminOverview.
namespace App\Support;

// Imports the Question Eloquent model for querying the question bank.
use App\Models\Question;
// Imports the QuestionReport model for the student report-queue figures.
use App\Models\QuestionReport;
// Imports the QuizAttempt model for the platform activity figures.
use App\Models\QuizAttempt;
// Imports the Subject model for catalog counts and LET area labels.
use App\Models\Subject;
// Imports the User model for account totals and the recent-users list.
use App\Models\User;
// Imports the DB facade so raw query-builder queries can run without a model.
use Illuminate\Support\Facades\DB;

/**
 * The figures shown on the admin dashboard: users, the question bank, platform
 * activity, and the report queue.
 */
// A "support" helper class: only static methods, so it is never instantiated;
// callers simply use AdminOverview::collect().
class AdminOverview
{
    /**
     * The number of days counted as "recent" activity.
     */
    // A class constant shared by every method that needs the same time window.
    // 30 means "recent" panels look back 30 days.
    public const WINDOW_DAYS = 30;

    /**
     * Collect every figure the dashboard needs.
     *
     * @return array<string, mixed>
     */
    // The single public entry point; returns one associative array of all data.
    public static function collect(): array
    {
        // Build and return the whole dashboard payload as key => value pairs.
        return [
            // Account totals produced by the private users() helper below.
            'users' => self::users(),
            // Question-bank totals produced by the questions() helper below.
            'questions' => self::questions(),
            // Catalog size: how many subjects and topics exist.
            'catalog' => [
                // Total number of subject rows in the reviewer.
                'subjects' => Subject::count(),
                // Total number of topic rows, counted with a light raw query.
                'topics' => DB::table('topics')->count(),
            ],
            // Usage activity figures from the activity() helper below.
            'activity' => self::activity(),
            // Report-queue figures from the reports() helper below.
            'reports' => self::reports(),
            // Per-area breakdown of the question bank from areas() below.
            'areas' => self::areas(),
            // The five newest accounts (by id) for the recent-users panel.
            'recent_users' => User::query()->latest('id')->limit(5)->get(),
            // The five most recently started attempts for the recent attempts panel.
            'recent_attempts' => QuizAttempt::query()
                // Eager-load the related user and quiz to avoid extra per-row queries.
                ->with('user', 'quiz')
                // Order by start time, newest first.
                ->latest('started_at')
                // Keep only the first five rows.
                ->limit(5)
                // Run the query and return a Collection of models.
                ->get(),
        ];
    }

    /**
     * Account totals.
     *
     * @return array<string, int>
     */
    // Returns the account counts shown on the dashboard's user cards.
    private static function users(): array
    {
        // Return each account figure under a readable key.
        return [
            // Every user row regardless of role or state.
            'total' => User::count(),
            // Users whose role is student (a query scope defined on the model).
            'students' => User::students()->count(),
            // Users whose role is administrator.
            'admins' => User::admins()->count(),
            // Accounts with a non-null suspended_at are currently suspended.
            'suspended' => User::whereNotNull('suspended_at')->count(),
            // Accounts created since one week ago (subWeek() returns a Carbon date).
            'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];
    }

    /**
     * Question bank totals by state and by completeness.
     *
     * @return array<string, int>
     */
    // Returns counts describing the state and quality of the question bank.
    private static function questions(): array
    {
        // Return each question-bank figure under a readable key.
        return [
            // Every question in the bank, whatever its status.
            'total' => Question::count(),
            // Questions with the published status (visible to students).
            'published' => Question::where('status', Question::STATUS_PUBLISHED)->count(),
            // Questions still in draft (not yet visible to students).
            'draft' => Question::where('status', Question::STATUS_DRAFT)->count(),
            // Questions archived (retired from circulation).
            'archived' => Question::where('status', Question::STATUS_ARCHIVED)->count(),
            // Questions that carry a verification timestamp.
            'verified' => Question::whereNotNull('verified_at')->count(),
            // Published questions that have not yet been verified.
            'unverified' => Question::published()->whereNull('verified_at')->count(),
            // Published questions that have no explanation text yet.
            'unexplained' => Question::published()
                // A closure groups "explanation is null OR explanation is empty".
                ->where(fn ($query) => $query->whereNull('explanation')->orWhere('explanation', ''))
                ->count(),
        ];
    }

    /**
     * What students have done recently.
     *
     * @return array<string, int|null>
     */
    // Returns aggregate activity figures; a null average means nothing was answered.
    private static function activity(): array
    {
        // Sum all answered items over completed attempts; cast the result to int.
        $items = (int) QuizAttempt::completed()->sum('total_items');
        // Sum all correctly answered items over completed attempts.
        $correct = (int) QuizAttempt::completed()->sum('score');

        // Return each activity figure under a readable key.
        return [
            // Number of completed attempts.
            'attempts' => QuizAttempt::completed()->count(),
            // Total items answered across those attempts.
            'items' => $items,
            // Overall accuracy as a whole percent, or null when nothing was answered.
            'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
            // Attempts still in progress (started but not finished).
            'open_attempts' => QuizAttempt::inProgress()->count(),
            // Distinct students who started an attempt inside the recent window.
            'active_students' => QuizAttempt::where('started_at', '>=', now()->subDays(self::WINDOW_DAYS))
                // Count unique user_id values rather than all rows.
                ->distinct()
                ->count('user_id'),
            // Number of attempts started inside the recent window.
            'attempts_in_window' => QuizAttempt::where('started_at', '>=', now()->subDays(self::WINDOW_DAYS))->count(),
            // The window length in days, so the view can label these figures.
            'window_days' => self::WINDOW_DAYS,
        ];
    }

    /**
     * The state of the report queue.
     *
     * @return array<string, int>
     */
    // Returns counts of student-submitted question reports by status.
    private static function reports(): array
    {
        // Return each report-queue figure under a readable key.
        return [
            // Reports still awaiting action (a query scope on the model).
            'open' => QuestionReport::open()->count(),
            // Reports marked resolved.
            'resolved' => QuestionReport::where('status', QuestionReport::STATUS_RESOLVED)->count(),
            // Reports dismissed as not actionable.
            'dismissed' => QuestionReport::where('status', QuestionReport::STATUS_DISMISSED)->count(),
        ];
    }

    /**
     * How the question bank is spread across the three LET areas.
     *
     * @return list<array{label: string, subjects: int, topics: int, published: int}>
     */
    // Returns one row per LET area (gen_ed, prof_ed, specialization) with counts.
    private static function areas(): array
    {
        // Count subjects per area, then build a map of area => total.
        $subjects = DB::table('subjects')
            // Select the area plus a COUNT(*) aliased as "total".
            ->selectRaw('area, COUNT(*) as total')
            // Collapse rows so the count is per distinct area.
            ->groupBy('area')
            // pluck('total', 'area') yields a Collection indexed by area.
            ->pluck('total', 'area');

        // Count topics per area by joining each topic to its owning subject.
        $topics = DB::table('topics')
            // Match every topic to the subject it belongs to.
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Select the subject's area and a count.
            ->selectRaw('subjects.area as area, COUNT(*) as total')
            // Group by that area so we get one total per area.
            ->groupBy('subjects.area')
            // Build the area => total map.
            ->pluck('total', 'area');

        // Count published questions per area by joining questions to topic to subject.
        $questions = DB::table('questions')
            // Link each question to its topic.
            ->join('topics', 'topics.id', '=', 'questions.topic_id')
            // Then link the topic to its subject.
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Only published questions count toward the bank.
            ->where('questions.status', Question::STATUS_PUBLISHED)
            // Select the area plus a count.
            ->selectRaw('subjects.area as area, COUNT(*) as total')
            // Group by area for one total per area.
            ->groupBy('subjects.area')
            // Build the area => total map.
            ->pluck('total', 'area');

        // Accumulator for the final ordered list of area rows.
        $rows = [];

        // Walk the canonical area labels so areas always appear in a fixed order.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // Append one summary row for this area.
            $rows[] = [
                // Human-readable area name (e.g. General Education).
                'label' => $label,
                // Subject count for the area, defaulting to 0 when none exist.
                'subjects' => (int) ($subjects[$area] ?? 0),
                // Topic count for the area, defaulting to 0 when none exist.
                'topics' => (int) ($topics[$area] ?? 0),
                // Published-question count for the area, defaulting to 0.
                'published' => (int) ($questions[$area] ?? 0),
            ];
        }

        // Hand the completed list of area rows back to the caller.
        return $rows;
    }
}
