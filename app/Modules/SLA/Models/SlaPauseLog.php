<?php

namespace App\Modules\SLA\Models;

use Database\Factories\SlaPauseLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaPauseLog extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'sla_pause_logs';

    protected static function newFactory(): SlaPauseLogFactory
    {
        return SlaPauseLogFactory::new();
    }

    protected $fillable = [
        'ticket_sla_id',
        'paused_at',
        'resumed_at',
        'pause_status',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'paused_at'        => 'datetime',
            'resumed_at'       => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function ticketSla(): BelongsTo
    {
        return $this->belongsTo(TicketSla::class, 'ticket_sla_id');
    }
}
