<?php

// Lets Laravel find this class automatically.
namespace App\Support;

/**
 * The exam types and specialization areas of the Licensure Examination for Teachers.
 */
// Only static methods, so this class is never created.
class LetTrack
{
    /**
     * The available LET exam types.
     *
     * @var array<string, string>
     */
    // Maps the stored exam-type key to the label shown in the UI.
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
    // Maps each exam type to the majors a student may pick.
    public const SPECIALIZATIONS = [
        // Majors under the elementary (BEED) track, in school order.
        'elementary' => [
            'English',
            'Filipino',
            'Mathematics',
            'Science',
            'Social Studies / Araling Panlipunan',
            'MAPEH',
            'EPP / TLE',
            'Values Education',
        ],
        // Majors under the secondary (BSED) track, in school order.
        'secondary' => [
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
    public static function specializations(?string $examType): array
    {
        // An unknown track has no majors.
        return self::SPECIALIZATIONS[$examType] ?? [];
    }

    /**
     * The readable label of an exam type.
     */
    public static function examTypeLabel(?string $examType): string
    {
        // Falls back to a friendly "not selected" message.
        return self::EXAM_TYPES[$examType] ?? 'No track selected';
    }

    /**
     * A one-line description of a student's track, for example "Secondary (BSED) - English".
     */
    public static function label(?string $examType, ?string $specialization): string
    {
        // No exam type means the student has not chosen a track.
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
    public static function hasSpecializationTest(?string $examType): bool
    {
        // Only the secondary (BSED) track has a specialization area.
        return $examType === 'secondary';
    }
}
