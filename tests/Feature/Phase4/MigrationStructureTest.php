<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ─── Table existence ──────────────────────────────────────────────────────────

it('creates the comments table', function () {
    expect(Schema::hasTable('comments'))->toBeTrue();
});

it('creates the notification_logs table', function () {
    expect(Schema::hasTable('notification_logs'))->toBeTrue();
});

it('creates the response_templates table', function () {
    expect(Schema::hasTable('response_templates'))->toBeTrue();
});

// ─── comments columns ─────────────────────────────────────────────────────────

it('comments has all required columns', function () {
    $columns = ['id', 'ticket_id', 'user_id', 'body', 'is_internal', 'created_at', 'updated_at'];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('comments', $column))
            ->toBeTrue("Column 'comments.{$column}' is missing");
    }
});

it('comments has no deleted_at column', function () {
    expect(Schema::hasColumn('comments', 'deleted_at'))->toBeFalse();
});

it('comments is_internal defaults to true', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM comments WHERE Field = 'is_internal'");
    expect((int) $row[0]->Default)->toBe(1);
});

it('comments has a FULLTEXT index on body', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select("SHOW INDEX FROM comments WHERE Index_type = 'FULLTEXT'");
    $fulltextColumns = collect($indexes)->pluck('Column_name')->all();

    expect($fulltextColumns)->toContain('body');
});

it('comments has a composite index on ticket_id and created_at', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select('SHOW INDEX FROM comments');
    $indexedColumns = collect($indexes)->pluck('Column_name')->unique()->values()->all();

    expect($indexedColumns)->toContain('ticket_id');
    expect($indexedColumns)->toContain('created_at');
});

it('comments user_id FK is RESTRICT on delete', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('information_schema requires MySQL');
    }

    $fks = DB::select("
        SELECT DELETE_RULE
        FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'comments'
          AND REFERENCED_TABLE_NAME = 'users'
    ");

    expect(count($fks))->toBeGreaterThan(0);
    expect($fks[0]->DELETE_RULE)->toBe('RESTRICT');
});

it('comments ticket_id FK is CASCADE on delete', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('information_schema requires MySQL');
    }

    $fks = DB::select("
        SELECT DELETE_RULE
        FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = 'comments'
          AND REFERENCED_TABLE_NAME = 'tickets'
    ");

    expect(count($fks))->toBeGreaterThan(0);
    expect($fks[0]->DELETE_RULE)->toBe('CASCADE');
});

// ─── notification_logs columns ────────────────────────────────────────────────

it('notification_logs has all required columns', function () {
    $columns = [
        'id', 'recipient_id', 'ticket_id', 'type', 'channel',
        'subject', 'body_preview', 'status', 'sent_at',
        'failure_reason', 'attempts', 'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('notification_logs', $column))
            ->toBeTrue("Column 'notification_logs.{$column}' is missing");
    }
});

it('notification_logs has no deleted_at column', function () {
    expect(Schema::hasColumn('notification_logs', 'deleted_at'))->toBeFalse();
});

it('notification_logs status enum has queued, sent, failed', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS enum introspection requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM notification_logs WHERE Field = 'status'");
    $type = $row[0]->Type;

    expect($type)->toContain('queued');
    expect($type)->toContain('sent');
    expect($type)->toContain('failed');
});

it('notification_logs ticket_id is nullable', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM notification_logs WHERE Field = 'ticket_id'");
    expect($row[0]->Null)->toBe('YES');
});

it('notification_logs sent_at is nullable', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM notification_logs WHERE Field = 'sent_at'");
    expect($row[0]->Null)->toBe('YES');
});

it('notification_logs attempts defaults to 0', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM notification_logs WHERE Field = 'attempts'");
    expect((int) $row[0]->Default)->toBe(0);
});

it('notification_logs has indexed FK columns', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select('SHOW INDEX FROM notification_logs');
    $indexedColumns = collect($indexes)->pluck('Column_name')->unique()->values()->all();

    expect($indexedColumns)->toContain('recipient_id');
});

// ─── response_templates columns ───────────────────────────────────────────────

it('response_templates has all required columns', function () {
    $columns = [
        'id', 'title_ar', 'title_en', 'body_ar', 'body_en',
        'is_internal', 'is_active', 'deleted_at', 'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('response_templates', $column))
            ->toBeTrue("Column 'response_templates.{$column}' is missing");
    }
});

it('response_templates has deleted_at for SoftDeletes', function () {
    expect(Schema::hasColumn('response_templates', 'deleted_at'))->toBeTrue();
});

it('response_templates is_active defaults to true', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM response_templates WHERE Field = 'is_active'");
    expect((int) $row[0]->Default)->toBe(1);
});

it('response_templates is_internal defaults to true', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM response_templates WHERE Field = 'is_internal'");
    expect((int) $row[0]->Default)->toBe(1);
});
