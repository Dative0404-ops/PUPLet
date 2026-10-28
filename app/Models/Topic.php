<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by subject().
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// HasMany: used by lessons(), quizzes() and questions().
use Illuminate\Database\Eloquent\Relations\HasMany;

// A topic within a subject.
class Topic extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set.
    protected $fillable = [
        'subject_id',
        'name',
        'sort_order',
    ];

    /**
     * The subject this topic belongs to.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * The review lessons written for this topic.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * The practice quizzes built from this topic.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * The questions written for this topic.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
