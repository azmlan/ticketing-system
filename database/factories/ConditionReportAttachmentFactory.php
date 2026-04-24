<?php

namespace Database\Factories;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\ConditionReportAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConditionReportAttachmentFactory extends Factory
{
    protected $model = ConditionReportAttachment::class;

    public function definition(): array
    {
        $reportId = ConditionReport::inRandomOrder()->value('id')
            ?? ConditionReport::factory()->create()->id;

        return [
            'condition_report_id' => $reportId,
            'original_name'       => fake()->word() . '.jpg',
            'file_path'           => 'escalation/' . $reportId . '/' . strtolower((string) Str::ulid()),
            'file_size'           => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'mime_type'           => 'image/jpeg',
        ];
    }
}
