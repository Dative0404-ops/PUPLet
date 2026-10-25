<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;
// Imports the HasMany relation type used by the topics() method.
use Illuminate\Database\Eloquent\Relations\HasMany;

// Eloquent model representing a LET subject (an area of the examination).
class Subject extends Model
{
    /**
     * The three areas of the Licensure Examination for Teachers.
     *
     * @var array<string, string>
     */
    // Maps each stored area key to the label shown to students and admins.
    public const AREA_LABELS = [
        // General Education area label.
        'gen_ed' => 'General Education',
        // Professional Education area label.
        'prof_ed' => 'Professional Education',
        // Specialization (major) area label.
        'specialization' => 'Specialization',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for subject columns.
    protected $fillable = [
        // The LET area this subject sits in (one of the AREA_LABELS keys).
        'area',
        // The subject name, such as "English" or "Mathematics".
        'name',
        // Manual ordering value used to sequence subjects on screen.
        'sort_order',
    ];

    /**
     * The topics covered under this subject.
     */
    // HasMany relationship: one subject contains many topics.
    public function topics(): HasMany
    {
        // Matches topics.subject_id to this subject's id.
        return $this->hasMany(Topic::class);
    }

    /**
     * The readable label of the area this subject belongs to.
     */
    // Returns the area's label, falling back to the raw area value.
    public function areaLabel(): string
    {
        // Looks up AREA_LABELS, defaulting to the stored area string.
        return self::AREA_LABELS[$this->area] ?? $this->area;
    }
}
