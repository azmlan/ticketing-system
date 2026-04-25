<?php

use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Reporting\Livewire\ReportPage;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeSlaCsatReporter(): User
{
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $perm = Permission::firstOrCreate(
        ['key' => 'system.view-reports'],
        ['name_ar' => 'عرض التقارير', 'name_en' => 'View Reports', 'group_key' => 'system']
    );
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);

    return $user;
}

// ─── sla_compliance ───────────────────────────────────────────────────────────

it('can switch to sla_compliance report', function () {
    $reporter = makeSlaCsatReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'sla_compliance')
        ->assertSet('reportType', 'sla_compliance');
});

it('sla_compliance report returns rows for tickets with SLA records', function () {
    $reporter = makeSlaCsatReporter();
    $ticket   = Ticket::factory()->create(['created_at' => now()]);

    TicketSla::factory()->create([
        'ticket_id'         => $ticket->id,
        'resolution_status' => 'on_track',
    ]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'sla_compliance')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── sla_breaches ─────────────────────────────────────────────────────────────

it('can switch to sla_breaches report', function () {
    $reporter = makeSlaCsatReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'sla_breaches')
        ->assertSet('reportType', 'sla_breaches');
});

it('sla_breaches report returns rows for breached tickets', function () {
    $reporter = makeSlaCsatReporter();
    $tech     = User::factory()->tech()->create();
    $ticket   = Ticket::factory()->create(['assigned_to' => $tech->id, 'created_at' => now()]);

    TicketSla::factory()->create([
        'ticket_id'         => $ticket->id,
        'resolution_status' => 'breached',
    ]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'sla_breaches')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── csat_overview ────────────────────────────────────────────────────────────

it('can switch to csat_overview report', function () {
    $reporter = makeSlaCsatReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'csat_overview')
        ->assertSet('reportType', 'csat_overview');
});

it('csat_overview report returns rows for submitted ratings', function () {
    $reporter  = makeSlaCsatReporter();
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);
    $ticket    = Ticket::factory()->create(['assigned_to' => $tech->id]);

    CsatRating::factory()->submitted()->create([
        'ticket_id'    => $ticket->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'created_at'   => now(),
    ]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'csat_overview')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── csat_by_tech ─────────────────────────────────────────────────────────────

it('can switch to csat_by_tech report', function () {
    $reporter = makeSlaCsatReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'csat_by_tech')
        ->assertSet('reportType', 'csat_by_tech');
});

it('csat_by_tech report returns rows for techs with submitted ratings', function () {
    $reporter  = makeSlaCsatReporter();
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);
    $ticket    = Ticket::factory()->create(['assigned_to' => $tech->id]);

    CsatRating::factory()->submitted()->create([
        'ticket_id'    => $ticket->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'created_at'   => now(),
    ]);

    $component = Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'csat_by_tech')
        ->set('dateFrom', now()->subDay()->toDateString())
        ->set('dateTo', now()->addDay()->toDateString());

    expect($component->viewData('rows'))->not->toBeEmpty();
});

// ─── All 4 types appear in the types list ─────────────────────────────────────

it('all 4 SLA+CSAT report types appear in the types list', function () {
    $reporter = makeSlaCsatReporter();

    $component = Livewire::actingAs($reporter)->test(ReportPage::class);
    $types     = $component->viewData('reportTypes');

    expect($types)->toContain('sla_compliance');
    expect($types)->toContain('sla_breaches');
    expect($types)->toContain('csat_overview');
    expect($types)->toContain('csat_by_tech');
});

// ─── All 12 report types accounted for ───────────────────────────────────────

it('report service exposes exactly 12 report types', function () {
    $reporter = makeSlaCsatReporter();

    $component = Livewire::actingAs($reporter)->test(ReportPage::class);
    $types     = $component->viewData('reportTypes');

    expect(count($types))->toBe(12);
});

// ─── No data shown for empty results ──────────────────────────────────────────

it('shows no_data message for sla_compliance with no SLA records', function () {
    $reporter = makeSlaCsatReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'sla_compliance')
        ->set('dateFrom', '2000-01-01')
        ->set('dateTo', '2000-01-31')
        ->assertSeeText(__('reports.labels.no_data'));
});

it('shows no_data message for csat_overview with no ratings', function () {
    $reporter = makeSlaCsatReporter();

    Livewire::actingAs($reporter)
        ->test(ReportPage::class)
        ->set('reportType', 'csat_overview')
        ->set('dateFrom', '2000-01-01')
        ->set('dateTo', '2000-01-31')
        ->assertSeeText(__('reports.labels.no_data'));
});
