<?php

namespace Database\Factories;

use App\Modules\Shared\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name_ar' => fake('ar_SA')->words(rand(2, 3), true),
            'name_en' => fake('en_US')->words(rand(2, 3), true),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
