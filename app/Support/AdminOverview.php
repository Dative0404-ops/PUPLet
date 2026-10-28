<?php

// Lets Laravel find this class automatically.
namespace App\Support;

// Question: for the question-bank counts.
use App\Models\Question;
// QuestionReport: for the report-queue figures.
use App\Models\QuestionReport;
// QuizAttempt: for the platform activity figures.
use App\Models\QuizAttempt;
// Subject: for catalog counts and LET area labels.
use App\Models\Subject;
// User: for account totals and the recent-users list.
use App\Models\User;
// DB: lets raw query-builder queries run without a model.
use Illuminate\Support\Facades\DB;

/**
 * The figures shown on the admin dashboard: users, the question bank, platform
 * activity, and the report queue.
 */
// Only static methods, so this class is never created; call AdminOverview::collect().
class AdminOverview
{
    /**
     * The number of days counted as "recent" activity.
     */
    // "Recent" panels look back this many days.
    public const WINDOW_DAYS = 30;

    /**
     * Collect every figure the dashboard needs.
     *
     * @return array<string, mixed>
     */
    public static function collect(): array
    {
        return [
            // Account totals, from users() below.
            'users' => self::users(),
            // Question-bank totals, from questions() below.
            'questions' => self::questions(),
            // How many subjects and topics exist.
            'catalog' => [
                'subjects' => Subject::count(),
                // Counted with a light raw query.
                'topics' => DB::table('topics')->count(),
            ],
            // Activity figures, from activity() below.
            'activity' => self::activity(),
            // Report-queue figures, from reports() below.
            'reports' => self::reports(),
            // Per-area breakdown, from areas() below.
            'areas' => self::areas(),
            // The five newest accounts.
            'recent_users' => User::query()->latest('id')->limit(5)->get(),
            // The five most recently started attempts.
            'recent_attempts' => QuizAttempt::query()
                // Also fetch the user and quiz in the same query.
                ->with('user', 'quiz')
                // Newest start time first.
                ->latest('started_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Account totals.
     *
     * @return array<string, int>
     */
    private static function users(): array
    {
        return [
            'total' => User::count(),
            'students' => User::students()->count(),
            'admins' => User::admins()->count(),
            // Suspended accounts are the ones with a suspended_at value.
            'suspended' => User::whereNotNull('suspended_at')->count(),
            // Accounts created in the last seven days.
            'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];
    }

    /**
     * Question bank totals by state and by completeness.
     *
     * @return array<string, int>
     */
    private static function questions(): array
    {
        return [
            'total' => Question::count(),
            'published' => Question::where('status', Question::STATUS_PUBLISHED)->count(),
            'draft' => Question::where('status', Question::STATUS_DRAFT)->count(),
            'archived' => Question::where('status', Question::STATUS_ARCHIVED)->count(),
            'verified' => Question::whereNotNull('verified_at')->count(),
            // Published but not yet verified.
            'unverified' => Question::published()->whereNull('verified_at')->count(),
            // Published but with no explanation text yet.
            'unexplained' => Question::published()
                // Groups "explanation is null OR explanation is empty".
                ->where(fn ($query) => $query->whereNull('explanation')->orWhere('explanation', ''))
                ->count(),
        ];
    }

    /**
     * What students have done recently.
     *
     * @return array<string, int|null>
     */
    // A null average means nothing was answered.
    private static function activity(): array
    {
        // Totals over completed attempts, cast to int.
        $items = (int) QuizAttempt::completed()->sum('total_items');
        $correct = (int) QuizAttempt::completed()->sum('score');

        return [
            'attempts' => QuizAttempt::completed()->count(),
            'items' => $items,
            // Whole-percent accuracy, or null when nothing was answered.
            'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
            // Attempts started but not finished.
            'open_attempts' => QuizAttempt::inProgress()->count(),
            // Students with an attempt in the recent window.
            'active_students' => QuizAttempt::where('started_at', '>=', now()->subDays(self::WINDOW_DAYS))
                // Count unique user_id values, not all rows.
                ->distinct()
                ->count('user_id'),
            // Attempts started in the recent window.
            'attempts_in_window' => QuizAttempt::where('started_at', '>=', now()->subDays(self::WINDOW_DAYS))->count(),
            // The window length, so the view can label these figures.
            'window_days' => self::WINDOW_DAYS,
        ];
    }

    /**
     * The state of the report queue.
     *
     * @return array<string, int>
     */
    private static function reports(): array
    {
        return [
            'open' => QuestionReport::open()->count(),
            'resolved' => QuestionReport::where('status', QuestionReport::STATUS_RESOLVED)->count(),
            'dismissed' => QuestionReport::where('status', QuestionReport::STATUS_DISMISSED)->count(),
        ];
    }

    /**
     * How the question bank is spread across the three LET areas.
     *
     * @return list<array{label: string, subjects: int, topics: int, published: int}>
     */
    // One row per LET area (gen_ed, prof_ed, specialization).
    private static function areas(): array
    {
        // Subjects per area, as an area => total map.
        $subjects = DB::table('subjects')
            ->selectRaw('area, COUNT(*) as total')
            ->groupBy('area')
            ->pluck('total', 'area');

        // Topics per area, joined to the owning subject, as an area => total map.
        $topics = DB::table('topics')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->selectRaw('subjects.area as area, COUNT(*) as total')
            ->groupBy('subjects.area')
            ->pluck('total', 'area');

        // Published questions per area, joined question -> topic -> subject.
        $questions = DB::table('questions')
            ->join('topics', 'topics.id', '=', 'questions.topic_id')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->where('questions.status', Question::STATUS_PUBLISHED)
            ->selectRaw('subjects.area as area, COUNT(*) as total')
            ->groupBy('subjects.area')
            ->pluck('total', 'area');

        $rows = [];

        // Walk the area labels so areas always appear in a fixed order.
        foreach (Subject::AREA_LABELS as $area => $label) {
            $rows[] = [
                'label' => $label,
                // Each count defaults to 0 when the area has no rows.
                'subjects' => (int) ($subjects[$area] ?? 0),
                'topics' => (int) ($topics[$area] ?? 0),
                'published' => (int) ($questions[$area] ?? 0),
            ];
        }

        return $rows;
    }
}
