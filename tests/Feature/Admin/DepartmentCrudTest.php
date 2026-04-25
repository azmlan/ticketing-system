<?php

use App\Modules\Admin\Livewire\Departments\DepartmentIndex;
use App\Modules\Shared\Models\Department;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function deptManageUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'system.manage-departments')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

// ─── Route access ─────────────────────────────────────────────────────────────

test('unauthenticated user is redirected from admin departments', function () {
    $this->get(route('admin.departments.index'))
        ->assertRedirect(route('login'));
});

test('user without system.manage-departments cannot access admin departments', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.departments.index'))
        ->assertForbidden();
});

test('user with system.manage-departments can access admin departments', function () {
    $user = deptManageUser();
    $this->actingAs($user)
        ->get(route('admin.departments.index'))
        ->assertOk();
});

test('super user can access admin departments', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.departments.index'))
        ->assertOk();
});

// ─── Create ──────────────────────────────────────────────────────────────────

test('authorised user can create a department', function () {
    $user = deptManageUser();

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'تقنية المعلومات')
        ->set('formNameEn', 'Information Technology')
        ->set('formSortOrder', 1)
        ->set('formIsActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $dept = Department::first();
    expect($dept)->not->toBeNull();
    expect($dept->name_ar)->toBe('تقنية المعلومات');
    expect($dept->name_en)->toBe('Information Technology');
    expect($dept->sort_order)->toBe(1);
    expect($dept->is_active)->toBeTrue();
});

test('create requires bilingual names', function () {
    $user = deptManageUser();

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('openCreate')
        ->set('formNameAr', '')
        ->set('formNameEn', '')
        ->call('save')
        ->assertHasErrors(['formNameAr', 'formNameEn']);
});

test('unauthorised user cannot mount DepartmentIndex', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->assertForbidden();
});

// ─── Edit ────────────────────────────────────────────────────────────────────

test('authorised user can edit a department', function () {
    $user = deptManageUser();
    $dept = Department::factory()->create(['name_en' => 'HR', 'sort_order' => 5]);

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('openEdit', $dept->id)
        ->assertSet('formNameEn', 'HR')
        ->assertSet('formSortOrder', 5)
        ->set('formNameEn', 'Human Resources')
        ->set('formSortOrder', 10)
        ->call('save')
        ->assertHasNoErrors();

    expect($dept->fresh()->name_en)->toBe('Human Resources');
    expect($dept->fresh()->sort_order)->toBe(10);
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('authorised user can deactivate a department', function () {
    $user = deptManageUser();
    $dept = Department::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('toggleActive', $dept->id);

    expect($dept->fresh()->is_active)->toBeFalse();
});

test('authorised user can reactivate a department', function () {
    $user = deptManageUser();
    $dept = Department::factory()->inactive()->create();

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('toggleActive', $dept->id);

    expect($dept->fresh()->is_active)->toBeTrue();
});

// ─── Soft delete ─────────────────────────────────────────────────────────────

test('authorised user can soft-delete a department', function () {
    $user = deptManageUser();
    $dept = Department::factory()->create();

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->call('delete', $dept->id);

    expect(Department::find($dept->id))->toBeNull();
    expect(Department::withTrashed()->find($dept->id))->not->toBeNull();
});

test('deleted departments are shown in the list via withTrashed', function () {
    $user = deptManageUser();
    $dept = Department::factory()->create(['name_en' => 'Finance']);
    $dept->delete();

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->assertSee('Finance');
});

// ─── Search ──────────────────────────────────────────────────────────────────

test('search filters departments by name', function () {
    $user = deptManageUser();
    Department::factory()->create(['name_en' => 'Engineering', 'name_ar' => 'هندسة']);
    Department::factory()->create(['name_en' => 'Finance', 'name_ar' => 'مالية']);

    Livewire::actingAs($user)
        ->test(DepartmentIndex::class)
        ->set('search', 'Engineering')
        ->assertSee('Engineering')
        ->assertDontSee('Finance');
});

// ─── Scope: active-only for dropdowns ────────────────────────────────────────

test('inactive departments are excluded from active scope', function () {
    $active   = Department::factory()->create(['is_active' => true]);
    $inactive = Department::factory()->inactive()->create();

    $results = Department::active()->get();

    expect($results->pluck('id'))->toContain($active->id);
    expect($results->pluck('id'))->not->toContain($inactive->id);
});

test('soft-deleted departments are excluded from active scope', function () {
    $dept = Department::factory()->create();
    $dept->delete();

    $results = Department::active()->get();

    expect($results->pluck('id'))->not->toContain($dept->id);
});

// ─── localizedName ────────────────────────────────────────────────────────────

test('localizedName returns Arabic name when locale is ar', function () {
    app()->setLocale('ar');
    $dept = Department::factory()->create(['name_ar' => 'تقنية المعلومات', 'name_en' => 'IT']);
    expect($dept->localizedName())->toBe('تقنية المعلومات');
});

test('localizedName returns English name when locale is en', function () {
    app()->setLocale('en');
    $dept = Department::factory()->create(['name_ar' => 'تقنية المعلومات', 'name_en' => 'IT']);
    expect($dept->localizedName())->toBe('IT');
});
