<?php

use App\Modules\Admin\Models\Group;
use App\Modules\Reporting\Reports\TeamWorkloadReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('counts open tickets per tech', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    Ticket::factory()->count(3)->create(['assigned_to' => $techA->id, 'status' => TicketStatus::InProgress, 'created_at' => now()]);
    Ticket::factory()->count(1)->create(['assigned_to' => $techB->id, 'status' => TicketStatus::InProgress, 'created_at' => now()]);

    $report = new TeamWorkloadReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    $rowA = $rows->firstWhere('tech_name', $techA->full_name);
    $rowB = $rows->firstWhere('tech_name', $techB->full_name);

    expect($rowA['open_count'])->toBe(3);
    expect($rowB['open_count'])->toBe(1);
});

it('excludes resolved tickets', function () {
    $tech = User::factory()->tech()->create();

    Ticket::factory()->create(['assigned_to' => $tech->id, 'status' => TicketStatus::InProgress, 'created_at' => now()]);
    Ticket::factory()->create(['assigned_to' => $tech->id, 'status' => TicketStatus::Resolved, 'resolved_at' => now(), 'created_at' => now()]);

    $report = new TeamWorkloadReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    $row = $rows->firstWhere('tech_name', $tech->full_name);
    expect($row['open_count'])->toBe(1);
});

it('excludes closed tickets', function () {
    $tech = User::factory()->tech()->create();
    Ticket::factory()->create(['assigned_to' => $tech->id, 'status' => TicketStatus::Closed, 'closed_at' => now(), 'created_at' => now()]);

    $report = new TeamWorkloadReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toBeEmpty();
});

it('excludes cancelled tickets', function () {
    $tech = User::factory()->tech()->create();
    Ticket::factory()->create(['assigned_to' => $tech->id, 'status' => TicketStatus::Cancelled, 'cancelled_at' => now(), 'created_at' => now()]);

    $report = new TeamWorkloadReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toBeEmpty();
});

it('excludes unassigned tickets', function () {
    Ticket::factory()->create(['assigned_to' => null, 'status' => TicketStatus::AwaitingAssignment, 'created_at' => now()]);

    $report = new TeamWorkloadReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toBeEmpty();
});

it('filters by group_id', function () {
    $groupA = Group::factory()->create();
    $groupB = Group::factory()->create();
    $tech   = User::factory()->tech()->create();

    Ticket::factory()->create(['assigned_to' => $tech->id, 'group_id' => $groupA->id, 'status' => TicketStatus::InProgress, 'created_at' => now()]);
    Ticket::factory()->create(['assigned_to' => $tech->id, 'group_id' => $groupB->id, 'status' => TicketStatus::InProgress, 'created_at' => now()]);

    $report = new TeamWorkloadReport;
    $rows   = $report->run([
        'date_from' => now()->subDay()->toDateString(),
        'date_to'   => now()->addDay()->toDateString(),
        'group_id'  => $groupA->id,
    ]);

    expect($rows->sum('open_count'))->toBe(1);
});

it('returns rows with tech_name and open_count keys', function () {
    $tech = User::factory()->tech()->create();
    Ticket::factory()->create(['assigned_to' => $tech->id, 'status' => TicketStatus::InProgress, 'created_at' => now()]);

    $report = new TeamWorkloadReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first())->toHaveKeys(['tech_name', 'open_count']);
});

it('returns empty when no open tickets', function () {
    $report = new TeamWorkloadReport;
    $rows   = $report->run(['date_from' => '2020-01-01', 'date_to' => '2020-01-31']);

    expect($rows)->toBeEmpty();
});
