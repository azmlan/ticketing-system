<?php

namespace App\Modules\CSAT\Listeners;

use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Tickets\Events\TicketStatusChanged;

class HandleCsatOnResolution
{
    public function handle(TicketStatusChanged $event): void
    {
        if ($event->toStatus !== 'resolved') {
            return;
        }

        $ticket = $event->ticket;

        // Only create if the ticket has an assigned tech
        if (! $ticket->assigned_to) {
            return;
        }

        CsatRating::firstOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'requester_id' => $ticket->requester_id,
                'tech_id' => $ticket->assigned_to,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'dismissed_count' => 0,
            ]
        );
    }
}
