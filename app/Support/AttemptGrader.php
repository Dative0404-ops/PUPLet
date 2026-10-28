<?php

// Lets Laravel find this class automatically.
namespace App\Support;

// QuizAttempt: the attempt being graded.
use App\Models\QuizAttempt;
// DB: runs the whole grading step in one transaction.
use Illuminate\Support\Facades\DB;

/**
 * Scores a quiz attempt and closes it once it has been submitted or timed out.
 */
// Only static methods, so this class is never created; call AttemptGrader::finish().
class AttemptGrader
{
    /**
     * Grade and close an attempt.
     *
     * When $selected is given it holds [question_id => option_index] and is
     * written to the answer rows first. When it is null the options already
     * stored on the attempt are used, which is what a timeout does.
     *
     * @param  array<int, int|null>|null  $selected
     */
    public static function finish(QuizAttempt $attempt, ?array $selected = null): QuizAttempt
    {
        // Already finished? Leave it alone.
        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        // Grade inside a transaction so the whole step passes or fails together.
        return DB::transaction(function () use ($attempt, $selected) {
            // Tally the questions answered and how many were correct.
            $answered = 0;
            $correct = 0;

            // Walk every answer row, pulling its question in the same query.
            foreach ($attempt->answers()->with('question')->get() as $answer) {
                // Only overwrite the stored answers when the caller sent some.
                if ($selected !== null) {
                    // Find this question's chosen option in the submitted array.
                    $choice = $selected[$answer->question_id] ?? null;
                    // Save it as an int, or null for a blank answer.
                    $answer->selected_option = ($choice === null || $choice === '')
                        ? null
                        : (int) $choice;
                }

                // Compare the stored option to the question's answer key.
                $answer->is_correct = $answer->question->isCorrect($answer->selected_option);
                // Save the updated row.
                $answer->save();

                // Tally the answered and correct rows.
                $answered += $answer->isAnswered() ? 1 : 0;
                $correct += $answer->is_correct ? 1 : 0;
            }

            // Write the summary onto the attempt and mark it finished.
            $attempt->forceFill([
                // Questions the student actually answered (skipped ones excluded).
                'answered_items' => $answered,
                // Correct answers, i.e. the raw score.
                'score' => $correct,
                // Finish time, so the attempt counts as completed.
                'completed_at' => now(),
            ])->save();

            // Read the attempt back so the caller sees the saved values.
            return $attempt->refresh();
        });
    }
}
