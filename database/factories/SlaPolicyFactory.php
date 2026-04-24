<?php

namespace Database\Factories;

use App\Modules\SLA\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class SlaPolicyFactory extends Factory
{
    protected $model = SlaPolicy::class;

    private static array $priorities = ['low', 'medium', 'high', 'critical'];
    private static int $index = 0;

    public function definition(): array
    {
        $priority = self::$priorities[self::$index % count(self::$priorities)];
        self::$index++;

        return [
            'priority'                  => $priority,
            'response_target_minutes'   => fake()->randomElement([30, 60, 120, 240, 480]),
            'resolution_target_minutes' => fake()->randomElement([240, 480, 960, 1440, 2880]),
            'use_24x7'                  => false,
        ];
    }

    public function low(): static
    {
        return $this->state([
            'priority'                  => 'low',
            'response_target_minutes'   => 480,
            'resolution_target_minutes' => 2880,
            'use_24x7'                  => false,
        ]);
    }

    public function medium(): static
    {
        return $this->state([
            'priority'                  => 'medium',
            'response_target_minutes'   => 240,
            'resolution_target_minutes' => 1440,
            'use_24x7'                  => false,
        ]);
    }

    public function high(): static
    {
        return $this->state([
            'priority'                  => 'high',
            'response_target_minutes'   => 60,
            'resolution_target_minutes' => 480,
            'use_24x7'                  => false,
        ]);
    }

    public function critical(): static
    {
        return $this->state([
            'priority'                  => 'critical',
            'response_target_minutes'   => 30,
            'resolution_target_minutes' => 240,
            'use_24x7'                  => true,
        ]);
    }
}
