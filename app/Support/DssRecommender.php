<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\DssRecommender.
namespace App\Support;

// Imports the Question model for counting published questions per topic.
use App\Models\Question;
// Imports the Quiz model to find each topic's practice quiz.
use App\Models\Quiz;
// Imports the Subject model for the LET area labels.
use App\Models\Subject;
// Imports the Topic model for the untouched-topics query.
use App\Models\Topic;
// Imports the User model, the student being recommended to.
use App\Models\User;
// Imports the Collection class (Laravel's enriched array wrapper).
use Illuminate\Support\Collection;
// Imports the DB facade for the raw aggregate queries.
use Illuminate\Support\Facades\DB;

/**
 * The decision support recommendations: which topics a student should review
 * next, and why. The rules come from the administrator's DSS settings.
 */
// A static helper class: it computes "DSS" (decision support) recommendations.
class DssRecommender
{
    /**
     * The recommendations for one student, strongest case first.
     *
     * @return array{weak: list<array<string, mixed>>, untouched: list<array<string, mixed>>, ranked: list<array<string, mixed>>}
     */
    // Produces the recommendation lists (weak topics, untouched topics, combined).
    public static function forUser(User $user): array
    {
        // Read the administrator's configured cap on how many topics to show.
        $limit = DssSettings::limit();

        // Find weak topics, keep at most $limit of them, and reindex.
        $weak = self::weakTopics($user)->take($limit)->values();

        // Untouched topics are only included when the setting allows it.
        $untouched = DssSettings::includeUntouched()
            // Then take just the remaining slots (never a negative number).
            ? self::untouchedTopics($user)->take(max(0, $limit - $weak->count()))->values()
            // Otherwise contribute an empty collection.
            : new Collection;

        // Return the three views the page needs.
        return [
            // Weak topics as a plain array.
            'weak' => $weak->all(),
            // Untouched topics as a plain array.
            'untouched' => $untouched->all(),
            // Weak topics followed by untouched ones, as one ranked list.
            'ranked' => $weak->concat($untouched)->values()->all(),
        ];
    }

    /**
     * Topics the student has answered enough items in but scores below the
     * threshold, weakest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    // Returns the student's weak topics (below the accuracy threshold).
    private static function weakTopics(User $user): Collection
    {
        // Read the accuracy percentage below which a topic counts as weak.
        $threshold = DssSettings::weakThreshold();

        // Query completed attempts joined through quizzes, topics and subjects.
        $rows = DB::table('quiz_attempts')
            // Link each attempt to the quiz it was on.
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            // Link each quiz to its topic.
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            // Link each topic to its subject.
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            // Only this student's attempts.
            ->where('quiz_attempts.user_id', $user->id)
            // Only finished attempts (completed_at set).
            ->whereNotNull('quiz_attempts.completed_at')
            // Group the sums by topic and the labels we need for display.
            ->groupBy('topics.id', 'topics.name', 'subjects.name', 'subjects.area')
            // Keep only topics with at least the minimum number of answered items;
            // HAVING filters on the aggregate after grouping (unlike WHERE).
            ->havingRaw('SUM(quiz_attempts.total_items) >= ?', [DssSettings::minItems()])
            // Select the per-topic totals: items served and correct answers.
            ->selectRaw('topics.id as topic_id, topics.name as topic_name, subjects.name as subject_name, subjects.area, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            // Run the query and get the grouped rows.
            ->get()
            // Turn each database row into a clean recommendation array.
            ->map(function ($row) {
                // Cast the summed item count to an integer.
                $items = (int) $row->items;

                // Build the recommendation entry for this topic.
                return [
                    // Marks this as a weak topic (as opposed to untouched).
                    'type' => 'weak',
                    // The topic's primary key.
                    'topic_id' => (int) $row->topic_id,
                    // The topic's name.
                    'topic' => $row->topic_name,
                    // The owning subject's name.
                    'subject' => $row->subject_name,
                    // The LET area as a readable label, falling back to the raw key.
                    'area' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                    // How many items the student answered in this topic.
                    'items' => $items,
                    // Accuracy as a whole percent, or 0 when there were no items.
                    'percentage' => $items > 0 ? (int) round($row->correct / $items * 100) : 0,
                ];
            })
            // Keep only topics whose accuracy is below the weak threshold.
            ->filter(fn (array $row) => $row['percentage'] < $threshold)
            // Sort lowest accuracy first, so the weakest topic leads.
            ->sortBy('percentage')
            // Reindex the collection after sorting and filtering.
            ->values();

        // Attach the reason text, practice quiz, and available question count.
        return self::withPractice($rows, fn (array $row) => $row['percentage'].'% correct across '.$row['items'].' '.
            // Add "item"/"items" so the sentence reads naturally for a count of one.
            ($row['items'] === 1 ? 'item' : 'items'));
    }

    /**
     * Topics inside the student's LET track that they have never practised.
     *
     * @return Collection<int, array<string, mixed>>
     */
    // Returns topics the student has no completed attempts for.
    private static function untouchedTopics(User $user): Collection
    {
        // A sub-query listing every topic id the student has already practised.
        $practised = DB::table('quiz_attempts')
            // Join attempts to their quizzes to reach the topic id.
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            // Only this student's attempts.
            ->where('quiz_attempts.user_id', $user->id)
            // Select the topic ids (used as a sub-query below).
            ->select('quizzes.topic_id');

        // Query all topics except the practised ones.
        $rows = Topic::query()
            // Exclude topics whose id appears in the practised sub-query.
            ->whereNotIn('id', $practised)
            // Eager-load each topic's subject so we can read its name and area.
            ->with('subject')
            // Order the topics consistently by subject...
            ->orderBy('subject_id')
            // ...then by the author's ordering...
            ->orderBy('sort_order')
            // ...then alphabetically by name.
            ->orderBy('name')
            // Run the query and get the Topic models.
            ->get()
            // Keep only topics that fall within the student's track/subjects.
            ->filter(fn (Topic $topic) => $user->coversSubject($topic->subject))
            // Map each remaining topic into a recommendation array.
            ->map(fn (Topic $topic) => [
                // Marks this as an untouched topic.
                'type' => 'untouched',
                // The topic's primary key.
                'topic_id' => $topic->id,
                // The topic's name.
                'topic' => $topic->name,
                // The owning subject's name.
                'subject' => $topic->subject->name,
                // The area as a readable label (helper on the Subject model).
                'area' => $topic->subject->areaLabel(),
                // No items answered yet, since the topic is untouched.
                'items' => 0,
                // No accuracy yet, so the percentage is null.
                'percentage' => null,
            ])
            // Reindex the collection after filtering.
            ->values();

        // Attach the reason, practice quiz, and available question count;
        // the reason is a constant string for every untouched topic.
        return self::withPractice($rows, fn () => 'Not practised yet');
    }

    /**
     * Add the reason, the practice quiz, and how many published items each
     * recommendation can draw on.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): string  $reason
     * @return Collection<int, array<string, mixed>>
     */
    // Enriches each recommendation row with reason text, quiz id and item count.
    private static function withPractice(Collection $rows, callable $reason): Collection
    {
        // If there are no rows, there is nothing to enrich, so return early.
        if ($rows->isEmpty()) {
            return $rows;
        }

        // Collect the topic ids from the rows to use in the queries below.
        $topicIds = $rows->pluck('topic_id')->all();

        // Find the practice quiz for each of those topics.
        $quizzes = Quiz::query()
            // Restrict to practice quizzes (a query scope on the model).
            ->practice()
            // Only quizzes for the topics we are recommending.
            ->whereIn('topic_id', $topicIds)
            // Prefer the earliest quiz id when a topic has several.
            ->orderBy('id')
            // Build a map of topic_id => quiz id.
            ->pluck('id', 'topic_id');

        // Count the published questions available per topic.
        $available = DB::table('questions')
            // Only published questions are usable for a practice quiz.
            ->where('status', Question::STATUS_PUBLISHED)
            // Limit to the recommended topics.
            ->whereIn('topic_id', $topicIds)
            // Group so the count is per topic.
            ->groupBy('topic_id')
            // Map each topic_id to its COUNT(*) via a raw expression.
            ->pluck(DB::raw('COUNT(*)'), 'topic_id');

        // Merge the extra fields onto each recommendation row.
        return $rows->map(fn (array $row) => [
            // ...$row spreads the existing keys of the row into the new array.
            ...$row,
            // The plain-language reason, produced by the callable we were given.
            'reason' => $reason($row),
            // The topic's practice quiz id, if one exists.
            'quiz_id' => $quizzes->get($row['topic_id']),
            // How many published questions back this topic, defaulting to 0.
            'available' => (int) $available->get($row['topic_id'], 0),
        ]);
    }
}
