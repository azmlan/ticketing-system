<?php

use Database\Seeders\CategorySeeder;
use Database\Seeders\GroupSeeder;
use Database\Seeders\SubcategorySeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ─── Table existence ────────────────────────────────────────────────────────

it('creates the groups table', function () {
    expect(Schema::hasTable('groups'))->toBeTrue();
});

it('creates the categories table', function () {
    expect(Schema::hasTable('categories'))->toBeTrue();
});

it('creates the subcategories table', function () {
    expect(Schema::hasTable('subcategories'))->toBeTrue();
});

it('creates the tickets table', function () {
    expect(Schema::hasTable('tickets'))->toBeTrue();
});

it('creates the ticket_counters table', function () {
    expect(Schema::hasTable('ticket_counters'))->toBeTrue();
});

it('creates the group_user table', function () {
    expect(Schema::hasTable('group_user'))->toBeTrue();
});

it('creates the transfer_requests table', function () {
    expect(Schema::hasTable('transfer_requests'))->toBeTrue();
});

it('creates the ticket_attachments table', function () {
    expect(Schema::hasTable('ticket_attachments'))->toBeTrue();
});

// ─── Column spot-checks ──────────────────────────────────────────────────────

it('tickets table has all required columns', function () {
    $columns = [
        'id', 'display_number', 'subject', 'description', 'status', 'priority',
        'category_id', 'subcategory_id', 'group_id', 'assigned_to', 'requester_id',
        'location_id', 'department_id', 'close_reason', 'close_reason_text',
        'incident_origin', 'resolved_at', 'closed_at', 'cancelled_at', 'deleted_at',
        'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('tickets', $column))
            ->toBeTrue("Column 'tickets.{$column}' is missing");
    }
});

it('tickets table has nullable priority and subcategory_id', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM tickets WHERE Field IN ('priority', 'subcategory_id')");
    $nullableMap = collect($row)->pluck('Null', 'Field');

    expect($nullableMap['priority'])->toBe('YES');
    expect($nullableMap['subcategory_id'])->toBe('YES');
});

it('tickets status enum contains all 9 values', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS enum introspection requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM tickets WHERE Field = 'status'");
    $type = $row[0]->Type;

    $expected = [
        'awaiting_assignment', 'in_progress', 'on_hold', 'awaiting_approval',
        'action_required', 'awaiting_final_approval', 'resolved', 'closed', 'cancelled',
    ];

    foreach ($expected as $value) {
        expect($type)->toContain($value);
    }
});

it('groups table has bilingual name columns', function () {
    expect(Schema::hasColumn('groups', 'name_ar'))->toBeTrue();
    expect(Schema::hasColumn('groups', 'name_en'))->toBeTrue();
});

it('groups manager_id is nullable', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM groups WHERE Field = 'manager_id'");
    expect($row[0]->Null)->toBe('YES');
});

it('transfer_requests has composite index on ticket_id and status', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select("SHOW INDEX FROM transfer_requests WHERE Column_name IN ('ticket_id', 'status')");

    $compositeExists = collect($indexes)
        ->groupBy('Key_name')
        ->filter(fn ($cols) => $cols->pluck('Column_name')->contains('ticket_id')
            && $cols->pluck('Column_name')->contains('status'))
        ->isNotEmpty();

    expect($compositeExists)->toBeTrue();
});

it('ticket_attachments has file_size as unsigned int', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS type introspection requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM ticket_attachments WHERE Field = 'file_size'");
    expect($row[0]->Type)->toContain('int');
    expect($row[0]->Type)->toContain('unsigned');
});

// ─── ticket_counters invariant ───────────────────────────────────────────────

it('ticket_counters table is seeded with exactly one row and last_number 0', function () {
    $rows = DB::table('ticket_counters')->get();

    expect($rows)->toHaveCount(1);
    expect($rows[0]->id)->toBe(1);
    expect((int) $rows[0]->last_number)->toBe(0);
});

// ─── Seeder output ───────────────────────────────────────────────────────────

it('GroupSeeder produces exactly 2 groups', function () {
    $this->seed(GroupSeeder::class);

    expect(DB::table('groups')->count())->toBe(2);
});

it('CategorySeeder produces exactly 2 categories', function () {
    $this->seed(GroupSeeder::class);
    $this->seed(CategorySeeder::class);

    expect(DB::table('categories')->count())->toBe(2);
});

it('SubcategorySeeder produces exactly 4 subcategories (2 per category)', function () {
    $this->seed(GroupSeeder::class);
    $this->seed(CategorySeeder::class);
    $this->seed(SubcategorySeeder::class);

    expect(DB::table('subcategories')->count())->toBe(4);
});

it('categories are mapped to the correct groups', function () {
    $this->seed(GroupSeeder::class);
    $this->seed(CategorySeeder::class);

    $categories = DB::table('categories')->get();
    $groupIds = DB::table('groups')->pluck('id');

    foreach ($categories as $category) {
        expect($groupIds)->toContain($category->group_id);
    }
});
