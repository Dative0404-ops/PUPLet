<?php

// Factories live in the Database\Factories namespace so Laravel can autoload them.
namespace Database\Factories;

// The User model this factory builds instances of.
use App\Models\User;
// The base Factory class provides fake(), state(), and create() behaviour.
use Illuminate\Database\Eloquent\Factories\Factory;
// The Hash facade hashes passwords so stored values are never plain text.
use Illuminate\Support\Facades\Hash;
// Str provides helpers such as random string generation.
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
// A factory that creates realistic fake User rows for tests and seed data.
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    // Static cache so the (expensive) password hash is computed only once per run.
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // Returns the default set of column values for a new fake user.
    public function definition(): array
    {
        // Each key is a users-table column; each value is generated fake data.
        return [
            // A random full name for the user.
            'name' => fake()->name(),
            // A random email; unique() prevents duplicates across generated rows.
            'email' => fake()->unique()->safeEmail(),
            // Marks the email as verified now(), so tests skip email confirmation.
            'email_verified_at' => now(),
            // Hash the default password once and reuse it via the null-coalescing assignment.
            'password' => static::$password ??= Hash::make('password'),
            // A random token backing Laravel's "remember me" cookie.
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    // A named "state" that tweaks the defaults; used as User::factory()->unverified().
    public function unverified(): static
    {
        // state() returns a modified copy of this factory with attribute overrides.
        // The arrow function receives the current attributes and returns the changes.
        return $this->state(fn (array $attributes) => [
            // Null means the email has not been verified yet.
            'email_verified_at' => null,
        ]);
    }
}
