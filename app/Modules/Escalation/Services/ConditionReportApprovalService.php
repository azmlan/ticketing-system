<?php

namespace App\Modules\Escalation\Services;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Support\Facades\DB;

// Cross-module Ticket import is permitted in Escalation services (the designated seam).
class ConditionReportApprovalService
{
    public function __construct(
        private readonly TicketStateMachine $stateMachine,
    ) {}

    public function approve(ConditionReport $report, User $actor): void
    {
        if (! $actor->is_super_user && ! $actor->hasPermission('escalation.approve')) {
            abort(403);
        }

        if ($actor->id === $report->tech_id) {
            abort(403);
        }

        $ticket = Ticket::withoutGlobalScopes()->where('id', $report->ticket_id)->firstOrFail();

        if ($ticket->status->value !== 'awaiting_approval') {
            abort(403);
        }

        DB::transaction(function () use ($report, $ticket, $actor) {
            $report->update([
                'status'      => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            $this->stateMachine->transition($ticket, 'action_required', $actor);
        });
    }

    public function reject(ConditionReport $report, User $actor, string $reviewNotes): void
    {
        if (! $actor->is_super_user && ! $actor->hasPermission('escalation.approve')) {
            abort(403);
        }

        if ($actor->id === $report->tech_id) {
            abort(403);
        }

        $ticket = Ticket::withoutGlobalScopes()->where('id', $report->ticket_id)->firstOrFail();

        if ($ticket->status->value !== 'awaiting_approval') {
            abort(403);
        }

        DB::transaction(function () use ($report, $ticket, $actor, $reviewNotes) {
            $report->update([
                'status'       => 'rejected',
                'reviewed_by'  => $actor->id,
                'reviewed_at'  => now(),
                'review_notes' => $reviewNotes,
            ]);

            $this->stateMachine->transition($ticket, 'in_progress', $actor);
        });
    }
}
