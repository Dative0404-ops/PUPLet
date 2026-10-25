<?php

// This controller lives in the Student sub-namespace of the app.
namespace App\Http\Controllers\Student;

// Parent controller that every controller extends.
use App\Http\Controllers\Controller;
// Eloquent model for a lesson (reviewer material).
use App\Models\Lesson;
// Eloquent model tracking a student's progress through a lesson.
use App\Models\LessonProgress;
// Subject model; used here for its AREA_LABELS map.
use App\Models\Subject;
// Eloquent model for a topic inside a subject.
use App\Models\Topic;
// Eloquent model for a student account.
use App\Models\User;
// Wraps the incoming HTTP request.
use Illuminate\Http\Request;
// Facade used to run the raw aggregate queries for the analytics.
use Illuminate\Support\Facades\DB;
// Return type for actions that render a Blade template.
use Illuminate\View\View;

// Controller for the student dashboard (progress and recommendations).
class StudentDashboardController extends Controller
{
    /**
     * Answered items required before a topic is scored in the recommendations.
     */
    // Minimum answered items before a topic is judged "weak".
    private const MIN_ITEMS_FOR_RECOMMENDATION = 5;

    /**
     * How many days of study activity are shown.
     */
    // Length of the activity window, in days.
    private const ACTIVITY_DAYS = 7;

    /**
     * Display the student dashboard.
     */
    // Invokable action: builds and renders the dashboard.
    public function __invoke(Request $request): View
    {
        // The signed-in student.
        $user = $request->user();

        // Render the dashboard with each computed section.
        return view('student.dashboard', [
            // The student account.
            'user' => $user,
            // Overall totals: quizzes, lessons, items, average score.
            'overall' => $this->overallProgress($user),
            // Per-day study activity for the last week.
            'activity' => $this->studyActivity($user),
            // Per-area and per-subject performance.
            'performance' => $this->subjectPerformance($user),
            // What the student should pick up next.
            'resume' => $this->continueReviewing($user),
            // Topics recommended for extra practice.
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
    // Private helper: computes the headline progress numbers.
    private function overallProgress(User $user): array
    {
        // Base query for this student's completed attempts (reused below).
        $completed = $user->quizAttempts()->completed();

        // Total items across completed attempts (cloned to reuse the query).
        $itemsAnswered = (int) (clone $completed)->sum('total_items');
        // Total correct answers across completed attempts.
        $itemsCorrect = (int) (clone $completed)->sum('score');

        // The summary numbers shown on the dashboard.
        return [
            // How many quizzes the student has finished.
            'quizzes_completed' => (clone $completed)->count(),
            // How many lessons the student has marked reviewed.
            'lessons_completed' => $user->lessonProgresses()->whereNotNull('completed_at')->count(),
            // Total items answered.
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
    // Private helper: builds the seven-day activity series.
    private function studyActivity(User $user): array
    {
        // Start of the window (today minus six days, at midnight).
        $start = now()->subDays(self::ACTIVITY_DAYS - 1)->startOfDay();

        // Completed attempts inside the window, with their item counts.
        $attempts = $user->quizAttempts()
            // Only finished attempts.
            ->completed()
            // Only attempts completed on or after the window start.
            ->where('completed_at', '>=', $start)
            // Select just the columns needed below.
            ->get(['completed_at', 'total_items']);

        // Lesson views inside the window.
        $lessons = $user->lessonProgresses()
            // Only lessons last viewed on or after the window start.
            ->where('last_viewed_at', '>=', $start)
            // Select just the timestamp column.
            ->get(['last_viewed_at']);

        // Build one row per day, from six days ago up to today.
        $days = collect(range(self::ACTIVITY_DAYS - 1, 0))->map(function (int $daysAgo) use ($attempts, $lessons) {
            // Midnight of the day being summarised.
            $date = now()->subDays($daysAgo)->startOfDay();
            // A Y-m-d key used to match rows to this day.
            $key = $date->toDateString();

            // The per-day summary row.
            return [
                // The day as a date object.
                'date' => $date,
                // Short weekday label for the chart.
                'label' => $date->format('D'),
                // Items answered that day, summed from matching attempts.
                'items' => (int) $attempts
                    ->filter(fn ($attempt) => $attempt->completed_at->toDateString() === $key)
                    ->sum('total_items'),
                // Lessons viewed that day.
                'lessons' => $lessons
                    ->filter(fn ($lesson) => $lesson->last_viewed_at->toDateString() === $key)
                    ->count(),
                // Flag marking the today column.
                'is_today' => $daysAgo === 0,
            ];
        });

        // Largest daily item count, floored at 1 to avoid dividing by zero.
        $maxItems = max(1, (int) $days->max('items'));

        // The activity payload for the view.
        return [
            // Days with an added relative bar height (percent of the max).
            'days' => $days->map(fn (array $day) => [
                // Copy the day fields through.
                ...$day,
                // Bar height as a percentage of the busiest day.
                'height' => (int) round($day['items'] / $maxItems * 100),
            ])->all(),
            // Total items answered across the window.
            'items' => (int) $days->sum('items'),
            // Total lessons viewed across the window.
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
    // Private helper: aggregates attempt scores per subject and area.
    private function subjectPerformance(User $user): array
    {
        // One raw query joining attempts through quizzes up to subjects.
        $rows = DB::table('quiz_attempts')
            // Join quizzes to reach the attempt's quiz.
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            // Join topics to reach the quiz's topic.
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            // Join subjects to reach the topic's subject.
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Only this student's attempts.
            ->where('quiz_attempts.user_id', $user->id)
            // Only finished attempts.
            ->whereNotNull('quiz_attempts.completed_at')
            // Group by the subject identifiers.
            ->groupBy('subjects.id', 'subjects.area', 'subjects.name', 'subjects.sort_order')
            // Present subjects in their configured order.
            ->orderBy('subjects.sort_order')
            // Aggregate attempts, items, and correct answers per subject.
            ->selectRaw('subjects.id as subject_id, subjects.area, subjects.name, COUNT(*) as attempts, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            // Run the query.
            ->get();

        // The performance map keyed by LET area.
        $performance = [];

        // Walk each known LET area so the order matches AREA_LABELS.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // The aggregated rows for this area.
            $areaRows = $rows->where('area', $area);

            // Per-subject rows for this area.
            $subjects = $areaRows->map(fn ($row) => [
                // The subject id.
                'id' => $row->subject_id,
                // The subject name.
                'name' => $row->name,
                // Number of attempts on the subject.
                'attempts' => (int) $row->attempts,
                // Number of items answered on the subject.
                'items' => (int) $row->items,
                // Accuracy percentage for the subject.
                'percentage' => (int) round($row->correct / $row->items * 100),
            ])->values()->all();

            // Total items answered across the area.
            $items = (int) $areaRows->sum('items');

            // Store the area summary keyed by its code.
            $performance[$area] = [
                // The area code.
                'area' => $area,
                // The friendly area name.
                'label' => $label,
                // Items answered in the area.
                'items' => $items,
                // Area accuracy, or null when nothing has been answered.
                'percentage' => $items > 0
                    ? (int) round($areaRows->sum('correct') / $items * 100)
                    : null,
                // The per-subject breakdown.
                'subjects' => $subjects,
            ];
        }

        // Return the ordered area map.
        return $performance;
    }

    /**
     * The unfinished attempt or lesson the student should pick up next.
     *
     * @return array<string, mixed>
     */
    // Private helper: finds things to resume or start next.
    private function continueReviewing(User $user): array
    {
        // The "resume" payload.
        return [
            // The most recent attempt still in progress, if any.
            'attempt' => $user->quizAttempts()
                // Only attempts still in progress.
                ->inProgress()
                // Eager-load the quiz, topic, and subject.
                ->with('quiz.topic.subject')
                // Most recently started first.
                ->latest('started_at')
                // Return the first match or null.
                ->first(),
            // The most recent lesson that was opened but not completed.
            'lesson' => $user->lessonProgresses()
                // Only lessons without a completion timestamp.
                ->whereNull('completed_at')
                // Eager-load the lesson, topic, and subject.
                ->with('lesson.topic.subject')
                // Most recently viewed first.
                ->latest('last_viewed_at')
                // Return the first match or null.
                ->first(),
            // A lesson the student has never completed, as a suggestion.
            'suggested' => Lesson::query()
                // Exclude lessons already completed by this student.
                ->whereNotIn('id', LessonProgress::query()
                    // Scope to this student.
                    ->where('user_id', $user->id)
                    // Only completed progress rows.
                    ->whereNotNull('completed_at')
                    // Sub-select the lesson ids.
                    ->select('lesson_id'))
                // Eager-load the lesson's topic and subject.
                ->with('topic.subject')
                // Take the earliest lesson by id.
                ->orderBy('id')
                // Return the first match or null.
                ->first(),
            // The three most recently viewed lessons.
            'recent' => $user->lessonProgresses()
                // Eager-load the lesson, topic, and subject.
                ->with('lesson.topic.subject')
                // Most recently viewed first.
                ->latest('last_viewed_at')
                // At most three rows.
                ->limit(3)
                // Run the query.
                ->get(),
        ];
    }

    /**
     * Topics with the lowest accuracy, plus topics the student has not started.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    // Private helper: picks weak topics and not-yet-started topics.
    private function recommendedTopics(User $user): array
    {
        // Aggregate this student's accuracy per topic.
        $weak = DB::table('quiz_attempts')
            // Join quizzes to reach the attempt's quiz.
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            // Join topics to reach the quiz's topic.
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            // Join subjects to reach the topic's subject.
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Only this student's attempts.
            ->where('quiz_attempts.user_id', $user->id)
            // Only finished attempts.
            ->whereNotNull('quiz_attempts.completed_at')
            // Group by the topic and subject fields.
            ->groupBy('topics.id', 'topics.name', 'subjects.name', 'subjects.area')
            // Ignore topics with too few answered items to judge.
            ->havingRaw('SUM(quiz_attempts.total_items) >= ?', [self::MIN_ITEMS_FOR_RECOMMENDATION])
            // Aggregate items and correct answers per topic.
            ->selectRaw('topics.id as topic_id, topics.name as topic_name, subjects.name as subject_name, subjects.area, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            // Run the query.
            ->get()
            // Reshape each row into the fields the view needs.
            ->map(fn ($row) => [
                // The topic id.
                'topic_id' => $row->topic_id,
                // The topic name.
                'topic' => $row->topic_name,
                // The subject name.
                'subject' => $row->subject_name,
                // The friendly area label, falling back to the raw code.
                'area' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                // Items answered on the topic.
                'items' => (int) $row->items,
                // Accuracy percentage for the topic.
                'percentage' => (int) round($row->correct / $row->items * 100),
            ])
            // Show the weakest topics first.
            ->sortBy('percentage')
            // Re-index the sorted collection.
            ->values();

        // Keep at most four weak topics.
        $weakTopics = $weak->take(4)->all();

        // Topics the student has never attempted, to fill remaining slots.
        $untouched = Topic::query()
            // Exclude topics that appear in any of this student's attempts.
            ->whereNotIn('id', DB::table('quiz_attempts')
                // Join quizzes to reach each attempt's topic.
                ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                // Only this student's attempts.
                ->where('quiz_attempts.user_id', $user->id)
                // Sub-select the topic ids.
                ->select('quizzes.topic_id'))
            // Eager-load each topic's subject.
            ->with('subject')
            // Group topics by their subject.
            ->orderBy('subject_id')
            // Then by the manual sort order.
            ->orderBy('sort_order')
            // Take only as many as needed to reach four suggestions.
            ->limit(max(0, 4 - count($weakTopics)))
            // Run the query.
            ->get()
            // Reshape each topic into the view's fields.
            ->map(fn (Topic $topic) => [
                // The topic id.
                'topic_id' => $topic->id,
                // The topic name.
                'topic' => $topic->name,
                // The subject name.
                'subject' => $topic->subject->name,
                // The friendly area label.
                'area' => $topic->subject->areaLabel(),
                // No items answered yet.
                'items' => 0,
                // No accuracy yet.
                'percentage' => null,
            ])
            // Convert to a plain array.
            ->all();

        // Return both recommendation buckets.
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
    // Private helper: assembles today's goal progress and upcoming sessions.
    private function studyGoals(User $user): array
    {
        // Midnight of today, used as the range boundary.
        $today = now()->startOfDay();

        // Today's explicit daily-target goal, if the student set one.
        $dailyTarget = $user->studyGoals()
            // Only goals of the daily-target kind.
            ->dailyTargets()
            // Only the goal dated today.
            ->whereDate('target_date', $today)
            // Return the first match or null.
            ->first();

        // Items the student has answered today.
        $itemsToday = (int) $user->quizAttempts()
            // Only finished attempts.
            ->completed()
            // Only attempts completed today or later.
            ->where('completed_at', '>=', $today)
            // Sum the item counts.
            ->sum('total_items');

        // Fall back to the student's preferred target when no goal was added
        // for today, so the daily target is always meaningful.
        $targetItems = $dailyTarget?->target_items ?? $user->daily_target_items;

        // The goals payload for the view.
        return [
            // The daily-target goal row, if any.
            'daily_target' => $dailyTarget,
            // The effective target number of items.
            'daily_target_items' => $targetItems,
            // Items answered today.
            'items_today' => $itemsToday,
            // Today's progress toward the target, capped at 100%, or null.
            'daily_progress' => $targetItems
                ? min(100, (int) round($itemsToday / $targetItems * 100))
                : null,
            // Upcoming review sessions, soonest first.
            'sessions' => $user->studyGoals()
                // Only goals of the review-session kind.
                ->reviewSessions()
                // Only sessions dated today or later.
                ->whereDate('target_date', '>=', $today)
                // Soonest date first.
                ->orderBy('target_date')
                // Then earliest time first.
                ->orderBy('target_time')
                // At most four sessions.
                ->limit(4)
                // Run the query.
                ->get(),
        ];
    }
}
