<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used for the topic() method's return hint.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Eloquent model representing one review lesson attached to a topic.
class Lesson extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist: the columns that may be set from request data.
    protected $fillable = [
        // Foreign key linking this lesson to its parent topic.
        'topic_id',
        // The lesson's display title.
        'title',
        // A short overview paragraph shown at the top of the lesson.
        'summary',
        // The key terms, stored as JSON and cast to an array below.
        'key_terms',
        // Worked examples, stored as JSON and cast to an array below.
        'examples',
        // The longer reviewer notes body.
        'study_notes',
        // Estimated reading time in minutes shown to the student.
        'estimated_minutes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Declares attribute casts; Eloquent converts the raw column values automatically.
    protected function casts(): array
    {
        return [
            // Decodes the stored JSON text into a PHP array when read, and encodes on save.
            'key_terms' => 'array',
            // Decodes the stored JSON text into a PHP array when read, and encodes on save.
            'examples' => 'array',
        ];
    }

    /**
     * The topic this lesson covers.
     */
    // BelongsTo relationship: resolves the Topic row whose id equals this lesson's topic_id.
    public function topic(): BelongsTo
    {
        // Tells Eloquent to match topics.id to lessons.topic_id.
        return $this->belongsTo(Topic::class);
    }

    /**
     * Whether any reviewer material has been written for this lesson.
     */
    // Helper used by the UI to decide if a lesson is worth opening.
    public function hasContent(): bool
    {
        // True if at least one content field holds a non-empty value; filled() treats
        // empty strings, null, and empty arrays as "not filled".
        return filled($this->summary)
            || filled($this->key_terms)
            || filled($this->examples)
            || filled($this->study_notes);
    }
}
