<?php

namespace App\Modules\Assignment\Services;

// Cross-module: Assignment → Tickets via TicketStateMachine service call.
// Ticket objects are received as injected parameters from the Tickets module.
// Documented acceptable boundary per task-2-6 spec.
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Auth\Access\AuthorizationException;

class AssignmentService
{
    public function __construct(private TicketStateMachine $stateMachine) {}

    /**
     * Any tech grabs an awaiting_assignment ticket from any group's queue.
     * Sets assigned_to and transitions status → in_progress via state machine.
     */
    public function selfAssign(Ticket $ticket, User $tech): void
    {
        if (! $tech->is_tech) {
            throw new AuthorizationException(__('tickets.assignment.not_a_tech'));
        }

        $ticket->assigned_to = $tech->id;
        $this->stateMachine->transition($ticket, 'in_progress', $tech);
    }

    /**
     * Group Manager assigns within their group; IT Manager / ticket.assign can assign anywhere.
     * Sets assigned_to and transitions status → in_progress via state machine.
     */
    public function managerAssign(Ticket $ticket, User $manager, User $tech): void
    {
        $hasFullAccess = $manager->is_super_user || $manager->hasPermission('ticket.assign');

        if (! $hasFullAccess) {
            $isGroupManager = Group::where('id', $ticket->group_id)
                ->where('manager_id', $manager->id)
                ->exists();

            if (! $isGroupManager) {
                throw new AuthorizationException(__('tickets.assignment.not_group_manager'));
            }
        }

        $ticket->assigned_to = $tech->id;
        $this->stateMachine->transition($ticket, 'in_progress', $manager);
    }

    /**
     * IT Manager / ticket.assign: direct reassign, no acceptance step.
     * Transitions awaiting_assignment → in_progress if not yet assigned.
     */
    public function reassign(Ticket $ticket, User $actor, User $newTech): void
    {
        if (! $actor->is_super_user && ! $actor->hasPermission('ticket.assign')) {
            throw new AuthorizationException(__('tickets.assignment.no_reassign_permission'));
        }

        $ticket->assigned_to = $newTech->id;

        if ($ticket->status->value === 'awaiting_assignment') {
            $this->stateMachine->transition($ticket, 'in_progress', $actor);
        } else {
            $ticket->save();
        }
    }
}
