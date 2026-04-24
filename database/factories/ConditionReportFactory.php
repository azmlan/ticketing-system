<?php

namespace Database\Factories;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConditionReportFactory extends Factory
{
    protected $model = ConditionReport::class;

    public function definition(): array
    {
        return [
            'ticket_id'          => Ticket::factory(),
            'report_type'        => fake()->randomElement(['hardware', 'software', 'network', 'peripheral']),
            'location_id'        => null,
            'report_date'        => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'current_condition'  => fake()->paragraph(),
            'condition_analysis' => fake()->paragraph(),
            'required_action'    => fake()->paragraph(),
            'tech_id'            => User::factory()->tech(),
            'status'             => 'pending',
            'reviewed_by'        => null,
            'reviewed_at'        => null,
            'review_notes'       => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status'       => 'approved',
            'reviewed_by'  => User::factory(),
            'reviewed_at'  => now(),
            'review_notes' => fake()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status'       => 'rejected',
            'reviewed_by'  => User::factory(),
            'reviewed_at'  => now(),
            'review_notes' => fake()->sentence(),
        ]);
    }
}
