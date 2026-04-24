<?php

namespace App\Modules\Communication\Listeners;

use App\Modules\Communication\Services\NotificationService;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Events\TicketStatusChanged;

class HandleTicketStatusChanged
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(TicketStatusChanged $event): void
    {
        $ticket     = $event->ticket;
        $fromStatus = $event->fromStatus;
        $toStatus   = $event->toStatus;

        $pairs = $this->resolveTriggers($ticket, $fromStatus, $toStatus);

        foreach ($pairs as [$triggerKey, $recipients]) {
            $this->notificationService->dispatch(
                $triggerKey,
                $ticket->id,
                $ticket->display_number,
                $ticket->subject,
                array_values(array_filter($recipients)),
            );
        }
    }

    /** @return array<int, array{0: string, 1: array<int, User|null>}> */
    private function resolveTriggers($ticket, string $fromStatus, string $toStatus): array
    {
        return match ($toStatus) {
            'awaiting_assignment' => [
                ['ticket_created', [$ticket->requester]],
            ],

            'in_progress' => match ($fromStatus) {
                'awaiting_assignment' => [['ticket_assigned', [$ticket->assignedTo]]],
                'awaiting_approval'   => [['escalation_updated', [$ticket->assignedTo]]],
                default               => [],
            },

            'awaiting_approval' => [
                ['escalation_submitted', User::whereHas('permissions', fn ($q) => $q->where('key', 'escalation.approve'))->get()->all()],
            ],

            'action_required' => match ($fromStatus) {
                'awaiting_approval'       => [
                    ['action_required',    [$ticket->requester]],
                    ['escalation_updated', [$ticket->assignedTo]],
                ],
                'awaiting_final_approval' => [
                    ['form_rejected', [$ticket->requester]],
                ],
                default => [],
            },

            'resolved' => [
                ['ticket_resolved', array_filter([$ticket->requester, $ticket->assignedTo])],
            ],

            'closed' => [
                ['ticket_closed', [$ticket->requester]],
            ],

            default => [],
        };
    }
}
