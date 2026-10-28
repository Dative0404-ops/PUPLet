<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The query builder, used as the type hint in the scopes below.
use Illuminate\Database\Eloquent\Builder;
// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by topic().
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// HasMany: used by attempts().
use Illuminate\Database\Eloquent\Relations\HasMany;

// A quiz a student can take.
class Quiz extends Model
{
    /**
     * A short drill covering a single topic.
     */
    public const TYPE_PRACTICE = 'practice';

    /**
     * A long, timed examination covering every LET area.
     */
    public const TYPE_MOCK = 'mock';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. topic_id is null for mocks, which span all areas,
    // and type is one of the TYPE_* constants above.
    protected $fillable = [
        'topic_id',
        'type',
        'title',
        'item_count',
        'time_limit_minutes',
    ];

    /**
     * The topic this quiz drills. Mock examinations have none.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * The attempts students have made on this quiz.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Only the topic-level practice quizzes.
     * Used as Quiz::practice().
     */
    public function scopePractice(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PRACTICE);
    }

    /**
     * Only the full mock examinations.
     * Used as Quiz::mock().
     */
    public function scopeMock(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_MOCK);
    }

    /**
     * Whether this is a full mock examination.
     */
    public function isMock(): bool
    {
        return $this->type === self::TYPE_MOCK;
    }

    /**
     * The label used on buttons and cards, for example "Practice quiz".
     */
    public function typeLabel(): string
    {
        return $this->isMock() ? 'Mock examination' : 'Practice quiz';
    }
}
