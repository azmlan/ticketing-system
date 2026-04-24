<?php

namespace Database\Factories;

use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class CsatRatingFactory extends Factory
{
    protected $model = CsatRating::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'requester_id' => User::factory(),
            'tech_id' => User::factory(),
            'rating' => null,
            'comment' => null,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'submitted_at' => null,
            'dismissed_count' => 0,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function expiredSoon(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinutes(1),
        ]);
    }
}
