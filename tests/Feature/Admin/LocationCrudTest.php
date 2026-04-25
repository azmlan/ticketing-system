<?php

use App\Modules\Admin\Livewire\Locations\LocationIndex;
use App\Modules\Shared\Models\Location;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function locManageUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'system.manage-locations')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

// ─── Route access ─────────────────────────────────────────────────────────────

test('unauthenticated user is redirected from admin locations', function () {
    $this->get(route('admin.locations.index'))
        ->assertRedirect(route('login'));
});

test('user without system.manage-locations cannot access admin locations', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.locations.index'))
        ->assertForbidden();
});

test('user with system.manage-locations can access admin locations', function () {
    $user = locManageUser();
    $this->actingAs($user)
        ->get(route('admin.locations.index'))
        ->assertOk();
});

test('super user can access admin locations', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.locations.index'))
        ->assertOk();
});

// ─── Create ──────────────────────────────────────────────────────────────────

test('authorised user can create a location', function () {
    $user = locManageUser();

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'المبنى الرئيسي')
        ->set('formNameEn', 'Main Building')
        ->set('formSortOrder', 1)
        ->set('formIsActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $loc = Location::first();
    expect($loc)->not->toBeNull();
    expect($loc->name_ar)->toBe('المبنى الرئيسي');
    expect($loc->name_en)->toBe('Main Building');
    expect($loc->sort_order)->toBe(1);
    expect($loc->is_active)->toBeTrue();
});

test('create requires bilingual names', function () {
    $user = locManageUser();

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->call('openCreate')
        ->set('formNameAr', '')
        ->set('formNameEn', '')
        ->call('save')
        ->assertHasErrors(['formNameAr', 'formNameEn']);
});

test('unauthorised user cannot mount LocationIndex', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->assertForbidden();
});

// ─── Edit ────────────────────────────────────────────────────────────────────

test('authorised user can edit a location', function () {
    $user = locManageUser();
    $loc = Location::factory()->create(['name_en' => 'Campus A', 'sort_order' => 3]);

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->call('openEdit', $loc->id)
        ->assertSet('formNameEn', 'Campus A')
        ->assertSet('formSortOrder', 3)
        ->set('formNameEn', 'North Campus')
        ->set('formSortOrder', 7)
        ->call('save')
        ->assertHasNoErrors();

    expect($loc->fresh()->name_en)->toBe('North Campus');
    expect($loc->fresh()->sort_order)->toBe(7);
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('authorised user can deactivate a location', function () {
    $user = locManageUser();
    $loc = Location::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->call('toggleActive', $loc->id);

    expect($loc->fresh()->is_active)->toBeFalse();
});

test('authorised user can reactivate a location', function () {
    $user = locManageUser();
    $loc = Location::factory()->inactive()->create();

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->call('toggleActive', $loc->id);

    expect($loc->fresh()->is_active)->toBeTrue();
});

// ─── Soft delete ─────────────────────────────────────────────────────────────

test('authorised user can soft-delete a location', function () {
    $user = locManageUser();
    $loc = Location::factory()->create();

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->call('delete', $loc->id);

    expect(Location::find($loc->id))->toBeNull();
    expect(Location::withTrashed()->find($loc->id))->not->toBeNull();
});

test('deleted locations are shown in the list via withTrashed', function () {
    $user = locManageUser();
    $loc = Location::factory()->create(['name_en' => 'Old Site']);
    $loc->delete();

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->assertSee('Old Site');
});

// ─── Search ──────────────────────────────────────────────────────────────────

test('search filters locations by name', function () {
    $user = locManageUser();
    Location::factory()->create(['name_en' => 'Riyadh Campus', 'name_ar' => 'حرم الرياض']);
    Location::factory()->create(['name_en' => 'Jeddah Branch', 'name_ar' => 'فرع جدة']);

    Livewire::actingAs($user)
        ->test(LocationIndex::class)
        ->set('search', 'Riyadh')
        ->assertSee('Riyadh Campus')
        ->assertDontSee('Jeddah Branch');
});

// ─── Scope: active-only for dropdowns ────────────────────────────────────────

test('inactive locations are excluded from active scope', function () {
    $active   = Location::factory()->create(['is_active' => true]);
    $inactive = Location::factory()->inactive()->create();

    $results = Location::active()->get();

    expect($results->pluck('id'))->toContain($active->id);
    expect($results->pluck('id'))->not->toContain($inactive->id);
});

test('soft-deleted locations are excluded from active scope', function () {
    $loc = Location::factory()->create();
    $loc->delete();

    $results = Location::active()->get();

    expect($results->pluck('id'))->not->toContain($loc->id);
});

// ─── localizedName ────────────────────────────────────────────────────────────

test('localizedName returns Arabic name when locale is ar', function () {
    app()->setLocale('ar');
    $loc = Location::factory()->create(['name_ar' => 'المبنى الرئيسي', 'name_en' => 'Main Building']);
    expect($loc->localizedName())->toBe('المبنى الرئيسي');
});

test('localizedName returns English name when locale is en', function () {
    app()->setLocale('en');
    $loc = Location::factory()->create(['name_ar' => 'المبنى الرئيسي', 'name_en' => 'Main Building']);
    expect($loc->localizedName())->toBe('Main Building');
});
