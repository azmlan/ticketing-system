<?php

namespace App\Modules\Escalation\Listeners;

use App\Modules\Escalation\Jobs\GenerateMaintenanceRequestDocxJob;
use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Tickets\Events\TicketStatusChanged;

class GenerateMaintenanceRequestOnActionRequired
{
    public function handle(TicketStatusChanged $event): void
    {
        if ($event->toStatus !== 'action_required') {
            return;
        }

        $ticketId = $event->ticket->id;

        // Guard: do not create a duplicate record on reject-resubmit loop
        $exists = MaintenanceRequest::where('ticket_id', $ticketId)->exists();

        if (! $exists) {
            MaintenanceRequest::create([
                'ticket_id' => $ticketId,
                'status'    => 'pending',
            ]);
        }

        GenerateMaintenanceRequestDocxJob::dispatch($ticketId, 'ar');
    }
}
