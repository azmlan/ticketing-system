<?php

namespace App\Modules\Communication\Models;

use App\Modules\Shared\Models\User;
use Database\Factories\NotificationLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory, HasUlids;

    protected static function newFactory(): NotificationLogFactory
    {
        return NotificationLogFactory::new();
    }

    protected $fillable = [
        'recipient_id',
        'ticket_id',
        'type',
        'channel',
        'subject',
        'body_preview',
        'status',
        'sent_at',
        'failure_reason',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'  => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
