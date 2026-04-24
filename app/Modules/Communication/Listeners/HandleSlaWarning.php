<?php

namespace App\Modules\Communication\Listeners;

use App\Modules\Communication\Services\NotificationService;
use App\Modules\Shared\Models\User;
use App\Modules\SLA\Events\SlaWarning;

class HandleSlaWarning
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(SlaWarning $event): void
    {
        $tech = $event->assignedTechId ? User::find($event->assignedTechId) : null;
        if (! $tech) {
            return;
        }

        $this->notificationService->dispatch(
            'sla_warning',
            $event->ticketId,
            $event->displayNumber,
            $event->ticketSubject,
            [$tech],
        );
    }
}
