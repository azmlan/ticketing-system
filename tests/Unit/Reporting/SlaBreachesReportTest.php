<?php

use App\Modules\Reporting\Reports\SlaBreachesReport;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeBreachedTicket(User $tech, array $slaAttrs = []): Ticket
{
    $ticket = Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'created_at'  => now(),
    ]);

    TicketSla::factory()->create(array_merge([
        'ticket_id'                  => $ticket->id,
        'resolution_status'          => 'breached',
        'resolution_target_minutes'  => 480,
        'resolution_elapsed_minutes' => 600,
    ], $slaAttrs));

    return $ticket;
}

it('returns breached tickets in the date range', function () {
    $tech = User::factory()->tech()->create();
    makeBreachedTicket($tech);

    $report = new SlaBreachesReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toHaveCount(1);
});

it('excludes non-breached tickets', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create(['assigned_to' => $tech->id, 'created_at' => now()]);
    TicketSla::factory()->create(['ticket_id' => $ticket->id, 'resolution_status' => 'on_track']);

    $report = new SlaBreachesReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toBeEmpty();
});

it('converts minutes to hours in the output', function () {
    $tech = User::factory()->tech()->create();
    makeBreachedTicket($tech, [
        'resolution_target_minutes'  => 120,
        'resolution_elapsed_minutes' => 180,
    ]);

    $report = new SlaBreachesReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['target_hours'])->toBe(2.0);
    expect($rows->first()['actual_hours'])->toBe(3.0);
});

it('shows none label for target_hours when target is null', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create(['assigned_to' => $tech->id, 'created_at' => now()]);
    TicketSla::factory()->create([
        'ticket_id'                  => $ticket->id,
        'resolution_status'          => 'breached',
        'resolution_target_minutes'  => null,
        'resolution_elapsed_minutes' => 240,
    ]);

    $report = new SlaBreachesReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['target_hours'])->toBe(__('reports.labels.none'));
});

it('includes the tech name from the assigned user', function () {
    $tech = User::factory()->tech()->create(['full_name' => 'Test Tech']);
    makeBreachedTicket($tech);

    $report = new SlaBreachesReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['tech_name'])->toBe('Test Tech');
});

it('returns empty collection when no breaches in range', function () {
    $tech = User::factory()->tech()->create();
    makeBreachedTicket($tech);

    $report = new SlaBreachesReport;
    $rows   = $report->run(['date_from' => '2000-01-01', 'date_to' => '2000-01-31']);

    expect($rows)->toBeEmpty();
});

it('filters by tech_id', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    makeBreachedTicket($techA);
    makeBreachedTicket($techB);

    $report = new SlaBreachesReport;
    $rows   = $report->run([
        'date_from' => now()->subDay()->toDateString(),
        'date_to'   => now()->addDay()->toDateString(),
        'tech_id'   => $techA->id,
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['tech_name'])->toBe($techA->full_name);
});
