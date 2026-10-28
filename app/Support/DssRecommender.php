<?php

// Lets Laravel find this class automatically.
namespace App\Support;

// Question: counts of published questions per topic.
use App\Models\Question;
// Quiz: finds each topic's practice quiz.
use App\Models\Quiz;
// Subject: the LET area labels.
use App\Models\Subject;
// Topic: the untouched-topics query.
use App\Models\Topic;
// User: the student being recommended to.
use App\Models\User;
// Collection: Laravel's array wrapper.
use Illuminate\Support\Collection;
// DB: runs the raw count queries.
use Illuminate\Support\Facades\DB;

/**
 * The decision support recommendations: which topics a student should review
 * next, and why. The rules come from the administrator's DSS settings.
 */
// Only static methods, so this class is never created; call DssRecommender::forUser().
class DssRecommender
{
    /**
     * The recommendations for one student, strongest case first.
     *
     * @return array{weak: list<array<string, mixed>>, untouched: list<array<string, mixed>>, ranked: list<array<string, mixed>>}
     */
    public static function forUser(User $user): array
    {
        // How many topics the admin allows in the list.
        $limit = DssSettings::limit();

        // Keep at most $limit weak topics.
        $weak = self::weakTopics($user)->take($limit)->values();

        // Only add untouched topics if the admin turned that setting on,
        // filling whatever slots are left (never a negative number).
        $untouched = DssSettings::includeUntouched()
            ? self::untouchedTopics($user)->take(max(0, $limit - $weak->count()))->values()
            : new Collection;

        return [
            // Weak topics.
            'weak' => $weak->all(),
            // Untouched topics.
            'untouched' => $untouched->all(),
            // Weak first, then untouched, as one ranked list.
            'ranked' => $weak->concat($untouched)->values()->all(),
        ];
    }

    /**
     * Topics the student has answered enough items in but scores below the
     * threshold, weakest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private static function weakTopics(User $user): Collection
    {
        // The accuracy below which a topic counts as weak.
        $threshold = DssSettings::weakThreshold();

        // Total the student's completed attempts per topic.
        $rows = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('topics', 'topics.id', '=', 'quizzes.topic_id')
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->where('quiz_attempts.user_id', $user->id)
            // Only finished attempts (completed_at set).
            ->whereNotNull('quiz_attempts.completed_at')
            ->groupBy('topics.id', 'topics.name', 'subjects.name', 'subjects.area')
            // Keep topics with at least the minimum number of answered items.
            // HAVING filters on the total after grouping (WHERE cannot).
            ->havingRaw('SUM(quiz_attempts.total_items) >= ?', [DssSettings::minItems()])
            // Items served and correct answers, summed per topic.
            ->selectRaw('topics.id as topic_id, topics.name as topic_name, subjects.name as subject_name, subjects.area, SUM(quiz_attempts.total_items) as items, SUM(quiz_attempts.score) as correct')
            ->get()
            // Turn each row into a recommendation entry.
            ->map(function ($row) {
                $items = (int) $row->items;

                return [
                    // A weak topic (not untouched).
                    'type' => 'weak',
                    'topic_id' => (int) $row->topic_id,
                    'topic' => $row->topic_name,
                    'subject' => $row->subject_name,
                    // Readable area label, falling back to the raw key.
                    'area' => Subject::AREA_LABELS[$row->area] ?? $row->area,
                    // Items answered in this topic.
                    'items' => $items,
                    // Accuracy as a whole percent, or 0 when there were no items.
                    'percentage' => $items > 0 ? (int) round($row->correct / $items * 100) : 0,
                ];
            })
            // Keep only topics below the weak threshold.
            ->filter(fn (array $row) => $row['percentage'] < $threshold)
            // Weakest first, so the topic needing most work leads.
            ->sortBy('percentage')
            ->values();

        // Add the reason text, the practice quiz, and the question count.
        return self::withPractice($rows, fn (array $row) => $row['percentage'].'% correct across '.$row['items'].' '.
            // "item" or "items", so the sentence reads right for a count of one.
            ($row['items'] === 1 ? 'item' : 'items'));
    }

    /**
     * Topics inside the student's LET track that they have never practised.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private static function untouchedTopics(User $user): Collection
    {
        // A sub-query: every topic id the student has already practised.
        $practised = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->where('quiz_attempts.user_id', $user->id)
            ->select('quizzes.topic_id');

        // All topics the student has not practised.
        $rows = Topic::query()
            // Exclude any topic id in the practised sub-query.
            ->whereNotIn('id', $practised)
            // Also fetch each topic's subject, for its name and area.
            ->with('subject')
            // Order by subject, then the author's order, then name.
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            // Keep only topics inside the student's track.
            ->filter(fn (Topic $topic) => $user->coversSubject($topic->subject))
            // Turn each topic into a recommendation entry.
            ->map(fn (Topic $topic) => [
                // An untouched topic.
                'type' => 'untouched',
                'topic_id' => $topic->id,
                'topic' => $topic->name,
                'subject' => $topic->subject->name,
                // Readable area label (helper on the Subject model).
                'area' => $topic->subject->areaLabel(),
                // No items yet, since the topic is untouched.
                'items' => 0,
                // No accuracy yet.
                'percentage' => null,
            ])
            ->values();

        // Add the reason, quiz and question count; the reason is the same
        // string for every untouched topic.
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
    private static function withPractice(Collection $rows, callable $reason): Collection
    {
        // Nothing to add to an empty list.
        if ($rows->isEmpty()) {
            return $rows;
        }

        // The topic ids we need to look up below.
        $topicIds = $rows->pluck('topic_id')->all();

        // The practice quiz for each topic.
        $quizzes = Quiz::query()
            // Practice quizzes only (a scope on the model).
            ->practice()
            ->whereIn('topic_id', $topicIds)
            // Earliest quiz id wins when a topic has several.
            ->orderBy('id')
            // A map of topic_id => quiz id.
            ->pluck('id', 'topic_id');

        // Published questions available per topic.
        $available = DB::table('questions')
            // Only published questions can back a practice quiz.
            ->where('status', Question::STATUS_PUBLISHED)
            ->whereIn('topic_id', $topicIds)
            // One count per topic.
            ->groupBy('topic_id')
            // Map each topic_id to its COUNT(*).
            ->pluck(DB::raw('COUNT(*)'), 'topic_id');

        // Merge the extra fields onto each row.
        return $rows->map(fn (array $row) => [
            // ...$row keeps the existing keys of the row.
            ...$row,
            // The plain-language reason, from the callable we were given.
            'reason' => $reason($row),
            // The topic's practice quiz id, when one exists.
            'quiz_id' => $quizzes->get($row['topic_id']),
            // How many published questions back this topic, defaulting to 0.
            'available' => (int) $available->get($row['topic_id'], 0),
        ]);
    }
}
