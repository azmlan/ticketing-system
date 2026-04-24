<?php

use App\Modules\SLA\Models\SlaPauseLog;
use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Models\Ticket;

// ─── SlaPolicy ────────────────────────────────────────────────────────────────

it('SlaPolicy uses HasUlids', function () {
    expect(in_array(
        \Illuminate\Database\Eloquent\Concerns\HasUlids::class,
        class_uses_recursive(SlaPolicy::class)
    ))->toBeTrue();
});

it('SlaPolicy factory creates a valid record', function () {
    $policy = SlaPolicy::factory()->high()->create();

    expect($policy->priority)->toBe('high');
    expect($policy->response_target_minutes)->toBe(60);
    expect($policy->resolution_target_minutes)->toBe(480);
    expect($policy->use_24x7)->toBeFalse();
});

it('SlaPolicy critical factory sets use_24x7 true', function () {
    $policy = SlaPolicy::factory()->critical()->create();

    expect($policy->priority)->toBe('critical');
    expect($policy->use_24x7)->toBeTrue();
});

it('SlaPolicy casts use_24x7 to boolean', function () {
    $policy = SlaPolicy::factory()->low()->create();
    expect($policy->use_24x7)->toBeBool();
});

it('SlaPolicy casts target minutes to integer', function () {
    $policy = SlaPolicy::factory()->medium()->create();
    expect($policy->response_target_minutes)->toBeInt();
    expect($policy->resolution_target_minutes)->toBeInt();
});

// ─── TicketSla ────────────────────────────────────────────────────────────────

it('TicketSla uses HasUlids', function () {
    expect(in_array(
        \Illuminate\Database\Eloquent\Concerns\HasUlids::class,
        class_uses_recursive(TicketSla::class)
    ))->toBeTrue();
});

it('TicketSla factory creates a valid record with defaults', function () {
    $sla = TicketSla::factory()->create();

    expect($sla->response_status)->toBe('on_track');
    expect($sla->resolution_status)->toBe('on_track');
    expect($sla->is_clock_running)->toBeTrue();
    expect($sla->response_elapsed_minutes)->toBe(0);
    expect($sla->resolution_elapsed_minutes)->toBe(0);
    expect($sla->response_met_at)->toBeNull();
});

it('TicketSla paused factory state sets is_clock_running false', function () {
    $sla = TicketSla::factory()->paused()->create();

    expect($sla->is_clock_running)->toBeFalse();
    expect($sla->last_clock_start)->toBeNull();
});

it('TicketSla warning factory state sets both statuses to warning', function () {
    $sla = TicketSla::factory()->warning()->create();

    expect($sla->response_status)->toBe('warning');
    expect($sla->resolution_status)->toBe('warning');
});

it('TicketSla breached factory state sets both statuses to breached', function () {
    $sla = TicketSla::factory()->breached()->create();

    expect($sla->response_status)->toBe('breached');
    expect($sla->resolution_status)->toBe('breached');
});

it('TicketSla withoutTargets factory state sets targets to null', function () {
    $sla = TicketSla::factory()->withoutTargets()->create();

    expect($sla->response_target_minutes)->toBeNull();
    expect($sla->resolution_target_minutes)->toBeNull();
});

it('TicketSla has pauseLogs relationship', function () {
    $sla = TicketSla::factory()->create();
    expect($sla->pauseLogs())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('TicketSla casts datetimes correctly', function () {
    $sla = TicketSla::factory()->create(['last_clock_start' => now()]);
    expect($sla->last_clock_start)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('TicketSla does not use SoftDeletes', function () {
    expect(in_array(
        \Illuminate\Database\Eloquent\SoftDeletes::class,
        class_uses_recursive(TicketSla::class)
    ))->toBeFalse();
});

// ─── SlaPauseLog ─────────────────────────────────────────────────────────────

it('SlaPauseLog uses HasUlids', function () {
    expect(in_array(
        \Illuminate\Database\Eloquent\Concerns\HasUlids::class,
        class_uses_recursive(SlaPauseLog::class)
    ))->toBeTrue();
});

it('SlaPauseLog factory creates a valid open record', function () {
    $log = SlaPauseLog::factory()->create();

    expect($log->resumed_at)->toBeNull();
    expect($log->duration_minutes)->toBeNull();
    expect($log->pause_status)->toBe('on_hold');
});

it('SlaPauseLog resumed factory state fills resumed_at and duration', function () {
    $log = SlaPauseLog::factory()->resumed()->create();

    expect($log->resumed_at)->not->toBeNull();
    expect($log->duration_minutes)->toBe(45);
});

it('SlaPauseLog belongs to TicketSla', function () {
    $log = SlaPauseLog::factory()->create();
    expect($log->ticketSla())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($log->ticketSla)->toBeInstanceOf(TicketSla::class);
});

it('SlaPauseLog casts paused_at to Carbon', function () {
    $log = SlaPauseLog::factory()->create();
    expect($log->paused_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('SlaPauseLog does not use SoftDeletes', function () {
    expect(in_array(
        \Illuminate\Database\Eloquent\SoftDeletes::class,
        class_uses_recursive(SlaPauseLog::class)
    ))->toBeFalse();
});
