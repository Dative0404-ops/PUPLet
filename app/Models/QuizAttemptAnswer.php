<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by attempt() and question().
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One served question and its answer within an attempt.
class QuizAttemptAnswer extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quiz_attempt_answers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. selected_option stays null while the item is skipped.
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'position',
        'selected_option',
        'is_correct',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Reads is_correct as a real PHP boolean.
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /**
     * The attempt this answer belongs to.
     */
    public function attempt(): BelongsTo
    {
        // The foreign key column is quiz_attempt_id, not the default attempt_id.
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /**
     * The question that was answered.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Whether the student picked an option for this item.
     */
    public function isAnswered(): bool
    {
        // Answered once selected_option is set.
        return $this->selected_option !== null;
    }

    /**
     * Whether the picked option was correct. Only meaningful after grading.
     */
    public function isCorrect(): bool
    {
        // Strict check on true; ungraded rows stay false.
        return $this->is_correct === true;
    }

    /**
     * The letter of the option the student picked, or "-" when skipped.
     */
    public function selectedLabel(): string
    {
        // Show a dash when no option was chosen, else ask the question for its letter.
        return $this->selected_option === null
            ? '-'
            : $this->question->optionLabel($this->selected_option);
    }

    /**
     * The student's answer as a letter and its full text, or null when skipped.
     */
    public function selectedAnswerText(): ?string
    {
        // Nothing to show when the item was skipped.
        if ($this->selected_option === null) {
            return null;
        }

        // Re-index the question's options so the stored index maps directly.
        $options = array_values($this->question->options ?? []);
        // Look up the chosen option's text, guarding an out-of-range index.
        $text = $options[$this->selected_option] ?? null;

        // Return null when the text is missing; else format as "B. Some answer text".
        return $text === null ? null : $this->selectedLabel().'. '.$text;
    }
}
