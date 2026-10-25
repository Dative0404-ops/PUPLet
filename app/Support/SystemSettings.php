<?php

// Declares the PHP namespace so Laravel autoloads this class as App\Support\SystemSettings.
namespace App\Support;

// Imports the Setting model, the key/value store these settings live in.
use App\Models\Setting;

/**
 * The site-wide settings an administrator can change without touching the code.
 * A setting that was never saved falls back to the default declared here, while
 * one that was deliberately cleared is left blank.
 */
// A static helper class wrapping the site settings with defaults and validation.
class SystemSettings
{
    // Stored key for the site name shown in the title bar and footer.
    public const KEY_NAME = 'site.name';

    // Stored key for the support email address.
    public const KEY_SUPPORT_EMAIL = 'site.support_email';

    // Stored key for the support telephone number.
    public const KEY_SUPPORT_PHONE = 'site.support_phone';

    // Stored key for the support desk opening hours.
    public const KEY_SUPPORT_HOURS = 'site.support_hours';

    // Stored key for the default daily item target given to new accounts.
    public const KEY_DAILY_TARGET = 'defaults.daily_target_items';

    // Stored key for the default practice timer given to new accounts.
    public const KEY_PRACTICE_TIMER = 'defaults.practice_timer';

    // Stored key for the mock examination's time limit.
    public const KEY_MOCK_TIME_LIMIT = 'mock.time_limit_minutes';

    // Stored key for the flag that controls whether registration is open.
    public const KEY_REGISTRATION = 'registration.enabled';

    // Default support email used when the setting has never been saved.
    public const SUPPORT_EMAIL_DEFAULT = 'support@letreviewer.test';

    // Default support phone number used when the setting has never been saved.
    public const SUPPORT_PHONE_DEFAULT = '(02) 8123-4567';

    // Default support hours text used when the setting has never been saved.
    public const SUPPORT_HOURS_DEFAULT = 'Mon–Fri, 8:00 AM–5:00 PM';

    // Default daily item target for a new account.
    public const DAILY_TARGET_DEFAULT = 50;

    // Smallest value the daily item target may take.
    public const DAILY_TARGET_MIN = 1;

    // Largest value the daily item target may take.
    public const DAILY_TARGET_MAX = 999;

    // Default practice timer for a new account ("quiz" = follow each quiz's own limit).
    public const PRACTICE_TIMER_DEFAULT = 'quiz';

    // Default mock-exam time limit, taken from MockExam::TIME_LIMIT_MINUTES (150).
    public const MOCK_TIME_LIMIT_DEFAULT = MockExam::TIME_LIMIT_MINUTES;

    // Smallest value the mock time limit may take.
    public const MOCK_TIME_LIMIT_MIN = 1;

    // Largest value the mock time limit may take.
    public const MOCK_TIME_LIMIT_MAX = 600;

    /**
     * The name of the site, shown in the browser title and the footer.
     */
    // Returns the site name, falling back to config('app.name') when unset.
    public static function name(): string
    {
        // Read the stored name (or the app config default), cast to string.
        return (string) self::optionalText(self::KEY_NAME, config('app.name'));
    }

    /**
     * The address students write to for help.
     */
    // Returns the support email, defaulting to SUPPORT_EMAIL_DEFAULT.
    public static function supportEmail(): string
    {
        // Read the stored email (or the default), cast to string.
        return (string) self::optionalText(self::KEY_SUPPORT_EMAIL, self::SUPPORT_EMAIL_DEFAULT);
    }

    /**
     * The support telephone number, or null when it is not published.
     */
    // Returns the support phone, or null when it has been cleared.
    public static function supportPhone(): ?string
    {
        // Read the stored phone (or the default); null means it is not published.
        return self::optionalText(self::KEY_SUPPORT_PHONE, self::SUPPORT_PHONE_DEFAULT);
    }

    /**
     * The hours the support desk is open, or null when they are not published.
     */
    // Returns the support hours, or null when they have been cleared.
    public static function supportHours(): ?string
    {
        // Read the stored hours (or the default); null means not published.
        return self::optionalText(self::KEY_SUPPORT_HOURS, self::SUPPORT_HOURS_DEFAULT);
    }

    /**
     * The daily item target a new account starts with.
     */
    // Returns the default daily item target, clamped to its allowed range.
    public static function defaultDailyTargetItems(): int
    {
        // Delegate to number() with the key, default and allowed min/max.
        return self::number(self::KEY_DAILY_TARGET, self::DAILY_TARGET_DEFAULT, self::DAILY_TARGET_MIN, self::DAILY_TARGET_MAX);
    }

    /**
     * The practice timer a new account starts with, in the vocabulary the
     * student's own preference uses.
     */
    // Returns the default practice timer choice, validated against the known choices.
    public static function defaultPracticeTimer(): string
    {
        // Read the raw stored timer choice (null when never saved).
        $stored = Setting::get(self::KEY_PRACTICE_TIMER);

        // The choice keys are read back as strings, while the numeric ones are
        // cast to integers by array_keys, so both sides are compared as text.
        $choices = array_map('strval', StudyPreferences::timeLimitChoices());

        // Use the stored value only when it is a recognised choice; else the default.
        return in_array((string) $stored, $choices, true)
            ? $stored
            : self::PRACTICE_TIMER_DEFAULT;
    }

    /**
     * The practice timer a new account starts with, as stored on the account.
     */
    // Returns the default timer converted to the minutes stored on the account.
    public static function defaultPracticeTimeLimitMinutes(): ?int
    {
        // Convert the default choice into its stored minutes value.
        return StudyPreferences::timeLimitValue(self::defaultPracticeTimer());
    }

    /**
     * How long a student is given to finish the mock examination.
     */
    // Returns the mock time limit in minutes, clamped to its allowed range.
    public static function mockTimeLimitMinutes(): int
    {
        // Delegate to number() with the key, default and allowed min/max.
        return self::number(self::KEY_MOCK_TIME_LIMIT, self::MOCK_TIME_LIMIT_DEFAULT, self::MOCK_TIME_LIMIT_MIN, self::MOCK_TIME_LIMIT_MAX);
    }

    /**
     * Whether new students may still create an account.
     */
    // Returns true when registration is open; defaults to open when never saved.
    public static function registrationEnabled(): bool
    {
        // Read the raw stored flag (null when never saved).
        $stored = Setting::get(self::KEY_REGISTRATION);

        // Unset means open by default; otherwise interpret the text as a boolean.
        return $stored === null ? true : filter_var($stored, FILTER_VALIDATE_BOOL);
    }

    /**
     * The settings in force, ready to fill the settings form.
     *
     * @return array<string, string>
     */
    // Returns every setting as a string, the format HTML form fields expect.
    public static function values(): array
    {
        // Return each setting under its form-field name.
        return [
            // The site name.
            'name' => self::name(),
            // The support email address.
            'support_email' => self::supportEmail(),
            // The support phone, cast to an empty string when null.
            'support_phone' => (string) self::supportPhone(),
            // The support hours, cast to an empty string when null.
            'support_hours' => (string) self::supportHours(),
            // The default daily item target, as a string.
            'default_daily_target_items' => (string) self::defaultDailyTargetItems(),
            // The default practice timer choice.
            'default_practice_timer' => self::defaultPracticeTimer(),
            // The mock time limit, as a string.
            'mock_time_limit_minutes' => (string) self::mockTimeLimitMinutes(),
            // The registration flag as '1' or '0' for a checkbox value.
            'registration_enabled' => self::registrationEnabled() ? '1' : '0',
        ];
    }

    /**
     * Save the settings submitted from the settings form.
     *
     * @param  array<string, mixed>  $data
     */
    // Persists the submitted settings into the key/value store.
    public static function save(array $data): void
    {
        // putMany stores several key/value pairs in one call.
        Setting::putMany([
            // Site name, trimmed of surrounding whitespace.
            self::KEY_NAME => trim((string) $data['name']),
            // Support email, trimmed.
            self::KEY_SUPPORT_EMAIL => trim((string) $data['support_email']),
            // Support phone, trimmed; empty input is allowed (clears the value).
            self::KEY_SUPPORT_PHONE => trim((string) ($data['support_phone'] ?? '')),
            // Support hours, trimmed; empty input is allowed.
            self::KEY_SUPPORT_HOURS => trim((string) ($data['support_hours'] ?? '')),
            // Daily item target, cast to a string.
            self::KEY_DAILY_TARGET => (string) $data['default_daily_target_items'],
            // Default practice timer choice.
            self::KEY_PRACTICE_TIMER => (string) $data['default_practice_timer'],
            // Mock time limit, cast to a string.
            self::KEY_MOCK_TIME_LIMIT => (string) $data['mock_time_limit_minutes'],
            // Registration flag stored as '1' when on, '0' when off.
            self::KEY_REGISTRATION => $data['registration_enabled'] ? '1' : '0',
        ]);
    }

    /**
     * A stored piece of text. A setting that was never saved reads back as its
     * default; one that was cleared on purpose reads back as nothing.
     */
    // Reads a text setting, treating blank text as "not published" (null).
    private static function optionalText(string $key, string $default): ?string
    {
        // Read the stored value, falling back to $default when the key is absent.
        $stored = Setting::get($key, $default);

        // Return null when the text is blank after trimming; otherwise the text.
        return trim((string) $stored) === '' ? null : $stored;
    }

    /**
     * A stored whole number, clamped to the range the setting allows.
     */
    // Reads a numeric setting and keeps it inside the allowed min/max range.
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
}
