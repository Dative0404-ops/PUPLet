<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the query Builder type used by the scopeOpen method.
use Illuminate\Database\Eloquent\Builder;
// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by the relationship methods.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Eloquent model for a student's complaint about a single question.
class QuestionReport extends Model
{
    /**
     * The recorded answer does not match the material.
     */
    // Reason constant: the answer key appears to be wrong.
    public const REASON_WRONG_ANSWER = 'wrong_answer';

    /**
     * The wording is confusing or the question cannot be answered.
     */
    // Reason constant: the question is ambiguous or unclear.
    public const REASON_UNCLEAR = 'unclear';

    /**
     * A spelling, grammar, or formatting problem.
     */
    // Reason constant: a text or formatting mistake.
    public const REASON_TYPO = 'typo';

    /**
     * Anything that does not fit the other reasons.
     */
    // Reason constant: a catch-all for other issues.
    public const REASON_OTHER = 'other';

    /**
     * The reasons a student can pick, and their labels.
     *
     * @var array<string, string>
     */
    // Maps each reason constant to the label shown in the report form.
    public const REASON_LABELS = [
        // Label for the wrong-answer reason.
        self::REASON_WRONG_ANSWER => 'The answer key looks wrong',
        // Label for the unclear-wording reason.
        self::REASON_UNCLEAR => 'The question is unclear',
        // Label for the typo/formatting reason.
        self::REASON_TYPO => 'Spelling or formatting mistake',
        // Label for the catch-all reason.
        self::REASON_OTHER => 'Something else',
    ];

    /**
     * Waiting for an administrator to look at it.
     */
    // Status constant for a report that still needs action.
    public const STATUS_OPEN = 'open';

    /**
     * Dealt with, for example by correcting or archiving the question.
     */
    // Status constant for a report that was acted upon.
    public const STATUS_RESOLVED = 'resolved';

    /**
     * Looked at, but no change was needed.
     */
    // Status constant for a report reviewed without change.
    public const STATUS_DISMISSED = 'dismissed';

    /**
     * The states a report can be in.
     *
     * @var array<string, string>
     */
    // Maps each status constant to its human-readable label.
    public const STATUS_LABELS = [
        // Open state label.
        self::STATUS_OPEN => 'Open',
        // Resolved state label.
        self::STATUS_RESOLVED => 'Resolved',
        // Dismissed state label.
        self::STATUS_DISMISSED => 'Dismissed',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for report columns.
    protected $fillable = [
        // The question being reported.
        'question_id',
        // The student who filed the report.
        'user_id',
        // One of the REASON_* constants explaining why.
        'reason',
        // The student's optional extra explanation.
        'details',
        // One of the STATUS_* constants tracking handling progress.
        'status',
        // The admin's note explaining the outcome.
        'resolution_note',
        // Foreign key to the admin who closed the report.
        'resolved_by',
        // Timestamp when the report was closed.
        'resolved_at',
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
            // Converts the resolution timestamp into a Carbon datetime instance.
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Only the reports that still need attention.
     */
    // Local scope; called as QuestionReport::open() to filter by status.
    public function scopeOpen(Builder $query): Builder
    {
        // Adds a WHERE status = 'open' clause to the query.
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * The question this report is about.
     */
    // BelongsTo relationship linking back to the reported question.
    public function question(): BelongsTo
    {
        // Matches questions.id to question_reports.question_id.
        return $this->belongsTo(Question::class);
    }

    /**
     * The student who filed this report.
     */
    // BelongsTo relationship to the reporting student, with an explicit foreign key.
    public function reporter(): BelongsTo
    {
        // Names user_id as the foreign key (rather than a reporter_id column).
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The administrator who closed this report.
     */
    // BelongsTo relationship to the resolving admin, with a custom foreign key.
    public function resolver(): BelongsTo
    {
        // Names resolved_by as the foreign key back to users.
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Whether this report is still waiting to be handled.
     */
    // Instance helper checking the loaded model's own status.
    public function isOpen(): bool
    {
        // True when the status column equals the open constant.
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * The readable label of the reason this report was filed.
     */
    // Returns the reason's label, falling back to a prettified version.
    public function reasonLabel(): string
    {
        // Uses REASON_LABELS when known, otherwise replaces underscores with spaces.
        return self::REASON_LABELS[$this->reason] ?? ucfirst(str_replace('_', ' ', (string) $this->reason));
    }

    /**
     * The readable name of this report's state.
     */
    // Returns the status label, falling back to a prettified version.
    public function statusLabel(): string
    {
        // Uses STATUS_LABELS when known, otherwise capitalises the raw value.
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}
