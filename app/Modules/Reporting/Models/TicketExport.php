<?php

namespace App\Modules\Reporting\Models;

use App\Modules\Shared\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketExport extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'format',
        'filters',
        'locale',
        'include_csat',
        'file_path',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'include_csat' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
