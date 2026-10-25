<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\AttemptBuilder.
namespace App\Support;

// Imports the Question model, used to build the pools of selectable questions.
use App\Models\Question;
// Imports the Quiz model, the quiz definition a student is starting.
use App\Models\Quiz;
// Imports the QuizAttempt model, the record created for each sitting.
use App\Models\QuizAttempt;
// Imports the QuizAttemptAnswer model, one blank/filled row per served question.
use App\Models\QuizAttemptAnswer;
// Imports the Subject model for LET area labels.
use App\Models\Subject;
// Imports the User model, the student starting the attempt.
use App\Models\User;
// Imports the Collection class (Laravel's enriched array wrapper).
use Illuminate\Support\Collection;

/**
 * Creates quiz attempts and records which questions were served in each one.
 */
// A static helper class: every method is static, so it is never instantiated.
class AttemptBuilder
{
    /**
     * Start an attempt on a practice quiz, drawing from its topic's questions.
     */
    // Builds a practice attempt, or returns null when the topic has no questions.
    public static function forPractice(User $user, Quiz $quiz): ?QuizAttempt
    {
        // Fetch the quiz's question pool, shuffle it, and cut it down to the quiz size.
        $questions = self::poolForQuiz($quiz)->shuffle()->take($quiz->item_count);

        // If nothing was drawn, return null; otherwise persist the attempt.
        return $questions->isEmpty() ? null : self::create($user, $quiz, $questions);
    }

    /**
     * Start the mock examination, sampling every LET area to match the blueprint.
     */
    // Builds the mock-exam attempt, or returns null when the bank is too small.
    public static function forMock(User $user, Quiz $quiz): ?QuizAttempt
    {
        // Ask for the weighted sample of questions for this student's track.
        $questions = self::mockPool($user);

        // A null pool means the bank lacked enough items, so no attempt is created.
        return $questions === null ? null : self::create($user, $quiz, $questions);
    }

    /**
     * Start a fresh attempt on an exact set of questions, used when a student
     * retries a quiz or drills the items they got wrong.
     */
    // Builds an attempt from a caller-supplied question set, or null when empty.
    public static function fromQuestions(User $user, Quiz $quiz, Collection $questions): ?QuizAttempt
    {
        // Empty input means nothing to serve, so return null; otherwise persist.
        return $questions->isEmpty() ? null : self::create($user, $quiz, $questions);
    }

    /**
     * Every published question written for a practice quiz's topic.
     */
    // Returns the full set of published questions belonging to the quiz's topic.
    public static function poolForQuiz(Quiz $quiz): Collection
    {
        // Start an Eloquent query for questions.
        return Question::query()
            // Restrict to published questions only (a query scope on the model).
            ->published()
            // Restrict to the topic this quiz covers.
            ->where('topic_id', $quiz->topic_id)
            // Respect the author's intended order first...
            ->orderBy('sort_order')
            // ...then fall back to id for a stable, deterministic order.
            ->orderBy('id')
            // Run the query and return the matching Question models.
            ->get();
    }

    /**
     * The weighted sample used by the mock examination, or null when the bank
     * does not hold enough items for the student's track.
     */
    // Returns the mixed-area question set for the mock exam, or null if short.
    public static function mockPool(User $user): ?Collection
    {
        // Start an empty Collection that will accumulate the chosen questions.
        $selected = new Collection;

        // Walk each LET area and how many items the mock exam needs from it.
        foreach (MockExam::blueprint($user->exam_type) as $area => $needed) {
            // Collect that area's questions, shuffle them, and take the required count.
            $questions = self::areaQuestions($user, $area)->shuffle()->take($needed);

            // If fewer questions exist than required, the mock cannot be built.
            if ($questions->count() < $needed) {
                return null;
            }

            // Otherwise append this area's questions to the running collection.
            $selected = $selected->merge($questions);
        }

        // Shuffle the combined set and reindex with fresh keys before returning.
        return $selected->shuffle()->values();
    }

    /**
     * How many items the bank holds and still needs per area, for the readiness panel.
     *
     * @return array<string, array{label: string, needed: int, available: int, ready: bool}>
     */
    // Reports, per LET area, how many questions exist versus how many are needed.
    public static function mockAvailability(User $user): array
    {
        // Accumulator for the per-area availability rows.
        $availability = [];

        // Walk each required area and its needed item count.
        foreach (MockExam::blueprint($user->exam_type) as $area => $needed) {
            // Count how many questions the bank actually has for this area/track.
            $available = self::areaQuestions($user, $area)->count();

            // Store the readiness facts for this area under its key.
            $availability[$area] = [
                // Human-readable area name, falling back to the raw key.
                'label' => Subject::AREA_LABELS[$area] ?? $area,
                // How many items the mock exam wants from this area.
                'needed' => $needed,
                // How many items the bank currently offers.
                'available' => $available,
                // Whether the bank has enough items for this area.
                'ready' => $available >= $needed,
            ];
        }

        // Hand back the per-area map.
        return $availability;
    }

    /**
     * The time limit that applies to a new attempt. The mock examination runs to
     * the limit set in System Settings, while a practice quiz follows the
     * student's preference when they have set one.
     */
    // Chooses the time limit for a new attempt, in minutes (or null for no limit).
    public static function timeLimitFor(User $user, Quiz $quiz): ?int
    {
        // If this quiz is the mock examination...
        if ($quiz->isMock()) {
            // ...use the administrator's system-wide mock time limit.
            return SystemSettings::mockTimeLimitMinutes();
        }

        // Otherwise use the student's own practice limit, or the quiz's limit if unset.
        return $user->practice_time_limit_minutes ?? $quiz->time_limit_minutes;
    }

    /**
     * Whether the bank holds enough items for the student's mock examination.
     */
    // Returns true only when every required area has enough questions.
    public static function mockIsReady(User $user): bool
    {
        // Check each area's readiness flag...
        foreach (self::mockAvailability($user) as $area) {
            // ...as soon as one area is not ready, the whole mock is not ready.
            if (! $area['ready']) {
                return false;
            }
        }

        // All areas passed, so the mock examination can be started.
        return true;
    }

    /**
     * The published questions available for one LET area within the student's track.
     */
    // Returns the published questions for a given area, matched to the track.
    private static function areaQuestions(User $user, string $area): Collection
    {
        // Start an Eloquent query for questions.
        return Question::query()
            // Only published questions are eligible.
            ->published()
            // Filter by the area of the question's topic's subject.
            ->whereHas('topic.subject', function ($query) use ($user, $area) {
                // Match the subject's area to the requested area.
                $query->where('area', $area);

                // For the specialization area, also match the student's major.
                if ($area === 'specialization') {
                    // Restrict to subjects whose name equals the student's specialization.
                    $query->where('name', $user->specialization);
                }
            })
            // Run the query and return the matching Question models.
            ->get();
    }

    /**
     * Persist a new attempt with one blank answer row per question.
     */
    // Creates the attempt record, then one answer row per served question.
    private static function create(User $user, Quiz $quiz, Collection $questions): QuizAttempt
    {
        // Insert the attempt row capturing who, which quiz, size, limit and start time.
        $attempt = QuizAttempt::create([
            // The student this attempt belongs to.
            'user_id' => $user->id,
            // The quiz being attempted.
            'quiz_id' => $quiz->id,
            // How many questions were served.
            'total_items' => $questions->count(),
            // The applicable time limit (or null for untimed).
            'time_limit_minutes' => self::timeLimitFor($user, $quiz),
            // The moment the attempt began.
            'started_at' => now(),
        ]);

        // Create a blank answer row for each question, in served order.
        $questions->values()->each(function (Question $question, int $index) use ($attempt) {
            // Insert one answer row (no selection yet) for this question.
            QuizAttemptAnswer::create([
                // Link the answer row to the attempt just created.
                'quiz_attempt_id' => $attempt->id,
                // The question being answered.
                'question_id' => $question->id,
                // 1-based position so the questions keep a fixed display order.
                'position' => $index + 1,
            ]);
        });

        // Return the freshly created attempt to the caller.
        return $attempt;
    }
}
