<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\TicketList;
use App\Modules\Tickets\Models\Ticket;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeListTicket(User $user): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::factory()->create([
        'requester_id' => $user->id,
        'group_id'     => $group->id,
        'category_id'  => $category->id,
        'status'       => TicketStatus::AwaitingAssignment,
    ]);
}

// ─── Authentication guard ─────────────────────────────────────────────────────

it('redirects guests to login', function () {
    $this->get(route('tickets.index'))->assertRedirect(route('login'));
});

// ─── Employee visibility ──────────────────────────────────────────────────────

it('employee only sees their own tickets in the list', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $other    = User::factory()->create(['is_tech' => false]);

    $ownTicket   = makeListTicket($employee);
    $otherTicket = makeListTicket($other);

    Livewire::actingAs($employee)
        ->test(TicketList::class)
        ->assertSee($ownTicket->display_number)
        ->assertDontSee($otherTicket->display_number);
});

it('employee list does not contain tickets from other employees', function () {
    $employee = User::factory()->create(['is_tech' => false]);
    $other    = User::factory()->create(['is_tech' => false]);

    makeListTicket($other);
    makeListTicket($other);

    $component = Livewire::actingAs($employee)->test(TicketList::class);

    // The rendered tickets variable should be empty for this employee
    expect($component->viewData('tickets')->total())->toBe(0);
});

// ─── Tech visibility ──────────────────────────────────────────────────────────

it('tech sees all tickets in the list', function () {
    $tech     = User::factory()->tech()->create();
    $employee = User::factory()->create(['is_tech' => false]);

    $t1 = makeListTicket($employee);
    $t2 = makeListTicket($employee);

    Livewire::actingAs($tech)
        ->test(TicketList::class)
        ->assertSee($t1->display_number)
        ->assertSee($t2->display_number);
});

it('tech list total matches all tickets', function () {
    $tech     = User::factory()->tech()->create();
    $employee = User::factory()->create(['is_tech' => false]);

    makeListTicket($employee);
    makeListTicket($employee);
    makeListTicket($employee);

    $component = Livewire::actingAs($tech)->test(TicketList::class);

    expect($component->viewData('tickets')->total())->toBeGreaterThanOrEqual(3);
});

// ─── Pagination ───────────────────────────────────────────────────────────────

it('list paginates at 15 per page', function () {
    $tech = User::factory()->tech()->create();

    // Create 20 tickets
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(20)->create([
        'group_id'    => $group->id,
        'category_id' => $category->id,
        'status'      => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($tech)->test(TicketList::class);

    expect($component->viewData('tickets')->perPage())->toBe(15)
        ->and($component->viewData('tickets')->total())->toBeGreaterThanOrEqual(20);
});

// ─── Display-number-as-route-param guard ──────────────────────────────────────

it('GET /tickets/TKT-0000001 does not resolve a real ticket', function () {
    $tech = User::factory()->tech()->create();

    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = Ticket::factory()->create([
        'display_number' => 'TKT-0000001',
        'group_id'       => $group->id,
        'category_id'    => $category->id,
        'requester_id'   => $tech->id,
    ]);

    // display_number as URL segment must NOT resolve the ticket (ULID-only invariant §2.3)
    $this->actingAs($tech)
        ->get('/tickets/TKT-0000001')
        ->assertStatus(404);
});
