<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;

// Eloquent model representing one row in the feedbacks table (contact form messages).
class Feedback extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    // Explicitly names the table; otherwise Eloquent would guess "feedbacks" anyway,
    // but this makes it obvious and immune to pluralisation surprises.
    protected $table = 'feedbacks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist: only these columns may be set via create()/fill(),
    // protecting against users injecting unexpected fields from a form.
    protected $fillable = [
        // The sender's display name captured on the contact form.
        'name',
        // The sender's email address, used if the admin needs to reply.
        'email',
        // One of the CATEGORIES keys below describing the kind of message.
        'category',
        // The optional topic the message relates to (for example a quiz or subject).
        'subject',
        // The free-text body of the message.
        'message',
    ];

    /**
     * The feedback categories accepted by the contact form.
     *
     * @var array<string, string>
     */
    // Constant map of stored category keys to human-readable labels used in the UI.
    public const CATEGORIES = [
        // Feedback offering an improvement idea.
        'suggestion' => 'Suggestion',
        // Feedback reporting a problem with the site or content.
        'issue' => 'Reported issue',
        // A question or clarification request.
        'inquiry' => 'Question or inquiry',
        // Any feedback that does not fit the other buckets.
        'other' => 'Other',
    ];
}
