<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the query Builder type used by the scope methods.
use Illuminate\Database\Eloquent\Builder;
// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by topic() and reviewer().
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// Imports the HasMany relation type used by reports() and answers().
use Illuminate\Database\Eloquent\Relations\HasMany;

// Eloquent model representing one exam question belonging to a topic.
class Question extends Model
{
    /**
     * Written but not yet shown to students.
     */
    // Status constant for a question that is authored but unpublished.
    public const STATUS_DRAFT = 'draft';

    /**
     * Live in practice quizzes and the mock examination.
     */
    // Status constant for a question that students can actually answer.
    public const STATUS_PUBLISHED = 'published';

    /**
     * Withdrawn from circulation but kept for the record.
     */
    // Status constant for a retired question kept for history.
    public const STATUS_ARCHIVED = 'archived';

    /**
     * The states a question can be in.
     *
     * @var array<string, string>
     */
    // Maps each status constant to its human-readable label for the admin UI.
    public const STATUS_LABELS = [
        // Draft state label.
        self::STATUS_DRAFT => 'Draft',
        // Published state label.
        self::STATUS_PUBLISHED => 'Published',
        // Archived state label.
        self::STATUS_ARCHIVED => 'Archived',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for question columns.
    protected $fillable = [
        // Foreign key linking this question to its topic.
        'topic_id',
        // The question text shown to the student.
        'prompt',
        // The answer choices, stored as JSON and cast to an array below.
        'options',
        // Zero-based index of the correct entry within options.
        'correct_option',
        // The rationale shown after answering.
        'explanation',
        // One of the STATUS_* constants controlling visibility.
        'status',
        // A citation or source reference backing the answer.
        'reference',
        // A short note about where the question came from.
        'source',
        // Foreign key to the admin who last verified the question.
        'reviewed_by',
        // Timestamp of the last verification; null means unverified.
        'verified_at',
        // Manual ordering value used to sequence questions within a topic.
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Declares casts applied automatically whenever these attributes are read or written.
    protected function casts(): array
    {
        return [
            // Decodes the JSON options column into a PHP array.
            'options' => 'array',
            // Converts the verification timestamp into a Carbon datetime instance.
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Only the questions students are allowed to answer.
     */
    // Local scope; called as Question::published() to filter by status.
    public function scopePublished(Builder $query): Builder
    {
        // Adds a WHERE status = 'published' clause to the query.
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Only the questions that have been archived.
     */
    // Local scope; called as Question::archived() to filter by status.
    public function scopeArchived(Builder $query): Builder
    {
        // Adds a WHERE status = 'archived' clause to the query.
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Whether this question is live in the reviewer.
     */
    // Instance helper checking the loaded model's own status.
    public function isPublished(): bool
    {
        // True when the status column equals the published constant.
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Whether this question has been withdrawn.
     */
    // Instance helper checking the loaded model's own status.
    public function isArchived(): bool
    {
        // True when the status column equals the archived constant.
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Whether an administrator has signed this question off.
     */
    // Instance helper for the verification flag.
    public function isVerified(): bool
    {
        // Verified once a verified_at timestamp has been set.
        return $this->verified_at !== null;
    }

    /**
     * The readable name of this question's state.
     */
    // Returns the label map value, with a fallback for unknown statuses.
    public function statusLabel(): string
    {
        // Uses STATUS_LABELS when known, otherwise prettifies the raw value.
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * The topic this question belongs to.
     */
    // BelongsTo relationship linking back to the parent topic.
    public function topic(): BelongsTo
    {
        // Matches topics.id to questions.topic_id.
        return $this->belongsTo(Topic::class);
    }

    /**
     * The administrator who last checked this question.
     */
    // BelongsTo relationship to the verifying user account.
    public function reviewer(): BelongsTo
    {
        // Second argument names the custom foreign key column instead of user_id.
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The problems students have reported about this question.
     */
    // HasMany relationship: one question can have many reports.
    public function reports(): HasMany
    {
        // Matches question_reports.question_id to this question's id.
        return $this->hasMany(QuestionReport::class);
    }

    /**
     * The answers students have given to this question.
     */
    // HasMany relationship: one question appears in many recorded answers.
    public function answers(): HasMany
    {
        // Matches quiz_attempt_answers.question_id to this question's id.
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    /**
     * The answer choices, keyed by their letter (A, B, C, D).
     *
     * @return array<string, string>
     */
    // Accessor-style helper that pairs each option with its letter for display.
    public function labelledOptions(): array
    {
        // Starts with an empty result map of letter => option text.
        $labelled = [];

        // Re-indexes options from zero and walks each with its numeric position.
        foreach (array_values($this->options ?? []) as $index => $option) {
            // Stores the option text under its letter key (A, B, C...).
            $labelled[$this->optionLabel($index)] = $option;
        }

        // Returns the letter-keyed option list.
        return $labelled;
    }

    /**
     * The letter shown beside the option at the given index.
     */
    // Converts a numeric index into its A, B, C... letter.
    public function optionLabel(?int $index): string
    {
        // Guard against null (e.g. skipped question).
        if ($index === null) {
            return '—';
        }

        // ASCII 65 is "A", so adding the zero-based index yields the letter.
        return chr(65 + $index);
    }

    /**
     * Whether the selected option index is the correct answer.
     */
    // Grades one answer choice; null (skipped) is never correct.
    public function isCorrect(?int $option): bool
    {
        // Requires a chosen option that matches the stored correct_option index.
        return $option !== null && $option === $this->correct_option;
    }

    /**
     * The letter of the correct answer, for example "B".
     */
    // Turns the stored correct_option index into a letter.
    public function correctLabel(): string
    {
        // Reuses optionLabel on the rule answer index.
        return $this->optionLabel($this->correct_option);
    }

    /**
     * The correct answer as a letter and its full text.
     */
    // Builds a display string like "B. Some answer text".
    public function correctAnswerText(): string
    {
        // Re-indexes options so the correct_option index maps directly.
        $options = array_values($this->options ?? []);

        // Concatenates the letter, a period, and the option text (blank if missing).
        return $this->correctLabel().'. '.($options[$this->correct_option] ?? '');
    }

    /**
     * The subject this question ultimately drills.
     */
    // Walks the topic relation up to its subject; null-safe if topic is absent.
    public function subject(): ?Subject
    {
        // Uses the null-safe operator to avoid errors when topic is not loaded.
        return $this->topic?->subject;
    }
}
