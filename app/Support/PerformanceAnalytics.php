<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\PerformanceAnalytics.
namespace App\Support;

// Imports the Subject model for LET area labels and their order.
use App\Models\Subject;
// Imports the User model, the student whose analytics are being built.
use App\Models\User;
// Imports the Collection class (Laravel's enriched array wrapper).
use Illuminate\Support\Collection;
// Imports the DB facade for the raw aggregate query.
use Illuminate\Support\Facades\DB;

/**
 * Aggregates a student's completed practice sets into the figures behind the
 * progress analytics charts.
 */
// A static helper class computing the data for the progress charts.
class PerformanceAnalytics
{
    /**
     * How many topics the topic chart shows.
     */
    // Caps the topic chart so it lists only the weakest 12 topics.
    private const TOPIC_LIMIT = 12;

    /**
     * Scores per LET area, per subject, and per topic.
     *
     * @return array<string, mixed>
     */
    // The public entry point; returns all analytics figures in one array.
    public static function forUser(User $user): array
    {
        // Load the per-topic aggregate rows for this student.
        $rows = self::rows($user);

        // Sum all answered items across every row.
        $items = (int) $rows->sum('items');
        // Sum all correct answers across every row.
        $correct = (int) $rows->sum('correct');

        // Return the chart-ready figures.
        return [
            // Per-area breakdown from the areas() helper.
            'areas' => self::areas($rows),
            // Per-subject breakdown from the subjects() helper.
            'subjects' => self::subjects($rows),
            // Per-topic breakdown (weakest first) from the topics() helper.
            'topics' => self::topics($rows),
            // Number of completed attempts on the student's account.
            'attempts' => $user->quizAttempts()->completed()->count(),
            // Total items the student has answered.
            'items' => $items,
            // Total correct answers, i.e. the raw score.
            'correct' => $correct,
            // Overall accuracy as a whole percent, or null when no items were answered.
            'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
        ];
    }

    /**
     * One row per topic the student has answered items in.
     */
    // Builds the shared per-topic aggregate rows that the other methods reuse.
    private static function rows(User $user): Collection
    {
        // Query completed attempts joined through quizzes, topics and subjects.
        return DB::table('quiz_attempts')
            // Link each attempt to the quiz it was on.
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            // Link each quiz to its topic.
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            // Link each topic to its subject.
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Only this student's attempts.
            ->where('quiz_attempts.user_id', $user->id)
            // Only finished attempts.
            ->whereNotNull('quiz_attempts.completed_at')
            // Group one row per topic, carrying the subject fields along.
            ->groupBy('topics.id', 'topics.name', 'subjects.id', 'subjects.area', 'subjects.name', 'subjects.sort_order')
            // Select the per-topic totals: attempts, items served and correct answers.
            ->selectRaw('topics.id as topic_id, topics.name as topic_name, subjects.id as subject_id, subjects.area, subjects.name as subject_name, subjects.sort_order as subject_sort, COUNT(*) as attempts, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            // Run the query and get the grouped rows.
            ->get();
    }

    /**
     * Scores per LET area, in the order the areas are examined.
     *
     * @return list<array<string, mixed>>
     */
    // Summarises the per-topic rows into one entry per LET area.
    private static function areas(Collection $rows): array
    {
        // Accumulator for the area entries.
        $areas = [];

        // Walk the canonical area labels so areas appear in a fixed order.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // Keep only the rows belonging to this area.
            $areaRows = $rows->where('area', $area);
            // Total items answered in this area.
            $items = (int) $areaRows->sum('items');

            // Append the summary for this area.
            $areas[] = [
                // The raw area key (e.g. gen_ed).
                'area' => $area,
                // The readable area label.
                'label' => $label,
                // Items answered in this area.
                'items' => $items,
                // Correct answers in this area.
                'correct' => (int) $areaRows->sum('correct'),
                // Number of attempts covering this area.
                'attempts' => (int) $areaRows->sum('attempts'),
                // Accuracy as a whole percent, or null when no items were answered.
                'percentage' => $items > 0 ? (int) round($areaRows->sum('correct') / $items * 100) : null,
            ];
        }

        // Hand back the ordered area entries.
        return $areas;
    }

    /**
     * Scores per subject, grouped in area order.
     *
     * @return list<array<string, mixed>>
     */
    // Summarises the per-topic rows into one entry per subject.
    private static function subjects(Collection $rows): array
    {
        // Flip the area keys to numbers so they can be used for sorting.
        $areaOrder = array_flip(array_keys(Subject::AREA_LABELS));

        // Return the subjects, grouped and sorted, as a plain array.
        return $rows
            // Group the per-topic rows by their subject id.
            ->groupBy('subject_id')
            // Turn each group into one subject summary.
            ->map(function (Collection $subjectRows) {
                // Any row in the group carries the shared subject fields.
                $row = $subjectRows->first();
                // Total items answered in this subject.
                $items = (int) $subjectRows->sum('items');

                // Build the subject summary entry.
                return [
                    // The subject's LET area key.
                    'area' => $row->area,
                    // The area's readable label, falling back to the raw key.
                    'area_label' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                    // The subject's name.
                    'name' => $row->subject_name,
                    // Items answered in this subject.
                    'items' => $items,
                    // Correct answers in this subject.
                    'correct' => (int) $subjectRows->sum('correct'),
                    // Attempts covering this subject.
                    'attempts' => (int) $subjectRows->sum('attempts'),
                    // Accuracy as a whole percent, or null when nothing was answered.
                    'percentage' => $items > 0 ? (int) round($subjectRows->sum('correct') / $items * 100) : null,
                ];
            })
            // Build a sort key so subjects group by area then weakest first then name.
            ->sortBy(fn (array $subject) => sprintf(
                // Pad numbers to two digits so string sorting matches numeric order.
                '%02d-%02d-%s',
                // Area position in the canonical order (99 last when unknown).
                $areaOrder[$subject['area']] ?? 99,
                // Accuracy (null treated as 0) to push weaker subjects up.
                $subject['percentage'] ?? 0,
                // Subject name as the final tie-breaker.
                $subject['name'],
            ))
            // Reindex after sorting.
            ->values()
            // Convert the collection to a plain array.
            ->all();
    }

    /**
     * The weakest topics first, so the chart points at what to work on next.
     *
     * @return list<array<string, mixed>>
     */
    // Builds the topic chart entries, weakest first, capped at TOPIC_LIMIT.
    private static function topics(Collection $rows): array
    {
        // Return the topics transformed and sorted as a plain array.
        return $rows
            // Turn each raw row into a topic entry.
            ->map(function ($row) {
                // Cast the item count to an integer.
                $items = (int) $row->items;
                // Cast the correct count to an integer.
                $correct = (int) $row->correct;

                // Build the topic entry for the chart.
                return [
                    // The topic's primary key.
                    'id' => (int) $row->topic_id,
                    // The topic's name.
                    'name' => $row->topic_name,
                    // The owning subject's name.
                    'subject' => $row->subject_name,
                    // The LET area label, falling back to the raw key.
                    'area' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                    // Items answered in this topic.
                    'items' => $items,
                    // Correct answers in this topic.
                    'correct' => $correct,
                    // Attempts covering this topic.
                    'attempts' => (int) $row->attempts,
                    // Accuracy as a whole percent, or null when nothing was answered.
                    'percentage' => $items > 0 ? (int) round($correct / $items * 100) : null,
                ];
            })
            // Sort by accuracy ascending so the weakest topics lead.
            ->sortBy('percentage')
            // Keep only the first TOPIC_LIMIT topics.
            ->take(self::TOPIC_LIMIT)
            // Reindex after sorting and slicing.
            ->values()
            // Convert the collection to a plain array.
            ->all();
    }
}
