<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;
// HasMany: used by topics().
use Illuminate\Database\Eloquent\Relations\HasMany;

// A LET subject (an area of the examination).
class Subject extends Model
{
    /**
     * The three areas of the Licensure Examination for Teachers.
     *
     * @var array<string, string>
     */
    // Stored area key => label shown to students and admins.
    public const AREA_LABELS = [
        'gen_ed' => 'General Education',
        'prof_ed' => 'Professional Education',
        'specialization' => 'Specialization',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set. area is one of the AREA_LABELS keys above.
    protected $fillable = [
        'area',
        'name',
        'sort_order',
    ];

    /**
     * The topics covered under this subject.
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    /**
     * The readable label of the area this subject belongs to.
     */
    public function areaLabel(): string
    {
        // Look up AREA_LABELS, falling back to the stored area string.
        return self::AREA_LABELS[$this->area] ?? $this->area;
    }
}
