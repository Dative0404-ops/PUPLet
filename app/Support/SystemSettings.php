<?php

// Lets Laravel find this class automatically.
namespace App\Support;

// Setting: the key/value store these settings live in.
use App\Models\Setting;

/**
 * The site-wide settings an administrator can change without touching the code.
 * A setting that was never saved falls back to the default declared here, while
 * one that was deliberately cleared is left blank.
 */
// Only static methods, so this class is never created; call SystemSettings::values().
class SystemSettings
{
    // Stored key for the site name (title bar and footer).
    public const KEY_NAME = 'site.name';

    // Stored key for the support email address.
    public const KEY_SUPPORT_EMAIL = 'site.support_email';

    // Stored key for the support telephone number.
    public const KEY_SUPPORT_PHONE = 'site.support_phone';

    // Stored key for the support desk opening hours.
    public const KEY_SUPPORT_HOURS = 'site.support_hours';

    // Stored key for the default daily item target for new accounts.
    public const KEY_DAILY_TARGET = 'defaults.daily_target_items';

    // Stored key for the default practice timer for new accounts.
    public const KEY_PRACTICE_TIMER = 'defaults.practice_timer';

    // Stored key for the mock examination's time limit.
    public const KEY_MOCK_TIME_LIMIT = 'mock.time_limit_minutes';

    // Stored key for the flag that opens or closes registration.
    public const KEY_REGISTRATION = 'registration.enabled';

    // Default support email, used when never saved.
    public const SUPPORT_EMAIL_DEFAULT = 'support@letreviewer.test';

    // Default support phone number, used when never saved.
    public const SUPPORT_PHONE_DEFAULT = '(02) 8123-4567';

    // Default support hours text, used when never saved.
    public const SUPPORT_HOURS_DEFAULT = 'Mon–Fri, 8:00 AM–5:00 PM';

    // Default daily item target for a new account.
    public const DAILY_TARGET_DEFAULT = 50;

    // Smallest allowed daily item target.
    public const DAILY_TARGET_MIN = 1;

    // Largest allowed daily item target.
    public const DAILY_TARGET_MAX = 999;

    // Default practice timer for a new account ("quiz" = follow each quiz's own limit).
    public const PRACTICE_TIMER_DEFAULT = 'quiz';

    // Default mock-exam time limit, taken from MockExam::TIME_LIMIT_MINUTES (150).
    public const MOCK_TIME_LIMIT_DEFAULT = MockExam::TIME_LIMIT_MINUTES;

    // Smallest allowed mock time limit.
    public const MOCK_TIME_LIMIT_MIN = 1;

    // Largest allowed mock time limit.
    public const MOCK_TIME_LIMIT_MAX = 600;

    /**
     * The name of the site, shown in the browser title and the footer.
     */
    public static function name(): string
    {
        // Falls back to config('app.name') when never saved.
        return (string) self::optionalText(self::KEY_NAME, config('app.name'));
    }

    /**
     * The address students write to for help.
     */
    public static function supportEmail(): string
    {
        return (string) self::optionalText(self::KEY_SUPPORT_EMAIL, self::SUPPORT_EMAIL_DEFAULT);
    }

    /**
     * The support telephone number, or null when it is not published.
     */
    public static function supportPhone(): ?string
    {
        // Null means the number is not published.
        return self::optionalText(self::KEY_SUPPORT_PHONE, self::SUPPORT_PHONE_DEFAULT);
    }

    /**
     * The hours the support desk is open, or null when they are not published.
     */
    public static function supportHours(): ?string
    {
        // Null means the hours are not published.
        return self::optionalText(self::KEY_SUPPORT_HOURS, self::SUPPORT_HOURS_DEFAULT);
    }

    /**
     * The daily item target a new account starts with.
     */
    public static function defaultDailyTargetItems(): int
    {
        return self::number(self::KEY_DAILY_TARGET, self::DAILY_TARGET_DEFAULT, self::DAILY_TARGET_MIN, self::DAILY_TARGET_MAX);
    }

    /**
     * The practice timer a new account starts with, in the vocabulary the
     * student's own preference uses.
     */
    public static function defaultPracticeTimer(): string
    {
        // The raw stored timer choice (null when never saved).
        $stored = Setting::get(self::KEY_PRACTICE_TIMER);

        // The choice keys come back as strings, but array_keys turns the
        // numeric ones into integers, so both sides are compared as text.
        $choices = array_map('strval', StudyPreferences::timeLimitChoices());

        // Use the stored value only when it is a known choice; else the default.
        return in_array((string) $stored, $choices, true)
            ? $stored
            : self::PRACTICE_TIMER_DEFAULT;
    }

    /**
     * The practice timer a new account starts with, as stored on the account.
     */
    public static function defaultPracticeTimeLimitMinutes(): ?int
    {
        // Turn the default choice into the minutes stored on the account.
        return StudyPreferences::timeLimitValue(self::defaultPracticeTimer());
    }

    /**
     * How long a student is given to finish the mock examination.
     */
    public static function mockTimeLimitMinutes(): int
    {
        return self::number(self::KEY_MOCK_TIME_LIMIT, self::MOCK_TIME_LIMIT_DEFAULT, self::MOCK_TIME_LIMIT_MIN, self::MOCK_TIME_LIMIT_MAX);
    }

    /**
     * Whether new students may still create an account.
     */
    public static function registrationEnabled(): bool
    {
        // The raw stored flag (null when never saved).
        $stored = Setting::get(self::KEY_REGISTRATION);

        // Never saved means open by default; otherwise read the text as a boolean.
        return $stored === null ? true : filter_var($stored, FILTER_VALIDATE_BOOL);
    }

    /**
     * The settings in force, ready to fill the settings form.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return [
            // The site name.
            'name' => self::name(),
            // The support email address.
            'support_email' => self::supportEmail(),
            // Support phone, empty string when null.
            'support_phone' => (string) self::supportPhone(),
            // Support hours, empty string when null.
            'support_hours' => (string) self::supportHours(),
            // The default daily item target, as a string.
            'default_daily_target_items' => (string) self::defaultDailyTargetItems(),
            // The default practice timer choice.
            'default_practice_timer' => self::defaultPracticeTimer(),
            // The mock time limit, as a string.
            'mock_time_limit_minutes' => (string) self::mockTimeLimitMinutes(),
            // A checkbox value: '1' or '0'.
            'registration_enabled' => self::registrationEnabled() ? '1' : '0',
        ];
    }

    /**
     * Save the settings submitted from the settings form.
     *
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data): void
    {
        // putMany saves several key/value pairs in one call.
        Setting::putMany([
            // Trim whitespace from the free-text settings.
            self::KEY_NAME => trim((string) $data['name']),
            self::KEY_SUPPORT_EMAIL => trim((string) $data['support_email']),
            // Empty input is allowed here, which clears the value.
            self::KEY_SUPPORT_PHONE => trim((string) ($data['support_phone'] ?? '')),
            // Empty input is allowed here too.
            self::KEY_SUPPORT_HOURS => trim((string) ($data['support_hours'] ?? '')),
            self::KEY_DAILY_TARGET => (string) $data['default_daily_target_items'],
            self::KEY_PRACTICE_TIMER => (string) $data['default_practice_timer'],
            self::KEY_MOCK_TIME_LIMIT => (string) $data['mock_time_limit_minutes'],
            // A checkbox value: '1' when on, '0' when off.
            self::KEY_REGISTRATION => $data['registration_enabled'] ? '1' : '0',
        ]);
    }

    /**
     * A stored piece of text. A setting that was never saved reads back as its
     * default; one that was cleared on purpose reads back as nothing.
     */
    private static function optionalText(string $key, string $default): ?string
    {
        // The stored value, or $default when the key is missing.
        $stored = Setting::get($key, $default);

        // Blank (after trimming) means "not published", so return null.
        return trim((string) $stored) === '' ? null : $stored;
    }

    /**
     * A stored whole number, clamped to the range the setting allows.
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
}
