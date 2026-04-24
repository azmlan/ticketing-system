<?php

namespace App\Modules\Tickets\Policies;

use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->is_super_user || $user->is_tech || $user->hasPermission('ticket.view-all')) {
            return true;
        }

        return $ticket->requester_id === $user->id;
    }

    public function selfAssign(User $user, Ticket $ticket): bool
    {
        return $user->is_tech && $ticket->status->value === 'awaiting_assignment';
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        if ($user->is_super_user || $user->hasPermission('ticket.assign')) {
            return true;
        }

        return Group::where('id', $ticket->group_id)
            ->where('manager_id', $user->id)
            ->exists();
    }

    public function requestTransfer(User $user, Ticket $ticket): bool
    {
        return $user->is_tech && $ticket->assigned_to === $user->id;
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->is_super_user || $user->hasPermission('ticket.close');
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        return $ticket->requester_id === $user->id;
    }
}
