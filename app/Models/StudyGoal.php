<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the query Builder type used by the scope methods.
use Illuminate\Database\Eloquent\Builder;
// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by the user() method.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Eloquent model for a study goal a student sets for themselves.
class StudyGoal extends Model
{
    /**
     * A recurring daily target of items to answer.
     */
    // Type constant for a rolling daily item target.
    public const TYPE_DAILY_TARGET = 'daily_target';

    /**
     * A scheduled review session the student plans to attend.
     */
    // Type constant for a one-off scheduled review session.
    public const TYPE_REVIEW_SESSION = 'review_session';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for goal columns.
    protected $fillable = [
        // The student who owns this goal.
        'user_id',
        // The goal's display title.
        'title',
        // One of the TYPE_* constants (daily target or review session).
        'type',
        // The calendar date for a review session.
        'target_date',
        // The clock time for a review session.
        'target_time',
        // The number of items aimed for on a daily target.
        'target_items',
        // Timestamp when the student marked the goal done; null while pending.
        'completed_at',
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
            // Parses the target date into a Carbon date object.
            'target_date' => 'date',
            // Converts the completion timestamp into a Carbon datetime instance.
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The student who owns this goal.
     */
    // BelongsTo relationship to the owning student account.
    public function user(): BelongsTo
    {
        // Matches users.id to study_goals.user_id.
        return $this->belongsTo(User::class);
    }

    /**
     * Only daily item targets.
     */
    // Local scope; called as StudyGoal::dailyTargets() to filter by type.
    public function scopeDailyTargets(Builder $query): Builder
    {
        // Adds a WHERE type = 'daily_target' clause to the query.
        return $query->where('type', self::TYPE_DAILY_TARGET);
    }

    /**
     * Only scheduled review sessions.
     */
    // Local scope; called as StudyGoal::reviewSessions() to filter by type.
    public function scopeReviewSessions(Builder $query): Builder
    {
        // Adds a WHERE type = 'review_session' clause to the query.
        return $query->where('type', self::TYPE_REVIEW_SESSION);
    }

    /**
     * Whether this goal is a daily item target.
     */
    // Instance helper checking the loaded model's own type.
    public function isDailyTarget(): bool
    {
        // True when the type column equals the daily-target constant.
        return $this->type === self::TYPE_DAILY_TARGET;
    }

    /**
     * A short label for the scheduled time of a review session.
     */
    // Trims the stored time down to hours and minutes for display.
    public function timeLabel(): ?string
    {
        // Return the first five characters (HH:MM) when a time exists, else null.
        return $this->target_time
            ? substr($this->target_time, 0, 5)
            : null;
    }
}
