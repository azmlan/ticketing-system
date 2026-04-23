<?php

namespace App\Modules\Tickets\Services;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Exceptions\InvalidTicketTransitionException;
use App\Modules\Tickets\Models\Ticket;

class TicketStateMachine
{
    // Full transition table from §7.4. Only these edges are valid for normal transitions.
    private const TRANSITIONS = [
        'awaiting_assignment'    => ['in_progress'],
        'in_progress'            => ['on_hold', 'awaiting_approval', 'resolved'],
        'on_hold'                => ['in_progress'],
        'awaiting_approval'      => ['action_required', 'in_progress'],
        'action_required'        => ['awaiting_final_approval'],
        'awaiting_final_approval' => ['resolved', 'action_required'],
    ];

    // Tickets in these states cannot be transitioned further.
    private const TERMINAL = ['closed', 'cancelled'];

    public function transition(Ticket $ticket, string $toStatus, User $actor): void
    {
        $fromStatus = $ticket->status->value;

        if ($fromStatus === $toStatus) {
            throw new InvalidTicketTransitionException($fromStatus, $toStatus, 'already in this state');
        }

        if (in_array($fromStatus, self::TERMINAL, true)) {
            throw new InvalidTicketTransitionException($fromStatus, $toStatus, 'ticket is already terminal');
        }

        match ($toStatus) {
            'closed'    => $this->assertCanClose($fromStatus, $toStatus, $actor),
            'cancelled' => $this->assertCanCancel($fromStatus, $toStatus, $ticket, $actor),
            default     => $this->assertValidTransition($fromStatus, $toStatus),
        };

        $ticket->status = TicketStatus::from($toStatus);

        match ($toStatus) {
            'resolved'  => $ticket->resolved_at = now(),
            'closed'    => $ticket->closed_at = now(),
            'cancelled' => $ticket->cancelled_at = now(),
            default     => null,
        };

        $ticket->save();

        TicketStatusChanged::dispatch($ticket, $fromStatus, $toStatus, $actor);
    }

    private function assertCanClose(string $from, string $to, User $actor): void
    {
        if (! ($actor->is_super_user || $actor->hasPermission('ticket.close'))) {
            throw new InvalidTicketTransitionException($from, $to, 'requires ticket.close permission');
        }
    }

    private function assertCanCancel(string $from, string $to, Ticket $ticket, User $actor): void
    {
        if ($ticket->requester_id !== $actor->id) {
            throw new InvalidTicketTransitionException($from, $to, 'only the requester may cancel');
        }
    }

    private function assertValidTransition(string $from, string $to): void
    {
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidTicketTransitionException($from, $to);
        }
    }
}
