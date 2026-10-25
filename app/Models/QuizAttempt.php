<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the query Builder type used by the scope methods.
use Illuminate\Database\Eloquent\Builder;
// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by user() and quiz().
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// Imports the HasMany relation type used by the answers() method.
use Illuminate\Database\Eloquent\Relations\HasMany;
// Imports Carbon for the datetime return type of deadline().
use Illuminate\Support\Carbon;

// Eloquent model for one sitting of a quiz by one student.
class QuizAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for attempt columns.
    protected $fillable = [
        // The student who took the quiz.
        'user_id',
        // The quiz that was taken.
        'quiz_id',
        // How many questions the sitting contained.
        'total_items',
        // How many of those questions were answered so far.
        'answered_items',
        // Number of correct answers; final only after completion.
        'score',
        // Time limit frozen for this attempt (may override the quiz's own limit).
        'time_limit_minutes',
        // When the student began the attempt.
        'started_at',
        // When the student submitted; null while still open.
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Declares casts applied automatically when these attributes are accessed.
    protected function casts(): array
    {
        return [
            // Converts the start time into a Carbon datetime instance.
            'started_at' => 'datetime',
            // Converts the completion time into a Carbon datetime instance.
            'completed_at' => 'datetime',
            // Forces the time limit to an integer for arithmetic.
            'time_limit_minutes' => 'integer',
        ];
    }

    /**
     * The student who took the quiz.
     */
    // BelongsTo relationship to the owning student account.
    public function user(): BelongsTo
    {
        // Matches users.id to quiz_attempts.user_id.
        return $this->belongsTo(User::class);
    }

    /**
     * The quiz that was taken.
     */
    // BelongsTo relationship to the quiz definition.
    public function quiz(): BelongsTo
    {
        // Matches quizzes.id to quiz_attempts.quiz_id.
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Only attempts that have been submitted.
     */
    // Local scope; called as QuizAttempt::completed() to filter submitted attempts.
    public function scopeCompleted(Builder $query): Builder
    {
        // Selects rows where completed_at is not null.
        return $query->whereNotNull('completed_at');
    }

    /**
     * Only attempts that are still open.
     */
    // Local scope; called as QuizAttempt::inProgress() to filter open attempts.
    public function scopeInProgress(Builder $query): Builder
    {
        // Selects rows where completed_at is null.
        return $query->whereNull('completed_at');
    }

    /**
     * Whether the attempt is still being answered.
     */
    // Instance helper checking the loaded model's completion state.
    public function isInProgress(): bool
    {
        // Open while no completed_at timestamp has been set.
        return $this->completed_at === null;
    }

    /**
     * Percentage of items answered on an open attempt.
     */
    // Progress metric shown on the attempt screen.
    public function answeredPercent(): int
    {
        // Guard against divide-by-zero when the attempt has no items.
        return $this->total_items > 0
            // Ratio of answered to total, scaled to a whole percentage.
            ? (int) round($this->answered_items / $this->total_items * 100)
            : 0;
    }

    /**
     * The question-by-question answers recorded for this attempt.
     */
    // HasMany relationship: one attempt has many per-question answers.
    public function answers(): HasMany
    {
        // Matches quiz_attempt_answers.quiz_attempt_id to this attempt's id.
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    /**
     * Percentage of correct answers for a submitted attempt.
     */
    // Accuracy metric used on result screens and statistics.
    public function accuracy(): float
    {
        // Guard against divide-by-zero when the attempt has no items.
        return $this->total_items > 0
            // Score over total, as a percentage rounded to one decimal place.
            ? round($this->score / $this->total_items * 100, 1)
            : 0.0;
    }

    /**
     * Whether this attempt is timed.
     */
    // Instance helper derived from whether a deadline exists.
    public function hasTimeLimit(): bool
    {
        // Timed when deadline() returns a moment rather than null.
        return $this->deadline() !== null;
    }

    /**
     * The moment the time limit runs out, or null when the attempt is untimed.
     *
     * The limit recorded on the attempt wins, so a deadline already running is
     * never moved by a later change to the quiz or to the student's preference.
     */
    // Computes the expiry moment for the countdown and overdue checks.
    public function deadline(): ?Carbon
    {
        // Prefer this attempt's frozen limit, otherwise fall back to the quiz's limit.
        $minutes = $this->time_limit_minutes ?? $this->quiz?->time_limit_minutes;

        // Add the minutes to the start time, or null when there is no limit.
        return $minutes ? $this->started_at->copy()->addMinutes($minutes) : null;
    }

    /**
     * Whether the time limit has already passed on an open attempt.
     */
    // Flags an attempt that should be auto-submitted or re-scored.
    public function isOverdue(): bool
    {
        // Resolve the deadline first to avoid recomputing it below.
        $deadline = $this->deadline();

        // Overdue only if still open, timed, and the present moment is past the deadline.
        return $this->isInProgress() && $deadline !== null && now()->greaterThan($deadline);
    }

    /**
     * Whole seconds left before the deadline, or null when the quiz is untimed.
     */
    // Used by the client-side countdown timer.
    public function remainingSeconds(): ?int
    {
        // Resolve the deadline once for reuse.
        $deadline = $this->deadline();

        // Untimed attempts return null; otherwise clamp the difference at zero.
        return $deadline === null ? null : max(0, (int) now()->diffInSeconds($deadline, false));
    }

    /**
     * How many items the student got wrong on a submitted attempt.
     */
    // Counts incorrect answers, used for the "retry wrong items" flow.
    public function incorrectCount(): int
    {
        // Total minus correct, never below zero.
        return max(0, $this->total_items - $this->score);
    }
}
