<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\StudyPreferences.
namespace App\Support;

/**
 * The study preferences a student can set for themselves.
 */
// A static helper class holding the fixed preference vocabulary and conversions.
class StudyPreferences
{
    /**
     * Where a student can choose to land after signing in, keyed by the stored value.
     *
     * @var array<string, array{label: string, route: string}>
     */
    // Maps a stored landing-page key to its label and the Laravel route name to redirect to.
    public const LANDING_PAGES = [
        // Send the student to the student dashboard after signing in.
        'dashboard' => ['label' => 'Student dashboard', 'route' => 'dashboard'],
        // Send the student to the reviewer catalog after signing in.
        'review' => ['label' => 'Reviewer catalog', 'route' => 'review.index'],
        // Send the student to the study planner after signing in.
        'planner' => ['label' => 'Study planner', 'route' => 'account.planner'],
    ];

    /**
     * The practice timer choices, keyed by the submitted value.
     *
     * @var array<string, string>
     */
    // Maps a stored/submitted timer key to the text shown on the settings form.
    public const TIME_LIMIT_CHOICES = [
        // Defer to each quiz's own time limit.
        'quiz' => "Follow each quiz's own limit",
        // Practise without any timer.
        'untimed' => 'No timer',
        // Fixed practice limits in minutes, keyed by their value as text.
        '10' => '10 minutes',
        '15' => '15 minutes',
        '25' => '25 minutes',
        '40' => '40 minutes',
        '60' => '60 minutes',
    ];

    /**
     * The value stored for a practice timer choice. Null follows the quiz's own
     * limit, and zero means the student always practises untimed.
     */
    // Converts a submitted timer choice into the integer minutes to store.
    public static function timeLimitValue(string $choice): ?int
    {
        // match() compares the choice against each arm and returns the first match.
        return match ($choice) {
            // "untimed" is stored as 0 minutes.
            'untimed' => 0,
            // "quiz" is stored as null, meaning "use the quiz's limit".
            'quiz' => null,
            // Any other value is a minute count, so cast it to an integer.
            default => (int) $choice,
        };
    }

    /**
     * The choice that represents a stored practice timer value.
     */
    // Converts stored minutes back into a form choice key.
    public static function timeLimitChoice(?int $stored): string
    {
        // match(true) runs each arm as a boolean condition and returns the first true one.
        return match (true) {
            // Null stored value means the "quiz" choice.
            $stored === null => 'quiz',
            // Zero stored value means the "untimed" choice.
            $stored === 0 => 'untimed',
            // Any other number becomes its own string key (e.g. 25 => '25').
            default => (string) $stored,
        };
    }

    /**
     * A readable description of a stored practice timer value.
     */
    // Returns the display label for a stored timer value.
    public static function timeLimitLabel(?int $stored): string
    {
        // Convert to a choice key, then look up its readable label text.
        return self::TIME_LIMIT_CHOICES[self::timeLimitChoice($stored)];
    }

    /**
     * The route name of a stored landing page, falling back to the dashboard.
     */
    // Returns the route name for a stored landing page, defaulting to the dashboard.
    public static function landingRoute(?string $page): string
    {
        // Look up the route for the page key, or fall back to the dashboard route.
        return self::LANDING_PAGES[$page]['route'] ?? self::LANDING_PAGES['dashboard']['route'];
    }

    /**
     * A readable description of a stored landing page.
     */
    // Returns the label for a stored landing page, defaulting to the dashboard's.
    public static function landingLabel(?string $page): string
    {
        // Look up the label for the page key, or fall back to the dashboard label.
        return self::LANDING_PAGES[$page]['label'] ?? self::LANDING_PAGES['dashboard']['label'];
    }

    /**
     * The valid landing page keys, for validation rules.
     *
     * @return list<string>
     */
    // Returns the list of valid landing-page keys for form validation.
    public static function landingPages(): array
    {
        // The array keys of LANDING_PAGES are the valid choices.
        return array_keys(self::LANDING_PAGES);
    }

    /**
     * The valid practice timer keys, for validation rules.
     *
     * @return list<string>
     */
    // Returns the list of valid timer-choice keys for form validation.
    public static function timeLimitChoices(): array
    {
        // The array keys of TIME_LIMIT_CHOICES are the valid choices.
        return array_keys(self::TIME_LIMIT_CHOICES);
    }
}
