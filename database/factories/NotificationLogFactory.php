<?php

namespace Database\Factories;

use App\Modules\Communication\Models\NotificationLog;
use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'recipient_id'  => User::factory(),
            'ticket_id'     => null,
            'type'          => fake()->randomElement([
                'ticket_created', 'ticket_assigned', 'ticket_resolved',
                'ticket_closed', 'transfer_request',
            ]),
            'channel'       => 'email',
            'subject'       => fake()->sentence(),
            'body_preview'  => fake()->optional()->sentence(),
            'status'        => 'queued',
            'sent_at'       => null,
            'failure_reason' => null,
            'attempts'      => 0,
        ];
    }

    public function sent(): static
    {
        return $this->state([
            'status'   => 'sent',
            'sent_at'  => now(),
            'attempts' => 1,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'         => 'failed',
            'failure_reason' => fake()->sentence(),
            'attempts'       => 3,
        ]);
    }
}
