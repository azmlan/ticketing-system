<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the csat_ratings table', function () {
    expect(Schema::hasTable('csat_ratings'))->toBeTrue();
});

it('csat_ratings has all required columns', function () {
    $columns = [
        'id', 'ticket_id', 'requester_id', 'tech_id',
        'rating', 'comment', 'status', 'expires_at',
        'submitted_at', 'dismissed_count', 'created_at', 'updated_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('csat_ratings', $column))
            ->toBeTrue("Column 'csat_ratings.{$column}' is missing");
    }
});

it('csat_ratings status enum has correct values', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW COLUMNS requires MySQL');
    }

    $row = DB::select("SHOW COLUMNS FROM csat_ratings WHERE Field = 'status'");
    expect($row[0]->Type)
        ->toContain('pending')
        ->toContain('submitted')
        ->toContain('expired');
});

it('csat_ratings has unique index on ticket_id', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select("SHOW INDEX FROM csat_ratings WHERE Column_name = 'ticket_id' AND Non_unique = 0");
    expect($indexes)->not->toBeEmpty();
});

it('csat_ratings has composite index on tech_id and status', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select("SHOW INDEX FROM csat_ratings WHERE Column_name = 'tech_id'");
    expect($indexes)->not->toBeEmpty();
});

it('csat_ratings has composite index on status and expires_at', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('SHOW INDEX requires MySQL');
    }

    $indexes = DB::select("SHOW INDEX FROM csat_ratings WHERE Column_name = 'status'");
    expect($indexes)->not->toBeEmpty();
});
