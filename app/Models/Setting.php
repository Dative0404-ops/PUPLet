<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// Imports the base Eloquent Model class that this model extends.
use Illuminate\Database\Eloquent\Model;

/**
 * A site-wide setting, stored as a key and value pair. Settings without a row
 * fall back to the default the code declares for them.
 */
// Eloquent model backing the key/value settings store used across the app.
class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist: the pair of columns the table holds.
    protected $fillable = [
        // The unique name that identifies the setting.
        'key',
        // The stored value; null means "no value set" and callers use their default.
        'value',
    ];

    /**
     * The primary key of a setting is its key.
     */
    // Overrides Eloquent's default "id" primary key with the key column.
    protected $primaryKey = 'key';

    /**
     * Keys are strings, not auto-incrementing integers.
     */
    // Tells Eloquent not to expect an auto-incrementing numeric id.
    public $incrementing = false;

    /**
     * The key type.
     */
    // Declares the primary key is a string so Eloquent binds it correctly.
    protected $keyType = 'string';

    /**
     * The stored value of one setting.
     */
    // Static helper reading a single setting with an optional fallback.
    public static function get(string $key, ?string $default = null): ?string
    {
        // Queries one row's value column, falling back when the row is missing.
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * The stored values of several settings, in a single query.
     *
     * @param  list<string>  $keys
     * @return array<string, string|null>
     */
    // Static helper reading many settings efficiently to avoid N+1 queries.
    public static function many(array $keys): array
    {
        // Fetches all matching rows at once, keyed by the key column for quick lookup.
        $stored = static::query()->whereIn('key', $keys)->pluck('value', 'key');

        // Prepares the result array in the caller's requested order.
        $values = [];

        // Walks the requested keys so every one appears in the result.
        foreach ($keys as $key) {
            // Stores the found value, or null when no row exists for that key.
            $values[$key] = $stored->get($key);
        }

        // Returns the key => value map.
        return $values;
    }

    /**
     * Store a value for one setting.
     */
    // Static helper writing a single setting, creating the row if needed.
    public static function put(string $key, ?string $value): void
    {
        // Updates the existing row or inserts a new one, matching on the key.
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Store the values of several settings at once.
     *
     * @param  array<string, string|null>  $values
     */
    // Static helper writing a batch of settings.
    public static function putMany(array $values): void
    {
        // Iterates the key => value pairs and persists each one.
        foreach ($values as $key => $value) {
            // Delegates to put() to reuse the upsert logic.
            static::put($key, $value);
        }
    }
}
