<?php

namespace Database\Factories;

use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MaintenanceRequestFactory extends Factory
{
    protected $model = MaintenanceRequest::class;

    public function definition(): array
    {
        return [
            'ticket_id'            => Ticket::factory(),
            'generated_file_path'  => 'maintenance/' . strtolower((string) Str::ulid()) . '.docx',
            'generated_locale'     => fake()->randomElement(['ar', 'en']),
            'submitted_file_path'  => null,
            'submitted_at'         => null,
            'status'               => 'pending',
            'reviewed_by'          => null,
            'reviewed_at'          => null,
            'review_notes'         => null,
            'rejection_count'      => 0,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status'              => 'submitted',
            'submitted_file_path' => 'maintenance/' . strtolower((string) Str::ulid()) . '.pdf',
            'submitted_at'        => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->submitted()->state(fn () => [
            'status'       => 'approved',
            'reviewed_by'  => User::factory(),
            'reviewed_at'  => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->submitted()->state(fn () => [
            'status'          => 'rejected',
            'reviewed_by'     => User::factory(),
            'reviewed_at'     => now(),
            'review_notes'    => fake()->sentence(),
            'rejection_count' => fake()->numberBetween(1, 3),
        ]);
    }
}
