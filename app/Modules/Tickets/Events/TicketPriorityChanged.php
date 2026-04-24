<?php

namespace App\Modules\Tickets\Events;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketPriorityChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?string $fromPriority,
        public readonly string $toPriority,
        public readonly User $actor,
    ) {}
}
