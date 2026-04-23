<?php

namespace App\Modules\Escalation\Models;

use App\Modules\Shared\Models\User;
use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    use HasFactory, HasUlids;

    protected static function newFactory(): MaintenanceRequestFactory
    {
        return MaintenanceRequestFactory::new();
    }

    protected $fillable = [
        'ticket_id',
        'generated_file_path',
        'generated_locale',
        'submitted_file_path',
        'submitted_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'rejection_count',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at'    => 'datetime',
            'reviewed_at'     => 'datetime',
            'rejection_count' => 'integer',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
