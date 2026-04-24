<?php

namespace Database\Factories;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'display_number' => 'TKT-' . str_pad((string) fake()->unique()->numberBetween(1, 9999999), 7, '0', STR_PAD_LEFT),
            'subject'        => fake()->sentence(),
            'description'    => '<p>' . fake()->paragraph() . '</p>',
            'status'         => TicketStatus::AwaitingAssignment,
            'priority'       => null,
            'category_id'    => Category::factory(),
            'subcategory_id' => null,
            'group_id'       => Group::factory(),
            'assigned_to'    => null,
            'requester_id'   => User::factory(),
            'location_id'    => null,
            'department_id'  => null,
            'incident_origin' => 'web',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TicketStatus::InProgress]);
    }

    public function resolved(): static
    {
        return $this->state([
            'status'      => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state([
            'status'       => TicketStatus::Closed,
            'closed_at'    => now(),
            'close_reason' => 'resolved',
        ]);
    }
}
