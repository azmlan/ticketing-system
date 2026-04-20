<?php

use App\Modules\Shared\Models\Department;
use App\Modules\Shared\Models\Location;
use App\Modules\Shared\Models\User;
use Illuminate\Database\QueryException;

it('creates a user with all required fields', function () {
    $user = User::factory()->create([
        'full_name' => 'Ahmed Al-Rashidi',
        'email' => 'ahmed@example.com',
        'locale' => 'ar',
    ]);

    expect($user->fresh())
        ->full_name->toBe('Ahmed Al-Rashidi')
        ->email->toBe('ahmed@example.com')
        ->is_tech->toBeFalse()
        ->is_super_user->toBeFalse()
        ->locale->toBe('ar');
});

it('rejects a user missing full_name', function () {
    User::create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'locale' => 'ar',
    ]);
})->throws(QueryException::class);

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'dup@example.com']);
    User::factory()->create(['email' => 'dup@example.com']);
})->throws(QueryException::class);

it('soft-deletes a user and recovers via withTrashed', function () {
    $user = User::factory()->create();
    $id = $user->id;

    $user->delete();

    expect(User::find($id))->toBeNull()
        ->and(User::withTrashed()->find($id))->not->toBeNull()
        ->and(User::withTrashed()->find($id)->deleted_at)->not->toBeNull();
});

it('resolves the department relationship', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    expect($user->department->id)->toBe($department->id);
});

it('resolves the location relationship', function () {
    $location = Location::factory()->create();
    $user = User::factory()->create(['location_id' => $location->id]);

    expect($user->location->id)->toBe($location->id);
});

it('nullifies department_id on department force-delete (SET NULL)', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $department->forceDelete();

    expect($user->fresh()->department_id)->toBeNull();
});

it('nullifies location_id on location force-delete (SET NULL)', function () {
    $location = Location::factory()->create();
    $user = User::factory()->create(['location_id' => $location->id]);

    $location->forceDelete();

    expect($user->fresh()->location_id)->toBeNull();
});

it('factory produces a valid user row', function () {
    $user = User::factory()->create();

    expect($user->id)->toHaveLength(26)
        ->and($user->full_name)->not->toBeEmpty()
        ->and($user->email)->not->toBeEmpty()
        ->and($user->locale)->toBeIn(['ar', 'en']);
});

it('factory tech state sets is_tech true', function () {
    $user = User::factory()->tech()->create();
    expect($user->is_tech)->toBeTrue();
});

it('factory superUser state sets is_super_user and is_tech true', function () {
    $user = User::factory()->superUser()->create();
    expect($user->is_super_user)->toBeTrue()
        ->and($user->is_tech)->toBeTrue();
});
