<?php

namespace App\Modules\CSAT\Listeners;

use App\Modules\CSAT\Mail\TicketResolvedMail;
use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Mail;

class HandleCsatOnResolution
{
    public function handle(TicketStatusChanged $event): void
    {
        if ($event->toStatus !== 'resolved') {
            return;
        }

        $ticket = $event->ticket;

        if (! $ticket->assigned_to) {
            return;
        }

        $rating = CsatRating::firstOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'requester_id' => $ticket->requester_id,
                'tech_id'      => $ticket->assigned_to,
                'status'       => 'pending',
                'expires_at'   => now()->addDays(7),
                'dismissed_count' => 0,
            ]
        );

        if ($rating->wasRecentlyCreated) {
            $ticket->loadMissing(['requester', 'assignedTo']);

            Mail::to($ticket->requester->email)
                ->send(new TicketResolvedMail($ticket, $ticket->requester, $ticket->assignedTo));
        }
    }
}
