<?php

namespace App\Modules\Tickets\Events;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly User $actor,
    ) {}
}
