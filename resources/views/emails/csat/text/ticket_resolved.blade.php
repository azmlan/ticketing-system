{{ __('csat.email.greeting', ['name' => $recipientName]) }}

{{ __('csat.email.body', ['display_number' => $displayNumber, 'subject' => $ticketSubject, 'tech' => $techName]) }}

{{ __('notifications.view_ticket', ['display_number' => $displayNumber]) }}:
{{ $ticketUrl }}
