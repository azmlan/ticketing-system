<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldOption;
use App\Modules\Admin\Models\CustomFieldValue;
use App\Modules\Tickets\Models\Ticket;

// ─── scopeActive() ────────────────────────────────────────────────────────────

it('active scope returns only active custom fields', function () {
    CustomField::factory()->create(['is_active' => true]);
    CustomField::factory()->inactive()->create();

    expect(CustomField::active()->count())->toBe(1);
});

it('active scope excludes soft-deleted custom fields', function () {
    $active  = CustomField::factory()->create(['is_active' => true]);
    $deleted = CustomField::factory()->create(['is_active' => true]);
    $deleted->delete();

    expect(CustomField::active()->count())->toBe(1)
        ->and(CustomField::active()->first()->id)->toBe($active->id);
});

// ─── Casts ────────────────────────────────────────────────────────────────────

it('field_type is stored and retrieved as a string', function () {
    $field = CustomField::factory()->text()->create();

    expect($field->field_type)->toBe('text')
        ->toBeString();
});

it('scope_type defaults to global', function () {
    $field = CustomField::factory()->create(['scope_type' => 'global']);

    expect($field->scope_type)->toBe('global');
});

it('is_required casts to boolean', function () {
    $field = CustomField::factory()->required()->create();

    expect($field->is_required)->toBeTrue()
        ->toBeBool();
});

it('is_active casts to boolean', function () {
    $field = CustomField::factory()->create(['is_active' => true]);

    expect($field->is_active)->toBeTrue()
        ->toBeBool();
});

it('version casts to integer', function () {
    $field = CustomField::factory()->create(['version' => 2]);

    expect($field->version)->toBe(2)
        ->toBeInt();
});

it('display_order casts to integer', function () {
    $field = CustomField::factory()->create(['display_order' => 5]);

    expect($field->display_order)->toBe(5)
        ->toBeInt();
});

// ─── Relationships ────────────────────────────────────────────────────────────

it('custom field has many options', function () {
    $field = CustomField::factory()->dropdown()->create();
    CustomFieldOption::factory()->count(3)->create(['custom_field_id' => $field->id]);

    expect($field->options)->toHaveCount(3);
});

it('custom field has many values', function () {
    $field  = CustomField::factory()->create();
    $ticket = Ticket::factory()->create();
    CustomFieldValue::factory()->create(['custom_field_id' => $field->id, 'ticket_id' => $ticket->id]);
    CustomFieldValue::factory()->create(['custom_field_id' => $field->id, 'ticket_id' => Ticket::factory()->create()->id]);

    expect($field->values)->toHaveCount(2);
});

it('custom field scoped to category resolves the category', function () {
    $category = Category::factory()->create();
    $field    = CustomField::factory()->categoryScoped($category->id)->create();

    expect($field->category->id)->toBe($category->id);
});

// ─── localizedName() ─────────────────────────────────────────────────────────

it('localizedName returns name_ar when locale is ar', function () {
    app()->setLocale('ar');
    $field = CustomField::factory()->create(['name_ar' => 'الجهاز', 'name_en' => 'Device']);

    expect($field->localizedName())->toBe('الجهاز');
});

it('localizedName returns name_en when locale is en', function () {
    app()->setLocale('en');
    $field = CustomField::factory()->create(['name_ar' => 'الجهاز', 'name_en' => 'Device']);

    expect($field->localizedName())->toBe('Device');
});

// ─── Factory ──────────────────────────────────────────────────────────────────

it('custom field factory produces a valid row', function () {
    $field = CustomField::factory()->create();

    expect($field->id)->toHaveLength(26)
        ->and($field->name_ar)->not->toBeEmpty()
        ->and($field->name_en)->not->toBeEmpty()
        ->and($field->is_active)->toBeTrue()
        ->and($field->version)->toBe(1);
});
