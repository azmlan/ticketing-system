<?php

use App\Modules\Shared\Models\Department;
use App\Modules\Shared\Models\Location;
use App\Modules\Shared\Models\User;
use Illuminate\Database\QueryException;

// ── Departments ──────────────────────────────────────────────────────────────

it('creates a department with bilingual names', function () {
    $dept = Department::factory()->create([
        'name_ar' => 'قسم تقنية المعلومات',
        'name_en' => 'Information Technology',
        'sort_order' => 5,
    ]);

    expect($dept->fresh())
        ->name_ar->toBe('قسم تقنية المعلومات')
        ->name_en->toBe('Information Technology')
        ->is_active->toBeTrue()
        ->sort_order->toBe(5);
});

it('rejects a department missing name_ar', function () {
    Department::create([
        'name_en' => 'IT',
        'is_active' => true,
    ]);
})->throws(QueryException::class);

it('rejects a department missing name_en', function () {
    Department::create([
        'name_ar' => 'تقنية',
        'is_active' => true,
    ]);
})->throws(QueryException::class);

it('soft-deletes a department', function () {
    $dept = Department::factory()->create();
    $id = $dept->id;

    $dept->delete();

    expect(Department::find($id))->toBeNull()
        ->and(Department::withTrashed()->find($id))->not->toBeNull();
});

it('department users() relationship resolves', function () {
    $dept = Department::factory()->create();
    User::factory(3)->create(['department_id' => $dept->id]);

    expect($dept->users)->toHaveCount(3);
});

it('department factory produces a valid row', function () {
    $dept = Department::factory()->create();

    expect($dept->id)->toHaveLength(26)
        ->and($dept->name_ar)->not->toBeEmpty()
        ->and($dept->name_en)->not->toBeEmpty()
        ->and($dept->is_active)->toBeTrue();
});

it('department inactive state sets is_active false', function () {
    $dept = Department::factory()->inactive()->create();
    expect($dept->is_active)->toBeFalse();
});

// ── Locations ────────────────────────────────────────────────────────────────

it('creates a location with bilingual names', function () {
    $loc = Location::factory()->create([
        'name_ar' => 'المبنى الرئيسي',
        'name_en' => 'Main Building',
        'sort_order' => 1,
    ]);

    expect($loc->fresh())
        ->name_ar->toBe('المبنى الرئيسي')
        ->name_en->toBe('Main Building')
        ->is_active->toBeTrue()
        ->sort_order->toBe(1);
});

it('rejects a location missing name_ar', function () {
    Location::create(['name_en' => 'Building A', 'is_active' => true]);
})->throws(QueryException::class);

it('rejects a location missing name_en', function () {
    Location::create(['name_ar' => 'مبنى أ', 'is_active' => true]);
})->throws(QueryException::class);

it('soft-deletes a location', function () {
    $loc = Location::factory()->create();
    $id = $loc->id;

    $loc->delete();

    expect(Location::find($id))->toBeNull()
        ->and(Location::withTrashed()->find($id))->not->toBeNull();
});

it('location users() relationship resolves', function () {
    $loc = Location::factory()->create();
    User::factory(2)->create(['location_id' => $loc->id]);

    expect($loc->users)->toHaveCount(2);
});

it('location factory produces a valid row', function () {
    $loc = Location::factory()->create();

    expect($loc->id)->toHaveLength(26)
        ->and($loc->name_ar)->not->toBeEmpty()
        ->and($loc->name_en)->not->toBeEmpty();
});
