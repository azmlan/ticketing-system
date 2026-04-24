<?php

namespace App\Modules\SLA\Listeners;

use App\Modules\SLA\Services\SlaService;
use App\Modules\Tickets\Events\TicketStatusChanged;

class HandleTicketStatusChanged
{
    public function __construct(private SlaService $slaService) {}

    public function handle(TicketStatusChanged $event): void
    {
        $this->slaService->handleStatusChange($event);
    }
}
