<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The query builder, used as the type hint in the scopes below.
use Illuminate\Database\Eloquent\Builder;
// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by user().
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A study goal a student sets for themselves.
class StudyGoal extends Model
{
    /**
     * A recurring daily target of items to answer.
     */
    public const TYPE_DAILY_TARGET = 'daily_target';

    /**
     * A scheduled review session the student plans to attend.
     */
    public const TYPE_REVIEW_SESSION = 'review_session';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. type is one of the TYPE_* constants above, and
    // completed_at stays null until the goal is marked done.
    protected $fillable = [
        'user_id',
        'title',
        'type',
        'target_date',
        'target_time',
        'target_items',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Reads target_date as a Carbon date and completed_at as a Carbon date-time.
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The student who owns this goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Only daily item targets.
     * Used as StudyGoal::dailyTargets().
     */
    public function scopeDailyTargets(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DAILY_TARGET);
    }

    /**
     * Only scheduled review sessions.
     * Used as StudyGoal::reviewSessions().
     */
    public function scopeReviewSessions(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_REVIEW_SESSION);
    }

    /**
     * Whether this goal is a daily item target.
     */
    public function isDailyTarget(): bool
    {
        return $this->type === self::TYPE_DAILY_TARGET;
    }

    /**
     * A short label for the scheduled time of a review session.
     */
    public function timeLabel(): ?string
    {
        // Keep the first five characters (HH:MM); null when there is no time.
        return $this->target_time
            ? substr($this->target_time, 0, 5)
            : null;
    }
}
