<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Precedent\Livewire\ResolveModal;
use App\Modules\Precedent\Models\Resolution;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Livewire\Livewire;

// ── Helpers ───────────────────────────────────────────────────────────────────

function inProgressTicketAssignedTo(User $tech): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::factory()->create([
        'group_id'    => $group->id,
        'category_id' => $category->id,
        'status'      => TicketStatus::InProgress,
        'assigned_to' => $tech->id,
    ]);
}

// ── Authorization ─────────────────────────────────────────────────────────────

it('non-assigned tech cannot open the resolve modal', function () {
    $tech1  = User::factory()->tech()->create();
    $tech2  = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech1);

    Livewire::actingAs($tech2)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->dispatch('open-resolve-modal')
        ->assertForbidden();
});

it('employee cannot open the resolve modal', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $tech     = User::factory()->tech()->create();
    $ticket   = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($employee)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->dispatch('open-resolve-modal')
        ->assertForbidden();
});

it('assigned tech can open the resolve modal', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->dispatch('open-resolve-modal')
        ->assertSet('open', true);
});

it('super user can open the resolve modal on any ticket', function () {
    $superUser = User::factory()->superUser()->create();
    $tech      = User::factory()->tech()->create();
    $ticket    = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($superUser)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->dispatch('open-resolve-modal')
        ->assertSet('open', true);
});

// ── Ticket status unchanged until submitted ───────────────────────────────────

it('dispatching open-resolve-modal does not change ticket status', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->dispatch('open-resolve-modal');

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

// ── Cancel ────────────────────────────────────────────────────────────────────

it('cancel closes modal without changing ticket status or creating resolution', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->dispatch('open-resolve-modal')
        ->call('cancel')
        ->assertSet('open', false);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress)
        ->and(Resolution::where('ticket_id', $ticket->id)->count())->toBe(0);
});

// ── Validation ────────────────────────────────────────────────────────────────

it('submit without summary returns validation error and does not resolve', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('stepsTaken', '<p>steps</p>')
        ->set('resolutionType', 'known_fix')
        ->call('submit')
        ->assertHasErrors(['summary']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('submit without steps_taken returns validation error and does not resolve', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Fix applied')
        ->set('resolutionType', 'known_fix')
        ->call('submit')
        ->assertHasErrors(['stepsTaken']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('submit without resolution_type returns validation error', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Fix applied')
        ->set('stepsTaken', '<p>steps</p>')
        ->call('submit')
        ->assertHasErrors(['resolutionType']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

// ── Successful submission ─────────────────────────────────────────────────────

it('valid submission resolves ticket and creates Resolution row', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Replaced faulty cable')
        ->set('rootCause', 'Hardware failure')
        ->set('stepsTaken', '<p>Replaced the cable.</p>')
        ->set('partsResources', 'CAT6 cable')
        ->set('timeSpentMinutes', '30')
        ->set('resolutionType', 'known_fix')
        ->call('submit')
        ->assertSet('open', false);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);

    $resolution = Resolution::where('ticket_id', $ticket->id)->first();
    expect($resolution)->not->toBeNull()
        ->and($resolution->summary)->toBe('Replaced faulty cable')
        ->and($resolution->root_cause)->toBe('Hardware failure')
        ->and($resolution->parts_resources)->toBe('CAT6 cable')
        ->and($resolution->time_spent_minutes)->toBe(30)
        ->and($resolution->resolution_type)->toBe('known_fix')
        ->and($resolution->created_by)->toBe($tech->id);
});

it('valid submission dispatches ticket-resolved event', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Fix')
        ->set('stepsTaken', '<p>steps</p>')
        ->set('resolutionType', 'workaround')
        ->call('submit')
        ->assertDispatched('ticket-resolved');
});

it('optional fields may be omitted', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Quick fix')
        ->set('stepsTaken', '<p>Done.</p>')
        ->set('resolutionType', 'other')
        ->call('submit');

    $resolution = Resolution::where('ticket_id', $ticket->id)->first();
    expect($resolution->root_cause)->toBeNull()
        ->and($resolution->parts_resources)->toBeNull()
        ->and($resolution->time_spent_minutes)->toBeNull();
});

// ── Sanitization ──────────────────────────────────────────────────────────────

it('steps_taken is sanitized before storage (script tags stripped)', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Fix')
        ->set('stepsTaken', '<p>OK</p><script>alert(1)</script>')
        ->set('resolutionType', 'known_fix')
        ->call('submit');

    $resolution = Resolution::where('ticket_id', $ticket->id)->first();
    expect($resolution->steps_taken)->not->toContain('<script>');
});

// ── Transaction rollback ──────────────────────────────────────────────────────

it('no Resolution row created if ticket is not in in_progress state (wrong state rollback)', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    // Manually put ticket in resolved state to trigger invalid transition
    $ticket->update(['status' => TicketStatus::Resolved]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Fix')
        ->set('stepsTaken', '<p>steps</p>')
        ->set('resolutionType', 'known_fix')
        ->call('submit');

    expect(Resolution::where('ticket_id', $ticket->id)->count())->toBe(0);
});

// ── All 4 resolution types accepted ──────────────────────────────────────────

it('accepts all four valid resolution_type values', function (string $type) {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Fix')
        ->set('stepsTaken', '<p>steps</p>')
        ->set('resolutionType', $type)
        ->call('submit')
        ->assertHasNoErrors();

    expect(Resolution::where('ticket_id', $ticket->id)->value('resolution_type'))->toBe($type);
})->with(['known_fix', 'workaround', 'escalated_externally', 'other']);
