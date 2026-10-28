<?php

// Lets Laravel find this class automatically.
namespace App\Support;

// Subject: LET area labels and their order.
use App\Models\Subject;
// User: the student whose analytics are being built.
use App\Models\User;
// Collection: Laravel's array wrapper.
use Illuminate\Support\Collection;
// DB: runs the raw totals query.
use Illuminate\Support\Facades\DB;

/**
 * Adds up a student's completed practice sets into the figures behind the
 * progress analytics charts.
 */
// Only static methods, so this class is never created; call PerformanceAnalytics::forUser().
class PerformanceAnalytics
{
    /**
     * How many topics the topic chart shows.
     */
    // The topic chart lists only the weakest this many topics.
    private const TOPIC_LIMIT = 12;

    /**
     * Scores per LET area, per subject, and per topic.
     *
     * @return array<string, mixed>
     */
    public static function forUser(User $user): array
    {
        // The per-topic totals for this student.
        $rows = self::rows($user);

        // Add up items answered and correct answers across every topic.
        $items = (int) $rows->sum('items');
        $correct = (int) $rows->sum('correct');

        return [
            // Per-area breakdown.
            'areas' => self::areas($rows),
            // Per-subject breakdown.
            'subjects' => self::subjects($rows),
            // Per-topic breakdown, weakest first.
            'topics' => self::topics($rows),
            // Completed attempts on the student's account.
            'attempts' => $user->quizAttempts()->completed()->count(),
            // Total items answered.
            'items' => $items,
            // Total correct answers, i.e. the raw score.
            'correct' => $correct,
            // Overall accuracy as a whole percent, or null when nothing was answered.
            'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
        ];
    }

    /**
     * One row per topic the student has answered items in.
     */
    private static function rows(User $user): Collection
    {
        // Totals from the student's completed attempts, grouped per topic.
        return DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->where('quiz_attempts.user_id', $user->id)
            // Only finished attempts.
            ->whereNotNull('quiz_attempts.completed_at')
            // One row per topic; carry the subject fields along too.
            ->groupBy('topics.id', 'topics.name', 'subjects.id', 'subjects.area', 'subjects.name', 'subjects.sort_order')
            // Per-topic totals: attempts, items served and correct answers.
            ->selectRaw('topics.id as topic_id, topics.name as topic_name, subjects.id as subject_id, subjects.area, subjects.name as subject_name, subjects.sort_order as subject_sort, COUNT(*) as attempts, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            ->get();
    }

    /**
     * Scores per LET area, in the order the areas are examined.
     *
     * @return list<array<string, mixed>>
     */
    private static function areas(Collection $rows): array
    {
        $areas = [];

        // Walk the area labels so areas always appear in a fixed order.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // Just this area's rows, and the items answered in them.
            $areaRows = $rows->where('area', $area);
            $items = (int) $areaRows->sum('items');

            $areas[] = [
                // The raw area key (e.g. gen_ed).
                'area' => $area,
                // The readable area label.
                'label' => $label,
                'items' => $items,
                // Correct answers in this area.
                'correct' => (int) $areaRows->sum('correct'),
                // Attempts covering this area.
                'attempts' => (int) $areaRows->sum('attempts'),
                // Accuracy as a whole percent, or null when nothing was answered.
                'percentage' => $items > 0 ? (int) round($areaRows->sum('correct') / $items * 100) : null,
            ];
        }

        return $areas;
    }

    /**
     * Scores per subject, grouped in area order.
     *
     * @return list<array<string, mixed>>
     */
    private static function subjects(Collection $rows): array
    {
        // Area keys turned into numbers, so they can be sorted.
        $areaOrder = array_flip(array_keys(Subject::AREA_LABELS));

        return $rows
            // One group per subject.
            ->groupBy('subject_id')
            // Turn each group into one subject summary.
            ->map(function (Collection $subjectRows) {
                // Any row carries the subject fields shared by the group.
                $row = $subjectRows->first();
                // Total items answered in this subject.
                $items = (int) $subjectRows->sum('items');

                return [
                    // The subject's LET area key.
                    'area' => $row->area,
                    // Readable area label, falling back to the raw key.
                    'area_label' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                    'name' => $row->subject_name,
                    'items' => $items,
                    // Correct answers in this subject.
                    'correct' => (int) $subjectRows->sum('correct'),
                    // Attempts covering this subject.
                    'attempts' => (int) $subjectRows->sum('attempts'),
                    // Accuracy as a whole percent, or null when nothing was answered.
                    'percentage' => $items > 0 ? (int) round($subjectRows->sum('correct') / $items * 100) : null,
                ];
            })
            // Sort by area, then weakest first, then name.
            ->sortBy(fn (array $subject) => sprintf(
                // Two digits each, so text sorting matches number order.
                '%02d-%02d-%s',
                // Position in the area order (99 when the area is unknown).
                $areaOrder[$subject['area']] ?? 99,
                // Accuracy (null treated as 0), so weaker subjects come first.
                $subject['percentage'] ?? 0,
                // Name as the final tie-breaker.
                $subject['name'],
            ))
            ->values()
            ->all();
    }

    /**
     * The weakest topics first, so the chart points at what to work on next.
     *
     * @return list<array<string, mixed>>
     */
    private static function topics(Collection $rows): array
    {
        return $rows
            // Turn each row into a topic entry.
            ->map(function ($row) {
                $items = (int) $row->items;
                $correct = (int) $row->correct;

                return [
                    'id' => (int) $row->topic_id,
                    'name' => $row->topic_name,
                    'subject' => $row->subject_name,
                    // Readable area label, falling back to the raw key.
                    'area' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                    'items' => $items,
                    'correct' => $correct,
                    // Attempts covering this topic.
                    'attempts' => (int) $row->attempts,
                    // Accuracy as a whole percent, or null when nothing was answered.
                    'percentage' => $items > 0 ? (int) round($correct / $items * 100) : null,
                ];
            })
            // Weakest topics first.
            ->sortBy('percentage')
            // Keep only the first TOPIC_LIMIT topics.
            ->take(self::TOPIC_LIMIT)
            ->values()
            ->all();
    }
}
