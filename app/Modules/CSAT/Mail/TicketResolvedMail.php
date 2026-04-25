<?php

namespace App\Modules\CSAT\Mail;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketResolvedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $requester,
        public readonly User $tech,
    ) {
        $this->locale($this->requester->locale ?? 'ar');
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('csat.email.subject', [
                'display_number' => $this->ticket->display_number,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.csat.ticket_resolved',
            text: 'emails.csat.text.ticket_resolved',
            with: [
                'displayNumber' => $this->ticket->display_number,
                'ticketSubject' => $this->ticket->subject,
                'techName'      => $this->tech->full_name,
                'ticketUrl'     => route('tickets.show', $this->ticket->id),
                'recipientName' => $this->requester->full_name,
            ],
        );
    }
}
