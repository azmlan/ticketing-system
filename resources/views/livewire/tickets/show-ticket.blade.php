<div>
    {{-- Stub — full ticket detail view implemented in Task 2.7 --}}
    <h1>{{ $ticket->display_number }}</h1>
    <h2>{{ $ticket->subject }}</h2>
    <p>{{ $ticket->status->value }}</p>
</div>
