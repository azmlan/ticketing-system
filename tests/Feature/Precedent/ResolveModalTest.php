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

// ── Task 9.3 — Resolution Linking ────────────────────────────────────────────

it('modal opens in write mode by default', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->dispatch('open-resolve-modal')
        ->assertSet('mode', 'write');
});

it('switching to link mode clears steps_taken and resets search state', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('stepsTaken', '<p>some steps</p>')
        ->set('searchQuery', 'something')
        ->call('switchMode', 'link')
        ->assertSet('mode', 'link')
        ->assertSet('stepsTaken', '')
        ->assertSet('searchQuery', '');
});

it('switching back to write mode clears linked resolution', function () {
    $tech             = User::factory()->tech()->create();
    $ticket           = inProgressTicketAssignedTo($tech);
    $resolvedTicket   = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target           = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('linkedResolutionId', $target->id)
        ->call('switchMode', 'write')
        ->assertSet('mode', 'write')
        ->assertSet('linkedResolutionId', '');
});

it('selectResolution sets linkedResolutionId and clears search query', function () {
    $tech           = User::factory()->tech()->create();
    $ticket         = inProgressTicketAssignedTo($tech);
    $resolvedTicket = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target         = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('searchQuery', 'something')
        ->call('selectResolution', $target->id)
        ->assertSet('linkedResolutionId', $target->id)
        ->assertSet('searchQuery', '');
});

it('clearLinkedResolution unsets the linked resolution', function () {
    $tech           = User::factory()->tech()->create();
    $ticket         = inProgressTicketAssignedTo($tech);
    $resolvedTicket = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target         = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->call('selectResolution', $target->id)
        ->call('clearLinkedResolution')
        ->assertSet('linkedResolutionId', '');
});

it('link mode submit without selecting a resolution returns validation error', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('summary', 'Same fix')
        ->set('resolutionType', 'known_fix')
        ->call('submit')
        ->assertHasErrors(['linkedResolutionId']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('link mode submit creates Resolution with linked_resolution_id and resolves ticket', function () {
    $tech           = User::factory()->tech()->create();
    $ticket         = inProgressTicketAssignedTo($tech);
    $resolvedTicket = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target         = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id, 'usage_count' => 2]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('summary', 'Same fix as before')
        ->set('resolutionType', 'known_fix')
        ->call('selectResolution', $target->id)
        ->call('submit')
        ->assertSet('open', false)
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);

    $resolution = Resolution::where('ticket_id', $ticket->id)->first();
    expect($resolution)->not->toBeNull()
        ->and($resolution->linked_resolution_id)->toBe($target->id)
        ->and($resolution->steps_taken)->toBeNull()
        ->and($resolution->summary)->toBe('Same fix as before')
        ->and($resolution->created_by)->toBe($tech->id);
});

it('linking increments usage_count on the target resolution atomically', function () {
    $tech           = User::factory()->tech()->create();
    $ticket         = inProgressTicketAssignedTo($tech);
    $resolvedTicket = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target         = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id, 'usage_count' => 5]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('summary', 'Reusing fix')
        ->set('resolutionType', 'workaround')
        ->call('selectResolution', $target->id)
        ->call('submit');

    expect($target->fresh()->usage_count)->toBe(6);
});

it('link_notes are stored when linking', function () {
    $tech           = User::factory()->tech()->create();
    $ticket         = inProgressTicketAssignedTo($tech);
    $resolvedTicket = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target         = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('summary', 'Reusing fix')
        ->set('resolutionType', 'known_fix')
        ->set('linkNotes', 'Slightly different hardware model')
        ->call('selectResolution', $target->id)
        ->call('submit');

    $resolution = Resolution::where('ticket_id', $ticket->id)->first();
    expect($resolution->link_notes)->toBe('Slightly different hardware model');
});

it('link_notes are optional when linking', function () {
    $tech           = User::factory()->tech()->create();
    $ticket         = inProgressTicketAssignedTo($tech);
    $resolvedTicket = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target         = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('summary', 'Reusing fix')
        ->set('resolutionType', 'known_fix')
        ->call('selectResolution', $target->id)
        ->call('submit')
        ->assertHasNoErrors();

    expect(Resolution::where('ticket_id', $ticket->id)->value('link_notes'))->toBeNull();
});

it('xor validation rejects submit when both steps_taken and linked_resolution_id are set', function () {
    $tech           = User::factory()->tech()->create();
    $ticket         = inProgressTicketAssignedTo($tech);
    $resolvedTicket = Ticket::factory()->create(['status' => \App\Modules\Tickets\Enums\TicketStatus::Resolved]);
    $target         = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->set('summary', 'Fix')
        ->set('stepsTaken', '<p>steps</p>')
        ->set('linkedResolutionId', $target->id)
        ->set('resolutionType', 'known_fix')
        ->call('submit')
        ->assertHasErrors(['linkedResolutionId']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress)
        ->and(Resolution::where('ticket_id', $ticket->id)->count())->toBe(0);
});

it('link mode submit rejects a non-existent resolution id', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = inProgressTicketAssignedTo($tech);

    Livewire::actingAs($tech)
        ->test(ResolveModal::class, ['ticket' => $ticket])
        ->call('switchMode', 'link')
        ->set('summary', 'Fix')
        ->set('resolutionType', 'known_fix')
        ->set('linkedResolutionId', '01JTOTALLYINVALIDULID00000')
        ->call('submit')
        ->assertHasErrors(['linkedResolutionId']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});
