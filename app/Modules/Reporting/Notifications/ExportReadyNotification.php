<?php

namespace App\Modules\Reporting\Notifications;

use App\Modules\Reporting\Models\TicketExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TicketExport $export,
        private readonly string $downloadUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'export_id' => $this->export->id,
            'format' => $this->export->format,
            'download_url' => $this->downloadUrl,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        app()->setLocale($notifiable->locale ?? 'ar');

        return (new MailMessage)
            ->subject(__('reports.export.notification_subject'))
            ->line(__('reports.export.notification_body', ['format' => strtoupper($this->export->format)]))
            ->action(__('reports.export.notification_action'), $this->downloadUrl)
            ->line(__('reports.export.notification_expires'));
    }
}
