<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\DssSettings.
namespace App\Support;

// Imports the Setting model, the key/value store these settings live in.
use App\Models\Setting;

/**
 * The rules behind the decision support recommendations a student is given.
 * The administrator sets them from the DSS Settings screen.
 */
// A static helper class wrapping the DSS settings with defaults and validation.
class DssSettings
{
    /**
     * Answered items required before a topic is scored.
     */
    // The stored key under which the minimum-items rule is saved.
    public const KEY_MIN_ITEMS = 'dss.min_items';

    /**
     * How many topics the recommendation list holds.
     */
    // The stored key under which the list-size rule is saved.
    public const KEY_LIMIT = 'dss.limit';

    /**
     * The accuracy below which a topic counts as a weakness.
     */
    // The stored key under which the weak-threshold rule is saved.
    public const KEY_WEAK_THRESHOLD = 'dss.weak_threshold';

    /**
     * Whether topics the student has never practised are recommended too.
     */
    // The stored key under which the include-untouched flag is saved.
    public const KEY_INCLUDE_UNTOUCHED = 'dss.include_untouched';

    // Default minimum answered items used when nothing has been saved.
    public const MIN_ITEMS_DEFAULT = 5;

    // Default number of recommendations shown when nothing has been saved.
    public const LIMIT_DEFAULT = 6;

    // Default weak-accuracy percentage used when nothing has been saved.
    public const WEAK_THRESHOLD_DEFAULT = 70;

    // Smallest value the minimum-items rule may take.
    public const MIN_ITEMS_MIN = 1;

    // Largest value the minimum-items rule may take.
    public const MIN_ITEMS_MAX = 50;

    // Smallest value the recommendation-list limit may take.
    public const LIMIT_MIN = 1;

    // Largest value the recommendation-list limit may take.
    public const LIMIT_MAX = 12;

    // Smallest value the weak-threshold percentage may take.
    public const THRESHOLD_MIN = 1;

    // Largest value the weak-threshold percentage may take.
    public const THRESHOLD_MAX = 100;

    /**
     * Answered items required before a topic is scored.
     */
    // Reads the minimum-items rule, falling back to and clamped by the constants.
    public static function minItems(): int
    {
        // Delegate to number() with this rule's key, default and allowed range.
        return self::number(self::KEY_MIN_ITEMS, self::MIN_ITEMS_DEFAULT, self::MIN_ITEMS_MIN, self::MIN_ITEMS_MAX);
    }

    /**
     * How many topics the recommendation list holds.
     */
    // Reads the recommendation-list size, clamped to its allowed range.
    public static function limit(): int
    {
        // Delegate to number() with this rule's key, default and allowed range.
        return self::number(self::KEY_LIMIT, self::LIMIT_DEFAULT, self::LIMIT_MIN, self::LIMIT_MAX);
    }

    /**
     * The accuracy below which a topic counts as a weakness.
     */
    // Reads the weak-accuracy threshold, clamped to its allowed range.
    public static function weakThreshold(): int
    {
        // Delegate to number() with this rule's key, default and allowed range.
        return self::number(self::KEY_WEAK_THRESHOLD, self::WEAK_THRESHOLD_DEFAULT, self::THRESHOLD_MIN, self::THRESHOLD_MAX);
    }

    /**
     * Whether topics the student has never practised are recommended too.
     */
    // Reads the include-untouched flag, defaulting to true.
    public static function includeUntouched(): bool
    {
        // Delegate to flag() with this rule's key and the default of true.
        return self::flag(self::KEY_INCLUDE_UNTOUCHED, true);
    }

    /**
     * The rules in force, for the pages that explain them.
     *
     * @return array{min_items: int, limit: int, weak_threshold: int, include_untouched: bool}
     */
    // Returns all four rules at once, using their proper PHP types.
    public static function all(): array
    {
        // Return each rule under a readable, snake_case key.
        return [
            // Minimum answered items before a topic is scored.
            'min_items' => self::minItems(),
            // Maximum number of recommendations shown.
            'limit' => self::limit(),
            // Accuracy below which a topic counts as weak.
            'weak_threshold' => self::weakThreshold(),
            // Whether untouched topics are recommended too.
            'include_untouched' => self::includeUntouched(),
        ];
    }

    /**
     * The stored rules, ready to fill the settings form.
     *
     * @return array<string, string>
     */
    // Returns the rules as strings, the format HTML form fields expect.
    public static function values(): array
    {
        // Pull the typed rules once, then convert them below.
        $rules = self::all();

        // Return each rule rendered as a string.
        return [
            // Minimum items as a string.
            'min_items' => (string) $rules['min_items'],
            // Limit as a string.
            'limit' => (string) $rules['limit'],
            // Weak threshold as a string.
            'weak_threshold' => (string) $rules['weak_threshold'],
            // Boolean flag as '1' or '0' for a checkbox value.
            'include_untouched' => $rules['include_untouched'] ? '1' : '0',
        ];
    }

    /**
     * Save the rules submitted from the settings form.
     *
     * @param  array<string, mixed>  $data
     */
    // Persists the four submitted rules into the settings store.
    public static function save(array $data): void
    {
        // putMany stores several key/value pairs in one call.
        Setting::putMany([
            // Minimum items, cast to string for the key/value store.
            self::KEY_MIN_ITEMS => (string) $data['min_items'],
            // Limit, cast to string.
            self::KEY_LIMIT => (string) $data['limit'],
            // Weak threshold, cast to string.
            self::KEY_WEAK_THRESHOLD => (string) $data['weak_threshold'],
            // Flag stored as '1' when checked, '0' otherwise.
            self::KEY_INCLUDE_UNTOUCHED => $data['include_untouched'] ? '1' : '0',
        ]);
    }

    /**
     * A stored whole number, clamped to the range the rule allows.
     */
    // Reads a numeric rule and keeps it inside the allowed min/max range.
    private static function number(string $key, int $default, int $min, int $max): int
    {
        // Read the raw stored value for this key (null when never saved).
        $stored = Setting::get($key);

        // Missing or non-numeric values fall back to the declared default.
        if ($stored === null || ! is_numeric($stored)) {
            return $default;
        }

        // Cast to int, then clamp so it never goes below $min or above $max.
        return max($min, min($max, (int) $stored));
    }

    /**
     * A stored yes or no.
     */
    // Reads a boolean flag, returning the default when it was never saved.
    private static function flag(string $key, bool $default): bool
    {
        // Read the raw stored value for this key (null when never saved).
        $stored = Setting::get($key);

        // Use the default when unset; otherwise interpret the text as a boolean
        // ("1", "true", "yes", "on" are truthy; "0", "false" etc. are falsy).
        return $stored === null ? $default : filter_var($stored, FILTER_VALIDATE_BOOL);
    }
}
