{{ __("notifications.{$triggerKey}.greeting", ['name' => $recipientName]) }}

{{ __("notifications.{$triggerKey}.body", ['display_number' => $displayNumber, 'subject' => $ticketSubject]) }}

{{ __('notifications.view_ticket', ['display_number' => $displayNumber]) }}:
{{ $ticketUrl }}
