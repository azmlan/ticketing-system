<?php

namespace Database\Factories;

use App\Modules\Precedent\Models\Resolution;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resolution>
 */
class ResolutionFactory extends Factory
{
    protected $model = Resolution::class;

    public function definition(): array
    {
        return [
            'ticket_id'          => Ticket::factory()->resolved(),
            'summary'            => fake()->sentence(),
            'root_cause'         => fake()->optional(0.6)->sentence(),
            'steps_taken'        => '<p>' . fake()->paragraph() . '</p>',
            'parts_resources'    => fake()->optional(0.4)->sentence(),
            'time_spent_minutes' => fake()->optional(0.7)->numberBetween(5, 480),
            'resolution_type'    => fake()->randomElement(['known_fix', 'workaround', 'escalated_externally', 'other']),
            'linked_resolution_id' => null,
            'link_notes'         => null,
            'usage_count'        => 0,
            'created_by'         => User::factory(),
        ];
    }

    public function knownFix(): static
    {
        return $this->state(['resolution_type' => 'known_fix']);
    }

    public function workaround(): static
    {
        return $this->state(['resolution_type' => 'workaround']);
    }

    public function escalatedExternally(): static
    {
        return $this->state(['resolution_type' => 'escalated_externally']);
    }

    public function other(): static
    {
        return $this->state(['resolution_type' => 'other']);
    }

    public function linked(Resolution $target): static
    {
        return $this->state([
            'linked_resolution_id' => $target->id,
            'steps_taken'          => null,
            'link_notes'           => fake()->optional(0.5)->sentence(),
        ]);
    }
}
