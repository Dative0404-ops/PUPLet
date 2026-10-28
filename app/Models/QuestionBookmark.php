<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// BelongsTo: used by user() and question().
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One question saved by one student (a bookmark).
class QuestionBookmark extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'question_bookmarks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // The bookmark toggle creates rows with these two columns.
    protected $fillable = [
        'user_id',
        'question_id',
    ];

    /**
     * The student who saved the question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The question that was saved.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
