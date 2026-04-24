<?php

namespace App\Modules\Escalation\Models;

use Database\Factories\ConditionReportAttachmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionReportAttachment extends Model
{
    use HasFactory, HasUlids;

    protected static function newFactory(): ConditionReportAttachmentFactory
    {
        return ConditionReportAttachmentFactory::new();
    }

    protected $fillable = [
        'condition_report_id',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function conditionReport(): BelongsTo
    {
        return $this->belongsTo(ConditionReport::class);
    }
}
