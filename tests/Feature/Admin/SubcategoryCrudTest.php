<?php

use App\Modules\Admin\Livewire\Categories\SubcategoryIndex;
use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function subAdminUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'category.manage')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

function makeCategoryWithGroup(): Category
{
    return Category::factory()->create(['group_id' => Group::factory()->create()->id]);
}

// ─── Access ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('user without category.manage cannot access subcategory page', function () {
    $user     = User::factory()->create();
    $category = makeCategoryWithGroup();
    $this->actingAs($user)
        ->get(route('admin.categories.subcategories', $category))
        ->assertForbidden();
});

test('user with category.manage can access subcategory page', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();
    $this->actingAs($user)
        ->get(route('admin.categories.subcategories', $category))
        ->assertOk();
});

// ─── Create ──────────────────────────────────────────────────────────────────

test('authorised user can create a subcategory under a category', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();

    Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $category])
        ->call('openCreate')
        ->set('formNameAr', 'طابعات')
        ->set('formNameEn', 'Printers')
        ->set('formIsRequired', false)
        ->set('formIsActive', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(
        Subcategory::where('name_en', 'Printers')->where('category_id', $category->id)->exists()
    )->toBeTrue();
    expect(
        Subcategory::where('name_en', 'Printers')->first()->version
    )->toBe(1);
});

test('create subcategory requires name_ar and name_en', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();

    Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $category])
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['formNameAr', 'formNameEn']);
});

// ─── is_required flag ────────────────────────────────────────────────────────

test('is_required flag is persisted correctly', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();

    Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $category])
        ->call('openCreate')
        ->set('formNameAr', 'برمجيات')
        ->set('formNameEn', 'Software')
        ->set('formIsRequired', true)
        ->call('save');

    $sub = Subcategory::where('name_en', 'Software')->firstOrFail();
    expect($sub->is_required)->toBeTrue();
});

// ─── Edit / version bump ─────────────────────────────────────────────────────

test('editing a subcategory bumps the version', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();
    $sub      = Subcategory::factory()->create(['category_id' => $category->id, 'version' => 1]);

    Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $category])
        ->call('openEdit', $sub->id)
        ->set('formNameEn', 'Updated Sub')
        ->call('save')
        ->assertHasNoErrors();

    expect($sub->fresh()->version)->toBe(2);
    expect($sub->fresh()->name_en)->toBe('Updated Sub');
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('toggleActive deactivates an active subcategory', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();
    $sub      = Subcategory::factory()->create(['category_id' => $category->id, 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $category])
        ->call('toggleActive', $sub->id);

    expect($sub->fresh()->is_active)->toBeFalse();
});

// ─── Soft-delete ──────────────────────────────────────────────────────────────

test('delete soft-deletes a subcategory', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();
    $sub      = Subcategory::factory()->create(['category_id' => $category->id]);

    Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $category])
        ->call('delete', $sub->id);

    expect(Subcategory::find($sub->id))->toBeNull();
    expect(Subcategory::withTrashed()->find($sub->id)->deleted_at)->not->toBeNull();
});

test('soft-deleted subcategories are excluded from the active scope', function () {
    $category = makeCategoryWithGroup();
    $active   = Subcategory::factory()->create(['category_id' => $category->id, 'is_active' => true]);
    $deleted  = Subcategory::factory()->create(['category_id' => $category->id, 'is_active' => true]);
    $deleted->delete();

    expect(Subcategory::active()->count())->toBe(1);
    expect(Subcategory::active()->first()->id)->toBe($active->id);
});

// ─── Scoped to category ───────────────────────────────────────────────────────

test('subcategory list only shows subcategories for its own category', function () {
    $user      = subAdminUser();
    $catA      = makeCategoryWithGroup();
    $catB      = makeCategoryWithGroup();
    $subA      = Subcategory::factory()->create(['category_id' => $catA->id, 'name_en' => 'Sub A']);
    $subB      = Subcategory::factory()->create(['category_id' => $catB->id, 'name_en' => 'Sub B']);

    $component = Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $catA]);

    $component->assertSee('Sub A')
              ->assertDontSee('Sub B');
});

// ─── Versioning: unchanged original category subcategories ───────────────────

test('deactivating subcategory does not change version', function () {
    $user     = subAdminUser();
    $category = makeCategoryWithGroup();
    $sub      = Subcategory::factory()->create(['category_id' => $category->id, 'version' => 1, 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(SubcategoryIndex::class, ['category' => $category])
        ->call('toggleActive', $sub->id);

    expect($sub->fresh()->version)->toBe(1);
});
