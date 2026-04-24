<?php

namespace Database\Factories;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferRequestFactory extends Factory
{
    protected $model = TransferRequest::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'from_user_id' => User::factory()->tech(),
            'to_user_id' => User::factory()->tech(),
            'status' => 'pending',
            'responded_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(['status' => 'revoked']);
    }
}
