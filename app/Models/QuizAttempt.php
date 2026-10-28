<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The query builder, used as the type hint in the scopes below.
use Illuminate\Database\Eloquent\Builder;
// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by user() and quiz().
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// HasMany: used by answers().
use Illuminate\Database\Eloquent\Relations\HasMany;
// Carbon: the return type of deadline().
use Illuminate\Support\Carbon;

// One sitting of a quiz by one student.
class QuizAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. completed_at stays null until the student submits.
    protected $fillable = [
        'user_id',
        'quiz_id',
        'total_items',
        'answered_items',
        'score',
        'time_limit_minutes',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Reads the two timestamps as Carbon dates and the limit as an integer.
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'time_limit_minutes' => 'integer',
        ];
    }

    /**
     * The student who took the quiz.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The quiz that was taken.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Only attempts that have been submitted.
     * Used as QuizAttempt::completed().
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Only attempts that are still open.
     * Used as QuizAttempt::inProgress().
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Whether the attempt is still being answered.
     */
    public function isInProgress(): bool
    {
        return $this->completed_at === null;
    }

    /**
     * Percentage of items answered on an open attempt.
     */
    public function answeredPercent(): int
    {
        // When there are no items, report 0 instead of dividing by zero.
        return $this->total_items > 0
            ? (int) round($this->answered_items / $this->total_items * 100)
            : 0;
    }

    /**
     * The question-by-question answers recorded for this attempt.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    /**
     * Percentage of correct answers for a submitted attempt.
     */
    public function accuracy(): float
    {
        // When there are no items, report 0 instead of dividing by zero.
        return $this->total_items > 0
            ? round($this->score / $this->total_items * 100, 1)
            : 0.0;
    }

    /**
     * Whether this attempt is timed.
     */
    public function hasTimeLimit(): bool
    {
        return $this->deadline() !== null;
    }

    /**
     * The moment the time limit runs out, or null when the attempt is untimed.
     *
     * The limit recorded on the attempt wins, so a deadline already running is
     * never moved by a later change to the quiz or to the student's preference.
     */
    public function deadline(): ?Carbon
    {
        // Use this attempt's frozen limit, else fall back to the quiz's limit.
        $minutes = $this->time_limit_minutes ?? $this->quiz?->time_limit_minutes;

        // No limit means no deadline; otherwise add the minutes to the start time.
        return $minutes ? $this->started_at->copy()->addMinutes($minutes) : null;
    }

    /**
     * Whether the time limit has already passed on an open attempt.
     */
    public function isOverdue(): bool
    {
        // Get the deadline once here so it is not recomputed below.
        $deadline = $this->deadline();

        // Overdue only if still open, timed, and the present moment is past the deadline.
        return $this->isInProgress() && $deadline !== null && now()->greaterThan($deadline);
    }

    /**
     * Whole seconds left before the deadline, or null when the quiz is untimed.
     */
    public function remainingSeconds(): ?int
    {
        // Get the deadline once here so it is not recomputed below.
        $deadline = $this->deadline();

        // Untimed attempts return null; otherwise clamp the difference at zero.
        return $deadline === null ? null : max(0, (int) now()->diffInSeconds($deadline, false));
    }

    /**
     * How many items the student got wrong on a submitted attempt.
     */
    public function incorrectCount(): int
    {
        // Total minus correct, never below zero.
        return max(0, $this->total_items - $this->score);
    }
}
