<?php

namespace Database\Factories;

use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketSlaFactory extends Factory
{
    protected $model = TicketSla::class;

    public function definition(): array
    {
        return [
            'ticket_id'                  => Ticket::factory(),
            'response_target_minutes'    => 60,
            'resolution_target_minutes'  => 480,
            'response_elapsed_minutes'   => 0,
            'resolution_elapsed_minutes' => 0,
            'response_met_at'            => null,
            'response_status'            => 'on_track',
            'resolution_status'          => 'on_track',
            'last_clock_start'           => now(),
            'is_clock_running'           => true,
        ];
    }

    public function paused(): static
    {
        return $this->state([
            'is_clock_running' => false,
            'last_clock_start' => null,
        ]);
    }

    public function warning(): static
    {
        return $this->state([
            'response_status'   => 'warning',
            'resolution_status' => 'warning',
        ]);
    }

    public function breached(): static
    {
        return $this->state([
            'response_status'   => 'breached',
            'resolution_status' => 'breached',
        ]);
    }

    public function withoutTargets(): static
    {
        return $this->state([
            'response_target_minutes'   => null,
            'resolution_target_minutes' => null,
        ]);
    }
}
