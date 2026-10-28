<?php

// Lets Laravel find this class automatically.
namespace App\Support;

// Question: to build the pools of selectable questions.
use App\Models\Question;
// Quiz: the quiz a student is starting.
use App\Models\Quiz;
// QuizAttempt: the record created for each sitting.
use App\Models\QuizAttempt;
// QuizAttemptAnswer: one row per served question.
use App\Models\QuizAttemptAnswer;
// Subject: for LET area labels.
use App\Models\Subject;
// User: the student starting the attempt.
use App\Models\User;
// Collection: Laravel's array wrapper.
use Illuminate\Support\Collection;

/**
 * Creates quiz attempts and records which questions were served in each one.
 */
// Only static methods, so this class is never created; call AttemptBuilder::forPractice().
class AttemptBuilder
{
    /**
     * Start an attempt on a practice quiz, drawing from its topic's questions.
     */
    public static function forPractice(User $user, Quiz $quiz): ?QuizAttempt
    {
        // The quiz's question pool, shuffled, cut down to the quiz size.
        $questions = self::poolForQuiz($quiz)->shuffle()->take($quiz->item_count);

        // Nothing drawn means no attempt; otherwise save one.
        return $questions->isEmpty() ? null : self::create($user, $quiz, $questions);
    }

    /**
     * Start the mock examination, sampling every LET area to match the blueprint.
     */
    public static function forMock(User $user, Quiz $quiz): ?QuizAttempt
    {
        // The weighted question sample for this student's track.
        $questions = self::mockPool($user);

        // A null pool means the bank lacked enough items, so no attempt is created.
        return $questions === null ? null : self::create($user, $quiz, $questions);
    }

    /**
     * Start a fresh attempt on an exact set of questions, used when a student
     * retries a quiz or drills the items they got wrong.
     */
    public static function fromQuestions(User $user, Quiz $quiz, Collection $questions): ?QuizAttempt
    {
        // Empty input means nothing to serve; otherwise save the attempt.
        return $questions->isEmpty() ? null : self::create($user, $quiz, $questions);
    }

    /**
     * Every published question written for a practice quiz's topic.
     */
    public static function poolForQuiz(Quiz $quiz): Collection
    {
        return Question::query()
            // Published questions only (a scope on the model).
            ->published()
            // Only this quiz's topic.
            ->where('topic_id', $quiz->topic_id)
            // Author's order first, then id for a stable order.
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * The weighted sample used by the mock examination, or null when the bank
     * does not hold enough items for the student's track.
     */
    public static function mockPool(User $user): ?Collection
    {
        // Holds the chosen questions as we walk the areas.
        $selected = new Collection;

        // Walk each LET area and how many items the mock exam needs from it.
        foreach (MockExam::blueprint($user->exam_type) as $area => $needed) {
            // That area's questions, shuffled, cut down to the needed count.
            $questions = self::areaQuestions($user, $area)->shuffle()->take($needed);

            // Too few questions in this area means the mock cannot be built.
            if ($questions->count() < $needed) {
                return null;
            }

            $selected = $selected->merge($questions);
        }

        // Shuffle the combined set and reindex before returning.
        return $selected->shuffle()->values();
    }

    /**
     * How many items the bank holds and still needs per area, for the readiness panel.
     *
     * @return array<string, array{label: string, needed: int, available: int, ready: bool}>
     */
    public static function mockAvailability(User $user): array
    {
        // One row per LET area.
        $availability = [];

        foreach (MockExam::blueprint($user->exam_type) as $area => $needed) {
            // How many questions the bank actually has for this area/track.
            $available = self::areaQuestions($user, $area)->count();

            $availability[$area] = [
                // Readable area name, falling back to the raw key.
                'label' => Subject::AREA_LABELS[$area] ?? $area,
                // Items the mock exam wants from this area.
                'needed' => $needed,
                // Items the bank currently offers.
                'available' => $available,
                // Whether the bank has enough items for this area.
                'ready' => $available >= $needed,
            ];
        }

        return $availability;
    }

    /**
     * The time limit that applies to a new attempt. The mock examination runs to
     * the limit set in System Settings, while a practice quiz follows the
     * student's preference when they have set one.
     */
    public static function timeLimitFor(User $user, Quiz $quiz): ?int
    {
        // The mock exam uses the admin's system-wide limit.
        if ($quiz->isMock()) {
            return SystemSettings::mockTimeLimitMinutes();
        }

        // Practice uses the student's own limit, else the quiz's limit.
        return $user->practice_time_limit_minutes ?? $quiz->time_limit_minutes;
    }

    /**
     * Whether the bank holds enough items for the student's mock examination.
     */
    public static function mockIsReady(User $user): bool
    {
        // One area short means the whole mock is not ready.
        foreach (self::mockAvailability($user) as $area) {
            if (! $area['ready']) {
                return false;
            }
        }

        return true;
    }

    /**
     * The published questions available for one LET area within the student's track.
     */
    private static function areaQuestions(User $user, string $area): Collection
    {
        return Question::query()
            ->published()
            // Match on the area of the question's topic's subject.
            ->whereHas('topic.subject', function ($query) use ($user, $area) {
                $query->where('area', $area);

                // The specialization area also matches the student's major.
                if ($area === 'specialization') {
                    $query->where('name', $user->specialization);
                }
            })
            ->get();
    }

    /**
     * Save a new attempt with one blank answer row per question.
     */
    private static function create(User $user, Quiz $quiz, Collection $questions): QuizAttempt
    {
        // The attempt row: who, which quiz, size, limit and start time.
        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'total_items' => $questions->count(),
            // The applicable limit, or null when untimed.
            'time_limit_minutes' => self::timeLimitFor($user, $quiz),
            'started_at' => now(),
        ]);

        // One blank answer row per question, in served order.
        $questions->values()->each(function (Question $question, int $index) use ($attempt) {
            QuizAttemptAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                // 1-based position keeps a fixed display order.
                'position' => $index + 1,
            ]);
        });

        return $attempt;
    }
}
