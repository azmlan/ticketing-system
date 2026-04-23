<?php

namespace Database\Factories;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subcategory>
 */
class SubcategoryFactory extends Factory
{
    protected $model = Subcategory::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name_ar'     => fake()->words(2, true) . ' فئة فرعية',
            'name_en'     => fake()->words(2, true) . ' subcategory',
            'is_required' => false,
            'is_active'   => true,
            'sort_order'  => fake()->numberBetween(0, 10),
            'version'     => 1,
        ];
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
