<?php

use App\Modules\Admin\Models\Group;
use App\Modules\Reporting\Reports\SlaComplianceReport;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Enums\TicketPriority;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeComplianceSlaTicket(string $priority, string $resolutionStatus): Ticket
{
    $ticket = Ticket::factory()->create([
        'priority'   => $priority,
        'created_at' => now(),
    ]);

    TicketSla::factory()->create([
        'ticket_id'         => $ticket->id,
        'resolution_status' => $resolutionStatus,
    ]);

    return $ticket;
}

it('returns one row per priority with SLA records', function () {
    makeComplianceSlaTicket('high', 'on_track');
    makeComplianceSlaTicket('high', 'breached');
    makeComplianceSlaTicket('low', 'on_track');

    $report = new SlaComplianceReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toHaveCount(2);
});

it('calculates compliance_pct correctly', function () {
    makeComplianceSlaTicket('critical', 'on_track');
    makeComplianceSlaTicket('critical', 'on_track');
    makeComplianceSlaTicket('critical', 'warning');
    makeComplianceSlaTicket('critical', 'breached');

    $report = new SlaComplianceReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    $row = $rows->first();
    // 3 within SLA out of 4 = 75.0%
    expect($row['total_count'])->toBe(4);
    expect($row['within_sla_count'])->toBe(3);
    expect($row['compliance_pct'])->toBe('75.0%');
});

it('counts warning as within SLA', function () {
    makeComplianceSlaTicket('medium', 'warning');

    $report = new SlaComplianceReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['within_sla_count'])->toBe(1);
    expect($rows->first()['compliance_pct'])->toBe('100.0%');
});

it('returns empty when no SLA-tracked tickets in range', function () {
    makeComplianceSlaTicket('high', 'on_track');

    $report = new SlaComplianceReport;
    $rows   = $report->run(['date_from' => '2000-01-01', 'date_to' => '2000-01-31']);

    expect($rows)->toBeEmpty();
});

it('excludes tickets without SLA records', function () {
    Ticket::factory()->create(['priority' => TicketPriority::High, 'created_at' => now()]);

    $report = new SlaComplianceReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toBeEmpty();
});

it('filters by priority', function () {
    makeComplianceSlaTicket('high', 'on_track');
    makeComplianceSlaTicket('low', 'breached');

    $report = new SlaComplianceReport;
    $rows   = $report->run([
        'date_from' => now()->subDay()->toDateString(),
        'date_to'   => now()->addDay()->toDateString(),
        'priority'  => 'high',
    ]);

    expect($rows)->toHaveCount(1);
});

it('orders results by priority severity (critical first)', function () {
    makeComplianceSlaTicket('low', 'on_track');
    makeComplianceSlaTicket('critical', 'on_track');
    makeComplianceSlaTicket('medium', 'on_track');

    $report = new SlaComplianceReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    $priorities = $rows->pluck('priority')->toArray();
    expect($priorities[0])->toBe(__('tickets.priority.critical'));
    expect($priorities[2])->toBe(__('tickets.priority.low'));
});
