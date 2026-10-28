<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;

// One row in the feedbacks table: a message from the contact form.
class Feedback extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedbacks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may fill; category must be one of the CATEGORIES keys below.
    protected $fillable = [
        'name',
        'email',
        'category',
        'subject',
        'message',
    ];

    /**
     * The feedback categories accepted by the contact form.
     *
     * @var array<string, string>
     */
    // Stored category key => label shown in the UI.
    public const CATEGORIES = [
        'suggestion' => 'Suggestion',
        'issue' => 'Reported issue',
        'inquiry' => 'Question or inquiry',
        'other' => 'Other',
    ];
}
