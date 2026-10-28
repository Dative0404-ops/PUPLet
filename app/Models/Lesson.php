<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: a lesson belongs to one topic.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One review lesson attached to a topic.
class Lesson extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. key_terms and examples are JSON (see casts below).
    protected $fillable = [
        'topic_id',
        'title',
        'summary',
        'key_terms',
        'examples',
        'study_notes',
        'estimated_minutes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Reads these JSON columns as PHP arrays, and writes arrays back as JSON.
    protected function casts(): array
    {
        return [
            'key_terms' => 'array',
            'examples' => 'array',
        ];
    }

    /**
     * The topic this lesson covers.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Whether any reviewer material has been written for this lesson.
     */
    public function hasContent(): bool
    {
        // filled() treats "", null and empty arrays as "not filled".
        return filled($this->summary)
            || filled($this->key_terms)
            || filled($this->examples)
            || filled($this->study_notes);
    }
}
