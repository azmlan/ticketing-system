<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Reporting\Reports\AvgResolutionTimeReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeResolvedTicket(array $attrs = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now(),
    ], $attrs));
}

it('returns rows for resolved tickets grouped by resolved date', function () {
    makeResolvedTicket(['created_at' => '2026-04-01 08:00:00', 'resolved_at' => '2026-04-01 10:00:00']);
    makeResolvedTicket(['created_at' => '2026-04-01 09:00:00', 'resolved_at' => '2026-04-01 13:00:00']);
    makeResolvedTicket(['created_at' => '2026-04-03 08:00:00', 'resolved_at' => '2026-04-03 12:00:00']);

    $report = new AvgResolutionTimeReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toHaveCount(2);
    expect($rows->pluck('period')->toArray())->toContain('2026-04-01');
    expect($rows->pluck('period')->toArray())->toContain('2026-04-03');
});

it('uses resolved_at for date range filter, not created_at', function () {
    // Ticket created before range but resolved inside range
    makeResolvedTicket([
        'created_at'  => '2026-03-25 08:00:00',
        'resolved_at' => '2026-04-02 10:00:00',
    ]);
    // Ticket created inside range but resolved outside range
    makeResolvedTicket([
        'created_at'  => '2026-04-05 08:00:00',
        'resolved_at' => '2026-05-01 10:00:00',
    ]);

    $report = new AvgResolutionTimeReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['period'])->toBe('2026-04-02');
});

it('returns avg_hours and count keys', function () {
    makeResolvedTicket(['created_at' => '2026-04-10 08:00:00', 'resolved_at' => '2026-04-10 10:00:00']);

    $report = new AvgResolutionTimeReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows->first())->toHaveKeys(['period', 'avg_hours', 'count']);
});

it('count reflects number of tickets resolved on that day', function () {
    makeResolvedTicket(['created_at' => '2026-04-05 08:00:00', 'resolved_at' => '2026-04-05 09:00:00']);
    makeResolvedTicket(['created_at' => '2026-04-05 10:00:00', 'resolved_at' => '2026-04-05 12:00:00']);

    $report = new AvgResolutionTimeReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows->first()['count'])->toBe(2);
});

it('excludes tickets with no resolved_at', function () {
    Ticket::factory()->create([
        'status'      => TicketStatus::InProgress,
        'resolved_at' => null,
        'created_at'  => '2026-04-10',
    ]);

    $report = new AvgResolutionTimeReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toBeEmpty();
});

it('filters by tech', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    makeResolvedTicket(['assigned_to' => $techA->id, 'resolved_at' => '2026-04-05 10:00:00']);
    makeResolvedTicket(['assigned_to' => $techB->id, 'resolved_at' => '2026-04-05 12:00:00']);

    $report = new AvgResolutionTimeReport;
    $rows   = $report->run([
        'date_from' => '2026-04-01',
        'date_to'   => '2026-04-30',
        'tech_id'   => $techA->id,
    ]);

    expect($rows->sum('count'))->toBe(1);
});

it('returns empty collection when no tickets match', function () {
    $report = new AvgResolutionTimeReport;
    $rows   = $report->run(['date_from' => '2020-01-01', 'date_to' => '2020-01-31']);

    expect($rows)->toBeEmpty();
});
