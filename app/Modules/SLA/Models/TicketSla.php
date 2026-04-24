<?php

namespace App\Modules\SLA\Models;

use Database\Factories\TicketSlaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketSla extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'ticket_sla';

    protected static function newFactory(): TicketSlaFactory
    {
        return TicketSlaFactory::new();
    }

    protected $fillable = [
        'ticket_id',
        'response_target_minutes',
        'resolution_target_minutes',
        'response_elapsed_minutes',
        'resolution_elapsed_minutes',
        'response_met_at',
        'response_status',
        'resolution_status',
        'last_clock_start',
        'is_clock_running',
    ];

    protected function casts(): array
    {
        return [
            'response_met_at'             => 'datetime',
            'last_clock_start'            => 'datetime',
            'is_clock_running'            => 'boolean',
            'response_elapsed_minutes'    => 'integer',
            'resolution_elapsed_minutes'  => 'integer',
            'response_target_minutes'     => 'integer',
            'resolution_target_minutes'   => 'integer',
        ];
    }

    public function pauseLogs(): HasMany
    {
        return $this->hasMany(SlaPauseLog::class, 'ticket_sla_id');
    }
}
