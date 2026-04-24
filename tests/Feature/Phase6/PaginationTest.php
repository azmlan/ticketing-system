<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\EmployeeDashboard;
use App\Modules\Tickets\Livewire\ManagerDashboard;
use App\Modules\Tickets\Livewire\TechDashboard;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ── Helpers ───────────────────────────────────────────────────────────────────

function paginationEmployee(): User
{
    return User::factory()->create(['is_tech' => false]);
}

function create30EmployeeTickets(User $user): void
{
    $group = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'requester_id' => $user->id,
        'group_id' => $group->id,
        'category_id' => $category->id,
        'status' => TicketStatus::AwaitingAssignment,
    ]);
}

// ── Employee Dashboard Pagination ─────────────────────────────────────────────

it('employee dashboard page 1 returns 25 tickets when 30 exist', function () {
    $employee = paginationEmployee();
    create30EmployeeTickets($employee);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);
    $tickets = $component->viewData('tickets');

    expect($tickets->count())->toBe(25)
        ->and($tickets->total())->toBe(30);
});

it('employee dashboard page 2 returns remaining 5 tickets', function () {
    $employee = paginationEmployee();
    create30EmployeeTickets($employee);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);
    $component->call('gotoPage', 2);

    expect($component->viewData('tickets')->count())->toBe(5);
});

it('changing sort on employee dashboard resets to page 1', function () {
    $employee = paginationEmployee();
    create30EmployeeTickets($employee);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);
    $component->call('gotoPage', 2);

    $component->set('sortBy', 'updated_at');

    expect($component->viewData('tickets')->currentPage())->toBe(1);
});

it('changing sort direction on employee dashboard resets to page 1', function () {
    $employee = paginationEmployee();
    create30EmployeeTickets($employee);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);
    $component->call('gotoPage', 2);

    $component->set('sortDir', 'asc');

    expect($component->viewData('tickets')->currentPage())->toBe(1);
});

it('changing status filter on employee dashboard resets to page 1', function () {
    $employee = paginationEmployee();
    create30EmployeeTickets($employee);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);
    $component->call('gotoPage', 2);

    $component->set('statusFilter', 'open');

    expect($component->viewData('tickets')->currentPage())->toBe(1);
});

it('changing search on employee dashboard resets to page 1', function () {
    $employee = paginationEmployee();
    create30EmployeeTickets($employee);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);
    $component->call('gotoPage', 2);

    $component->set('search', 'test');

    expect($component->viewData('tickets')->currentPage())->toBe(1);
});

// ── Tech Dashboard Queue Pagination ───────────────────────────────────────────

it('tech dashboard queue page 1 returns 25 when 30 unassigned tickets exist', function () {
    $group = Group::factory()->create();
    $tech = User::factory()->create(['is_tech' => true]);
    DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $tech->id]);

    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'assigned_to' => null,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($tech)->test(TechDashboard::class);
    $queueTickets = $component->viewData('queueTickets');

    expect($queueTickets->count())->toBe(25)
        ->and($queueTickets->total())->toBe(30);
});

it('tech dashboard queue page 2 returns remaining 5 tickets', function () {
    $group = Group::factory()->create();
    $tech = User::factory()->create(['is_tech' => true]);
    DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $tech->id]);

    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'assigned_to' => null,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($tech)->test(TechDashboard::class);
    $component->call('gotoPage', 2, 'queuePage');

    expect($component->viewData('queueTickets')->count())->toBe(5);
});

it('changing search on tech dashboard resets queue to page 1', function () {
    $group = Group::factory()->create();
    $tech = User::factory()->create(['is_tech' => true]);
    DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $tech->id]);

    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'assigned_to' => null,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($tech)->test(TechDashboard::class);
    $component->call('gotoPage', 2, 'queuePage');

    $component->set('search', 'something');

    expect($component->viewData('queueTickets')->currentPage())->toBe(1);
});

it('changing sort on tech dashboard resets queue to page 1', function () {
    $group = Group::factory()->create();
    $tech = User::factory()->create(['is_tech' => true]);
    DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $tech->id]);

    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'assigned_to' => null,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($tech)->test(TechDashboard::class);
    $component->call('gotoPage', 2, 'queuePage');

    $component->set('sortBy', 'created_at');

    expect($component->viewData('queueTickets')->currentPage())->toBe(1);
});

// ── Manager Dashboard Activity Pagination ─────────────────────────────────────

it('manager dashboard activity feed page 1 returns 25 when 30 tickets exist', function () {
    $manager = User::factory()->create(['is_super_user' => true]);
    $group = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);
    $activity = $component->viewData('recentActivity');

    expect($activity->count())->toBe(25)
        ->and($activity->total())->toBe(30);
});

it('manager dashboard activity page 2 returns remaining 5', function () {
    $manager = User::factory()->create(['is_super_user' => true]);
    $group = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);
    $component->call('gotoPage', 2, 'activityPage');

    expect($component->viewData('recentActivity')->count())->toBe(5);
});

it('changing sort on manager dashboard resets activity to page 1', function () {
    $manager = User::factory()->create(['is_super_user' => true]);
    $group = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(30)->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);
    $component->call('gotoPage', 2, 'activityPage');

    $component->set('sortBy', 'created_at');

    expect($component->viewData('recentActivity')->currentPage())->toBe(1);
});

// ── Config-driven per_page ────────────────────────────────────────────────────

it('employee dashboard respects ticketing.dashboard.per_page config', function () {
    config(['ticketing.dashboard.per_page' => 10]);

    $employee = paginationEmployee();
    $group = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    Ticket::factory()->count(15)->create([
        'requester_id' => $employee->id,
        'group_id' => $group->id,
        'category_id' => $category->id,
        'status' => TicketStatus::AwaitingAssignment,
    ]);

    $component = Livewire::actingAs($employee)->test(EmployeeDashboard::class);

    expect($component->viewData('tickets')->count())->toBe(10);
});
