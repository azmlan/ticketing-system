<?php

namespace App\Modules\Precedent\Models;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Database\Factories\ResolutionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resolution extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ticket_id',
        'summary',
        'root_cause',
        'steps_taken',
        'parts_resources',
        'time_spent_minutes',
        'resolution_type',
        'linked_resolution_id',
        'link_notes',
        'usage_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'time_spent_minutes' => 'integer',
            'usage_count'        => 'integer',
        ];
    }

    protected static function newFactory(): ResolutionFactory
    {
        return ResolutionFactory::new();
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function linkedResolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class, 'linked_resolution_id');
    }

    public function linkedBy(): HasMany
    {
        return $this->hasMany(Resolution::class, 'linked_resolution_id');
    }
}
