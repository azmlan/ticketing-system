<?php

namespace App\Modules\Escalation\Services;

use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Support\Facades\DB;

// Cross-module Ticket import is permitted in Escalation services (designated seam).
class MaintenanceRequestApprovalService
{
    public function approve(MaintenanceRequest $mr, User $actor): void
    {
        if (! $actor->is_super_user && ! $actor->hasPermission('escalation.approve')) {
            abort(403);
        }

        $ticket = Ticket::withoutGlobalScopes()->findOrFail($mr->ticket_id);

        if ($ticket->status->value !== 'awaiting_final_approval') {
            abort(403);
        }

        DB::transaction(function () use ($mr, $ticket, $actor) {
            $mr->update([
                'status'      => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            app(TicketStateMachine::class)->transition($ticket, 'resolved', $actor);
        });
    }

    public function rejectResubmit(MaintenanceRequest $mr, User $actor, string $reviewNotes): void
    {
        if (! $actor->is_super_user && ! $actor->hasPermission('escalation.approve')) {
            abort(403);
        }

        $ticket = Ticket::withoutGlobalScopes()->findOrFail($mr->ticket_id);

        if ($ticket->status->value !== 'awaiting_final_approval') {
            abort(403);
        }

        DB::transaction(function () use ($mr, $ticket, $actor, $reviewNotes) {
            $mr->update([
                'status'          => 'rejected',
                'reviewed_by'     => $actor->id,
                'reviewed_at'     => now(),
                'review_notes'    => $reviewNotes,
                'rejection_count' => $mr->rejection_count + 1,
            ]);

            app(TicketStateMachine::class)->transition($ticket, 'action_required', $actor);
        });
    }

    public function rejectPermanently(
        MaintenanceRequest $mr,
        User $actor,
        string $closeReason,
        ?string $closeReasonText = null
    ): void {
        if (! $actor->is_super_user && ! $actor->hasPermission('escalation.approve')) {
            abort(403);
        }

        $ticket = Ticket::withoutGlobalScopes()->findOrFail($mr->ticket_id);

        if ($ticket->status->value !== 'awaiting_final_approval') {
            abort(403);
        }

        DB::transaction(function () use ($mr, $ticket, $actor, $closeReason, $closeReasonText) {
            $mr->update([
                'status'      => 'rejected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            $ticket->close_reason      = $closeReason;
            $ticket->close_reason_text = $closeReason === 'other' ? $closeReasonText : null;
            $ticket->save();

            app(TicketStateMachine::class)->transition($ticket, 'closed', $actor);
        });
    }
}
