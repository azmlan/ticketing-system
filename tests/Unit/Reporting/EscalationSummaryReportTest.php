<?php

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Reporting\Reports\EscalationSummaryReport;
use App\Modules\Shared\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a row per day with triggered approved rejected counts', function () {
    ConditionReport::factory()->create(['created_at' => '2026-04-05 10:00:00', 'status' => 'pending']);
    ConditionReport::factory()->approved()->create(['created_at' => '2026-04-05 11:00:00']);
    ConditionReport::factory()->rejected()->create(['created_at' => '2026-04-05 12:00:00']);

    $report = new EscalationSummaryReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toHaveCount(1);
    $row = $rows->first();
    expect($row['period'])->toBe('2026-04-05');
    expect($row['triggered'])->toBe(3);
    expect($row['approved'])->toBe(1);
    expect($row['rejected'])->toBe(1);
});

it('groups by created_at date, not reviewed_at', function () {
    ConditionReport::factory()->create(['created_at' => '2026-04-05', 'status' => 'pending']);
    ConditionReport::factory()->create(['created_at' => '2026-04-07', 'status' => 'pending']);

    $report = new EscalationSummaryReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toHaveCount(2);
});

it('applies date range filter to created_at', function () {
    ConditionReport::factory()->create(['created_at' => '2026-03-31', 'status' => 'pending']);
    ConditionReport::factory()->create(['created_at' => '2026-04-10', 'status' => 'pending']);
    ConditionReport::factory()->create(['created_at' => '2026-05-01', 'status' => 'pending']);

    $report = new EscalationSummaryReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['period'])->toBe('2026-04-10');
});

it('filters by tech_id', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    ConditionReport::factory()->create(['tech_id' => $techA->id, 'created_at' => '2026-04-10', 'status' => 'pending']);
    ConditionReport::factory()->create(['tech_id' => $techB->id, 'created_at' => '2026-04-10', 'status' => 'pending']);

    $report = new EscalationSummaryReport;
    $rows   = $report->run([
        'date_from' => '2026-04-01',
        'date_to'   => '2026-04-30',
        'tech_id'   => $techA->id,
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['triggered'])->toBe(1);
});

it('returns rows with period triggered approved rejected keys', function () {
    ConditionReport::factory()->create(['created_at' => '2026-04-10', 'status' => 'pending']);

    $report = new EscalationSummaryReport;
    $rows   = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows->first())->toHaveKeys(['period', 'triggered', 'approved', 'rejected']);
});

it('returns empty collection when no records in range', function () {
    $report = new EscalationSummaryReport;
    $rows   = $report->run(['date_from' => '2020-01-01', 'date_to' => '2020-01-31']);

    expect($rows)->toBeEmpty();
});
