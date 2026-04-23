<?php

namespace App\Modules\Escalation\Models;

use App\Modules\Shared\Models\User;
use Database\Factories\ConditionReportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConditionReport extends Model
{
    use HasFactory, HasUlids;

    protected static function newFactory(): ConditionReportFactory
    {
        return ConditionReportFactory::new();
    }

    protected $fillable = [
        'ticket_id',
        'report_type',
        'location_id',
        'report_date',
        'current_condition',
        'condition_analysis',
        'required_action',
        'tech_id',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ConditionReportAttachment::class);
    }

    public function tech(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tech_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
