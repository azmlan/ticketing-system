<?php

use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a csat_ratings row when a ticket transitions to resolved', function () {
    $tech = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::InProgress,
        'assigned_to' => $tech->id,
        'resolved_at' => null,
    ]);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $tech));

    expect(CsatRating::where('ticket_id', $ticket->id)->count())->toBe(1);

    $rating = CsatRating::where('ticket_id', $ticket->id)->first();
    expect($rating->tech_id)->toBe($tech->id)
        ->and($rating->requester_id)->toBe($ticket->requester_id)
        ->and($rating->status)->toBe('pending')
        ->and($rating->expires_at->isAfter(now()->addDays(6)))->toBeTrue();
});

it('does not create a csat row when status changes to something other than resolved', function () {
    $tech = User::factory()->create();
    $ticket = Ticket::factory()->create(['assigned_to' => $tech->id]);

    event(new TicketStatusChanged($ticket, 'awaiting_assignment', 'in_progress', $tech));

    expect(CsatRating::count())->toBe(0);
});

it('is idempotent: does not create a duplicate if resolved event fires twice', function () {
    $tech = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
        'assigned_to' => $tech->id,
    ]);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $tech));
    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $tech));

    expect(CsatRating::where('ticket_id', $ticket->id)->count())->toBe(1);
});

it('does not create a csat row when ticket has no assigned tech', function () {
    $actor = User::factory()->create();
    $ticket = Ticket::factory()->create(['assigned_to' => null]);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $actor));

    expect(CsatRating::count())->toBe(0);
});
