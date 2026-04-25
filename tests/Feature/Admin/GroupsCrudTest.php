<?php

use App\Modules\Admin\Livewire\Groups\GroupIndex;
use App\Modules\Admin\Livewire\Groups\GroupMembersIndex;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function groupManageUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'group.manage')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

function groupMembersUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'group.manage-members')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

function groupManagerPermUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'group.manage-manager')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

// ─── Route access: GroupIndex ─────────────────────────────────────────────────

test('unauthenticated user is redirected from admin groups', function () {
    $this->get(route('admin.groups.index'))
        ->assertRedirect(route('login'));
});

test('user without group.manage cannot access admin groups', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.groups.index'))
        ->assertForbidden();
});

test('user with group.manage can access admin groups', function () {
    $user = groupManageUser();
    $this->actingAs($user)
        ->get(route('admin.groups.index'))
        ->assertOk();
});

test('super user can access admin groups', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.groups.index'))
        ->assertOk();
});

// ─── Create ──────────────────────────────────────────────────────────────────

test('authorised user can create a group', function () {
    $user = groupManageUser();

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'مجموعة الدعم')
        ->set('formNameEn', 'Support Group')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('groups', [
        'name_ar'   => 'مجموعة الدعم',
        'name_en'   => 'Support Group',
        'is_active' => true,
    ]);
});

test('create group fails without arabic name', function () {
    $user = groupManageUser();

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->call('openCreate')
        ->set('formNameAr', '')
        ->set('formNameEn', 'Support Group')
        ->call('save')
        ->assertHasErrors(['formNameAr']);
});

test('create group fails without english name', function () {
    $user = groupManageUser();

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'مجموعة')
        ->set('formNameEn', '')
        ->call('save')
        ->assertHasErrors(['formNameEn']);
});

test('user without group.manage cannot mount GroupIndex', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->assertForbidden();
});

// ─── Edit ─────────────────────────────────────────────────────────────────────

test('authorised user can edit a group', function () {
    $user  = groupManageUser();
    $group = Group::factory()->create(['name_en' => 'Old Name']);

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->call('openEdit', $group->id)
        ->set('formNameEn', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('groups', ['id' => $group->id, 'name_en' => 'New Name']);
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('authorised user can deactivate a group', function () {
    $user  = groupManageUser();
    $group = Group::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->call('toggleActive', $group->id);

    $this->assertDatabaseHas('groups', ['id' => $group->id, 'is_active' => false]);
});

test('authorised user can reactivate a group', function () {
    $user  = groupManageUser();
    $group = Group::factory()->inactive()->create();

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->call('toggleActive', $group->id);

    $this->assertDatabaseHas('groups', ['id' => $group->id, 'is_active' => true]);
});

// ─── Soft-delete ──────────────────────────────────────────────────────────────

test('authorised user can soft-delete a group', function () {
    $user  = groupManageUser();
    $group = Group::factory()->create();

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->call('delete', $group->id);

    $this->assertSoftDeleted('groups', ['id' => $group->id]);
});

// ─── GroupMembersIndex route access ──────────────────────────────────────────

test('unauthenticated user is redirected from groups members page', function () {
    $group = Group::factory()->create();
    $this->get(route('admin.groups.members', $group))
        ->assertRedirect(route('login'));
});

test('user without group.manage-members or group.manage-manager gets 403 on members page', function () {
    $user  = User::factory()->create();
    $group = Group::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.groups.members', $group))
        ->assertForbidden();
});

test('user with group.manage-members can access members page', function () {
    $user  = groupMembersUser();
    $group = Group::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.groups.members', $group))
        ->assertOk();
});

test('user with group.manage-manager can access members page', function () {
    $user  = groupManagerPermUser();
    $group = Group::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.groups.members', $group))
        ->assertOk();
});

// ─── Add member ───────────────────────────────────────────────────────────────

test('user with group.manage-members can add a tech to a group', function () {
    $user  = groupMembersUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->call('addMember', $tech->id);

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id'  => $tech->id,
    ]);
});

test('user with only group.manage-manager cannot add members', function () {
    $user  = groupManagerPermUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->call('addMember', $tech->id)
        ->assertForbidden();
});

test('adding the same member twice does not create duplicate', function () {
    $user  = groupMembersUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();
    $group->users()->attach($tech->id);

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->call('addMember', $tech->id);

    $this->assertDatabaseCount('group_user', 1);
});

// ─── Remove member ────────────────────────────────────────────────────────────

test('user with group.manage-members can remove a tech from a group', function () {
    $user  = groupMembersUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();
    $group->users()->attach($tech->id);

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->call('removeMember', $tech->id);

    $this->assertDatabaseMissing('group_user', [
        'group_id' => $group->id,
        'user_id'  => $tech->id,
    ]);
});

test('removing the group manager clears manager_id', function () {
    $user  = groupMembersUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();
    $group->users()->attach($tech->id);
    $group->update(['manager_id' => $tech->id]);

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->call('removeMember', $tech->id);

    $this->assertDatabaseHas('groups', ['id' => $group->id, 'manager_id' => null]);
});

test('user with only group.manage-manager cannot remove members', function () {
    $user  = groupManagerPermUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();
    $group->users()->attach($tech->id);

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->call('removeMember', $tech->id)
        ->assertForbidden();
});

// ─── Group manager assignment ─────────────────────────────────────────────────

test('user with group.manage-manager can assign a group manager', function () {
    $user  = groupManagerPermUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();
    $group->users()->attach($tech->id);

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->set('selectedManagerId', $tech->id)
        ->call('saveManager');

    $this->assertDatabaseHas('groups', ['id' => $group->id, 'manager_id' => $tech->id]);
});

test('user with group.manage-manager can clear the group manager', function () {
    $user  = groupManagerPermUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();
    $group->users()->attach($tech->id);
    $group->update(['manager_id' => $tech->id]);

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->set('selectedManagerId', '')
        ->call('saveManager');

    $this->assertDatabaseHas('groups', ['id' => $group->id, 'manager_id' => null]);
});

test('user with only group.manage-members cannot assign manager', function () {
    $user  = groupMembersUser();
    $group = Group::factory()->create();
    $tech  = User::factory()->tech()->create();
    $group->users()->attach($tech->id);

    Livewire::actingAs($user)
        ->test(GroupMembersIndex::class, ['group' => $group])
        ->set('selectedManagerId', $tech->id)
        ->call('saveManager')
        ->assertForbidden();
});

// ─── List display ─────────────────────────────────────────────────────────────

test('group list shows member count and manager name', function () {
    $user  = groupManageUser();
    $group = Group::factory()->create(['name_en' => 'IT Support']);
    $tech  = User::factory()->tech()->create(['full_name' => 'Alice Smith']);
    $group->users()->attach($tech->id);
    $group->update(['manager_id' => $tech->id]);

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->assertSee('IT Support')
        ->assertSee('Alice Smith');
});

test('soft-deleted group appears in list', function () {
    $user  = groupManageUser();
    $group = Group::factory()->create(['name_en' => 'Deleted Group']);
    $group->delete();

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->assertSee('Deleted Group');
});

test('group list search filters by name', function () {
    $user = groupManageUser();
    Group::factory()->create(['name_en' => 'Network Team']);
    Group::factory()->create(['name_en' => 'HR Support']);

    Livewire::actingAs($user)
        ->test(GroupIndex::class)
        ->set('search', 'Network')
        ->assertSee('Network Team')
        ->assertDontSee('HR Support');
});

// ─── Admin layout nav visibility ─────────────────────────────────────────────

test('admin layout shows groups nav for user with group.manage', function () {
    $user = groupManageUser();
    $this->actingAs($user)
        ->get(route('admin.groups.index'))
        ->assertSee(__('admin.nav_groups'));
});
