<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Assignment\Services\AssignmentService;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeTicketInGroup(Group $group, ?User $requester = null): Ticket
{
    $category = Category::factory()->create(['group_id' => $group->id]);
    $requester ??= User::factory()->create();

    return Ticket::factory()->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'requester_id' => $requester->id,
        'status' => TicketStatus::AwaitingAssignment,
        'assigned_to' => null,
    ]);
}

function grantPermission(User $user, string $key): void
{
    $permission = Permission::firstOrCreate(
        ['key' => $key],
        ['name_ar' => $key, 'name_en' => $key, 'group_key' => 'ticket']
    );
    $user->permissions()->syncWithoutDetaching([$permission->id => [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]]);
}

// ─── Self-assign ──────────────────────────────────────────────────────────────

it('tech can self-assign an awaiting_assignment ticket', function () {
    $tech = User::factory()->tech()->create();
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group);

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('selfAssign')
        ->assertHasNoErrors();

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::InProgress)
        ->assigned_to->toBe($tech->id);
});

it('non-tech cannot self-assign (403)', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group, $employee);

    Livewire::actingAs($employee)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('selfAssign')
        ->assertForbidden();
});

it('TicketStatusChanged event is fired on self-assign', function () {
    Event::fake();

    $tech = User::factory()->tech()->create();
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group);

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('selfAssign');

    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->fromStatus === 'awaiting_assignment'
            && $event->toStatus === 'in_progress';
    });
});

// ─── Manager assign ───────────────────────────────────────────────────────────

it('group manager can assign a tech within their group', function () {
    $manager = User::factory()->tech()->create();
    $tech = User::factory()->tech()->create();
    $group = Group::factory()->create(['manager_id' => $manager->id]);
    $ticket = makeTicketInGroup($group);

    Livewire::actingAs($manager)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('assignToUserId', $tech->id)
        ->call('managerAssign')
        ->assertHasNoErrors();

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::InProgress)
        ->assigned_to->toBe($tech->id);
});

it('group manager cannot assign to tech outside their group (403)', function () {
    $manager = User::factory()->tech()->create();
    $tech = User::factory()->tech()->create();

    $managerGroup = Group::factory()->create(['manager_id' => $manager->id]);
    $otherGroup = Group::factory()->create();
    $ticket = makeTicketInGroup($otherGroup);

    Livewire::actingAs($manager)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('assignToUserId', $tech->id)
        ->call('managerAssign')
        ->assertForbidden();
});

it('IT manager (is_super_user) can assign across groups', function () {
    $itManager = User::factory()->superUser()->create();
    $tech = User::factory()->tech()->create();
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group);

    Livewire::actingAs($itManager)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('assignToUserId', $tech->id)
        ->call('managerAssign')
        ->assertHasNoErrors();

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::InProgress)
        ->assigned_to->toBe($tech->id);
});

it('user with ticket.assign permission can assign across groups', function () {
    $assignManager = User::factory()->tech()->create();
    grantPermission($assignManager, 'ticket.assign');

    $tech = User::factory()->tech()->create();
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group);

    Livewire::actingAs($assignManager)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('assignToUserId', $tech->id)
        ->call('managerAssign')
        ->assertHasNoErrors();

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::InProgress)
        ->assigned_to->toBe($tech->id);
});

it('TicketStatusChanged event is fired on manager assign', function () {
    Event::fake();

    $itManager = User::factory()->superUser()->create();
    $tech = User::factory()->tech()->create();
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group);

    Livewire::actingAs($itManager)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('assignToUserId', $tech->id)
        ->call('managerAssign');

    Event::assertDispatched(TicketStatusChanged::class);
});

// ─── Service-level tests ──────────────────────────────────────────────────────

it('AssignmentService::selfAssign throws for non-tech directly', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group);

    expect(fn () => app(AssignmentService::class)->selfAssign($ticket, $employee))
        ->toThrow(AuthorizationException::class);
});

it('AssignmentService::managerAssign throws for wrong group directly', function () {
    $manager = User::factory()->tech()->create();
    $tech = User::factory()->tech()->create();
    $managerGroup = Group::factory()->create(['manager_id' => $manager->id]);
    $otherGroup = Group::factory()->create();
    $ticket = makeTicketInGroup($otherGroup);

    expect(fn () => app(AssignmentService::class)->managerAssign($ticket, $manager, $tech))
        ->toThrow(AuthorizationException::class);
});

it('AssignmentService::reassign throws without permission directly', function () {
    $actor = User::factory()->create(['is_tech' => true, 'is_super_user' => false]);
    $tech = User::factory()->tech()->create();
    $group = Group::factory()->create();
    $ticket = makeTicketInGroup($group);

    expect(fn () => app(AssignmentService::class)->reassign($ticket, $actor, $tech))
        ->toThrow(AuthorizationException::class);
});
