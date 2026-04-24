<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketNotification extends Mailable
{
    public function __construct(
        public readonly string $triggerKey,
        public readonly string $ticketId,
        public readonly string $displayNumber,
        public readonly string $ticketSubject,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("notifications.{$this->triggerKey}.subject", [
                'display_number' => $this->displayNumber,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications.' . $this->triggerKey,
            with: [
                'displayNumber' => $this->displayNumber,
                'ticketSubject' => $this->ticketSubject,
                'ticketUrl'     => route('tickets.show', $this->ticketId),
                'recipientName' => $this->recipientName,
            ],
        );
    }
}
