<?php

namespace App\Modules\Communication\Listeners;

use App\Modules\Communication\Services\NotificationService;
use App\Modules\Shared\Models\User;
use App\Modules\SLA\Events\SlaBreach;

class HandleSlaBreach
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(SlaBreach $event): void
    {
        $byId = [];

        $tech = $event->assignedTechId ? User::find($event->assignedTechId) : null;
        if ($tech) {
            $byId[$tech->id] = $tech;
        }

        foreach (User::where('is_super_user', true)->get() as $manager) {
            $byId[$manager->id] = $manager;
        }

        $recipients = array_values($byId);
        if (empty($recipients)) {
            return;
        }

        $this->notificationService->dispatch(
            'sla_breached',
            $event->ticketId,
            $event->displayNumber,
            $event->ticketSubject,
            $recipients,
        );
    }
}
