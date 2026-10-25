<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the BelongsTo relation type used by user() and question().
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Pivot-style model recording one question saved by one student (bookmark).
class QuestionBookmark extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    // Names the table explicitly; Eloquent would otherwise guess "question_bookmarks".
    protected $table = 'question_bookmarks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist; the AJAX toggle creates rows with these two keys.
    protected $fillable = [
        // The student who saved the question.
        'user_id',
        // The question that was saved.
        'question_id',
    ];

    /**
     * The student who saved the question.
     */
    // BelongsTo relationship linking to the owning student account.
    public function user(): BelongsTo
    {
        // Matches users.id to question_bookmarks.user_id.
        return $this->belongsTo(User::class);
    }

    /**
     * The question that was saved.
     */
    // BelongsTo relationship linking to the bookmarked question.
    public function question(): BelongsTo
    {
        // Matches questions.id to question_bookmarks.question_id.
        return $this->belongsTo(Question::class);
    }
}
