<?php

// Lets Laravel find this class automatically.
namespace App\Support;

/**
 * The composition of the timed mock examination, following the PRC weighting
 * of the Licensure Examination for Teachers.
 */
// Only static methods, so this class is never created.
class MockExam
{
    /**
     * How long a student is given to finish the mock examination.
     */
    // Default time limit in minutes; an administrator can override it in System Settings.
    public const TIME_LIMIT_MINUTES = 150;

    /**
     * Number of items drawn from each LET area for the given exam type.
     *
     * @return array<string, int>
     */
    // The exam blueprint: how many items the mock exam draws from each LET area.
    public static function blueprint(?string $examType): array
    {
        // Elementary (BEED): 40% General Education and 60% Professional Education.
        // Secondary (BSED): 20% General Education, 40% Professional Education and 40% Specialisation.
        return $examType === 'elementary'
            // BEED weighting: 40 gen_ed + 60 prof_ed = 100 items.
            ? ['gen_ed' => 40, 'prof_ed' => 60]
            // BSED weighting (and the default): 20 + 40 + 40 = 100 items.
            : ['gen_ed' => 20, 'prof_ed' => 40, 'specialization' => 40];
    }

    /**
     * Total number of items in the mock examination.
     */
    // Adds up the blueprint, so the total is never hard-coded.
    public static function totalItems(?string $examType): int
    {
        return array_sum(self::blueprint($examType));
    }

    /**
     * The title of the mock examination for an exam type.
     */
    public static function title(?string $examType): string
    {
        // No track chosen: use the generic title...
        return $examType === null
            ? 'Mock Examination'
            // ...otherwise append the track's label.
            : 'Mock Examination - '.LetTrack::examTypeLabel($examType);
    }
}
