<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeDetailTicket(?User $requester = null, array $attrs = []): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $requester ??= User::factory()->create(['is_tech' => false]);

    return Ticket::factory()->create(array_merge([
        'requester_id' => $requester->id,
        'group_id'     => $group->id,
        'category_id'  => $category->id,
        'status'       => TicketStatus::AwaitingAssignment,
    ], $attrs));
}

function grantClosePermission(User $user): void
{
    $permission = Permission::firstOrCreate(
        ['key' => 'ticket.close'],
        ['name_ar' => 'ticket.close', 'name_en' => 'ticket.close', 'group_key' => 'ticket']
    );
    $user->permissions()->syncWithoutDetaching([$permission->id => [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]]);
}

// ─── View authorization ───────────────────────────────────────────────────────

it('employee can view their own ticket', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $ticket   = makeDetailTicket($employee);

    Livewire::actingAs($employee)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertOk()
        ->assertSee($ticket->subject);
});

it('employee cannot view another employee\'s ticket (403)', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $other    = User::factory()->create(['is_tech' => false]);
    $ticket   = makeDetailTicket($other);

    Livewire::actingAs($employee)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertForbidden();
});

it('tech can view any ticket', function () {
    $tech     = User::factory()->tech()->create();
    $employee = User::factory()->create(['is_tech' => false]);
    $ticket   = makeDetailTicket($employee);

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertOk();
});

// ─── Status localization ──────────────────────────────────────────────────────

it('detail page shows status label in English', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'en']);
    $ticket = makeDetailTicket(attrs: ['status' => TicketStatus::AwaitingAssignment]);

    app()->setLocale('en');

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Awaiting Assignment');
});

it('detail page shows status label in Arabic', function () {
    $employee = User::factory()->create(['is_tech' => false, 'locale' => 'ar']);
    $ticket   = makeDetailTicket($employee, ['status' => TicketStatus::AwaitingAssignment]);

    app()->setLocale('ar');

    Livewire::actingAs($employee)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('في انتظار التعيين');
});

// ─── Close flow ───────────────────────────────────────────────────────────────

it('authorized user can close a ticket with a valid reason', function () {
    $closer = User::factory()->tech()->create();
    grantClosePermission($closer);
    $ticket = makeDetailTicket();

    Livewire::actingAs($closer)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'duplicate')
        ->call('close')
        ->assertHasNoErrors();

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Closed)
        ->and($fresh->close_reason)->toBe('duplicate')
        ->and($fresh->closed_at)->not->toBeNull();
});

it('closing without a reason returns a validation error', function () {
    $closer = User::factory()->tech()->create();
    grantClosePermission($closer);
    $ticket = makeDetailTicket();

    Livewire::actingAs($closer)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', '')
        ->call('close')
        ->assertHasErrors(['closeReason']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingAssignment);
});

it('"Other" reason without text returns a validation error', function () {
    $closer = User::factory()->tech()->create();
    grantClosePermission($closer);
    $ticket = makeDetailTicket();

    Livewire::actingAs($closer)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'other')
        ->set('closeReasonText', '')
        ->call('close')
        ->assertHasErrors(['closeReasonText']);

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingAssignment);
});

it('"Other" reason with text persists close_reason_text', function () {
    $closer = User::factory()->tech()->create();
    grantClosePermission($closer);
    $ticket = makeDetailTicket();

    Livewire::actingAs($closer)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'other')
        ->set('closeReasonText', 'Custom closure explanation')
        ->call('close')
        ->assertHasNoErrors();

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Closed)
        ->and($fresh->close_reason)->toBe('other')
        ->and($fresh->close_reason_text)->toBe('Custom closure explanation');
});

it('non-"other" reason does not persist close_reason_text', function () {
    $closer = User::factory()->tech()->create();
    grantClosePermission($closer);
    $ticket = makeDetailTicket();

    Livewire::actingAs($closer)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'duplicate')
        ->set('closeReasonText', 'should be ignored')
        ->call('close')
        ->assertHasNoErrors();

    expect($ticket->fresh()->close_reason_text)->toBeNull();
});

it('user without ticket.close permission cannot close (403)', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $ticket   = makeDetailTicket($employee);

    Livewire::actingAs($employee)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'duplicate')
        ->call('close')
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingAssignment);
});

it('super_user can close without explicit ticket.close permission', function () {
    $superUser = User::factory()->superUser()->create();
    $ticket    = makeDetailTicket();

    Livewire::actingAs($superUser)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'out_of_scope')
        ->call('close')
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);
});

it('TicketStatusChanged event fires on close', function () {
    Event::fake();

    $closer = User::factory()->tech()->create();
    grantClosePermission($closer);
    $ticket = makeDetailTicket();

    Livewire::actingAs($closer)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'duplicate')
        ->call('close');

    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->toStatus === 'closed';
    });
});

// ─── Cancel flow ─────────────────────────────────────────────────────────────

it('requester can cancel their own ticket', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $ticket   = makeDetailTicket($employee);

    Livewire::actingAs($employee)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('cancel')
        ->assertHasNoErrors();

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Cancelled)
        ->and($fresh->cancelled_at)->not->toBeNull();
});

it('non-requester cannot cancel a ticket (403)', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $nonRequesterTech = User::factory()->tech()->create();  // tech can view but is not the requester
    $ticket    = makeDetailTicket($requester);

    Livewire::actingAs($nonRequesterTech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('cancel')
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingAssignment);
});

it('tech cannot cancel a ticket they did not request', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeDetailTicket();

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('cancel')
        ->assertForbidden();
});

it('TicketStatusChanged event fires on cancel', function () {
    Event::fake();

    $employee = User::factory()->create(['is_tech' => false]);
    $ticket   = makeDetailTicket($employee);

    Livewire::actingAs($employee)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('cancel');

    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->toStatus === 'cancelled';
    });
});

// ─── Already-closed/cancelled ticket cannot be transitioned ──────────────────

it('closed ticket cannot be closed again (state machine blocks it)', function () {
    $closer = User::factory()->tech()->create();
    grantClosePermission($closer);
    $ticket = makeDetailTicket(attrs: [
        'status'       => TicketStatus::Closed,
        'closed_at'    => now(),
        'close_reason' => 'duplicate',
    ]);

    Livewire::actingAs($closer)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('closeReason', 'out_of_scope')
        ->call('close')
        ->assertHasErrors(['closeReason']);

    // Status must remain closed
    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed)
        ->and($ticket->fresh()->close_reason)->toBe('duplicate');
});

// ─── ULID-only route enforcement ──────────────────────────────────────────────

it('GET /tickets/{display_number} returns 404 not a ticket', function () {
    $tech = User::factory()->tech()->create();

    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    Ticket::factory()->create([
        'display_number' => 'TKT-9999999',
        'group_id'       => $group->id,
        'category_id'    => $category->id,
        'requester_id'   => $tech->id,
    ]);

    $this->actingAs($tech)
        ->get('/tickets/TKT-9999999')
        ->assertStatus(404);
});
