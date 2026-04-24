<?php

namespace Database\Factories;

use App\Modules\SLA\Models\SlaPauseLog;
use App\Modules\SLA\Models\TicketSla;
use Illuminate\Database\Eloquent\Factories\Factory;

class SlaPauseLogFactory extends Factory
{
    protected $model = SlaPauseLog::class;

    public function definition(): array
    {
        $pausedAt = now()->subMinutes(fake()->numberBetween(30, 120));

        return [
            'ticket_sla_id'    => TicketSla::factory(),
            'paused_at'        => $pausedAt,
            'resumed_at'       => null,
            'pause_status'     => 'on_hold',
            'duration_minutes' => null,
        ];
    }

    public function resumed(): static
    {
        return $this->state(function (array $attributes) {
            $pausedAt = $attributes['paused_at'] ?? now()->subMinutes(60);
            $resumedAt = (clone $pausedAt)->addMinutes(45);

            return [
                'resumed_at'       => $resumedAt,
                'duration_minutes' => 45,
            ];
        });
    }
}
