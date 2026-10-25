<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\LetTrack.
namespace App\Support;

/**
 * The exam types and specialization areas of the Licensure Examination for Teachers.
 */
// A static helper class holding the fixed LET track vocabulary and lookups.
class LetTrack
{
    /**
     * The available LET exam types.
     *
     * @var array<string, string>
     */
    // Maps the stored exam-type key to its human-readable label used in the UI.
    public const EXAM_TYPES = [
        // BEED: the elementary track.
        'elementary' => 'Elementary (BEED)',
        // BSED: the secondary track.
        'secondary' => 'Secondary (BSED)',
    ];

    /**
     * The content areas and majors available per exam type.
     *
     * @var array<string, list<string>>
     */
    // Maps each exam type to the list of specialization majors a student may pick.
    public const SPECIALIZATIONS = [
        // Majors offered under the elementary (BEED) track.
        'elementary' => [
            // Elementary majors, in school standard order.
            'English',
            'Filipino',
            'Mathematics',
            'Science',
            'Social Studies / Araling Panlipunan',
            'MAPEH',
            'EPP / TLE',
            'Values Education',
        ],
        // Majors offered under the secondary (BSED) track.
        'secondary' => [
            // Secondary majors, in school standard order.
            'English',
            'Filipino',
            'Mathematics',
            'Science',
            'Social Studies',
            'MAPEH',
            'TLE',
            'Values Education',
        ],
    ];

    /**
     * The specialization options for an exam type.
     *
     * @return list<string>
     */
    // Returns the majors for a track, or an empty list when the track is unknown.
    public static function specializations(?string $examType): array
    {
        // Look up the track's majors, defaulting to an empty array.
        return self::SPECIALIZATIONS[$examType] ?? [];
    }

    /**
     * The readable label of an exam type.
     */
    // Returns the friendly name of a track, or a placeholder when none is set.
    public static function examTypeLabel(?string $examType): string
    {
        // Look up the label, defaulting to a friendly "not selected" message.
        return self::EXAM_TYPES[$examType] ?? 'No track selected';
    }

    /**
     * A one-line description of a student's track, for example "Secondary (BSED) - English".
     */
    // Combines track label and major into one description string.
    public static function label(?string $examType, ?string $specialization): string
    {
        // No exam type at all means the student has not chosen a track.
        if ($examType === null) {
            return 'No track selected';
        }

        // With a major, join the two with a dash; otherwise show the track alone.
        return $specialization
            ? self::examTypeLabel($examType).' - '.$specialization
            : self::examTypeLabel($examType);
    }

    /**
     * Whether the given exam type includes a specialization test.
     */
    // True only for the secondary (BSED) track, which has a specialization area.
    public static function hasSpecializationTest(?string $examType): bool
    {
        // The exam type includes a specialization test when it is "secondary".
        return $examType === 'secondary';
    }
}
