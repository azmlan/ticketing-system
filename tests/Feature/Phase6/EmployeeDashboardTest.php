<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\EmployeeDashboard;
use App\Modules\Tickets\Models\Ticket;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeEmployee(): User
{
    return User::factory()->create(['is_tech' => false]);
}

function employeeTicket(User $requester, array $attrs = []): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::factory()->create(array_merge([
        'requester_id' => $requester->id,
        'group_id'     => $group->id,
        'category_id'  => $category->id,
        'status'       => TicketStatus::AwaitingAssignment,
    ], $attrs));
}

// ─── Access control ───────────────────────────────────────────────────────────

it('redirects unauthenticated users to login', function () {
    $this->get(route('tickets.dashboard.employee'))
        ->assertRedirect(route('login'));
});

it('renders the employee dashboard for authenticated users', function () {
    $employee = makeEmployee();

    Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->assertOk();
});

// ─── Own-tickets isolation ────────────────────────────────────────────────────

it('employee sees only their own tickets', function () {
    $employee = makeEmployee();
    $other    = makeEmployee();

    $mine   = employeeTicket($employee, ['subject' => 'My printer broken']);
    $theirs = employeeTicket($other, ['subject' => 'Their network down']);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);
    $tickets   = $component->viewData('tickets');

    expect($tickets->pluck('id'))->toContain($mine->id)
        ->and($tickets->pluck('id'))->not->toContain($theirs->id);
});

it('another user ticket never leaks regardless of status filter', function () {
    $employee = makeEmployee();
    $other    = makeEmployee();

    employeeTicket($other, ['status' => TicketStatus::Resolved]);
    employeeTicket($other, ['status' => TicketStatus::Closed]);

    foreach (['', 'open', 'resolved', 'closed', 'cancelled'] as $filter) {
        $tickets = Livewire::actingAs($employee)
            ->test(EmployeeDashboard::class)
            ->set('statusFilter', $filter)
            ->viewData('tickets');

        expect($tickets->count())->toBe(0, "Filter '{$filter}' leaked other user's ticket");
    }
});

// ─── Status filter ────────────────────────────────────────────────────────────

it('status filter all returns all own tickets', function () {
    $employee = makeEmployee();

    employeeTicket($employee, ['status' => TicketStatus::AwaitingAssignment]);
    employeeTicket($employee, ['status' => TicketStatus::Resolved]);
    employeeTicket($employee, ['status' => TicketStatus::Closed]);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('statusFilter', '')
        ->viewData('tickets');

    expect($tickets->count())->toBe(3);
});

it('status filter open returns only non-terminal statuses', function () {
    $employee = makeEmployee();

    employeeTicket($employee, ['status' => TicketStatus::AwaitingAssignment]);
    employeeTicket($employee, ['status' => TicketStatus::InProgress]);
    employeeTicket($employee, ['status' => TicketStatus::Resolved]);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('statusFilter', 'open')
        ->viewData('tickets');

    expect($tickets->count())->toBe(2);
    expect($tickets->pluck('status')->map->value->toArray())
        ->not->toContain('resolved');
});

it('status filter resolved returns only resolved tickets', function () {
    $employee = makeEmployee();

    employeeTicket($employee, ['status' => TicketStatus::AwaitingAssignment]);
    $resolved = employeeTicket($employee, ['status' => TicketStatus::Resolved]);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('statusFilter', 'resolved')
        ->viewData('tickets');

    expect($tickets->count())->toBe(1)
        ->and($tickets->first()->id)->toBe($resolved->id);
});

it('status filter closed returns only closed tickets', function () {
    $employee = makeEmployee();

    employeeTicket($employee, ['status' => TicketStatus::Resolved]);
    $closed = employeeTicket($employee, ['status' => TicketStatus::Closed]);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('statusFilter', 'closed')
        ->viewData('tickets');

    expect($tickets->count())->toBe(1)
        ->and($tickets->first()->id)->toBe($closed->id);
});

it('status filter cancelled returns only cancelled tickets', function () {
    $employee = makeEmployee();

    employeeTicket($employee, ['status' => TicketStatus::Resolved]);
    $cancelled = employeeTicket($employee, ['status' => TicketStatus::Cancelled]);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('statusFilter', 'cancelled')
        ->viewData('tickets');

    expect($tickets->count())->toBe(1)
        ->and($tickets->first()->id)->toBe($cancelled->id);
});

// ─── Subject-only search ──────────────────────────────────────────────────────

it('search on subject returns matching ticket', function () {
    $employee = makeEmployee();

    $match   = employeeTicket($employee, ['subject' => 'Keyboard not working']);
    $nomatch = employeeTicket($employee, ['subject' => 'Network issue']);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('search', 'Keyboard')
        ->viewData('tickets');

    expect($tickets->count())->toBe(1)
        ->and($tickets->first()->id)->toBe($match->id);
});

it('search on description returns nothing (subject-only)', function () {
    $employee = makeEmployee();

    employeeTicket($employee, [
        'subject'     => 'Hardware request',
        'description' => 'The monitor screen is cracked',
    ]);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('search', 'cracked')
        ->viewData('tickets');

    expect($tickets->count())->toBe(0);
});

it('empty search returns all own tickets', function () {
    $employee = makeEmployee();

    employeeTicket($employee);
    employeeTicket($employee);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('search', '')
        ->viewData('tickets');

    expect($tickets->count())->toBe(2);
});

it('search is case-insensitive', function () {
    $employee = makeEmployee();

    $match = employeeTicket($employee, ['subject' => 'Printer Setup Required']);

    $tickets = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->set('search', 'printer setup')
        ->viewData('tickets');

    expect($tickets->count())->toBe(1)
        ->and($tickets->first()->id)->toBe($match->id);
});

// ─── Count badges ─────────────────────────────────────────────────────────────

it('count badges reflect own ticket totals', function () {
    $employee = makeEmployee();
    $other    = makeEmployee();

    employeeTicket($employee, ['status' => TicketStatus::AwaitingAssignment]);
    employeeTicket($employee, ['status' => TicketStatus::InProgress]);
    employeeTicket($employee, ['status' => TicketStatus::Resolved]);
    employeeTicket($employee, ['status' => TicketStatus::Closed]);
    employeeTicket($employee, ['status' => TicketStatus::Cancelled]);

    // other user's tickets should not affect counts
    employeeTicket($other, ['status' => TicketStatus::Resolved]);

    $counts = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->viewData('counts');

    expect($counts['open'])->toBe(2)
        ->and($counts['resolved'])->toBe(1)
        ->and($counts['closed'])->toBe(1)
        ->and($counts['cancelled'])->toBe(1);
});

// ─── Quick-submit link ────────────────────────────────────────────────────────

it('view contains a link to create a new ticket', function () {
    $employee = makeEmployee();

    Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->assertSee(route('tickets.create'));
});

// ─── AR locale renders ────────────────────────────────────────────────────────

it('renders without errors in AR locale', function () {
    $employee = makeEmployee();
    app()->setLocale('ar');

    Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->assertOk()
        ->assertSee('لوحة التحكم الخاصة بي');
});
