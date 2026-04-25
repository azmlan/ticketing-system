<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Reporting\Reports\TechPerformanceReport;
use App\Modules\Shared\Models\User;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeResolvedForTech(User $tech, array $attrs = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'assigned_to' => $tech->id,
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now(),
    ], $attrs));
}

it('returns one row per tech with resolved tickets in range', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    makeResolvedForTech($techA, ['resolved_at' => '2026-04-10 10:00:00']);
    makeResolvedForTech($techA, ['resolved_at' => '2026-04-11 10:00:00']);
    makeResolvedForTech($techB, ['resolved_at' => '2026-04-12 10:00:00']);

    $report = new TechPerformanceReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toHaveCount(2);
    $techARow = $rows->firstWhere('tech_name', $techA->full_name);
    expect($techARow['resolved_count'])->toBe(2);
});

it('calculates avg_csat from submitted ratings', function () {
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);

    $ticketA = makeResolvedForTech($tech, ['resolved_at' => '2026-04-10']);
    $ticketB = makeResolvedForTech($tech, ['resolved_at' => '2026-04-10']);

    CsatRating::factory()->submitted()->create([
        'ticket_id'    => $ticketA->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'rating'       => 5,
    ]);
    CsatRating::factory()->submitted()->create([
        'ticket_id'    => $ticketB->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'rating'       => 3,
    ]);

    $report = new TechPerformanceReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    $row = $rows->firstWhere('tech_name', $tech->full_name);
    expect($row['avg_csat'])->toBe(4.0);
});

it('shows none label when no submitted ratings exist', function () {
    $tech = User::factory()->tech()->create();
    makeResolvedForTech($tech, ['resolved_at' => '2026-04-10']);

    $report = new TechPerformanceReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    $row = $rows->firstWhere('tech_name', $tech->full_name);
    expect($row['avg_csat'])->toBe(__('reports.labels.none'));
});

it('pending csat ratings are not included in avg_csat', function () {
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);
    $ticket    = makeResolvedForTech($tech, ['resolved_at' => '2026-04-10']);

    CsatRating::factory()->create([
        'ticket_id'    => $ticket->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'status'       => 'pending',
        'rating'       => null,
    ]);

    $report = new TechPerformanceReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    $row = $rows->firstWhere('tech_name', $tech->full_name);
    expect($row['avg_csat'])->toBe(__('reports.labels.none'));
});

it('calculates sla_compliance_pct correctly', function () {
    $tech = User::factory()->tech()->create();

    $ticketA = makeResolvedForTech($tech, ['resolved_at' => '2026-04-10']);
    $ticketB = makeResolvedForTech($tech, ['resolved_at' => '2026-04-11']);
    $ticketC = makeResolvedForTech($tech, ['resolved_at' => '2026-04-12']);
    $ticketD = makeResolvedForTech($tech, ['resolved_at' => '2026-04-13']);

    TicketSla::factory()->create(['ticket_id' => $ticketA->id, 'resolution_status' => 'on_track']);
    TicketSla::factory()->create(['ticket_id' => $ticketB->id, 'resolution_status' => 'warning']);
    TicketSla::factory()->create(['ticket_id' => $ticketC->id, 'resolution_status' => 'breached']);
    TicketSla::factory()->create(['ticket_id' => $ticketD->id, 'resolution_status' => 'on_track']);

    $report = new TechPerformanceReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    $row = $rows->firstWhere('tech_name', $tech->full_name);
    // 3 compliant out of 4 = 75%
    expect($row['sla_compliance_pct'])->toBe('75.0%');
});

it('shows none label for sla_compliance_pct when no sla records', function () {
    $tech = User::factory()->tech()->create();
    makeResolvedForTech($tech, ['resolved_at' => '2026-04-10']);

    $report = new TechPerformanceReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    $row = $rows->firstWhere('tech_name', $tech->full_name);
    expect($row['sla_compliance_pct'])->toBe(__('reports.labels.none'));
});

it('filters by tech_id', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    makeResolvedForTech($techA, ['resolved_at' => '2026-04-10']);
    makeResolvedForTech($techB, ['resolved_at' => '2026-04-10']);

    $report = new TechPerformanceReport;
    $rows   = $report->run([
        'date_from' => '2026-04-01',
        'date_to'   => '2026-04-30',
        'tech_id'   => $techA->id,
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['tech_name'])->toBe($techA->full_name);
});

it('excludes tickets that are not resolved or closed', function () {
    $tech = User::factory()->tech()->create();
    Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'status'      => TicketStatus::InProgress,
    ]);

    $report = new TechPerformanceReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toBeEmpty();
});
