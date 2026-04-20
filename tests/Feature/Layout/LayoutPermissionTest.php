<?php

use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('sidebar shows ticket.view-all item when user has the permission', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertSee(__('layout.nav_all_tickets'));
});

test('sidebar hides ticket.view-all item when user lacks the permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertDontSee(__('layout.nav_all_tickets'));
});

test('sidebar shows promote link when user has user.promote permission', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'user.promote')->firstOrFail();
    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertSee(__('layout.nav_promote'));
});

test('sidebar hides promote link when user lacks user.promote permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertDontSee(__('layout.nav_promote'));
});

test('super user sees all gated sidebar items', function () {
    $user = User::factory()->superUser()->create();

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertSee(__('layout.nav_all_tickets'))
             ->assertSee(__('layout.nav_promote'));
});
