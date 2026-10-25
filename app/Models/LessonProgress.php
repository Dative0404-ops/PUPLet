<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used for the user() and lesson() methods.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Eloquent model tracking how far one student has read one lesson.
class LessonProgress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    // Names the table explicitly because Eloquent's default plural is ambiguous here.
    protected $table = 'lesson_progresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for the progress row.
    protected $fillable = [
        // The student this progress record belongs to.
        'user_id',
        // The lesson being tracked.
        'lesson_id',
        // Completion percentage (or similar progress value) for the lesson.
        'progress',
        // Timestamp of the student's most recent visit to the lesson.
        'last_viewed_at',
        // Timestamp when the student finished the lesson; null while unfinished.
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Declares casts so these timestamps become Carbon date objects, not raw strings.
    protected function casts(): array
    {
        return [
            // Converts the stored value into a Carbon datetime instance.
            'last_viewed_at' => 'datetime',
            // Converts the stored value into a Carbon datetime instance.
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The student this record belongs to.
     */
    // BelongsTo relationship linking back to the owning student account.
    public function user(): BelongsTo
    {
        // Matches users.id to lesson_progresses.user_id.
        return $this->belongsTo(User::class);
    }

    /**
     * The lesson being tracked.
     */
    // BelongsTo relationship linking back to the lesson that was read.
    public function lesson(): BelongsTo
    {
        // Matches lessons.id to lesson_progresses.lesson_id.
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Whether the lesson has been finished.
     */
    // Convenience check the views use instead of testing the timestamp directly.
    public function isCompleted(): bool
    {
        // Finished when a completed_at timestamp exists.
        return $this->completed_at !== null;
    }
}
