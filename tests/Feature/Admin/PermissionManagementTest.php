<?php

use App\Modules\Admin\Livewire\Users\UserDetail;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function permManagerUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'user.manage-permissions')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

// ─── Permission grant ─────────────────────────────────────────────────────────

test('user with user.manage-permissions can grant a permission', function () {
    $actor  = permManagerUser();
    $target = User::factory()->create();
    $perm   = Permission::where('key', 'ticket.view-all')->firstOrFail();

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->set('selectedPermissions', [$perm->id])
        ->call('savePermissions');

    $this->assertDatabaseHas('permission_user', [
        'user_id'       => $target->id,
        'permission_id' => $perm->id,
    ]);
});

test('granting a permission sets granted_by to the acting user', function () {
    $actor  = permManagerUser();
    $target = User::factory()->create();
    $perm   = Permission::where('key', 'ticket.assign')->firstOrFail();

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->set('selectedPermissions', [$perm->id])
        ->call('savePermissions');

    $this->assertDatabaseHas('permission_user', [
        'user_id'       => $target->id,
        'permission_id' => $perm->id,
        'granted_by'    => $actor->id,
    ]);
});

// ─── Permission revoke ────────────────────────────────────────────────────────

test('user with user.manage-permissions can revoke a permission', function () {
    $actor  = permManagerUser();
    $target = User::factory()->create();
    $perm   = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $target->permissions()->attach($perm->id, ['granted_by' => $actor->id, 'granted_at' => now()]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->set('selectedPermissions', [])
        ->call('savePermissions');

    $this->assertDatabaseMissing('permission_user', [
        'user_id'       => $target->id,
        'permission_id' => $perm->id,
    ]);
});

test('saving permissions syncs correctly — adds new and removes unchecked', function () {
    $actor   = permManagerUser();
    $target  = User::factory()->create();
    $permA   = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $permB   = Permission::where('key', 'ticket.assign')->firstOrFail();
    $permC   = Permission::where('key', 'group.manage')->firstOrFail();

    // Start with A and B
    $target->permissions()->attach($permA->id, ['granted_by' => $actor->id, 'granted_at' => now()]);
    $target->permissions()->attach($permB->id, ['granted_by' => $actor->id, 'granted_at' => now()]);

    // Save with B and C (remove A, keep B, add C)
    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->set('selectedPermissions', [$permB->id, $permC->id])
        ->call('savePermissions');

    $this->assertDatabaseMissing('permission_user', ['user_id' => $target->id, 'permission_id' => $permA->id]);
    $this->assertDatabaseHas('permission_user', ['user_id' => $target->id, 'permission_id' => $permB->id]);
    $this->assertDatabaseHas('permission_user', ['user_id' => $target->id, 'permission_id' => $permC->id]);
});

// ─── IT Manager protection ────────────────────────────────────────────────────

test('revoking IT Manager permissions is blocked', function () {
    $actor   = permManagerUser();
    $manager = User::factory()->superUser()->create();
    $perm    = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $manager->permissions()->attach($perm->id, ['granted_by' => $actor->id, 'granted_at' => now()]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $manager])
        ->set('selectedPermissions', [])
        ->call('savePermissions');

    // Permission should still be there — block was applied
    $this->assertDatabaseHas('permission_user', [
        'user_id'       => $manager->id,
        'permission_id' => $perm->id,
    ]);
});

test('permission panel shows blocked message for IT Manager', function () {
    $actor   = permManagerUser();
    $manager = User::factory()->superUser()->create();

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $manager])
        ->assertSee(__('admin.users.permissions_blocked'));
});

// ─── Access control ──────────────────────────────────────────────────────────

test('user without user.manage-permissions cannot save permissions', function () {
    // User has user.promote (can mount) but not user.manage-permissions
    $actor = User::factory()->create();
    $promotePerm = Permission::where('key', 'user.promote')->firstOrFail();
    $actor->permissions()->attach($promotePerm->id, ['granted_by' => $actor->id, 'granted_at' => now()]);

    $target = User::factory()->create();

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('savePermissions')
        ->assertForbidden();
});

test('user.manage-permissions gate is enforced on savePermissions', function () {
    $actor  = User::factory()->create();
    $perm   = Permission::where('key', 'user.promote')->firstOrFail();
    $actor->permissions()->attach($perm->id, ['granted_by' => $actor->id, 'granted_at' => now()]);

    $target = User::factory()->create();

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('savePermissions')
        ->assertForbidden();
});

// ─── Invalid permission IDs filtered out ─────────────────────────────────────

test('invalid permission IDs are ignored when saving', function () {
    $actor  = permManagerUser();
    $target = User::factory()->create();
    $perm   = Permission::where('key', 'ticket.view-all')->firstOrFail();

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->set('selectedPermissions', [$perm->id, 'not-a-real-ulid'])
        ->call('savePermissions');

    $this->assertDatabaseHas('permission_user', [
        'user_id'       => $target->id,
        'permission_id' => $perm->id,
    ]);
    $this->assertDatabaseCount('permission_user', 2); // actor's own + target's
});
