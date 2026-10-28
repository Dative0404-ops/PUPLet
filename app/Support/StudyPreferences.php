<?php

// Lets Laravel find this class automatically.
namespace App\Support;

/**
 * The study preferences a student can set for themselves.
 */
// Only static methods, so this class is never created.
class StudyPreferences
{
    /**
     * Where a student can choose to land after signing in, keyed by the stored value.
     *
     * @var array<string, array{label: string, route: string}>
     */
    // Maps a stored landing-page key to its label and the route name to redirect to.
    public const LANDING_PAGES = [
        // The student dashboard.
        'dashboard' => ['label' => 'Student dashboard', 'route' => 'dashboard'],
        // The reviewer catalog.
        'review' => ['label' => 'Reviewer catalog', 'route' => 'review.index'],
        // The study planner.
        'planner' => ['label' => 'Study planner', 'route' => 'account.planner'],
    ];

    /**
     * The practice timer choices, keyed by the submitted value.
     *
     * @var array<string, string>
     */
    // Maps a stored timer key to the text shown on the settings form.
    public const TIME_LIMIT_CHOICES = [
        // Follow each quiz's own time limit.
        'quiz' => "Follow each quiz's own limit",
        // Practise with no timer.
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
    public static function timeLimitValue(string $choice): ?int
    {
        // match() returns the value of the first arm that fits.
        return match ($choice) {
            // "untimed" is stored as 0 minutes.
            'untimed' => 0,
            // "quiz" is stored as null, meaning "use the quiz's limit".
            'quiz' => null,
            // Anything else is a minute count.
            default => (int) $choice,
        };
    }

    /**
     * The choice that represents a stored practice timer value.
     */
    public static function timeLimitChoice(?int $stored): string
    {
        // match(true) treats each arm as a condition and returns the first true one.
        return match (true) {
            // Null means the "quiz" choice.
            $stored === null => 'quiz',
            // Zero means the "untimed" choice.
            $stored === 0 => 'untimed',
            // Any other number becomes its own string key (e.g. 25 => '25').
            default => (string) $stored,
        };
    }

    /**
     * A readable description of a stored practice timer value.
     */
    public static function timeLimitLabel(?int $stored): string
    {
        // Turn the value into a choice key, then look up its label.
        return self::TIME_LIMIT_CHOICES[self::timeLimitChoice($stored)];
    }

    /**
     * The route name of a stored landing page, falling back to the dashboard.
     */
    public static function landingRoute(?string $page): string
    {
        // An unknown page falls back to the dashboard route.
        return self::LANDING_PAGES[$page]['route'] ?? self::LANDING_PAGES['dashboard']['route'];
    }

    /**
     * A readable description of a stored landing page.
     */
    public static function landingLabel(?string $page): string
    {
        // An unknown page falls back to the dashboard label.
        return self::LANDING_PAGES[$page]['label'] ?? self::LANDING_PAGES['dashboard']['label'];
    }

    /**
     * The valid landing page keys, for validation rules.
     *
     * @return list<string>
     */
    public static function landingPages(): array
    {
        // The keys of LANDING_PAGES are the valid choices.
        return array_keys(self::LANDING_PAGES);
    }

    /**
     * The valid practice timer keys, for validation rules.
     *
     * @return list<string>
     */
    public static function timeLimitChoices(): array
    {
        // The keys of TIME_LIMIT_CHOICES are the valid choices.
        return array_keys(self::TIME_LIMIT_CHOICES);
    }
}
