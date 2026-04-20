<?php

namespace Database\Factories;

use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'employee_number' => fake()->optional(0.7)->numerify('EMP-####'),
            'locale' => fake()->randomElement(['ar', 'en']),
            'is_tech' => false,
            'is_super_user' => false,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function tech(): static
    {
        return $this->state(['is_tech' => true]);
    }

    public function superUser(): static
    {
        return $this->state(['is_super_user' => true, 'is_tech' => true]);
    }
}
