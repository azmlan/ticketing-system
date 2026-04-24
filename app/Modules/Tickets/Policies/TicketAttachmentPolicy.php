<?php

namespace App\Modules\Tickets\Policies;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;

class TicketAttachmentPolicy
{
    public function view(User $user, TicketAttachment $attachment): bool
    {
        if ($user->is_super_user || $user->hasPermission('ticket.view-all')) {
            return true;
        }

        $ticket = Ticket::withoutGlobalScopes()->find($attachment->ticket_id);

        return $ticket !== null && (
            $user->id === $ticket->requester_id ||
            $user->id === $ticket->assigned_to
        );
    }
}
