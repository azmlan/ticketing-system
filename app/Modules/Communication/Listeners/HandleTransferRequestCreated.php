<?php

namespace App\Modules\Communication\Listeners;

use App\Modules\Assignment\Events\TransferRequestCreated;
use App\Modules\Communication\Services\NotificationService;
use App\Modules\Shared\Models\User;

class HandleTransferRequestCreated
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(TransferRequestCreated $event): void
    {
        $recipient = User::find($event->toUserId);

        if (! $recipient) {
            return;
        }

        $this->notificationService->dispatch(
            'transfer_request',
            $event->ticketId,
            $event->displayNumber,
            $event->ticketSubject,
            [$recipient],
        );
    }
}
