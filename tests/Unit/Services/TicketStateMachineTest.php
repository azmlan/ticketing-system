<?php

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Exceptions\InvalidTicketTransitionException;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\TicketStateMachine;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Event;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeTicket(string $status, ?User $requester = null): Ticket
{
    $requester ??= User::factory()->create();
    return Ticket::factory()->create([
        'status'       => $status,
        'requester_id' => $requester->id,
    ]);
}

function machine(): TicketStateMachine
{
    return new TicketStateMachine();
}

// ─── Valid transitions ────────────────────────────────────────────────────────

it('transitions awaiting_assignment → in_progress and fires event', function () {
    Event::fake();
    $actor  = User::factory()->create();
    $ticket = makeTicket('awaiting_assignment');

    machine()->transition($ticket, 'in_progress', $actor);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
    Event::assertDispatched(TicketStatusChanged::class, function ($e) use ($ticket, $actor) {
        return $e->fromStatus === 'awaiting_assignment'
            && $e->toStatus === 'in_progress'
            && $e->ticket->id === $ticket->id
            && $e->actor->id === $actor->id;
    });
});

it('transitions in_progress → on_hold', function () {
    Event::fake();
    $actor  = User::factory()->create();
    $ticket = makeTicket('in_progress');

    machine()->transition($ticket, 'on_hold', $actor);

    expect($ticket->fresh()->status)->toBe(TicketStatus::OnHold);
    Event::assertDispatched(TicketStatusChanged::class);
});

it('transitions in_progress → awaiting_approval', function () {
    Event::fake();
    $ticket = makeTicket('in_progress');

    machine()->transition($ticket, 'awaiting_approval', User::factory()->create());

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingApproval);
});

it('transitions in_progress → resolved and sets resolved_at', function () {
    Event::fake();
    $ticket = makeTicket('in_progress');

    machine()->transition($ticket, 'resolved', User::factory()->create());

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Resolved)
        ->and($fresh->resolved_at)->not->toBeNull();
});

it('transitions on_hold → in_progress', function () {
    Event::fake();
    $ticket = makeTicket('on_hold');

    machine()->transition($ticket, 'in_progress', User::factory()->create());

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('transitions awaiting_approval → action_required', function () {
    Event::fake();
    $ticket = makeTicket('awaiting_approval');

    machine()->transition($ticket, 'action_required', User::factory()->create());

    expect($ticket->fresh()->status)->toBe(TicketStatus::ActionRequired);
});

it('transitions awaiting_approval → in_progress (approver rejects)', function () {
    Event::fake();
    $ticket = makeTicket('awaiting_approval');

    machine()->transition($ticket, 'in_progress', User::factory()->create());

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('transitions action_required → awaiting_final_approval', function () {
    Event::fake();
    $ticket = makeTicket('action_required');

    machine()->transition($ticket, 'awaiting_final_approval', User::factory()->create());

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingFinalApproval);
});

it('transitions awaiting_final_approval → resolved and sets resolved_at', function () {
    Event::fake();
    $ticket = makeTicket('awaiting_final_approval');

    machine()->transition($ticket, 'resolved', User::factory()->create());

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Resolved)
        ->and($fresh->resolved_at)->not->toBeNull();
});

it('transitions awaiting_final_approval → action_required (form rejected)', function () {
    Event::fake();
    $ticket = makeTicket('awaiting_final_approval');

    machine()->transition($ticket, 'action_required', User::factory()->create());

    expect($ticket->fresh()->status)->toBe(TicketStatus::ActionRequired);
});

// ─── Closed transition ────────────────────────────────────────────────────────

it('closes ticket from any state when actor has ticket.close permission', function () {
    Event::fake();
    $this->seed(PermissionSeeder::class);

    $closer  = User::factory()->create();
    $perm    = \App\Modules\Shared\Models\Permission::where('key', 'ticket.close')->first();
    $closer->permissions()->attach($perm->id, ['granted_by' => $closer->id, 'granted_at' => now()]);

    foreach (['awaiting_assignment', 'in_progress', 'on_hold', 'awaiting_approval', 'action_required', 'awaiting_final_approval'] as $state) {
        $ticket = makeTicket($state);
        machine()->transition($ticket, 'closed', $closer);

        $fresh = $ticket->fresh();
        expect($fresh->status)->toBe(TicketStatus::Closed)
            ->and($fresh->closed_at)->not->toBeNull();
    }
});

it('close transition fails without ticket.close permission', function () {
    $actor  = User::factory()->create();
    $ticket = makeTicket('in_progress');

    expect(fn () => machine()->transition($ticket, 'closed', $actor))
        ->toThrow(InvalidTicketTransitionException::class);
});

it('super user can close without explicit permission', function () {
    Event::fake();
    $superUser = User::factory()->superUser()->create();
    $ticket    = makeTicket('in_progress');

    machine()->transition($ticket, 'closed', $superUser);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);
});

// ─── Cancelled transition ─────────────────────────────────────────────────────

it('requester can cancel their own ticket', function () {
    Event::fake();
    $requester = User::factory()->create();
    $ticket    = makeTicket('in_progress', $requester);

    machine()->transition($ticket, 'cancelled', $requester);

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Cancelled)
        ->and($fresh->cancelled_at)->not->toBeNull();
});

it('non-requester cannot cancel ticket', function () {
    $requester   = User::factory()->create();
    $otherUser   = User::factory()->create();
    $ticket      = makeTicket('in_progress', $requester);

    expect(fn () => machine()->transition($ticket, 'cancelled', $otherUser))
        ->toThrow(InvalidTicketTransitionException::class);
});

// ─── Invalid transitions ──────────────────────────────────────────────────────

it('throws on awaiting_assignment → resolved (skip steps)', function () {
    $ticket = makeTicket('awaiting_assignment');

    expect(fn () => machine()->transition($ticket, 'resolved', User::factory()->create()))
        ->toThrow(InvalidTicketTransitionException::class);
});

it('throws on in_progress → awaiting_assignment (backwards)', function () {
    $ticket = makeTicket('in_progress');

    expect(fn () => machine()->transition($ticket, 'awaiting_assignment', User::factory()->create()))
        ->toThrow(InvalidTicketTransitionException::class);
});

it('throws on on_hold → resolved (must resume first)', function () {
    $ticket = makeTicket('on_hold');

    expect(fn () => machine()->transition($ticket, 'resolved', User::factory()->create()))
        ->toThrow(InvalidTicketTransitionException::class);
});

it('throws on awaiting_final_approval → in_progress (invalid shortcut)', function () {
    $ticket = makeTicket('awaiting_final_approval');

    expect(fn () => machine()->transition($ticket, 'in_progress', User::factory()->create()))
        ->toThrow(InvalidTicketTransitionException::class);
});

it('throws on self-transition', function () {
    $ticket = makeTicket('in_progress');

    expect(fn () => machine()->transition($ticket, 'in_progress', User::factory()->create()))
        ->toThrow(InvalidTicketTransitionException::class);
});

it('throws when transitioning from a closed (terminal) ticket', function () {
    $this->seed(PermissionSeeder::class);

    $closer = User::factory()->create();
    $perm   = \App\Modules\Shared\Models\Permission::where('key', 'ticket.close')->first();
    $closer->permissions()->attach($perm->id, ['granted_by' => $closer->id, 'granted_at' => now()]);

    $ticket = makeTicket('in_progress');
    machine()->transition($ticket, 'closed', $closer);

    expect(fn () => machine()->transition($ticket->fresh(), 'closed', $closer))
        ->toThrow(InvalidTicketTransitionException::class);
});

it('throws when transitioning from a cancelled (terminal) ticket', function () {
    $requester = User::factory()->create();
    $ticket    = makeTicket('in_progress', $requester);
    machine()->transition($ticket, 'cancelled', $requester);

    expect(fn () => machine()->transition($ticket->fresh(), 'in_progress', $requester))
        ->toThrow(InvalidTicketTransitionException::class);
});

// ─── Event payload ────────────────────────────────────────────────────────────

it('TicketStatusChanged event carries correct from/to/actor', function () {
    Event::fake();
    $actor  = User::factory()->create();
    $ticket = makeTicket('on_hold');

    machine()->transition($ticket, 'in_progress', $actor);

    Event::assertDispatched(TicketStatusChanged::class, function ($e) use ($actor) {
        return $e->fromStatus === 'on_hold'
            && $e->toStatus === 'in_progress'
            && $e->actor->id === $actor->id;
    });
});
