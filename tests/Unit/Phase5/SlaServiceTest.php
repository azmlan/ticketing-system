<?php

use App\Modules\SLA\Models\SlaPauseLog;
use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\SLA\Services\SlaService;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use Carbon\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function fireStatus(Ticket $ticket, string $from, string $to): void
{
    $actor = User::factory()->create();
    TicketStatusChanged::dispatch($ticket, $from, $to, $actor);
}

// ─── Bootstrap on ticket creation ────────────────────────────────────────────

it('creates ticket_sla row on ticket creation event', function () {
    SlaPolicy::factory()->high()->create();
    $ticket = Ticket::factory()->create(['priority' => 'high']);

    fireStatus($ticket, '', 'awaiting_assignment');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla)->not->toBeNull();
    expect($sla->is_clock_running)->toBeTrue();
    expect($sla->response_target_minutes)->toBe(60);
    expect($sla->resolution_target_minutes)->toBe(480);
    expect($sla->response_met_at)->toBeNull();
    expect($sla->response_elapsed_minutes)->toBe(0);
    expect($sla->resolution_elapsed_minutes)->toBe(0);
});

it('creates ticket_sla with null targets when ticket has no priority', function () {
    $ticket = Ticket::factory()->create(['priority' => null]);

    fireStatus($ticket, '', 'awaiting_assignment');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla)->not->toBeNull();
    expect($sla->response_target_minutes)->toBeNull();
    expect($sla->resolution_target_minutes)->toBeNull();
    expect($sla->is_clock_running)->toBeTrue();
});

// ─── Response timer: first assignment ─────────────────────────────────────────

it('sets response_met_at on first in_progress transition', function () {
    SlaPolicy::factory()->critical()->create(); // use_24x7=true for predictable time
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');

    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'in_progress');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla->response_met_at)->not->toBeNull();
    expect($sla->response_elapsed_minutes)->toBe(30);
});

it('does not update response_met_at on subsequent in_progress transitions', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');

    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'in_progress');

    $firstMet = TicketSla::where('ticket_id', $ticket->id)->value('response_met_at');

    Carbon::setTestNow($t0->copy()->addMinutes(60));
    fireStatus($ticket, 'in_progress', 'on_hold');

    Carbon::setTestNow($t0->copy()->addMinutes(90));
    fireStatus($ticket, 'on_hold', 'in_progress');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla->response_met_at->toDateTimeString())->toBe(Carbon::parse($firstMet)->toDateTimeString());
    expect($sla->response_elapsed_minutes)->toBe(30); // unchanged after first assignment
});

// ─── Pause clock ─────────────────────────────────────────────────────────────

it('creates pause log when clock pauses on on_hold', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');

    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'in_progress');

    Carbon::setTestNow($t0->copy()->addMinutes(90));
    fireStatus($ticket, 'in_progress', 'on_hold');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla->is_clock_running)->toBeFalse();
    expect($sla->resolution_elapsed_minutes)->toBe(90);

    $log = SlaPauseLog::where('ticket_sla_id', $sla->id)->first();
    expect($log)->not->toBeNull();
    expect($log->pause_status)->toBe('on_hold');
    expect($log->resumed_at)->toBeNull();
    expect($log->duration_minutes)->toBeNull();
});

it('pauses clock on awaiting_approval', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');
    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'in_progress');
    Carbon::setTestNow($t0->copy()->addMinutes(60));
    fireStatus($ticket, 'in_progress', 'awaiting_approval');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla->is_clock_running)->toBeFalse();

    $log = SlaPauseLog::where('ticket_sla_id', $sla->id)->first();
    expect($log->pause_status)->toBe('awaiting_approval');
});

it('does not create a second pause log if already paused', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');
    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'on_hold');
    // Idempotent: firing another pause status when already paused
    Carbon::setTestNow($t0->copy()->addMinutes(60));
    fireStatus($ticket, 'on_hold', 'awaiting_approval');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect(SlaPauseLog::where('ticket_sla_id', $sla->id)->count())->toBe(1);
});

// ─── Resume clock ─────────────────────────────────────────────────────────────

it('closes pause log with duration_minutes on resume', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');
    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'in_progress');
    Carbon::setTestNow($t0->copy()->addMinutes(60));
    fireStatus($ticket, 'in_progress', 'on_hold');

    // Wait 45 minutes in hold
    Carbon::setTestNow($t0->copy()->addMinutes(105));
    fireStatus($ticket, 'on_hold', 'in_progress');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla->is_clock_running)->toBeTrue();

    $log = SlaPauseLog::where('ticket_sla_id', $sla->id)->first();
    expect($log->resumed_at)->not->toBeNull();
    expect($log->duration_minutes)->toBe(45);
});

it('accumulates resolution elapsed correctly across pause/resume cycle', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');

    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'in_progress'); // response_elapsed=30

    Carbon::setTestNow($t0->copy()->addMinutes(90));
    fireStatus($ticket, 'in_progress', 'on_hold'); // resolution_elapsed=90

    Carbon::setTestNow($t0->copy()->addMinutes(150)); // 60 min in hold
    fireStatus($ticket, 'on_hold', 'in_progress'); // resume

    Carbon::setTestNow($t0->copy()->addMinutes(210));
    fireStatus($ticket, 'in_progress', 'resolved'); // +60 min running

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    // Total running time: 90 (before hold) + 60 (after resume) = 150
    expect($sla->resolution_elapsed_minutes)->toBe(150);
    expect($sla->is_clock_running)->toBeFalse();
});

// ─── Stop clock (terminal states) ─────────────────────────────────────────────

it('stops clock on resolved with no pause log', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    $t0 = Carbon::parse('2026-04-20 09:00');
    Carbon::setTestNow($t0);
    fireStatus($ticket, '', 'awaiting_assignment');
    Carbon::setTestNow($t0->copy()->addMinutes(30));
    fireStatus($ticket, 'awaiting_assignment', 'in_progress');
    Carbon::setTestNow($t0->copy()->addMinutes(90));
    fireStatus($ticket, 'in_progress', 'resolved');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla->is_clock_running)->toBeFalse();
    expect($sla->last_clock_start)->toBeNull();
    expect(SlaPauseLog::where('ticket_sla_id', $sla->id)->count())->toBe(0);
    expect($sla->resolution_elapsed_minutes)->toBe(90);
});

it('stops clock on cancelled', function () {
    SlaPolicy::factory()->critical()->create();
    $ticket = Ticket::factory()->create(['priority' => 'critical']);

    Carbon::setTestNow(Carbon::parse('2026-04-20 09:00'));
    fireStatus($ticket, '', 'awaiting_assignment');
    Carbon::setTestNow(Carbon::parse('2026-04-20 09:45'));
    fireStatus($ticket, 'awaiting_assignment', 'cancelled');

    $sla = TicketSla::where('ticket_id', $ticket->id)->first();
    expect($sla->is_clock_running)->toBeFalse();
    expect($sla->resolution_elapsed_minutes)->toBe(45);
});

afterEach(function () {
    Carbon::setTestNow();
});
