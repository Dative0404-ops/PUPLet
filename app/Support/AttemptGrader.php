<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\AttemptGrader.
namespace App\Support;

// Imports the QuizAttempt model, the attempt being graded.
use App\Models\QuizAttempt;
// Imports the DB facade so the whole grading step can run in one transaction.
use Illuminate\Support\Facades\DB;

/**
 * Scores a quiz attempt and closes it once it has been submitted or timed out.
 */
// A static helper class: it holds the grading logic and is never instantiated.
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
    // Grades the attempt; $selected is the submitted answers, or null on timeout.
    public static function finish(QuizAttempt $attempt, ?array $selected = null): QuizAttempt
    {
        // Guard: if the attempt is already finished, do nothing and return it as-is.
        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        // Run the whole grading inside a database transaction so it is all-or-nothing.
        return DB::transaction(function () use ($attempt, $selected) {
            // Running count of questions the student actually answered.
            $answered = 0;
            // Running count of correctly answered questions.
            $correct = 0;

            // Loop over the attempt's answer rows, eager-loading each question.
            foreach ($attempt->answers()->with('question')->get() as $answer) {
                // Only when the caller passed submitted answers do we overwrite them.
                if ($selected !== null) {
                    // Look up this question's chosen option in the submitted array.
                    $choice = $selected[$answer->question_id] ?? null;
                    // Store the choice as an int, or null when nothing/blank was selected.
                    $answer->selected_option = ($choice === null || $choice === '')
                        ? null
                        : (int) $choice;
                }

                // Mark correctness by comparing the stored option to the question's key.
                $answer->is_correct = $answer->question->isCorrect($answer->selected_option);
                // Persist the updated selection and correctness for this row.
                $answer->save();

                // Add one to the answered tally when the row has a selection.
                $answered += $answer->isAnswered() ? 1 : 0;
                // Add one to the correct tally when this row is correct.
                $correct += $answer->is_correct ? 1 : 0;
            }

            // Write the summary figures back onto the attempt and close it.
            $attempt->forceFill([
                // Total questions the student answered (skipped ones excluded).
                'answered_items' => $answered,
                // Number of correct answers, i.e. the raw score.
                'score' => $correct,
                // Stamp the finish time so the attempt counts as completed.
                'completed_at' => now(),
            ])->save();

            // Reload the attempt from the database so callers see the saved values.
            return $attempt->refresh();
        });
    }
}
