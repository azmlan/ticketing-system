<?php

namespace App\Modules\Communication\Services;

use App\Modules\Communication\Jobs\SendNotificationJob;
use App\Modules\Communication\Models\NotificationLog;
use App\Modules\Shared\Models\User;

class NotificationService
{
    /**
     * Create a queued log entry and dispatch a SendNotificationJob for each recipient.
     *
     * @param  array<int, User>  $recipients
     */
    public function dispatch(
        string $triggerKey,
        string $ticketId,
        string $displayNumber,
        string $ticketSubject,
        array $recipients,
    ): void {
        foreach ($recipients as $recipient) {
            if (! $recipient instanceof User) {
                continue;
            }

            $prevLocale = app()->getLocale();
            app()->setLocale($recipient->locale ?? 'ar');
            $emailSubject = __("notifications.{$triggerKey}.subject", [
                'display_number' => $displayNumber,
            ]);
            app()->setLocale($prevLocale);

            $log = NotificationLog::create([
                'recipient_id' => $recipient->id,
                'ticket_id'    => $ticketId,
                'type'         => $triggerKey,
                'channel'      => 'email',
                'subject'      => $emailSubject,
                'status'       => 'queued',
                'attempts'     => 0,
            ]);

            SendNotificationJob::dispatch(
                $log->id,
                $recipient,
                $triggerKey,
                $ticketId,
                $displayNumber,
                $ticketSubject,
            )->onQueue('notifications');
        }
    }
}
