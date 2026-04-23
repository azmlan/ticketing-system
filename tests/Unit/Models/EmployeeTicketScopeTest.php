<?php

use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Database\Seeders\PermissionSeeder;

// ─── EmployeeTicketScope ─────────────────────────────────────────────────────

it('employee only sees their own tickets', function () {
    $employee = User::factory()->create();
    $other    = User::factory()->create();

    $mine   = Ticket::factory()->create(['requester_id' => $employee->id]);
    $theirs = Ticket::factory()->create(['requester_id' => $other->id]);

    $this->actingAs($employee);

    $visible = Ticket::all()->pluck('id');

    expect($visible)->toContain($mine->id)
        ->and($visible)->not->toContain($theirs->id);
});

it('tech with ticket.view-all sees all tickets', function () {
    $this->seed(PermissionSeeder::class);

    $tech     = User::factory()->tech()->create();
    $employee = User::factory()->create();

    $permission = Permission::where('key', 'ticket.view-all')->first();
    $tech->permissions()->attach($permission->id, [
        'granted_by' => $tech->id,
        'granted_at' => now(),
    ]);

    $ownTicket   = Ticket::factory()->create(['requester_id' => $tech->id]);
    $otherTicket = Ticket::factory()->create(['requester_id' => $employee->id]);

    $this->actingAs($tech);

    $visible = Ticket::all()->pluck('id');

    expect($visible)->toContain($ownTicket->id)
        ->and($visible)->toContain($otherTicket->id);
});

it('super user sees all tickets regardless of requester', function () {
    $superUser = User::factory()->superUser()->create();
    $employee  = User::factory()->create();

    $otherTicket = Ticket::factory()->create(['requester_id' => $employee->id]);

    $this->actingAs($superUser);

    $visible = Ticket::all()->pluck('id');

    expect($visible)->toContain($otherTicket->id);
});

it('unauthenticated query returns all tickets (CLI/queue context)', function () {
    $ticket = Ticket::factory()->create();

    // No actingAs — simulates queue/CLI context
    $visible = Ticket::all()->pluck('id');

    expect($visible)->toContain($ticket->id);
});

it('employee without ticket.view-all cannot see tickets of another user', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $other    = User::factory()->create();

    Ticket::factory()->count(3)->create(['requester_id' => $other->id]);

    $this->actingAs($employee);

    expect(Ticket::count())->toBe(0);
});
