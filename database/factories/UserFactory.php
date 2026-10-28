<?php

// Factories live in the Database\Factories namespace.
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
// Hashes passwords so stored values are never plain text.
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
// Builds fake User rows for tests and seed data.
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    // Cache the hash so it is calculated only once per run.
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Each key is a users-table column, filled with fake data.
        return [
            'name' => fake()->name(),
            // unique() stops duplicate emails across generated rows.
            'email' => fake()->unique()->safeEmail(),
            // Set now(), so tests skip the email-confirmation step.
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    // Used as User::factory()->unverified().
    public function unverified(): static
    {
        // state() returns a copy of this factory with the given changes.
        return $this->state(fn (array $attributes) => [
            // Null means the email is not verified.
            'email_verified_at' => null,
        ]);
    }
}
