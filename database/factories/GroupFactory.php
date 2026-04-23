<?php

namespace Database\Factories;

use App\Modules\Admin\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name_ar'    => fake()->words(2, true) . ' عربي',
            'name_en'    => fake()->words(2, true) . ' group',
            'manager_id' => null,
            'is_active'  => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
