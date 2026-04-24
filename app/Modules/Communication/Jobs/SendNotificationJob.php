<?php

namespace App\Modules\Communication\Jobs;

use App\Mail\TicketNotification;
use App\Modules\Communication\Models\NotificationLog;
use App\Modules\Shared\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $notificationLogId,
        public readonly User $recipient,
        public readonly string $triggerKey,
        public readonly string $ticketId,
        public readonly string $displayNumber,
        public readonly string $ticketSubject,
    ) {}

    public function handle(): void
    {
        $log = NotificationLog::findOrFail($this->notificationLogId);
        $log->attempts += 1;
        $log->save();

        app()->setLocale($this->recipient->locale ?? 'ar');

        Mail::to($this->recipient->email)->send(
            new TicketNotification(
                triggerKey: $this->triggerKey,
                ticketId: $this->ticketId,
                displayNumber: $this->displayNumber,
                ticketSubject: $this->ticketSubject,
                recipientName: $this->recipient->full_name,
            )
        );

        $log->status  = 'sent';
        $log->sent_at = now();
        $log->save();
    }

    public function failed(\Throwable $exception): void
    {
        NotificationLog::where('id', $this->notificationLogId)->update([
            'status'         => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
