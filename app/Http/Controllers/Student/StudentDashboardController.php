<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// Shows the student dashboard (progress and recommendations).
class StudentDashboardController extends Controller
{
    /**
     * Answered items required before a topic is scored in the recommendations.
     */
    private const MIN_ITEMS_FOR_RECOMMENDATION = 5;

    /**
     * How many days of study activity are shown.
     */
    private const ACTIVITY_DAYS = 7;

    /**
     * Show the student dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Build the dashboard from the computed sections.
        return view('student.dashboard', [
            'user' => $user,
            // Overall totals: quizzes, lessons, items, average score.
            'overall' => $this->overallProgress($user),
            // Per-day study activity for the last week.
            'activity' => $this->studyActivity($user),
            // Per-area and per-subject performance.
            'performance' => $this->subjectPerformance($user),
            // What the student should pick up next.
            'resume' => $this->continueReviewing($user),
            // Topics to practise more.
            'recommended' => $this->recommendedTopics($user),
            // Today's goal and the upcoming review sessions.
            'goals' => $this->studyGoals($user),
        ]);
    }

    /**
     * Completed quizzes, average score, and answered items.
     *
     * @return array<string, int|null>
     */
    private function overallProgress(User $user): array
    {
        // Query for this student's completed attempts. Cloned below to reuse it.
        $completed = $user->quizAttempts()->completed();

        // Total items and correct answers across completed attempts.
        $itemsAnswered = (int) (clone $completed)->sum('total_items');
        $itemsCorrect = (int) (clone $completed)->sum('score');

        return [
            // Quizzes finished and lessons marked reviewed.
            'quizzes_completed' => (clone $completed)->count(),
            'lessons_completed' => $user->lessonProgresses()->whereNotNull('completed_at')->count(),
            'items_answered' => $itemsAnswered,
            // Overall accuracy, or null when nothing has been answered.
            'average_score' => $itemsAnswered > 0
                ? (int) round($itemsCorrect / $itemsAnswered * 100)
                : null,
        ];
    }

    /**
     * Items answered and lessons studied per day over the last week.
     *
     * @return array<string, mixed>
     */
    private function studyActivity(User $user): array
    {
        // Start of the window (six days ago, at midnight).
        $start = now()->subDays(self::ACTIVITY_DAYS - 1)->startOfDay();

        // Finished attempts inside the window, with their item counts.
        $attempts = $user->quizAttempts()
            ->completed()
            ->where('completed_at', '>=', $start)
            ->get(['completed_at', 'total_items']);

        // Lesson views inside the window.
        $lessons = $user->lessonProgresses()
            ->where('last_viewed_at', '>=', $start)
            ->get(['last_viewed_at']);

        // Build one row per day, from six days ago up to today.
        $days = collect(range(self::ACTIVITY_DAYS - 1, 0))->map(function (int $daysAgo) use ($attempts, $lessons) {
            // The day being summarised, at midnight.
            $date = now()->subDays($daysAgo)->startOfDay();
            // A Y-m-d key used to match rows to this day.
            $key = $date->toDateString();

            return [
                'date' => $date,
                // Short weekday label for the chart.
                'label' => $date->format('D'),
                // Counts answered and viewed that day.
                'items' => (int) $attempts
                    ->filter(fn ($attempt) => $attempt->completed_at->toDateString() === $key)
                    ->sum('total_items'),
                'lessons' => $lessons
                    ->filter(fn ($lesson) => $lesson->last_viewed_at->toDateString() === $key)
                    ->count(),
                // Marks the today column.
                'is_today' => $daysAgo === 0,
            ];
        });

        // Largest daily item count, floored at 1 to avoid dividing by zero.
        $maxItems = max(1, (int) $days->max('items'));

        return [
            // Each day plus a bar height as a percentage of the busiest day.
            'days' => $days->map(fn (array $day) => [
                ...$day,
                'height' => (int) round($day['items'] / $maxItems * 100),
            ])->all(),
            // Totals across the window.
            'items' => (int) $days->sum('items'),
            'lessons' => (int) $days->sum('lessons'),
            // How many days had any activity.
            'active_days' => $days
                ->filter(fn (array $day) => $day['items'] > 0 || $day['lessons'] > 0)
                ->count(),
        ];
    }

    /**
     * Scores per LET area, broken down by subject.
     *
     * @return array<string, array<string, mixed>>
     */
    private function subjectPerformance(User $user): array
    {
        // One raw query: attempts joined through quizzes and topics up to subjects.
        $rows = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Only this student's finished attempts.
            ->where('quiz_attempts.user_id', $user->id)
            ->whereNotNull('quiz_attempts.completed_at')
            // Group and order by subject.
            ->groupBy('subjects.id', 'subjects.area', 'subjects.name', 'subjects.sort_order')
            ->orderBy('subjects.sort_order')
            // Count attempts, items, and correct answers per subject.
            ->selectRaw('subjects.id as subject_id, subjects.area, subjects.name, COUNT(*) as attempts, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            ->get();

        // Performance map keyed by LET area.
        $performance = [];

        // Walk each known area so the order matches AREA_LABELS.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // The rows for this area.
            $areaRows = $rows->where('area', $area);

            // Per-subject breakdown for the area.
            $subjects = $areaRows->map(fn ($row) => [
                'id' => $row->subject_id,
                'name' => $row->name,
                'attempts' => (int) $row->attempts,
                'items' => (int) $row->items,
                // Accuracy percentage for the subject.
                'percentage' => (int) round($row->correct / $row->items * 100),
            ])->values()->all();

            // Total items answered across the area.
            $items = (int) $areaRows->sum('items');

            $performance[$area] = [
                'area' => $area,
                'label' => $label,
                'items' => $items,
                // Area accuracy, or null when nothing has been answered.
                'percentage' => $items > 0
                    ? (int) round($areaRows->sum('correct') / $items * 100)
                    : null,
                'subjects' => $subjects,
            ];
        }

        return $performance;
    }

    /**
     * The unfinished attempt or lesson the student should pick up next.
     *
     * @return array<string, mixed>
     */
    private function continueReviewing(User $user): array
    {
        return [
            // The most recent attempt still in progress, if any.
            'attempt' => $user->quizAttempts()
                ->inProgress()
                // Also fetch the quiz, topic, and subject in the same query.
                ->with('quiz.topic.subject')
                // Most recent first.
                ->latest('started_at')
                ->first(),
            // The most recent lesson that was opened but not finished.
            'lesson' => $user->lessonProgresses()
                ->whereNull('completed_at')
                ->with('lesson.topic.subject')
                ->latest('last_viewed_at')
                ->first(),
            // A lesson the student has never completed.
            'suggested' => Lesson::query()
                // Exclude lessons already completed by this student.
                ->whereNotIn('id', LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('completed_at')
                    ->select('lesson_id'))
                ->with('topic.subject')
                // Take the earliest lesson by id.
                ->orderBy('id')
                ->first(),
            // The three most recently viewed lessons.
            'recent' => $user->lessonProgresses()
                ->with('lesson.topic.subject')
                ->latest('last_viewed_at')
                ->limit(3)
                ->get(),
        ];
    }

    /**
     * Topics with the lowest accuracy, plus topics the student has not started.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function recommendedTopics(User $user): array
    {
        // This student's accuracy per topic.
        $weak = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Only this student's finished attempts.
            ->where('quiz_attempts.user_id', $user->id)
            ->whereNotNull('quiz_attempts.completed_at')
            ->groupBy('topics.id', 'topics.name', 'subjects.name', 'subjects.area')
            // Ignore topics with too few answered items to judge.
            ->havingRaw('SUM(quiz_attempts.total_items) >= ?', [self::MIN_ITEMS_FOR_RECOMMENDATION])
            ->selectRaw('topics.id as topic_id, topics.name as topic_name, subjects.name as subject_name, subjects.area, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            ->get()
            // Turn each row into the fields the view needs.
            ->map(fn ($row) => [
                'topic_id' => $row->topic_id,
                'topic' => $row->topic_name,
                'subject' => $row->subject_name,
                // The friendly area label, falling back to the raw code.
                'area' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                'items' => (int) $row->items,
                // Accuracy percentage for the topic.
                'percentage' => (int) round($row->correct / $row->items * 100),
            ])
            // Weakest first.
            ->sortBy('percentage')
            ->values();

        // At most four weak topics.
        $weakTopics = $weak->take(4)->all();

        // Topics the student has never attempted, to fill remaining slots.
        $untouched = Topic::query()
            // Exclude topics that appear in any of this student's attempts.
            ->whereNotIn('id', DB::table('quiz_attempts')
                ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                ->where('quiz_attempts.user_id', $user->id)
                ->select('quizzes.topic_id'))
            // Also fetch each topic's subject in the same query.
            ->with('subject')
            // Group topics by subject, then by sort order.
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            // Take only as many as needed to reach four suggestions.
            ->limit(max(0, 4 - count($weakTopics)))
            ->get()
            // Turn each topic into the view's fields.
            ->map(fn (Topic $topic) => [
                'topic_id' => $topic->id,
                'topic' => $topic->name,
                'subject' => $topic->subject->name,
                'area' => $topic->subject->areaLabel(),
                // Nothing answered yet, so no count and no accuracy.
                'items' => 0,
                'percentage' => null,
            ])
            ->all();

        return [
            // Weakest topics (up to four).
            'weak' => $weakTopics,
            // Not-yet-started topics filling any remaining slots.
            'untouched' => $untouched,
        ];
    }

    /**
     * Today's item target and the review sessions coming up.
     *
     * @return array<string, mixed>
     */
    private function studyGoals(User $user): array
    {
        // Midnight today, used as the range boundary.
        $today = now()->startOfDay();

        // Today's daily-target goal, if the student set one.
        $dailyTarget = $user->studyGoals()
            ->dailyTargets()
            ->whereDate('target_date', $today)
            ->first();

        // Items the student has answered today.
        $itemsToday = (int) $user->quizAttempts()
            ->completed()
            ->where('completed_at', '>=', $today)
            ->sum('total_items');

        // Fall back to the preferred target when no goal was added for today,
        // so the daily target is always meaningful.
        $targetItems = $dailyTarget?->target_items ?? $user->daily_target_items;

        return [
            // The daily-target goal row, if any.
            'daily_target' => $dailyTarget,
            // The effective target number of items.
            'daily_target_items' => $targetItems,
            'items_today' => $itemsToday,
            // Progress toward the target, capped at 100%, or null.
            'daily_progress' => $targetItems
                ? min(100, (int) round($itemsToday / $targetItems * 100))
                : null,
            // Upcoming review sessions, soonest first.
            'sessions' => $user->studyGoals()
                ->reviewSessions()
                ->whereDate('target_date', '>=', $today)
                // Soonest date first, then earliest time.
                ->orderBy('target_date')
                ->orderBy('target_time')
                // At most four sessions.
                ->limit(4)
                ->get(),
        ];
    }
}
