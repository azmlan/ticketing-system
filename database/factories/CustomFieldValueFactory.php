<?php

namespace Database\Factories;

use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldValue;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldValue>
 */
class CustomFieldValueFactory extends Factory
{
    protected $model = CustomFieldValue::class;

    public function definition(): array
    {
        return [
            'ticket_id'       => Ticket::factory(),
            'custom_field_id' => CustomField::factory(),
            'value'           => fake()->sentence(),
        ];
    }
}
