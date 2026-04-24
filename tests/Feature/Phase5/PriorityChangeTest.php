<?php

use App\Modules\Shared\Models\User;
use App\Modules\SLA\Events\SlaBreach;
use App\Modules\SLA\Events\SlaWarning;
use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Events\TicketPriorityChanged;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Event;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function firePriorityChange(Ticket $ticket, ?string $from, string $to): void
{
    $actor = User::factory()->create();
    TicketPriorityChanged::dispatch($ticket, $from, $to, $actor);
}

// ─── Target recalculation ─────────────────────────────────────────────────────

it('updates SLA targets on priority change while preserving elapsed', function () {
    SlaPolicy::factory()->low()->create();   // response: 480, resolution: 2880
    SlaPolicy::factory()->high()->create();  // response: 60,  resolution: 480

    $ticket = Ticket::factory()->create(['priority' => 'low']);

    TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'response_target_minutes' => 480,
        'resolution_target_minutes' => 2880,
        'response_elapsed_minutes' => 40,
        'resolution_elapsed_minutes' => 40,
        'response_status' => 'on_track',
        'resolution_status' => 'on_track',
    ]);

    firePriorityChange($ticket, 'low', 'high');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();

    expect($sla->response_target_minutes)->toBe(60);
    expect($sla->resolution_target_minutes)->toBe(480);
    expect($sla->response_elapsed_minutes)->toBe(40);   // elapsed preserved
    expect($sla->resolution_elapsed_minutes)->toBe(40); // elapsed preserved
});

// ─── Warning event ───────────────────────────────────────────────────────────

it('fires SlaWarning when the new target puts the ticket into warning territory', function () {
    Event::fake([SlaWarning::class, SlaBreach::class]);

    SlaPolicy::factory()->low()->create();   // response: 480
    SlaPolicy::factory()->high()->create();  // response: 60

    $ticket = Ticket::factory()->create(['priority' => 'low']);

    // 50 / 480 = 10% → on_track for low
    // 50 / 60  = 83% → warning for high (threshold 75%)
    TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'response_target_minutes' => 480,
        'resolution_target_minutes' => 2880,
        'response_elapsed_minutes' => 50,
        'resolution_elapsed_minutes' => 50,
        'response_status' => 'on_track',
        'resolution_status' => 'on_track',
    ]);

    firePriorityChange($ticket, 'low', 'high');

    Event::assertDispatched(SlaWarning::class);
    Event::assertNotDispatched(SlaBreach::class);
});

// ─── Breach event ────────────────────────────────────────────────────────────

it('fires SlaBreach when the new target puts the ticket into breached territory', function () {
    Event::fake([SlaWarning::class, SlaBreach::class]);

    SlaPolicy::factory()->low()->create();   // response: 480
    SlaPolicy::factory()->high()->create();  // response: 60

    $ticket = Ticket::factory()->create(['priority' => 'low']);

    // 65 / 480 = 13% → on_track for low
    // 65 / 60  = 108% → breached for high
    TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'response_target_minutes' => 480,
        'resolution_target_minutes' => 2880,
        'response_elapsed_minutes' => 65,
        'resolution_elapsed_minutes' => 65,
        'response_status' => 'on_track',
        'resolution_status' => 'on_track',
    ]);

    firePriorityChange($ticket, 'low', 'high');

    Event::assertDispatched(SlaBreach::class);
    Event::assertNotDispatched(SlaWarning::class);
});
