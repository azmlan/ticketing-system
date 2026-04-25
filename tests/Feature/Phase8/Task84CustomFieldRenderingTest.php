<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldOption;
use App\Modules\Admin\Models\CustomFieldValue;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function cf84Category(): Category
{
    $group = Group::factory()->create();

    return Category::factory()->create(['group_id' => $group->id]);
}

function cf84Ticket(User $user, Category $category): Ticket
{
    return Ticket::withoutGlobalScopes()->create([
        'display_number' => 'TKT-0000001',
        'subject' => 'Test ticket',
        'description' => '<p>Test</p>',
        'status' => TicketStatus::AwaitingAssignment,
        'category_id' => $category->id,
        'group_id' => $category->group_id,
        'requester_id' => $user->id,
        'incident_origin' => 'web',
    ]);
}

// Use English locale in all Phase8 CF tests so assertSee uses name_en
beforeEach(function () {
    App::setLocale('en');
    RateLimiter::clear('ticket.create:*');
});

// ─── Global fields appear on create form ─────────────────────────────────────

test('global active custom field appears on ticket create form', function () {
    $user = User::factory()->create();
    CustomField::factory()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'is_active' => true,
        'name_ar' => 'Asset Tag',
        'name_en' => 'Asset Tag',
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertSee('Asset Tag');
});

test('global inactive field does not appear on create form', function () {
    $user = User::factory()->create();
    CustomField::factory()->inactive()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'name_ar' => 'Hidden Field',
        'name_en' => 'Hidden Field',
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertDontSee('Hidden Field');
});

test('soft-deleted global field does not appear on create form', function () {
    $user = User::factory()->create();
    $field = CustomField::factory()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'name_ar' => 'Deleted Field',
        'name_en' => 'Deleted Field',
    ]);
    $field->delete();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertDontSee('Deleted Field');
});

// ─── Category-scoped field visibility ────────────────────────────────────────

test('category-scoped field appears only when matching category is selected', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    CustomField::factory()->categoryScoped($category->id)->create([
        'field_type' => 'text',
        'name_ar' => 'Scoped Field',
        'name_en' => 'Scoped Field',
        'is_active' => true,
    ]);

    $component = Livewire::actingAs($user)->test(CreateTicket::class);
    $component->assertDontSee('Scoped Field');

    $component->set('category_id', $category->id)
        ->assertSee('Scoped Field');
});

test('category-scoped field disappears when a different category is selected', function () {
    $user = User::factory()->create();
    $catA = cf84Category();
    $catB = cf84Category();

    CustomField::factory()->categoryScoped($catA->id)->create([
        'field_type' => 'text',
        'name_ar' => 'Cat A Field',
        'name_en' => 'Cat A Field',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('category_id', $catA->id)
        ->assertSee('Cat A Field')
        ->set('category_id', $catB->id)
        ->assertDontSee('Cat A Field');
});

// ─── Required field validation ────────────────────────────────────────────────

test('required text custom field blocks ticket creation when empty', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    CustomField::factory()->required()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'name_ar' => 'Required Text',
        'name_en' => 'Required Text',
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->call('submit')
        ->assertHasErrors();

    expect(Ticket::withoutGlobalScopes()->count())->toBe(0);
});

test('required multi_select field blocks submission when nothing selected', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    $field = CustomField::factory()->create([
        'field_type' => 'multi_select',
        'scope_type' => 'global',
        'is_required' => true,
        'name_ar' => 'Multi Required',
        'name_en' => 'Multi Required',
    ]);
    CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Option A',
        'value_en' => 'Option A',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->call('submit')
        ->assertHasErrors();
});

// ─── Values are saved to custom_field_values ─────────────────────────────────

test('text custom field value is saved when ticket is created', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    $field = CustomField::factory()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->set('customFieldValues.'.$field->id, 'My Value')
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::withoutGlobalScopes()->first();
    $cfv = CustomFieldValue::where('ticket_id', $ticket->id)
        ->where('custom_field_id', $field->id)
        ->first();

    expect($cfv)->not->toBeNull()
        ->and($cfv->value)->toBe('My Value');
});

test('dropdown custom field stores the selected option id', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    $field = CustomField::factory()->create([
        'field_type' => 'dropdown',
        'scope_type' => 'global',
        'is_active' => true,
    ]);
    $option = CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Choice One',
        'value_en' => 'Choice One',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->set('customFieldValues.'.$field->id, $option->id)
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::withoutGlobalScopes()->first();
    $cfv = CustomFieldValue::where('ticket_id', $ticket->id)
        ->where('custom_field_id', $field->id)
        ->first();

    expect($cfv)->not->toBeNull()
        ->and($cfv->value)->toBe($option->id);
});

test('multi_select custom field stores JSON-encoded array of option ids', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    $field = CustomField::factory()->create([
        'field_type' => 'multi_select',
        'scope_type' => 'global',
        'is_active' => true,
    ]);
    $optA = CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Alpha', 'value_en' => 'Alpha', 'is_active' => true,
    ]);
    $optB = CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Beta', 'value_en' => 'Beta', 'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->set('customFieldValues.'.$field->id, [$optA->id, $optB->id])
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::withoutGlobalScopes()->first();
    $cfv = CustomFieldValue::where('ticket_id', $ticket->id)
        ->where('custom_field_id', $field->id)
        ->first();

    expect($cfv)->not->toBeNull();
    $stored = json_decode($cfv->value, true);
    expect($stored)->toContain($optA->id)->toContain($optB->id);
});

test('checkbox custom field stores 1 when checked', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    $field = CustomField::factory()->create([
        'field_type' => 'checkbox',
        'scope_type' => 'global',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->set('customFieldValues.'.$field->id, true)
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::withoutGlobalScopes()->first();
    $cfv = CustomFieldValue::where('ticket_id', $ticket->id)
        ->where('custom_field_id', $field->id)
        ->first();

    expect($cfv)->not->toBeNull()
        ->and($cfv->value)->toBe('1');
});

test('optional empty custom field does not create a custom_field_values row', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    CustomField::factory()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'is_active' => true,
        'is_required' => false,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->call('submit')
        ->assertHasNoErrors();

    expect(CustomFieldValue::count())->toBe(0);
});

// ─── Category-scoped field not saved when different category ──────────────────

test('category-scoped field value is not saved when ticket uses different category', function () {
    $user = User::factory()->create();
    $catA = cf84Category();
    $catB = cf84Category();

    $scopedField = CustomField::factory()->categoryScoped($catA->id)->create([
        'field_type' => 'text',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $catB->id)
        ->call('submit')
        ->assertHasNoErrors();

    expect(CustomFieldValue::where('custom_field_id', $scopedField->id)->count())->toBe(0);
});

// ─── ShowTicket: display existing values ─────────────────────────────────────

test('show ticket page displays custom field values', function () {
    $user = User::factory()->create();
    $category = cf84Category();
    $ticket = cf84Ticket($user, $category);

    $field = CustomField::factory()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'is_active' => true,
        'name_ar' => 'Asset Number',
        'name_en' => 'Asset Number',
    ]);

    CustomFieldValue::factory()->create([
        'ticket_id' => $ticket->id,
        'custom_field_id' => $field->id,
        'value' => 'PC-12345',
    ]);

    Livewire::actingAs($user)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Asset Number')
        ->assertSee('PC-12345');
});

test('show ticket displays custom field value even when field is deactivated', function () {
    $user = User::factory()->create();
    $category = cf84Category();
    $ticket = cf84Ticket($user, $category);

    $field = CustomField::factory()->inactive()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'name_ar' => 'Old Field',
        'name_en' => 'Old Field',
    ]);

    CustomFieldValue::factory()->create([
        'ticket_id' => $ticket->id,
        'custom_field_id' => $field->id,
        'value' => 'Legacy Value',
    ]);

    Livewire::actingAs($user)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Old Field')
        ->assertSee('Legacy Value');
});

test('show ticket displays custom field value even when field is soft-deleted', function () {
    $user = User::factory()->create();
    $category = cf84Category();
    $ticket = cf84Ticket($user, $category);

    $field = CustomField::factory()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'name_ar' => 'Deleted Field Label',
        'name_en' => 'Deleted Field Label',
    ]);

    CustomFieldValue::factory()->create([
        'ticket_id' => $ticket->id,
        'custom_field_id' => $field->id,
        'value' => 'Preserved Value',
    ]);

    $field->delete();

    Livewire::actingAs($user)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Deleted Field Label')
        ->assertSee('Preserved Value');
});

test('show ticket resolves dropdown option label for display', function () {
    $user = User::factory()->create();
    $category = cf84Category();
    $ticket = cf84Ticket($user, $category);

    $field = CustomField::factory()->create([
        'field_type' => 'dropdown',
        'scope_type' => 'global',
        'is_active' => true,
        'name_ar' => 'Priority Override',
        'name_en' => 'Priority Override',
    ]);
    $option = CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Critical',
        'value_en' => 'Critical',
        'is_active' => true,
    ]);

    CustomFieldValue::factory()->create([
        'ticket_id' => $ticket->id,
        'custom_field_id' => $field->id,
        'value' => $option->id,
    ]);

    Livewire::actingAs($user)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Priority Override')
        ->assertSee('Critical');
});

test('show ticket resolves soft-deleted option label for existing ticket value', function () {
    $user = User::factory()->create();
    $category = cf84Category();
    $ticket = cf84Ticket($user, $category);

    $field = CustomField::factory()->create([
        'field_type' => 'dropdown',
        'scope_type' => 'global',
        'is_active' => true,
        'name_ar' => 'Status Reason',
        'name_en' => 'Status Reason',
    ]);
    $option = CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Removed Option',
        'value_en' => 'Removed Option',
        'is_active' => true,
    ]);

    CustomFieldValue::factory()->create([
        'ticket_id' => $ticket->id,
        'custom_field_id' => $field->id,
        'value' => $option->id,
    ]);

    $option->delete();

    Livewire::actingAs($user)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Removed Option');
});

test('show ticket displays multi_select values as comma-separated labels', function () {
    $user = User::factory()->create();
    $category = cf84Category();
    $ticket = cf84Ticket($user, $category);

    $field = CustomField::factory()->create([
        'field_type' => 'multi_select',
        'scope_type' => 'global',
        'is_active' => true,
        'name_ar' => 'Tags Field',
        'name_en' => 'Tags Field',
    ]);
    $optA = CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Windows', 'value_en' => 'Windows', 'is_active' => true,
    ]);
    $optB = CustomFieldOption::factory()->create([
        'custom_field_id' => $field->id,
        'value_ar' => 'Linux', 'value_en' => 'Linux', 'is_active' => true,
    ]);

    CustomFieldValue::factory()->create([
        'ticket_id' => $ticket->id,
        'custom_field_id' => $field->id,
        'value' => json_encode([$optA->id, $optB->id]),
    ]);

    Livewire::actingAs($user)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee('Windows')
        ->assertSee('Linux');
});

test('show ticket marks deactivated field with inactive indicator', function () {
    $user = User::factory()->create();
    $category = cf84Category();
    $ticket = cf84Ticket($user, $category);

    $field = CustomField::factory()->inactive()->create([
        'field_type' => 'text',
        'scope_type' => 'global',
        'name_ar' => 'Inactive Field',
        'name_en' => 'Inactive Field',
    ]);

    CustomFieldValue::factory()->create([
        'ticket_id' => $ticket->id,
        'custom_field_id' => $field->id,
        'value' => 'some value',
    ]);

    Livewire::actingAs($user)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->assertSee(__('tickets.show.field_inactive'));
});

// ─── New tickets see current field definitions ────────────────────────────────

test('new ticket form shows latest active fields only', function () {
    $user = User::factory()->create();

    CustomField::factory()->create([
        'field_type' => 'text', 'scope_type' => 'global',
        'is_active' => true, 'name_ar' => 'Active CF', 'name_en' => 'Active CF',
    ]);
    CustomField::factory()->inactive()->create([
        'field_type' => 'text', 'scope_type' => 'global',
        'name_ar' => 'Inactive CF', 'name_en' => 'Inactive CF',
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertSee('Active CF')
        ->assertDontSee('Inactive CF');
});

test('number field validates numeric input', function () {
    $user = User::factory()->create();
    $category = cf84Category();

    $field = CustomField::factory()->required()->create([
        'field_type' => 'number',
        'scope_type' => 'global',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', 'Description')
        ->set('category_id', $category->id)
        ->set('customFieldValues.'.$field->id, 'not-a-number')
        ->call('submit')
        ->assertHasErrors(['customFieldValues.'.$field->id]);
});
