<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\MockExam.
namespace App\Support;

/**
 * The composition of the timed mock examination, following the PRC weighting
 * of the Licensure Examination for Teachers.
 */
// A static helper class describing how the mock examination is put together.
class MockExam
{
    /**
     * How long a student is given to finish the mock examination.
     */
    // Default time limit in minutes; an administrator can override it via System Settings.
    public const TIME_LIMIT_MINUTES = 150;

    /**
     * Number of items drawn from each LET area for the given exam type.
     *
     * @return array<string, int>
     */
    // The exam "blueprint": maps each LET area to how many items the mock exam draws.
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
    // Sums the blueprint so the total is derived, never hard-coded.
    public static function totalItems(?string $examType): int
    {
        // array_sum adds up all the per-area item counts (100 for either track).
        return array_sum(self::blueprint($examType));
    }

    /**
     * The title of the mock examination for an exam type.
     */
    // Builds the title shown for the mock exam, naming the track when known.
    public static function title(?string $examType): string
    {
        // No track chosen: use the generic title.
        return $examType === null
            ? 'Mock Examination'
            // Otherwise append the track's readable label.
            : 'Mock Examination - '.LetTrack::examTypeLabel($examType);
    }
}
