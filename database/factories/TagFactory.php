<?php

namespace Database\Factories;

use App\Modules\Admin\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name_ar'   => fake()->word() . ' وسم',
            'name_en'   => fake()->word() . ' tag',
            'color'     => fake()->hexColor(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
