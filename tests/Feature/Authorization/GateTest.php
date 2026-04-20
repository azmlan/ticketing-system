<?php

use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('Gate::allows resolves without throwing for every registered key', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    foreach (array_keys(config('permissions')) as $key) {
        // Returns false (no permissions), but must not throw
        expect(Gate::allows($key))->toBeFalse("Gate threw for key: {$key}");
    }
});

test('Gate::allows returns false for user without the permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(Gate::allows('ticket.view-all'))->toBeFalse();
});

test('Gate::allows returns true for user with the permission', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    $this->actingAs($user);

    expect(Gate::allows('ticket.view-all'))->toBeTrue();
});

test('super user Gate::before bypass returns true for every permission key', function () {
    $user = User::factory()->superUser()->create();

    $this->actingAs($user);

    foreach (array_keys(config('permissions')) as $key) {
        expect(Gate::allows($key))->toBeTrue("Gate::allows returned false for super user on: {$key}");
    }
});

test('is_tech flag does not substitute for explicit permission', function () {
    $user = User::factory()->tech()->create();

    $this->actingAs($user);

    // Being a tech does not grant ticket.assign
    expect(Gate::allows('ticket.assign'))->toBeFalse();
});
