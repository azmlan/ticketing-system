<?php

use App\Modules\Admin\Livewire\CustomFields\CustomFieldIndex;
use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldOption;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cfOptionAdminUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'system.manage-custom-fields')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

beforeEach(fn () => $this->seed(PermissionSeeder::class));

// ─── Add option ───────────────────────────────────────────────────────────────

test('authorised user can add an option to a dropdown field', function () {
    $user  = cfOptionAdminUser();
    $field = CustomField::factory()->dropdown()->create();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openOptions', $field->id)
        ->set('optionValueAr', 'ويندوز')
        ->set('optionValueEn', 'Windows')
        ->call('saveOption')
        ->assertHasNoErrors();

    expect(CustomFieldOption::where('value_en', 'Windows')->where('custom_field_id', $field->id)->exists())->toBeTrue();
});

test('adding an option requires value_ar and value_en', function () {
    $user  = cfOptionAdminUser();
    $field = CustomField::factory()->dropdown()->create();

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openOptions', $field->id)
        ->call('saveOption')
        ->assertHasErrors(['optionValueAr', 'optionValueEn']);
});

test('authorised user can add an option to a multi_select field', function () {
    $user  = cfOptionAdminUser();
    $field = CustomField::factory()->create(['field_type' => 'multi_select']);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openOptions', $field->id)
        ->set('optionValueAr', 'لينكس')
        ->set('optionValueEn', 'Linux')
        ->call('saveOption')
        ->assertHasNoErrors();

    expect(CustomFieldOption::where('value_en', 'Linux')->exists())->toBeTrue();
});

// ─── Edit option ──────────────────────────────────────────────────────────────

test('authorised user can edit an existing option', function () {
    $user   = cfOptionAdminUser();
    $field  = CustomField::factory()->dropdown()->create();
    $option = CustomFieldOption::factory()->create(['custom_field_id' => $field->id, 'value_en' => 'Old']);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openOptions', $field->id)
        ->call('openEditOption', $option->id)
        ->set('optionValueAr', 'جديد')
        ->set('optionValueEn', 'New')
        ->call('saveOption')
        ->assertHasNoErrors();

    expect($option->fresh()->value_en)->toBe('New');
    expect($option->fresh()->value_ar)->toBe('جديد');
});

// ─── Delete option ────────────────────────────────────────────────────────────

test('authorised user can soft-delete an option', function () {
    $user   = cfOptionAdminUser();
    $field  = CustomField::factory()->dropdown()->create();
    $option = CustomFieldOption::factory()->create(['custom_field_id' => $field->id]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openOptions', $field->id)
        ->call('deleteOption', $option->id);

    expect(CustomFieldOption::find($option->id))->toBeNull();
    expect(CustomFieldOption::withTrashed()->find($option->id)->deleted_at)->not->toBeNull();
});

// ─── Reorder options ──────────────────────────────────────────────────────────

test('reorderOptions updates sort_order of all options', function () {
    $user  = cfOptionAdminUser();
    $field = CustomField::factory()->dropdown()->create();
    $a     = CustomFieldOption::factory()->create(['custom_field_id' => $field->id, 'sort_order' => 0]);
    $b     = CustomFieldOption::factory()->create(['custom_field_id' => $field->id, 'sort_order' => 1]);
    $c     = CustomFieldOption::factory()->create(['custom_field_id' => $field->id, 'sort_order' => 2]);

    Livewire::actingAs($user)
        ->test(CustomFieldIndex::class)
        ->call('openOptions', $field->id)
        ->call('reorderOptions', [$c->id, $a->id, $b->id]);

    expect($c->fresh()->sort_order)->toBe(0);
    expect($a->fresh()->sort_order)->toBe(1);
    expect($b->fresh()->sort_order)->toBe(2);
});

// ─── Soft-deleted options excluded from active scope ─────────────────────────

test('soft-deleted option is excluded from active scope', function () {
    $field  = CustomField::factory()->dropdown()->create();
    $active = CustomFieldOption::factory()->create(['custom_field_id' => $field->id, 'is_active' => true]);
    $gone   = CustomFieldOption::factory()->create(['custom_field_id' => $field->id, 'is_active' => true]);
    $gone->delete();

    expect(CustomFieldOption::active()->count())->toBe(1);
    expect(CustomFieldOption::active()->first()->id)->toBe($active->id);
});

// ─── Unauthorized ─────────────────────────────────────────────────────────────

test('unauthorized user gets 403 on custom fields route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.custom-fields.index'))
        ->assertForbidden();
});
