<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The query builder, used as the type hint in scopeOpen below.
use Illuminate\Database\Eloquent\Builder;
// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by the relationship methods below.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A student's complaint about a single question.
class QuestionReport extends Model
{
    /**
     * The recorded answer does not match the material.
     */
    public const REASON_WRONG_ANSWER = 'wrong_answer';

    /**
     * The wording is confusing or the question cannot be answered.
     */
    public const REASON_UNCLEAR = 'unclear';

    /**
     * A spelling, grammar, or formatting problem.
     */
    public const REASON_TYPO = 'typo';

    /**
     * Anything that does not fit the other reasons.
     */
    public const REASON_OTHER = 'other';

    /**
     * The reasons a student can pick, and their labels.
     *
     * @var array<string, string>
     */
    // Reason constant => label shown in the report form.
    public const REASON_LABELS = [
        self::REASON_WRONG_ANSWER => 'The answer key looks wrong',
        self::REASON_UNCLEAR => 'The question is unclear',
        self::REASON_TYPO => 'Spelling or formatting mistake',
        self::REASON_OTHER => 'Something else',
    ];

    /**
     * Waiting for an administrator to look at it.
     */
    public const STATUS_OPEN = 'open';

    /**
     * Dealt with, for example by correcting or archiving the question.
     */
    public const STATUS_RESOLVED = 'resolved';

    /**
     * Looked at, but no change was needed.
     */
    public const STATUS_DISMISSED = 'dismissed';

    /**
     * The states a report can be in.
     *
     * @var array<string, string>
     */
    // Status constant => label shown in the UI.
    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_DISMISSED => 'Dismissed',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. reason is one of the REASON_* constants and status
    // is one of the STATUS_* constants above.
    protected $fillable = [
        'question_id',
        'user_id',
        'reason',
        'details',
        'status',
        'resolution_note',
        'resolved_by',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Reads resolved_at as a Carbon date object.
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Only the reports that still need attention.
     * Used as QuestionReport::open().
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * The question this report is about.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * The student who filed this report.
     */
    public function reporter(): BelongsTo
    {
        // The foreign key column is user_id, not the default reporter_id.
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The administrator who closed this report.
     */
    public function resolver(): BelongsTo
    {
        // The foreign key column is resolved_by, not the default user_id.
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Whether this report is still waiting to be handled.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * The readable label of the reason this report was filed.
     */
    public function reasonLabel(): string
    {
        // Use REASON_LABELS when known, else replace underscores with spaces.
        return self::REASON_LABELS[$this->reason] ?? ucfirst(str_replace('_', ' ', (string) $this->reason));
    }

    /**
     * The readable name of this report's state.
     */
    public function statusLabel(): string
    {
        // Use STATUS_LABELS when known, else capitalise the raw value.
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}
