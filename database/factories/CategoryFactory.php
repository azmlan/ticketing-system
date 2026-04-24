<?php

namespace Database\Factories;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name_ar'    => fake()->words(2, true) . ' فئة',
            'name_en'    => fake()->words(2, true) . ' category',
            'group_id'   => Group::factory(),
            'is_active'  => true,
            'sort_order' => fake()->numberBetween(0, 10),
            'version'    => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
