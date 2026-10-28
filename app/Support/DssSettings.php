<?php

// Lets Laravel find this class automatically.
namespace App\Support;

// Setting: the key/value store these settings live in.
use App\Models\Setting;

/**
 * The rules behind the decision support recommendations a student is given.
 * The administrator sets them from the DSS Settings screen.
 */
// Only static methods, so this class is never created; call DssSettings::all().
class DssSettings
{
    /**
     * Answered items required before a topic is scored.
     */
    // The stored key for the minimum-items rule.
    public const KEY_MIN_ITEMS = 'dss.min_items';

    /**
     * How many topics the recommendation list holds.
     */
    // The stored key for the list-size rule.
    public const KEY_LIMIT = 'dss.limit';

    /**
     * The accuracy below which a topic counts as a weakness.
     */
    // The stored key for the weak-threshold rule.
    public const KEY_WEAK_THRESHOLD = 'dss.weak_threshold';

    /**
     * Whether topics the student has never practised are recommended too.
     */
    // The stored key for the include-untouched flag.
    public const KEY_INCLUDE_UNTOUCHED = 'dss.include_untouched';

    // Default minimum answered items, used when nothing has been saved.
    public const MIN_ITEMS_DEFAULT = 5;

    // Default number of recommendations, used when nothing has been saved.
    public const LIMIT_DEFAULT = 6;

    // Default weak-accuracy percentage, used when nothing has been saved.
    public const WEAK_THRESHOLD_DEFAULT = 70;

    // Smallest allowed minimum-items value.
    public const MIN_ITEMS_MIN = 1;

    // Largest allowed minimum-items value.
    public const MIN_ITEMS_MAX = 50;

    // Smallest allowed recommendation-list size.
    public const LIMIT_MIN = 1;

    // Largest allowed recommendation-list size.
    public const LIMIT_MAX = 12;

    // Smallest allowed weak-threshold percentage.
    public const THRESHOLD_MIN = 1;

    // Largest allowed weak-threshold percentage.
    public const THRESHOLD_MAX = 100;

    /**
     * Answered items required before a topic is scored.
     */
    public static function minItems(): int
    {
        return self::number(self::KEY_MIN_ITEMS, self::MIN_ITEMS_DEFAULT, self::MIN_ITEMS_MIN, self::MIN_ITEMS_MAX);
    }

    /**
     * How many topics the recommendation list holds.
     */
    public static function limit(): int
    {
        return self::number(self::KEY_LIMIT, self::LIMIT_DEFAULT, self::LIMIT_MIN, self::LIMIT_MAX);
    }

    /**
     * The accuracy below which a topic counts as a weakness.
     */
    public static function weakThreshold(): int
    {
        return self::number(self::KEY_WEAK_THRESHOLD, self::WEAK_THRESHOLD_DEFAULT, self::THRESHOLD_MIN, self::THRESHOLD_MAX);
    }

    /**
     * Whether topics the student has never practised are recommended too.
     */
    public static function includeUntouched(): bool
    {
        // Defaults to true when the admin has never saved it.
        return self::flag(self::KEY_INCLUDE_UNTOUCHED, true);
    }

    /**
     * The rules in force, for the pages that explain them.
     *
     * @return array{min_items: int, limit: int, weak_threshold: int, include_untouched: bool}
     */
    public static function all(): array
    {
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
    public static function values(): array
    {
        // Read the typed rules once, then convert them to strings below.
        $rules = self::all();

        return [
            'min_items' => (string) $rules['min_items'],
            'limit' => (string) $rules['limit'],
            'weak_threshold' => (string) $rules['weak_threshold'],
            // A checkbox value: '1' or '0'.
            'include_untouched' => $rules['include_untouched'] ? '1' : '0',
        ];
    }

    /**
     * Save the rules submitted from the settings form.
     *
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data): void
    {
        // putMany saves several key/value pairs in one call.
        Setting::putMany([
            self::KEY_MIN_ITEMS => (string) $data['min_items'],
            self::KEY_LIMIT => (string) $data['limit'],
            self::KEY_WEAK_THRESHOLD => (string) $data['weak_threshold'],
            // A checkbox value: '1' when checked, '0' otherwise.
            self::KEY_INCLUDE_UNTOUCHED => $data['include_untouched'] ? '1' : '0',
        ]);
    }

    /**
     * A stored whole number, clamped to the range the rule allows.
     */
    private static function number(string $key, int $default, int $min, int $max): int
    {
        // The raw stored value (null when never saved).
        $stored = Setting::get($key);

        // A missing or non-numeric value falls back to the default.
        if ($stored === null || ! is_numeric($stored)) {
            return $default;
        }

        // Cast to int, then clamp it between $min and $max.
        return max($min, min($max, (int) $stored));
    }

    /**
     * A stored yes or no.
     */
    private static function flag(string $key, bool $default): bool
    {
        // The raw stored value (null when never saved).
        $stored = Setting::get($key);

        // Unset means use the default; otherwise read the text as a boolean
        // ("1", "true", "yes", "on" are true; "0", "false" etc. are false).
        return $stored === null ? $default : filter_var($stored, FILTER_VALIDATE_BOOL);
    }
}
