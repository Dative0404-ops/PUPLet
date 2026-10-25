<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by the subject() method.
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// Imports the HasMany relation type used by lessons(), quizzes() and questions().
use Illuminate\Database\Eloquent\Relations\HasMany;

// Eloquent model representing a topic within a subject.
class Topic extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for topic columns.
    protected $fillable = [
        // Foreign key to the parent subject.
        'subject_id',
        // The topic's display name.
        'name',
        // Manual ordering value used to sequence topics within a subject.
        'sort_order',
    ];

    /**
     * The subject this topic belongs to.
     */
    // BelongsTo relationship to the parent subject.
    public function subject(): BelongsTo
    {
        // Matches subjects.id to topics.subject_id.
        return $this->belongsTo(Subject::class);
    }

    /**
     * The review lessons written for this topic.
     */
    // HasMany relationship: one topic has many lessons.
    public function lessons(): HasMany
    {
        // Matches lessons.topic_id to this topic's id.
        return $this->hasMany(Lesson::class);
    }

    /**
     * The practice quizzes built from this topic.
     */
    // HasMany relationship: one topic has many practice quizzes.
    public function quizzes(): HasMany
    {
        // Matches quizzes.topic_id to this topic's id.
        return $this->hasMany(Quiz::class);
    }

    /**
     * The questions written for this topic.
     */
    // HasMany relationship: one topic contains many questions.
    public function questions(): HasMany
    {
        // Matches questions.topic_id to this topic's id.
        return $this->hasMany(Question::class);
    }
}
