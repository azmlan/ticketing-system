<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ─── Table existence ─────────────────────────────────────────────────────────

it('creates the condition_reports table', function () {
    expect(Schema::hasTable('condition_reports'))->toBeTrue();
});

it('creates the condition_report_attachments table', function () {
    expect(Schema::hasTable('condition_report_attachments'))->toBeTrue();
});

it('creates the maintenance_requests table', function () {
    expect(Schema::hasTable('maintenance_requests'))->toBeTrue();
});

// ─── condition_reports columns ───────────────────────────────────────────────

it('condition_reports has all required columns', function () {
    $columns = [
        'id', 'ticket_id', 'report_type', 'location_id', 'report_date',
        'current_condition', 'condition_analysis', 'required_action',
        'tech_id', 'status', 'reviewed_by', 'reviewed_at', 'review_notes',
        'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('condition_reports', $column))
            ->toBeTrue("Column 'condition_reports.{$column}' is missing");
    }
});

it('condition_reports has no deleted_at column', function () {
    expect(Schema::hasColumn('condition_reports', 'deleted_at'))->toBeFalse();
});

it('condition_reports status enum has 3 values: pending, approved, rejected', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS enum introspection requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM condition_reports WHERE Field = 'status'");
    $type = $row[0]->Type;

    expect($type)->toContain('pending');
    expect($type)->toContain('approved');
    expect($type)->toContain('rejected');
});

it('condition_reports nullable columns are nullable', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $rows = DB::select("SHOW COLUMNS FROM condition_reports WHERE Field IN ('location_id', 'reviewed_by', 'reviewed_at', 'review_notes')");
    $nullableMap = collect($rows)->pluck('Null', 'Field');

    expect($nullableMap['location_id'])->toBe('YES');
    expect($nullableMap['reviewed_by'])->toBe('YES');
    expect($nullableMap['reviewed_at'])->toBe('YES');
    expect($nullableMap['review_notes'])->toBe('YES');
});

it('condition_reports non-nullable text fields are NOT NULL', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $rows = DB::select("SHOW COLUMNS FROM condition_reports WHERE Field IN ('current_condition', 'condition_analysis', 'required_action')");
    $nullableMap = collect($rows)->pluck('Null', 'Field');

    expect($nullableMap['current_condition'])->toBe('NO');
    expect($nullableMap['condition_analysis'])->toBe('NO');
    expect($nullableMap['required_action'])->toBe('NO');
});

it('condition_reports has indexed FK columns', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select('SHOW INDEX FROM condition_reports');
    $indexedColumns = collect($indexes)->pluck('Column_name')->unique()->values()->all();

    expect($indexedColumns)->toContain('ticket_id');
    expect($indexedColumns)->toContain('location_id');
    expect($indexedColumns)->toContain('tech_id');
    expect($indexedColumns)->toContain('reviewed_by');
});

// ─── condition_report_attachments columns ────────────────────────────────────

it('condition_report_attachments has all required columns', function () {
    $columns = [
        'id', 'condition_report_id', 'original_name', 'file_path',
        'file_size', 'mime_type', 'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('condition_report_attachments', $column))
            ->toBeTrue("Column 'condition_report_attachments.{$column}' is missing");
    }
});

it('condition_report_attachments has no deleted_at column', function () {
    expect(Schema::hasColumn('condition_report_attachments', 'deleted_at'))->toBeFalse();
});

it('condition_report_attachments has indexed condition_report_id', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select('SHOW INDEX FROM condition_report_attachments');
    $indexedColumns = collect($indexes)->pluck('Column_name')->unique()->values()->all();

    expect($indexedColumns)->toContain('condition_report_id');
});

it('condition_report_attachments file_size is unsigned int', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS type introspection requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM condition_report_attachments WHERE Field = 'file_size'");
    expect($row[0]->Type)->toContain('int');
    expect($row[0]->Type)->toContain('unsigned');
});

// ─── maintenance_requests columns ────────────────────────────────────────────

it('maintenance_requests has all required columns', function () {
    $columns = [
        'id', 'ticket_id', 'generated_file_path', 'generated_locale',
        'submitted_file_path', 'submitted_at', 'status', 'reviewed_by',
        'reviewed_at', 'review_notes', 'rejection_count',
        'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('maintenance_requests', $column))
            ->toBeTrue("Column 'maintenance_requests.{$column}' is missing");
    }
});

it('maintenance_requests has no deleted_at column', function () {
    expect(Schema::hasColumn('maintenance_requests', 'deleted_at'))->toBeFalse();
});

it('maintenance_requests status enum has 4 values: pending, submitted, approved, rejected', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS enum introspection requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM maintenance_requests WHERE Field = 'status'");
    $type = $row[0]->Type;

    expect($type)->toContain('pending');
    expect($type)->toContain('submitted');
    expect($type)->toContain('approved');
    expect($type)->toContain('rejected');
});

it('maintenance_requests has rejection_count as unsigned int defaulting to 0', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS type introspection requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM maintenance_requests WHERE Field = 'rejection_count'");
    expect($row[0]->Type)->toContain('int');
    expect($row[0]->Type)->toContain('unsigned');
    expect($row[0]->Default)->toBe('0');
});

it('maintenance_requests has generated_locale column', function () {
    expect(Schema::hasColumn('maintenance_requests', 'generated_locale'))->toBeTrue();
});

it('maintenance_requests nullable columns are nullable', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $rows = DB::select("SHOW COLUMNS FROM maintenance_requests WHERE Field IN ('submitted_file_path', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes')");
    $nullableMap = collect($rows)->pluck('Null', 'Field');

    expect($nullableMap['submitted_file_path'])->toBe('YES');
    expect($nullableMap['submitted_at'])->toBe('YES');
    expect($nullableMap['reviewed_by'])->toBe('YES');
    expect($nullableMap['reviewed_at'])->toBe('YES');
    expect($nullableMap['review_notes'])->toBe('YES');
});

it('maintenance_requests has indexed FK columns', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select('SHOW INDEX FROM maintenance_requests');
    $indexedColumns = collect($indexes)->pluck('Column_name')->unique()->values()->all();

    expect($indexedColumns)->toContain('ticket_id');
    expect($indexedColumns)->toContain('reviewed_by');
});
