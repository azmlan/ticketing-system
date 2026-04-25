<?php

namespace Database\Factories;

use App\Modules\Admin\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomField>
 */
class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition(): array
    {
        return [
            'name_ar'           => fake()->words(2, true) . ' حقل',
            'name_en'           => fake()->words(2, true) . ' field',
            'field_type'        => fake()->randomElement(['text', 'number', 'dropdown', 'multi_select', 'date', 'checkbox']),
            'is_required'       => false,
            'scope_type'        => 'global',
            'scope_category_id' => null,
            'display_order'     => fake()->numberBetween(0, 20),
            'is_active'         => true,
            'version'           => 1,
        ];
    }

    public function text(): static
    {
        return $this->state(['field_type' => 'text']);
    }

    public function dropdown(): static
    {
        return $this->state(['field_type' => 'dropdown']);
    }

    public function categoryScoped(string $categoryId): static
    {
        return $this->state([
            'scope_type'        => 'category',
            'scope_category_id' => $categoryId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }
}
