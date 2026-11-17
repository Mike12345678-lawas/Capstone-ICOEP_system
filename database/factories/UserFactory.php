<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => \App\Models\Role::firstOrCreate(['name' => 'Student'], ['description' => 'Student'])->id,
            'status' => 'active',
            'privacy_consent' => true,
            'privacy_consent_date' => now(),
        ];
    }

    /**
     * Indicate that the user has not accepted the privacy consent yet.
     */
    public function unconsented(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy_consent' => false,
            'privacy_consent_date' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
