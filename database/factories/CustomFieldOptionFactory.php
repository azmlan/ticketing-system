<?php

namespace Database\Factories;

use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldOption>
 */
class CustomFieldOptionFactory extends Factory
{
    protected $model = CustomFieldOption::class;

    public function definition(): array
    {
        return [
            'custom_field_id' => CustomField::factory()->dropdown(),
            'value_ar'        => fake()->word() . ' خيار',
            'value_en'        => fake()->word() . ' option',
            'sort_order'      => fake()->numberBetween(0, 20),
            'is_active'       => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
