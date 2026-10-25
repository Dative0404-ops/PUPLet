<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the query Builder type used by the scope methods.
use Illuminate\Database\Eloquent\Builder;
// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by the topic() method.
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// Imports the HasMany relation type used by the attempts() method.
use Illuminate\Database\Eloquent\Relations\HasMany;

// Eloquent model representing a quiz a student can take.
class Quiz extends Model
{
    /**
     * A short drill covering a single topic.
     */
    // Type constant for the per-topic practice drills.
    public const TYPE_PRACTICE = 'practice';

    /**
     * A long, timed examination covering every LET area.
     */
    // Type constant for the full mock examination.
    public const TYPE_MOCK = 'mock';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for quiz columns.
    protected $fillable = [
        // Foreign key to the topic; null for mock exams that span all areas.
        'topic_id',
        // One of the TYPE_* constants (practice or mock).
        'type',
        // The quiz's display title.
        'title',
        // Number of questions served in a sitting.
        'item_count',
        // Per-quiz time limit in minutes; null means untimed.
        'time_limit_minutes',
    ];

    /**
     * The topic this quiz drills. Mock examinations have none.
     */
    // BelongsTo relationship to the parent topic (may be null for mocks).
    public function topic(): BelongsTo
    {
        // Matches topics.id to quizzes.topic_id.
        return $this->belongsTo(Topic::class);
    }

    /**
     * The attempts students have made on this quiz.
     */
    // HasMany relationship: one quiz is taken in many attempts.
    public function attempts(): HasMany
    {
        // Matches quiz_attempts.quiz_id to this quiz's id.
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Only the topic-level practice quizzes.
     */
    // Local scope; called as Quiz::practice() to filter by type.
    public function scopePractice(Builder $query): Builder
    {
        // Adds a WHERE type = 'practice' clause to the query.
        return $query->where('type', self::TYPE_PRACTICE);
    }

    /**
     * Only the full mock examinations.
     */
    // Local scope; called as Quiz::mock() to filter by type.
    public function scopeMock(Builder $query): Builder
    {
        // Adds a WHERE type = 'mock' clause to the query.
        return $query->where('type', self::TYPE_MOCK);
    }

    /**
     * Whether this is a full mock examination.
     */
    // Instance helper checking the loaded model's own type.
    public function isMock(): bool
    {
        // True when the type column equals the mock constant.
        return $this->type === self::TYPE_MOCK;
    }

    /**
     * The label used on buttons and cards, for example "Practice quiz".
     */
    // Returns a presentable name for the quiz type.
    public function typeLabel(): string
    {
        // Ternary picks the mock label or falls back to the practice label.
        return $this->isMock() ? 'Mock examination' : 'Practice quiz';
    }
}
