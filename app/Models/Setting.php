<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// The base Eloquent model class.
use Illuminate\Database\Eloquent\Model;

/**
 * A site-wide setting, stored as a key and value pair. A setting with no row
 * falls back to the default the code declares for it.
 */
class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // The only two columns: key names the setting, value holds it.
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * The primary key of a setting is its key.
     */
    protected $primaryKey = 'key';

    /**
     * Keys are strings, not auto-incrementing integers.
     */
    public $incrementing = false;

    /**
     * The key type.
     */
    protected $keyType = 'string';

    /**
     * The stored value of one setting.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        // Read the value column for this key; use $default when no row exists.
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * The stored values of several settings, in a single query.
     *
     * @param  list<string>  $keys
     * @return array<string, string|null>
     */
    public static function many(array $keys): array
    {
        // Read all matching rows at once, keyed by the key column.
        $stored = static::query()->whereIn('key', $keys)->pluck('value', 'key');

        $values = [];

        // Walk the requested keys so every one appears in the result, even if missing.
        foreach ($keys as $key) {
            $values[$key] = $stored->get($key);
        }

        return $values;
    }

    /**
     * Store a value for one setting.
     */
    public static function put(string $key, ?string $value): void
    {
        // Update the existing row, or insert one when the key is new.
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Store the values of several settings at once.
     *
     * @param  array<string, string|null>  $values
     */
    public static function putMany(array $values): void
    {
        // Save each key => value pair through put().
        foreach ($values as $key => $value) {
            static::put($key, $value);
        }
    }
}
