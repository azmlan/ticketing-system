<?php

use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('authenticated user with permission passes middleware and gets 200', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    Route::middleware('permission:ticket.view-all')->get('/_test/perm/allowed', fn () => response('OK'));

    $this->actingAs($user)->get('/_test/perm/allowed')->assertOk();
});

test('authenticated user without permission is denied with 403', function () {
    $user = User::factory()->create();

    Route::middleware('permission:ticket.view-all')->get('/_test/perm/denied', fn () => response('OK'));

    $this->actingAs($user)->get('/_test/perm/denied')->assertForbidden();
});

test('guest hitting permission-gated route is redirected to login', function () {
    Route::middleware('permission:ticket.view-all')->get('/_test/perm/guest', fn () => response('OK'));

    $this->get('/_test/perm/guest')->assertRedirect(route('login'));
});

test('super user with no permission rows passes every registered permission key', function () {
    $user = User::factory()->superUser()->create();

    // Confirm user has no explicit permissions
    expect($user->permissions()->count())->toBe(0);

    foreach (array_keys(config('permissions')) as $key) {
        $uri = '/_test/perm/super/' . str_replace(['.', '-'], '/', $key);
        Route::middleware("permission:{$key}")->get($uri, fn () => response('OK'));
        $this->actingAs($user)->get($uri)->assertOk("Middleware blocked super user for: {$key}");
    }
});

test('granting a permission is immediately reflected without cache', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'ticket.view-all')->firstOrFail();

    expect($user->hasPermission('ticket.view-all'))->toBeFalse();

    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    expect($user->hasPermission('ticket.view-all'))->toBeTrue();
});

test('revoking a permission is immediately reflected without cache', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'ticket.view-all')->firstOrFail();

    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    expect($user->hasPermission('ticket.view-all'))->toBeTrue();

    $user->permissions()->detach($permission->id);

    expect($user->hasPermission('ticket.view-all'))->toBeFalse();
});
