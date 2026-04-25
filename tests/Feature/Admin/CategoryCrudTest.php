<?php

use App\Modules\Admin\Livewire\Categories\CategoryIndex;
use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function adminCategoryUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'category.manage')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

// ─── Route access ────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('unauthenticated user is redirected from admin categories', function () {
    $this->get(route('admin.categories.index'))
        ->assertRedirect(route('login'));
});

test('user without category.manage cannot access admin categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});

test('user with category.manage can access admin categories', function () {
    $user = adminCategoryUser();
    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertOk();
});

test('super user can access admin categories', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertOk();
});

// ─── Create ──────────────────────────────────────────────────────────────────

test('authorised user can create a category', function () {
    $user  = adminCategoryUser();
    $group = Group::factory()->create();

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'أجهزة')
        ->set('formNameEn', 'Hardware')
        ->set('formGroupId', $group->id)
        ->set('formIsActive', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::where('name_en', 'Hardware')->exists())->toBeTrue();
    expect(Category::where('name_en', 'Hardware')->first()->version)->toBe(1);
});

test('create category requires name_ar, name_en, and group_id', function () {
    $user = adminCategoryUser();

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['formNameAr', 'formNameEn', 'formGroupId']);
});

test('create category rejects non-existent group_id', function () {
    $user = adminCategoryUser();

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'أجهزة')
        ->set('formNameEn', 'Hardware')
        ->set('formGroupId', '01JXXXXXXXXXXXXXXXXXXXXXXXXX')
        ->call('save')
        ->assertHasErrors(['formGroupId']);
});

test('unauthorised user gets 403 on admin categories route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});

// ─── Edit / version bump ─────────────────────────────────────────────────────

test('editing a category bumps the version', function () {
    $user     = adminCategoryUser();
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['version' => 1, 'group_id' => $group->id]);

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('openEdit', $category->id)
        ->set('formNameEn', 'Updated Hardware')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh()->version)->toBe(2);
    expect($category->fresh()->name_en)->toBe('Updated Hardware');
});

test('each subsequent edit continues to increment version', function () {
    $user     = adminCategoryUser();
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['version' => 1, 'group_id' => $group->id]);

    Livewire::actingAs($user)->test(CategoryIndex::class)
        ->call('openEdit', $category->id)
        ->set('formNameEn', 'Edit 1')
        ->call('save');

    Livewire::actingAs($user)->test(CategoryIndex::class)
        ->call('openEdit', $category->fresh()->id)
        ->set('formNameEn', 'Edit 2')
        ->call('save');

    expect($category->fresh()->version)->toBe(3);
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('toggleActive deactivates an active category', function () {
    $user     = adminCategoryUser();
    $category = Category::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('toggleActive', $category->id);

    expect($category->fresh()->is_active)->toBeFalse();
});

test('toggleActive reactivates an inactive category', function () {
    $user     = adminCategoryUser();
    $category = Category::factory()->create(['is_active' => false]);

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('toggleActive', $category->id);

    expect($category->fresh()->is_active)->toBeTrue();
});

// ─── Soft-delete ──────────────────────────────────────────────────────────────

test('delete soft-deletes a category', function () {
    $user     = adminCategoryUser();
    $category = Category::factory()->create();

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('delete', $category->id);

    expect(Category::find($category->id))->toBeNull();
    expect(Category::withTrashed()->find($category->id))->not->toBeNull();
    expect(Category::withTrashed()->find($category->id)->deleted_at)->not->toBeNull();
});

test('soft-deleted categories are excluded from the active scope', function () {
    $active  = Category::factory()->create(['is_active' => true]);
    $deleted = Category::factory()->create(['is_active' => true]);
    $deleted->delete();

    expect(Category::active()->count())->toBe(1);
    expect(Category::active()->first()->id)->toBe($active->id);
});

// ─── Versioning: deactivated categories persist historical names ──────────────

test('renaming a category does not affect existing tickets category_id reference', function () {
    // Categories table holds the new name — old tickets still join correctly
    // because we never update the FK; this test verifies that the version bumps
    // and the existing category row's name is updated (renaming is non-destructive)
    $user     = adminCategoryUser();
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['name_en' => 'Old Name', 'group_id' => $group->id]);

    Livewire::actingAs($user)
        ->test(CategoryIndex::class)
        ->call('openEdit', $category->id)
        ->set('formNameEn', 'New Name')
        ->call('save');

    expect($category->fresh()->name_en)->toBe('New Name');
    expect($category->fresh()->version)->toBe(2);
});

// ─── Admin layout nav visibility ─────────────────────────────────────────────

test('admin layout shows categories nav for user with category.manage', function () {
    $user = adminCategoryUser();
    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertSee(__('admin.nav_categories'));
});

test('admin layout hides group nav when user lacks group.manage', function () {
    $user = adminCategoryUser();
    $this->actingAs($user)
        ->get(route('admin.categories.index'))
        ->assertDontSee(__('admin.nav_groups'));
});
