<?php

namespace App\Modules\CSAT\Models;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Database\Factories\CsatRatingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsatRating extends Model
{
    use HasFactory, HasUlids;

    protected static function newFactory(): CsatRatingFactory
    {
        return CsatRatingFactory::new();
    }

    protected $table = 'csat_ratings';

    protected $fillable = [
        'ticket_id',
        'requester_id',
        'tech_id',
        'rating',
        'comment',
        'status',
        'expires_at',
        'submitted_at',
        'dismissed_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function tech(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tech_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', 'submitted');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }
}
