<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Reporting\Livewire\ReportPage;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makePerformanceReporter(): User
{
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $perm = Permission::firstOrCreate(
        ['key' => 'system.view-reports'],
        ['name_ar' => 'عرض التقارير', 'name_en' => 'View Reports', 'group_key' => 'system']
    );
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);

    return $user;
}

function makeAssignedResolvedTicket(User $tech): Ticket
{
    return Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now(),
        'created_at'  => now(),
    ]);
}

// ─── avg_resolution_time ─────────────────────────────────────────────────────

it('can switch to avg_resolution_time report', function () {
    $reporter = makePerformanceReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'avg_resolution_time')
        ->assertSet('reportType', 'avg_resolution_time');
});

it('avg_resolution_time report returns rows for resolved tickets', function () {
    $reporter = makePerformanceReporter();
    $tech     = User::factory()->tech()->create();

    Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now(),
        'created_at'  => now()->subHours(3),
    ]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'avg_resolution_time')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── tech_performance ────────────────────────────────────────────────────────

it('can switch to tech_performance report', function () {
    $reporter = makePerformanceReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tech_performance')
        ->assertSet('reportType', 'tech_performance');
});

it('tech_performance report returns rows for techs with resolved tickets', function () {
    $reporter = makePerformanceReporter();
    $tech     = User::factory()->tech()->create();

    makeAssignedResolvedTicket($tech);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'tech_performance')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── team_workload ────────────────────────────────────────────────────────────

it('can switch to team_workload report', function () {
    $reporter = makePerformanceReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'team_workload')
        ->assertSet('reportType', 'team_workload');
});

it('team_workload report returns rows for techs with open tickets', function () {
    $reporter = makePerformanceReporter();
    $tech     = User::factory()->tech()->create();

    Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'status'      => TicketStatus::InProgress,
        'created_at'  => now(),
    ]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'team_workload')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── escalation_summary ───────────────────────────────────────────────────────

it('can switch to escalation_summary report', function () {
    $reporter = makePerformanceReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'escalation_summary')
        ->assertSet('reportType', 'escalation_summary');
});

it('escalation_summary report returns rows for condition reports in range', function () {
    $reporter = makePerformanceReporter();

    ConditionReport::factory()->create(['created_at' => now(), 'status' => 'pending']);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'escalation_summary')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── All 4 types present in the report type list ─────────────────────────────

it('all 4 performance report types appear in the types list', function () {
    $reporter = makePerformanceReporter();

    $component = Livewire::actingAs($reporter)->test(ReportPage::class);
    $types     = $component->viewData('reportTypes');

    expect($types)->toContain('avg_resolution_time');
    expect($types)->toContain('tech_performance');
    expect($types)->toContain('team_workload');
    expect($types)->toContain('escalation_summary');
});

// ─── Access control ───────────────────────────────────────────────────────────

it('regular user without permission cannot access performance reports', function () {
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);

    Livewire::actingAs($user)
        ->test(ReportPage::class)
        ->assertForbidden();
});

it('super user can access performance bundle reports', function () {
    $su = User::factory()->superUser()->create();

    Livewire::actingAs($su)
        ->test(ReportPage::class)
        ->set('reportType', 'tech_performance')
        ->assertOk();
});
