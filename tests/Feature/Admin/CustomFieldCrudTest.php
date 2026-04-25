<?php

use App\Modules\Admin\Livewire\CustomFields\CustomFieldIndex;
use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldValue; // factory creates real Ticket via cross-module factory
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cfAdminUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'system.manage-custom-fields')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

beforeEach(fn () => $this->seed(PermissionSeeder::class));

// ─── Route access ─────────────────────────────────────────────────────────────

test('unauthenticated user is redirected from custom fields', function () {
    $this->get(route('admin.custom-fields.index'))
        ->assertRedirect(route('login'));
});

test('user without system.manage-custom-fields is forbidden', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.custom-fields.index'))
        ->assertForbidden();
});

test('user with system.manage-custom-fields can access custom fields', function () {
    $user = cfAdminUser();
    $this->actingAs($user)
        ->get(route('admin.custom-fields.index'))
        ->assertOk();
});

test('super user can access custom fields', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.custom-fields.index'))
        ->assertOk();
});

// ─── Create — all 6 types ─────────────────────────────────────────────────────

test('authorised user can create a text custom field', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'ملاحظات')
        ->set('formNameEn', 'Notes')
        ->set('formFieldType', 'text')
        ->set('formScopeType', 'global')
        ->set('formIsRequired', false)
        ->set('formIsActive', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomField::where('name_en', 'Notes')->exists())->toBeTrue();
    expect(CustomField::where('name_en', 'Notes')->first()->version)->toBe(1);
});

test('authorised user can create a number custom field', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'كمية')
        ->set('formNameEn', 'Quantity')
        ->set('formFieldType', 'number')
        ->set('formScopeType', 'global')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomField::where('name_en', 'Quantity')->first()->field_type)->toBe('number');
});

test('authorised user can create a dropdown custom field', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'نظام التشغيل')
        ->set('formNameEn', 'OS')
        ->set('formFieldType', 'dropdown')
        ->set('formScopeType', 'global')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomField::where('name_en', 'OS')->first()->field_type)->toBe('dropdown');
});

test('authorised user can create a multi_select custom field', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'الأعراض')
        ->set('formNameEn', 'Symptoms')
        ->set('formFieldType', 'multi_select')
        ->set('formScopeType', 'global')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomField::where('name_en', 'Symptoms')->first()->field_type)->toBe('multi_select');
});

test('authorised user can create a date custom field', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'تاريخ الشراء')
        ->set('formNameEn', 'Purchase Date')
        ->set('formFieldType', 'date')
        ->set('formScopeType', 'global')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomField::where('name_en', 'Purchase Date')->first()->field_type)->toBe('date');
});

test('authorised user can create a checkbox custom field', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'ضمان')
        ->set('formNameEn', 'Under Warranty')
        ->set('formFieldType', 'checkbox')
        ->set('formScopeType', 'global')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomField::where('name_en', 'Under Warranty')->first()->field_type)->toBe('checkbox');
});

// ─── Category-scoped field ────────────────────────────────────────────────────

test('authorised user can create a category-scoped custom field', function () {
    $user     = cfAdminUser();
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'رقم الجهاز')
        ->set('formNameEn', 'Device Serial')
        ->set('formFieldType', 'text')
        ->set('formScopeType', 'category')
        ->set('formScopeCategoryId', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $field = CustomField::where('name_en', 'Device Serial')->firstOrFail();
    expect($field->scope_type)->toBe('category');
    expect($field->scope_category_id)->toBe($category->id);
});

// ─── Create validation ────────────────────────────────────────────────────────

test('create requires name_ar and name_en', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['formNameAr', 'formNameEn']);
});

test('invalid field_type is rejected', function () {
    $user = cfAdminUser();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'اختبار')
        ->set('formNameEn', 'Test')
        ->set('formFieldType', 'invalid_type')
        ->call('save')
        ->assertHasErrors(['formFieldType']);
});

// ─── Edit / version bump ──────────────────────────────────────────────────────

test('editing a custom field bumps the version', function () {
    $user  = cfAdminUser();
    $field = CustomField::factory()->create(['version' => 1]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openEdit', $field->id)
        ->set('formNameEn', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($field->fresh()->version)->toBe(2);
    expect($field->fresh()->name_en)->toBe('Updated Name');
});

test('each subsequent edit continues to increment version', function () {
    $user  = cfAdminUser();
    $field = CustomField::factory()->create(['version' => 1]);

    Livewire::actingAs($user)->test(CustomFieldIndex::class)
        ->call('openEdit', $field->id)
        ->set('formNameEn', 'Edit One')
        ->call('save');

    Livewire::actingAs($user)->test(CustomFieldIndex::class)
        ->call('openEdit', $field->fresh()->id)
        ->set('formNameEn', 'Edit Two')
        ->call('save');

    expect($field->fresh()->version)->toBe(3);
});

// ─── field_type change blocked when values exist ──────────────────────────────

test('changing field_type when values exist returns a validation error', function () {
    $user  = cfAdminUser();
    $field = CustomField::factory()->create(['field_type' => 'text', 'version' => 1]);

    // Simulate an existing value row via factory (creates a real ticket)
    CustomFieldValue::factory()->create(['custom_field_id' => $field->id, 'value' => 'some value']);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openEdit', $field->id)
        ->set('formFieldType', 'number')
        ->call('save')
        ->assertHasErrors(['formFieldType']);

    // Version must not have changed
    expect($field->fresh()->version)->toBe(1);
    expect($field->fresh()->field_type)->toBe('text');
});

test('field_type can be changed when no values exist', function () {
    $user  = cfAdminUser();
    $field = CustomField::factory()->create(['field_type' => 'text', 'version' => 1]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openEdit', $field->id)
        ->set('formFieldType', 'number')
        ->call('save')
        ->assertHasNoErrors();

    expect($field->fresh()->field_type)->toBe('number');
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('toggleActive deactivates an active custom field', function () {
    $user  = cfAdminUser();
    $field = CustomField::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('toggleActive', $field->id);

    expect($field->fresh()->is_active)->toBeFalse();
});

test('toggleActive reactivates an inactive custom field', function () {
    $user  = cfAdminUser();
    $field = CustomField::factory()->create(['is_active' => false]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('toggleActive', $field->id);

    expect($field->fresh()->is_active)->toBeTrue();
});

// ─── Soft-delete ──────────────────────────────────────────────────────────────

test('delete soft-deletes a custom field', function () {
    $user  = cfAdminUser();
    $field = CustomField::factory()->create();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('delete', $field->id);

    expect(CustomField::find($field->id))->toBeNull();
    expect(CustomField::withTrashed()->find($field->id)->deleted_at)->not->toBeNull();
});

test('soft-deleted fields are excluded from active scope', function () {
    $active  = CustomField::factory()->create(['is_active' => true]);
    $deleted = CustomField::factory()->create(['is_active' => true]);
    $deleted->delete();

    expect(CustomField::active()->count())->toBe(1);
    expect(CustomField::active()->first()->id)->toBe($active->id);
});

// ─── Unauthorized ─────────────────────────────────────────────────────────────

test('unauthorized user gets 403 on custom fields route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.custom-fields.index'))
        ->assertForbidden();
});

// ─── Display order reorder ────────────────────────────────────────────────────

test('reorder updates display_order of all fields', function () {
    $user = cfAdminUser();
    $a    = CustomField::factory()->create(['display_order' => 0]);
    $b    = CustomField::factory()->create(['display_order' => 1]);
    $c    = CustomField::factory()->create(['display_order' => 2]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('reorder', [$c->id, $a->id, $b->id]);

    expect($c->fresh()->display_order)->toBe(0);
    expect($a->fresh()->display_order)->toBe(1);
    expect($b->fresh()->display_order)->toBe(2);
});
