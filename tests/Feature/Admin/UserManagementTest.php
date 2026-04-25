<?php

use App\Modules\Admin\Events\UserPromotedToTech;
use App\Modules\Admin\Livewire\Users\UserDetail;
use App\Modules\Admin\Livewire\Users\UserList;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\TechProfile;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function userPromoteUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'user.promote')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

function userManagePermsUser(): User
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

// ─── Route access: UserList ───────────────────────────────────────────────────

test('unauthenticated user is redirected from admin users', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

test('user without user.promote or user.manage-permissions gets 403 on user list', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('user with user.promote can access admin user list', function () {
    $user = userPromoteUser();
    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('user with user.manage-permissions can access admin user list', function () {
    $user = userManagePermsUser();
    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('super user can access admin user list', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk();
});

// ─── UserList filters ────────────────────────────────────────────────────────

test('user list shows users by name', function () {
    $actor  = userPromoteUser();
    $target = User::factory()->create(['full_name' => 'Alice Smith']);

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->assertSee('Alice Smith');
});

test('user list search filters by name', function () {
    $actor = userPromoteUser();
    User::factory()->create(['full_name' => 'Alice Smith']);
    User::factory()->create(['full_name' => 'Bob Jones']);

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->set('search', 'Alice')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

test('user list search filters by email', function () {
    $actor = userPromoteUser();
    User::factory()->create(['full_name' => 'Alice', 'email' => 'alice@example.com']);
    User::factory()->create(['full_name' => 'Bob', 'email' => 'bob@example.com']);

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->set('search', 'alice@')
        ->assertSee('alice@example.com')
        ->assertDontSee('bob@example.com');
});

test('user list filters by role employee', function () {
    $actor = userPromoteUser();
    User::factory()->create(['full_name' => 'Emp User', 'is_tech' => false, 'is_super_user' => false]);
    User::factory()->tech()->create(['full_name' => 'Tech User']);

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->set('filterRole', 'employee')
        ->assertSee('Emp User')
        ->assertDontSee('Tech User');
});

test('user list filters by role tech', function () {
    $actor = userPromoteUser();
    User::factory()->create(['full_name' => 'Emp User', 'is_tech' => false]);
    User::factory()->tech()->create(['full_name' => 'Tech User', 'is_super_user' => false]);

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->set('filterRole', 'tech')
        ->assertSee('Tech User')
        ->assertDontSee('Emp User');
});

test('user list filters by role it_manager', function () {
    $actor   = userPromoteUser();
    $manager = User::factory()->superUser()->create(['full_name' => 'Manager User']);
    User::factory()->create(['full_name' => 'Regular User']);

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->set('filterRole', 'it_manager')
        ->assertSee('Manager User')
        ->assertDontSee('Regular User');
});

test('user list filters active users', function () {
    $actor   = userPromoteUser();
    $active  = User::factory()->create(['full_name' => 'Active User']);
    $deleted = User::factory()->create(['full_name' => 'Deleted User']);
    $deleted->delete();

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->set('filterStatus', 'active')
        ->assertSee('Active User')
        ->assertDontSee('Deleted User');
});

test('user list filters inactive (deleted) users', function () {
    $actor   = userPromoteUser();
    $active  = User::factory()->create(['full_name' => 'Active User']);
    $deleted = User::factory()->create(['full_name' => 'Deleted User']);
    $deleted->delete();

    Livewire::actingAs($actor)
        ->test(UserList::class)
        ->set('filterStatus', 'inactive')
        ->assertDontSee('Active User')
        ->assertSee('Deleted User');
});

// ─── Route access: UserDetail ─────────────────────────────────────────────────

test('unauthenticated user is redirected from user detail', function () {
    $target = User::factory()->create();
    $this->get(route('admin.users.show', $target))
        ->assertRedirect(route('login'));
});

test('user without permissions gets 403 on user detail', function () {
    $actor  = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->get(route('admin.users.show', $target))
        ->assertForbidden();
});

test('user with user.promote can access user detail', function () {
    $actor  = userPromoteUser();
    $target = User::factory()->create();

    $this->actingAs($actor)
        ->get(route('admin.users.show', $target))
        ->assertOk();
});

// ─── Promote action ───────────────────────────────────────────────────────────

test('user with user.promote can promote an employee to tech', function () {
    $actor  = userPromoteUser();
    $target = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('confirmPromote')
        ->call('promote');

    $this->assertDatabaseHas('users', ['id' => $target->id, 'is_tech' => true]);
});

test('promoting creates a tech profile', function () {
    $actor  = userPromoteUser();
    $target = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('confirmPromote')
        ->call('promote');

    $this->assertDatabaseHas('tech_profiles', ['user_id' => $target->id]);
});

test('promoting does not create duplicate tech profile if one exists', function () {
    $actor  = userPromoteUser();
    $target = User::factory()->tech()->create();
    TechProfile::create([
        'user_id'     => $target->id,
        'promoted_by' => $actor->id,
        'promoted_at' => now(),
    ]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('promote');

    $this->assertDatabaseCount('tech_profiles', 1);
});

test('UserPromotedToTech event is dispatched on promotion', function () {
    Event::fake([UserPromotedToTech::class]);

    $actor  = userPromoteUser();
    $target = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('confirmPromote')
        ->call('promote');

    Event::assertDispatched(UserPromotedToTech::class, function ($e) use ($target) {
        return $e->user->id === $target->id;
    });
});

test('user without user.promote cannot promote', function () {
    // User has manage-permissions (can mount) but not user.promote
    $actor = userManagePermsUser();
    $target = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('confirmPromote')
        ->assertForbidden();
});

test('promoting already-tech user shows error and does not create duplicate', function () {
    $actor  = userPromoteUser();
    $target = User::factory()->tech()->create();
    TechProfile::create([
        'user_id'     => $target->id,
        'promoted_by' => $actor->id,
        'promoted_at' => now(),
    ]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('promote');

    $this->assertDatabaseCount('tech_profiles', 1);
});

// ─── Promoted tech visible in assignment/group flows ─────────────────────────

test('promoted tech user has is_tech flag set and tech profile', function () {
    $actor  = userPromoteUser();
    $target = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($actor)
        ->test(UserDetail::class, ['user' => $target])
        ->call('confirmPromote')
        ->call('promote');

    $target->refresh();
    expect($target->is_tech)->toBeTrue();
    expect($target->techProfile)->not->toBeNull();
});

// ─── Admin nav visibility ─────────────────────────────────────────────────────

test('admin nav shows users link for user with user.promote', function () {
    $user = userPromoteUser();
    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertSee(__('admin.nav_users'));
});

test('admin nav shows users link for user with user.manage-permissions', function () {
    $user = userManagePermsUser();
    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertSee(__('admin.nav_users'));
});
