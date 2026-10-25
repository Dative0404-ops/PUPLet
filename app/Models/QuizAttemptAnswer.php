<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by attempt() and question().
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Eloquent model recording one served question and its answer within an attempt.
class QuizAttemptAnswer extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    // Names the table explicitly; Eloquent would otherwise guess "quiz_attempt_answers".
    protected $table = 'quiz_attempt_answers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for the per-question answer row.
    protected $fillable = [
        // The attempt this answer belongs to.
        'quiz_attempt_id',
        // The question that was served for this position.
        'question_id',
        // The question's slot number within the attempt.
        'position',
        // The zero-based option index the student picked; null when skipped.
        'selected_option',
        // Whether the picked option was correct, set when grading.
        'is_correct',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Declares casts applied automatically when attributes are accessed.
    protected function casts(): array
    {
        return [
            // Coerces the grading flag to a real PHP boolean.
            'is_correct' => 'boolean',
        ];
    }

    /**
     * The attempt this answer belongs to.
     */
    // BelongsTo relationship back to the parent attempt.
    public function attempt(): BelongsTo
    {
        // Second argument names the foreign key column explicitly.
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /**
     * The question that was answered.
     */
    // BelongsTo relationship to the served question.
    public function question(): BelongsTo
    {
        // Matches questions.id to quiz_attempt_answers.question_id.
        return $this->belongsTo(Question::class);
    }

    /**
     * Whether the student picked an option for this item.
     */
    // Distinguishes an answered item from a skipped one.
    public function isAnswered(): bool
    {
        // Answered once a selected_option index exists.
        return $this->selected_option !== null;
    }

    /**
     * Whether the picked option was correct. Only meaningful after grading.
     */
    // Reads the stored grading flag.
    public function isCorrect(): bool
    {
        // Strict comparison against true; ungraded rows stay false.
        return $this->is_correct === true;
    }

    /**
     * The letter of the option the student picked, or "-" when skipped.
     */
    // Produces the letter shown on the review screen.
    public function selectedLabel(): string
    {
        // Placeholder dash when no option was chosen.
        return $this->selected_option === null
            ? '-'
            // Otherwise delegate to the question's option letter helper.
            : $this->question->optionLabel($this->selected_option);
    }

    /**
     * The student's answer as a letter and its full text, or null when skipped.
     */
    // Builds a display string like "B. Some answer text".
    public function selectedAnswerText(): ?string
    {
        // Nothing to show when the item was skipped.
        if ($this->selected_option === null) {
            return null;
        }

        // Re-indexes the question's options so the stored index maps directly.
        $options = array_values($this->question->options ?? []);
        // Looks up the chosen option's text, guarding an out-of-range index.
        $text = $options[$this->selected_option] ?? null;

        // Return null if the text is missing, else the letter plus the text.
        return $text === null ? null : $this->selectedLabel().'. '.$text;
    }
}
