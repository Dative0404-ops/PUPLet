<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: a progress row belongs to one user and one lesson.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Tracks how far one student has read one lesson.
class LessonProgress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lesson_progresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. completed_at stays null until the lesson is finished.
    protected $fillable = [
        'user_id',
        'lesson_id',
        'progress',
        'last_viewed_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Reads these timestamps as Carbon date objects, not raw strings.
    protected function casts(): array
    {
        return [
            'last_viewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The student this record belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The lesson being tracked.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Whether the lesson has been finished.
     */
    public function isCompleted(): bool
    {
        // Finished once completed_at is set.
        return $this->completed_at !== null;
    }
}
