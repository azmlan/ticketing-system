@php $triggerKey = 'sla_breached'; @endphp
@include('emails.notifications.text.partials.base', ['triggerKey' => $triggerKey])
