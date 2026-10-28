<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The query builder, used as the type hint in the scopes below.
use Illuminate\Database\Eloquent\Builder;
// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by topic() and reviewer().
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// HasMany: used by reports() and answers().
use Illuminate\Database\Eloquent\Relations\HasMany;

// One exam question belonging to a topic.
class Question extends Model
{
    /**
     * Written but not yet shown to students.
     */
    public const STATUS_DRAFT = 'draft';

    /**
     * Live in practice quizzes and the mock examination.
     */
    public const STATUS_PUBLISHED = 'published';

    /**
     * Withdrawn from circulation but kept for the record.
     */
    public const STATUS_ARCHIVED = 'archived';

    /**
     * The states a question can be in.
     *
     * @var array<string, string>
     */
    // Status constant => label shown in the admin UI.
    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. options is JSON, and correct_option is a zero-based
    // index into it. status is one of the STATUS_* constants above.
    protected $fillable = [
        'topic_id',
        'prompt',
        'options',
        'correct_option',
        'explanation',
        'status',
        'reference',
        'source',
        'reviewed_by',
        'verified_at',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Reads options as a PHP array and verified_at as a Carbon date.
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Only the questions students are allowed to answer.
     * Used as Question::published().
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Only the questions that have been archived.
     * Used as Question::archived().
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Whether this question is live in the reviewer.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Whether this question has been withdrawn.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Whether an administrator has signed this question off.
     */
    public function isVerified(): bool
    {
        // Verified once verified_at is set.
        return $this->verified_at !== null;
    }

    /**
     * The readable name of this question's state.
     */
    public function statusLabel(): string
    {
        // Use STATUS_LABELS when the status is known, else prettify the raw value.
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * The topic this question belongs to.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * The administrator who last checked this question.
     */
    public function reviewer(): BelongsTo
    {
        // The foreign key column is reviewed_by, not the default user_id.
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The problems students have reported about this question.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(QuestionReport::class);
    }

    /**
     * The answers students have given to this question.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    /**
     * The answer choices, keyed by their letter (A, B, C, D).
     *
     * @return array<string, string>
     */
    public function labelledOptions(): array
    {
        $labelled = [];

        // Walk the options in order, keying each one by its letter (A, B, C...).
        foreach (array_values($this->options ?? []) as $index => $option) {
            $labelled[$this->optionLabel($index)] = $option;
        }

        return $labelled;
    }

    /**
     * The letter shown beside the option at the given index.
     */
    public function optionLabel(?int $index): string
    {
        // A null index (skipped question) has no letter.
        if ($index === null) {
            return '—';
        }

        // ASCII 65 is "A", so adding the zero-based index yields the letter.
        return chr(65 + $index);
    }

    /**
     * Whether the selected option index is the correct answer.
     */
    public function isCorrect(?int $option): bool
    {
        // A skipped (null) answer is never correct.
        return $option !== null && $option === $this->correct_option;
    }

    /**
     * The letter of the correct answer, for example "B".
     */
    public function correctLabel(): string
    {
        return $this->optionLabel($this->correct_option);
    }

    /**
     * The correct answer as a letter and its full text.
     */
    public function correctAnswerText(): string
    {
        // Re-index options so correct_option maps directly to an entry here.
        $options = array_values($this->options ?? []);

        // Format as "B. Some answer text"; blank text when the index is missing.
        return $this->correctLabel().'. '.($options[$this->correct_option] ?? '');
    }

    /**
     * The subject this question ultimately drills.
     */
    public function subject(): ?Subject
    {
        // The ?-> avoids an error when the topic is not loaded.
        return $this->topic?->subject;
    }
}
