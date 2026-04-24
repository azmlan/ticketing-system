<?php

namespace App\Modules\SLA\Listeners;

use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\SLA\Services\SlaService;
use App\Modules\Tickets\Events\TicketPriorityChanged;

class HandleTicketPriorityChanged
{
    public function __construct(private readonly SlaService $slaService) {}

    public function handle(TicketPriorityChanged $event): void
    {
        $sla = TicketSla::where('ticket_id', $event->ticket->id)->first();
        if (! $sla) {
            return;
        }

        $policy = SlaPolicy::where('priority', $event->toPriority)->first();

        $sla->response_target_minutes = $policy?->response_target_minutes;
        $sla->resolution_target_minutes = $policy?->resolution_target_minutes;
        $sla->save();

        $this->slaService->recalculateStatus(
            $sla,
            $event->ticket->display_number,
            $event->ticket->subject,
            $event->ticket->assigned_to,
        );
    }
}
