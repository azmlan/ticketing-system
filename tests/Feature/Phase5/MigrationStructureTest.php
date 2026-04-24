<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ─── Table existence ──────────────────────────────────────────────────────────

it('creates the sla_policies table', function () {
    expect(Schema::hasTable('sla_policies'))->toBeTrue();
});

it('creates the ticket_sla table', function () {
    expect(Schema::hasTable('ticket_sla'))->toBeTrue();
});

it('creates the sla_pause_logs table', function () {
    expect(Schema::hasTable('sla_pause_logs'))->toBeTrue();
});

// ─── sla_policies columns ─────────────────────────────────────────────────────

it('sla_policies has all required columns', function () {
    $columns = [
        'id', 'priority', 'response_target_minutes',
        'resolution_target_minutes', 'use_24x7', 'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('sla_policies', $column))
            ->toBeTrue("Column 'sla_policies.{$column}' is missing");
    }
});

it('sla_policies has no deleted_at column', function () {
    expect(Schema::hasColumn('sla_policies', 'deleted_at'))->toBeFalse();
});

it('sla_policies priority enum has 4 values', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM sla_policies WHERE Field = 'priority'");
    expect($row[0]->Type)->toContain('low')
        ->toContain('medium')
        ->toContain('high')
        ->toContain('critical');
});

it('sla_policies has unique index on priority', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select("SHOW INDEX FROM sla_policies WHERE Column_name = 'priority' AND Non_unique = 0");
    expect($indexes)->not->toBeEmpty();
});

it('sla_policies use_24x7 defaults to false', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM sla_policies WHERE Field = 'use_24x7'");
    expect((int) $row[0]->Default)->toBe(0);
});

// ─── ticket_sla columns ───────────────────────────────────────────────────────

it('ticket_sla has all required columns', function () {
    $columns = [
        'id', 'ticket_id',
        'response_target_minutes', 'resolution_target_minutes',
        'response_elapsed_minutes', 'resolution_elapsed_minutes',
        'response_met_at', 'response_status', 'resolution_status',
        'last_clock_start', 'is_clock_running',
        'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('ticket_sla', $column))
            ->toBeTrue("Column 'ticket_sla.{$column}' is missing");
    }
});

it('ticket_sla has no deleted_at column', function () {
    expect(Schema::hasColumn('ticket_sla', 'deleted_at'))->toBeFalse();
});

it('ticket_sla has unique index on ticket_id', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select("SHOW INDEX FROM ticket_sla WHERE Column_name = 'ticket_id' AND Non_unique = 0");
    expect($indexes)->not->toBeEmpty();
});

it('ticket_sla response_status defaults to on_track', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM ticket_sla WHERE Field = 'response_status'");
    expect($row[0]->Default)->toBe('on_track');
});

it('ticket_sla resolution_status defaults to on_track', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM ticket_sla WHERE Field = 'resolution_status'");
    expect($row[0]->Default)->toBe('on_track');
});

it('ticket_sla status enums have correct values', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM ticket_sla WHERE Field = 'response_status'");
    expect($row[0]->Type)
        ->toContain('on_track')
        ->toContain('warning')
        ->toContain('breached');
});

it('ticket_sla is_clock_running defaults to true', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM ticket_sla WHERE Field = 'is_clock_running'");
    expect((int) $row[0]->Default)->toBe(1);
});

it('ticket_sla elapsed columns default to 0', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $response = DB::select("SHOW COLUMNS FROM ticket_sla WHERE Field = 'response_elapsed_minutes'");
    $resolution = DB::select("SHOW COLUMNS FROM ticket_sla WHERE Field = 'resolution_elapsed_minutes'");

    expect((int) $response[0]->Default)->toBe(0);
    expect((int) $resolution[0]->Default)->toBe(0);
});

it('ticket_sla has indexes on response_status and resolution_status', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = collect(DB::select('SHOW INDEX FROM ticket_sla'))->pluck('Column_name');
    expect($indexes)->toContain('response_status')
        ->toContain('resolution_status');
});

// ─── sla_pause_logs columns ───────────────────────────────────────────────────

it('sla_pause_logs has all required columns', function () {
    $columns = [
        'id', 'ticket_sla_id', 'paused_at', 'resumed_at',
        'pause_status', 'duration_minutes', 'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('sla_pause_logs', $column))
            ->toBeTrue("Column 'sla_pause_logs.{$column}' is missing");
    }
});

it('sla_pause_logs has no deleted_at column', function () {
    expect(Schema::hasColumn('sla_pause_logs', 'deleted_at'))->toBeFalse();
});

it('sla_pause_logs has index on ticket_sla_id', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = collect(DB::select('SHOW INDEX FROM sla_pause_logs'))->pluck('Column_name');
    expect($indexes)->toContain('ticket_sla_id');
});

it('sla_pause_logs resumed_at and duration_minutes are nullable', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $resumedAt = DB::select("SHOW COLUMNS FROM sla_pause_logs WHERE Field = 'resumed_at'");
    $duration  = DB::select("SHOW COLUMNS FROM sla_pause_logs WHERE Field = 'duration_minutes'");

    expect($resumedAt[0]->Null)->toBe('YES');
    expect($duration[0]->Null)->toBe('YES');
});
