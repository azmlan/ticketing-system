<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Reporting\Livewire\ReportPage;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketPriority;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeReporter(): User
{
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $perm = Permission::firstOrCreate(
        ['key' => 'system.view-reports'],
        ['name_ar' => 'عرض التقارير', 'name_en' => 'View Reports', 'group_key' => 'system']
    );
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);

    return $user;
}

function makeReporterTicket(array $attrs = []): Ticket
{
    $category  = Category::factory()->create();
    $group     = Group::factory()->create();
    $requester = User::factory()->create(['is_tech' => false]);

    return Ticket::factory()->create(array_merge([
        'requester_id' => $requester->id,
        'category_id'  => $category->id,
        'group_id'     => $group->id,
    ], $attrs));
}

// ─── Access control ───────────────────────────────────────────────────────────

it('allows super user to access the report page', function () {
    $manager = User::factory()->create(['is_super_user' => true, 'is_tech' => true]);

    Livewire::actingAs($manager)
        ->test(ReportPage::class)
        ->assertOk();
});

it('allows user with system.view-reports permission', function () {
    $reporter = makeReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->assertOk();
});

it('denies access to regular users without permission', function () {
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);

    Livewire::actingAs($user)
        ->test(ReportPage::class)
        ->assertForbidden();
});

// ─── Default state ────────────────────────────────────────────────────────────

it('defaults to ticket_volume report with current month date range', function () {
    $reporter = makeReporter();

    $component = Livewire::actingAs($reporter)->test(ReportPage::class);

    expect($component->get('reportType'))->toBe('ticket_volume');
    expect($component->get('dateFrom'))->toBe(now()->startOfMonth()->toDateString());
    expect($component->get('dateTo'))->toBe(now()->toDateString());
});

// ─── Report type switching ────────────────────────────────────────────────────

it('switches to tickets_by_status report', function () {
    $reporter = makeReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tickets_by_status')
        ->assertSet('reportType', 'tickets_by_status');
});

it('switches to tickets_by_category report', function () {
    $reporter = makeReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tickets_by_category')
        ->assertSet('reportType', 'tickets_by_category');
});

it('switches to tickets_by_priority report', function () {
    $reporter = makeReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tickets_by_priority')
        ->assertSet('reportType', 'tickets_by_priority');
});

// ─── TicketVolume report results ──────────────────────────────────────────────

it('ticket_volume report returns rows for tickets in date range', function () {
    $reporter = makeReporter();

    makeReporterTicket(['created_at' => now()->startOfMonth()]);
    makeReporterTicket(['created_at' => now()->startOfMonth()]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'ticket_volume')
        ->set('dateFrom', now()->startOfMonth()->toDateString())
        ->set('dateTo', now()->endOfMonth()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── tickets_by_status results ────────────────────────────────────────────────

it('tickets_by_status returns status distribution', function () {
    $reporter = makeReporter();

    makeReporterTicket(['status' => TicketStatus::Resolved, 'created_at' => now()]);
    makeReporterTicket(['status' => TicketStatus::Closed, 'created_at' => now()]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tickets_by_status')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── tickets_by_category results ─────────────────────────────────────────────

it('tickets_by_category returns per-category counts', function () {
    $reporter  = makeReporter();
    $category  = Category::factory()->create();
    $group     = Group::factory()->create();
    $requester = User::factory()->create(['is_tech' => false]);

    Ticket::factory()->count(3)->create([
        'requester_id' => $requester->id,
        'category_id'  => $category->id,
        'group_id'     => $group->id,
        'created_at'   => now(),
    ]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tickets_by_category')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    $rows = $component->viewData('rows');
    expect($rows)->not->toBeEmpty();
    expect($rows->first()['count'])->toBeGreaterThanOrEqual(3);
});

// ─── tickets_by_priority results ─────────────────────────────────────────────

it('tickets_by_priority returns per-priority counts', function () {
    $reporter = makeReporter();

    makeReporterTicket(['priority' => TicketPriority::High, 'created_at' => now()]);
    makeReporterTicket(['priority' => TicketPriority::High, 'created_at' => now()]);
    makeReporterTicket(['priority' => TicketPriority::Low, 'created_at' => now()]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tickets_by_priority')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    $rows = $component->viewData('rows');
    expect($rows)->not->toBeEmpty();
});

// ─── Filter combinations ──────────────────────────────────────────────────────

it('category filter narrows ticket_volume results', function () {
    $reporter = makeReporter();
    $catA     = Category::factory()->create();
    $catB     = Category::factory()->create();
    $group    = Group::factory()->create();
    $req      = User::factory()->create(['is_tech' => false]);

    Ticket::factory()->count(3)->create(['requester_id' => $req->id, 'category_id' => $catA->id, 'group_id' => $group->id, 'created_at' => now()]);
    Ticket::factory()->count(2)->create(['requester_id' => $req->id, 'category_id' => $catB->id, 'group_id' => $group->id, 'created_at' => now()]);

    $all = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'ticket_volume')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString())
        ->viewData('rows');

    $filtered = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'ticket_volume')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString())
        ->set('categoryId', $catA->id)
        ->viewData('rows');

    expect($filtered->sum('count'))->toBeLessThan($all->sum('count'));
});

it('no data message shown when date range yields no results', function () {
    $reporter = makeReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'ticket_volume')
        ->set('dateFrom', '2000-01-01')
        ->set('dateTo', '2000-01-31')
        ->assertSeeText(__('reports.labels.no_data'));
});

it('resetFilters clears optional filter fields', function () {
    $reporter = makeReporter();

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('priority', 'high')
        ->set('status', 'resolved')
        ->call('resetFilters');

    expect($component->get('priority'))->toBe('');
    expect($component->get('status'))->toBe('');
});

// ─── Route access ─────────────────────────────────────────────────────────────

it('GET /reports redirects unauthenticated users', function () {
    $this->get('/reports')->assertRedirect('/login');
});

it('GET /reports returns 200 for super user', function () {
    $manager = User::factory()->create(['is_super_user' => true, 'is_tech' => true]);

    $this->actingAs($manager)->get('/reports')->assertOk();
});
