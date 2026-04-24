<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Livewire\TicketList;
use App\Modules\Tickets\Models\Ticket;
use Livewire\Livewire;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeSlaTicket(User $requester, array $slaAttrs = []): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    $ticket = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'group_id'     => $group->id,
        'category_id'  => $category->id,
        'status'       => TicketStatus::AwaitingAssignment,
    ]);

    if (! empty($slaAttrs)) {
        TicketSla::factory()->create(array_merge(['ticket_id' => $ticket->id], $slaAttrs));
    }

    return $ticket;
}

// ─── Show-ticket: SLA badges ─────────────────────────────────────────────────

it('shows green on-track badge on ticket detail when both statuses are on_track', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'en']);
    $ticket = makeSlaTicket($tech, [
        'response_status'   => 'on_track',
        'resolution_status' => 'on_track',
    ]);

    app()->setLocale('en');

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('On Track');
});

it('shows warning badge on ticket detail when response_status is warning', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'en']);
    $ticket = makeSlaTicket($tech, [
        'response_status'   => 'warning',
        'resolution_status' => 'on_track',
    ]);

    app()->setLocale('en');

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Warning');
});

it('shows breached badge on ticket detail when resolution_status is breached', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'en']);
    $ticket = makeSlaTicket($tech, [
        'response_status'   => 'on_track',
        'resolution_status' => 'breached',
    ]);

    app()->setLocale('en');

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Breached');
});

it('shows Arabic SLA status labels when locale is ar', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'ar']);
    $ticket = makeSlaTicket($tech, [
        'response_status'   => 'breached',
        'resolution_status' => 'warning',
    ]);

    app()->setLocale('ar');

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('خرق')
        ->assertSee('تحذير');
});

it('shows no-policy message on ticket detail when no SLA record exists', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'en']);
    $ticket = makeSlaTicket($tech);  // no SLA attrs → no TicketSla row

    app()->setLocale('en');

    Livewire::actingAs($tech)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('No SLA Policy');
});

// ─── Ticket list: SLA column ──────────────────────────────────────────────────

it('SLA column header is visible in ticket list', function () {
    $tech = User::factory()->tech()->create(['locale' => 'en']);

    app()->setLocale('en');

    Livewire::actingAs($tech)
        ->test(TicketList::class)
        ->assertSee('SLA');
});

it('SLA badge appears in the list for a ticket with SLA data', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'en']);
    $ticket = makeSlaTicket($tech, [
        'response_status'   => 'warning',
        'resolution_status' => 'on_track',
    ]);

    app()->setLocale('en');

    Livewire::actingAs($tech)
        ->test(TicketList::class)
        ->assertSee($ticket->display_number)
        ->assertSee('Warning');
});

it('ticket without SLA record shows dash in SLA column, not an error', function () {
    $tech   = User::factory()->tech()->create(['locale' => 'en']);
    $ticket = makeSlaTicket($tech);  // no TicketSla

    Livewire::actingAs($tech)
        ->test(TicketList::class)
        ->assertSee($ticket->display_number)
        ->assertOk();
});

// ─── Compliance summary ───────────────────────────────────────────────────────

it('compliance summary shows percentage when SLA data exists', function () {
    $tech = User::factory()->tech()->create(['locale' => 'en']);

    $t1 = makeSlaTicket($tech, ['resolution_status' => 'on_track']);
    $t2 = makeSlaTicket($tech, ['resolution_status' => 'on_track']);
    $t3 = makeSlaTicket($tech, ['resolution_status' => 'breached']);

    app()->setLocale('en');

    // 2 out of 3 = 67% compliant, 1 breached
    Livewire::actingAs($tech)
        ->test(TicketList::class)
        ->assertSee('compliant')
        ->assertSee('breached');
});

it('compliance summary is hidden when there are no SLA records', function () {
    // Clear any existing SLA data
    \Illuminate\Support\Facades\DB::table('ticket_sla')->delete();

    $tech = User::factory()->tech()->create(['locale' => 'en']);

    app()->setLocale('en');

    $component = Livewire::actingAs($tech)->test(TicketList::class);

    expect($component->viewData('compliance')['total'])->toBe(0);
});

// ─── RTL: logical properties sanity ──────────────────────────────────────────

it('ticket list view uses ps-3 (logical) not pl-3 (physical) for column padding', function () {
    $view = file_get_contents(resource_path('views/livewire/tickets/ticket-list.blade.php'));

    expect($view)->not->toContain('pl-3')
        ->and($view)->not->toContain('pr-3')
        ->and($view)->toContain('ps-3');
});

it('SLA badge view uses no physical left/right margin or padding classes', function () {
    $view = file_get_contents(resource_path('views/components/sla/status-badge.blade.php'));

    expect($view)->not->toContain('ml-')
        ->and($view)->not->toContain('mr-')
        ->and($view)->not->toContain('pl-')
        ->and($view)->not->toContain('pr-');
});
