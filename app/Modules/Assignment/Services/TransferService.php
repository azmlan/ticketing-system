<?php

namespace App\Modules\Assignment\Services;

// Cross-module: Assignment → Tickets. Ticket/TransferRequest objects are passed in
// from the Tickets module. Documented acceptable boundary per task-2-6 spec.
use App\Modules\Assignment\Events\TransferRequestCreated;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use Illuminate\Auth\Access\AuthorizationException;

class TransferService
{
    /**
     * Tech A requests to transfer their ticket to Tech B.
     * Only one active (pending) request per ticket is allowed.
     */
    public function request(Ticket $ticket, User $fromTech, User $toTech): TransferRequest
    {
        $hasPending = TransferRequest::where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            throw new \RuntimeException(__('tickets.transfer.pending_exists'));
        }

        $transfer = TransferRequest::create([
            'ticket_id' => $ticket->id,
            'from_user_id' => $fromTech->id,
            'to_user_id' => $toTech->id,
            'status' => 'pending',
        ]);

        TransferRequestCreated::dispatch(
            $ticket->id,
            $toTech->id,
            $ticket->display_number,
            $ticket->subject,
        );

        return $transfer;
    }

    /**
     * Target tech accepts the transfer: ticket reassigned, request closed.
     * Status stays in_progress — no state machine transition needed.
     */
    public function accept(TransferRequest $tr, User $actor): void
    {
        if ($tr->to_user_id !== $actor->id) {
            throw new AuthorizationException(__('tickets.transfer.not_the_recipient'));
        }

        $tr->status = 'accepted';
        $tr->responded_at = now();
        $tr->save();

        $ticket = $tr->ticket;
        $ticket->assigned_to = $tr->to_user_id;
        $ticket->save();
    }

    /**
     * Target tech rejects the transfer: assigned_to unchanged.
     */
    public function reject(TransferRequest $tr, User $actor): void
    {
        if ($tr->to_user_id !== $actor->id) {
            throw new AuthorizationException(__('tickets.transfer.not_the_recipient'));
        }

        $tr->status = 'rejected';
        $tr->responded_at = now();
        $tr->save();
    }

    /**
     * Requesting tech revokes their pending request.
     * Only allowed while status is still pending.
     */
    public function revoke(TransferRequest $tr, User $actor): void
    {
        if ($tr->from_user_id !== $actor->id) {
            throw new AuthorizationException(__('tickets.transfer.not_the_requester'));
        }

        if ($tr->status !== 'pending') {
            throw new \RuntimeException(__('tickets.transfer.cannot_revoke'));
        }

        $tr->status = 'revoked';
        $tr->save();
    }
}
