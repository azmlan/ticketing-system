<?php

namespace Database\Factories;

use App\Modules\Communication\Models\ResponseTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponseTemplateFactory extends Factory
{
    protected $model = ResponseTemplate::class;

    public function definition(): array
    {
        return [
            'title_ar'    => fake()->words(3, true) . ' نموذج',
            'title_en'    => fake()->words(3, true) . ' template',
            'body_ar'     => '<p>' . fake()->paragraph() . ' عربي</p>',
            'body_en'     => '<p>' . fake()->paragraph() . '</p>',
            'is_internal' => true,
            'is_active'   => true,
        ];
    }

    public function public(): static
    {
        return $this->state(['is_internal' => false]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
