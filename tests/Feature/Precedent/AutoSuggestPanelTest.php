<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldValue;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Precedent\Livewire\AutoSuggestPanel;
use App\Modules\Precedent\Models\Resolution;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Livewire\Livewire;

// ── Helpers ───────────────────────────────────────────────────────────────────

function inProgressTicket(Category $category, ?Subcategory $subcategory = null): Ticket
{
    return Ticket::factory()->create([
        'category_id'    => $category->id,
        'subcategory_id' => $subcategory?->id,
        'status'         => TicketStatus::InProgress,
    ]);
}

function resolvedResolutionFor(Category $category, ?Subcategory $subcategory = null, array $resolutionAttrs = []): Resolution
{
    $ticket = Ticket::factory()->resolved()->create([
        'category_id'    => $category->id,
        'subcategory_id' => $subcategory?->id,
    ]);

    return Resolution::factory()->create(array_merge(['ticket_id' => $ticket->id], $resolutionAttrs));
}

// ── Collapsed state ───────────────────────────────────────────────────────────

it('panel starts collapsed', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $user     = User::factory()->tech()->create();

    Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->assertSet('collapsed', true);
});

it('toggle expands and collapses the panel', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $user     = User::factory()->tech()->create();

    Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->call('toggle')
        ->assertSet('collapsed', false)
        ->call('toggle')
        ->assertSet('collapsed', true);
});

// ── Filter: category + subcategory exact match ────────────────────────────────

it('shows resolutions from resolved tickets with same category and null subcategory', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $target   = resolvedResolutionFor($category);
    $user     = User::factory()->tech()->create();

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id'))->toContain($target->id);
});

it('shows resolutions from resolved tickets with same category and same subcategory', function () {
    $group       = Group::factory()->create();
    $category    = Category::factory()->create(['group_id' => $group->id]);
    $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
    $ticket      = inProgressTicket($category, $subcategory);
    $target      = resolvedResolutionFor($category, $subcategory);
    $user        = User::factory()->tech()->create();

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id'))->toContain($target->id);
});

it('excludes resolutions from tickets with a different category', function () {
    $group     = Group::factory()->create();
    $category1 = Category::factory()->create(['group_id' => $group->id]);
    $category2 = Category::factory()->create(['group_id' => $group->id]);
    $ticket    = inProgressTicket($category1);
    $other     = resolvedResolutionFor($category2);
    $user      = User::factory()->tech()->create();

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id'))->not->toContain($other->id);
});

it('excludes resolutions from tickets with a different subcategory', function () {
    $group       = Group::factory()->create();
    $category    = Category::factory()->create(['group_id' => $group->id]);
    $sub1        = Subcategory::factory()->create(['category_id' => $category->id]);
    $sub2        = Subcategory::factory()->create(['category_id' => $category->id]);
    $ticket      = inProgressTicket($category, $sub1);
    $other       = resolvedResolutionFor($category, $sub2);
    $user        = User::factory()->tech()->create();

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id'))->not->toContain($other->id);
});

it('excludes resolutions where source ticket has subcategory but current ticket does not', function () {
    $group       = Group::factory()->create();
    $category    = Category::factory()->create(['group_id' => $group->id]);
    $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
    $ticket      = inProgressTicket($category, null);
    $other       = resolvedResolutionFor($category, $subcategory);
    $user        = User::factory()->tech()->create();

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id'))->not->toContain($other->id);
});

// ── Filter: only resolved tickets ─────────────────────────────────────────────

it('excludes resolutions from non-resolved source tickets', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $user     = User::factory()->tech()->create();

    $nonResolvedTicket = Ticket::factory()->create([
        'category_id' => $category->id,
        'status'      => TicketStatus::Closed,
    ]);
    $resolution = Resolution::factory()->create(['ticket_id' => $nonResolvedTicket->id]);

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id'))->not->toContain($resolution->id);
});

// ── Filter: current ticket excluded ──────────────────────────────────────────

it('excludes the current ticket own resolution if it exists', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $resolvedTicket = Ticket::factory()->resolved()->create(['category_id' => $category->id]);
    $ownResolution  = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);
    $user = User::factory()->tech()->create();

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $resolvedTicket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id'))->not->toContain($ownResolution->id);
});

// ── Sort order ────────────────────────────────────────────────────────────────

it('sorts suggestions by usage_count DESC then created_at DESC', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $user     = User::factory()->tech()->create();

    $low  = resolvedResolutionFor($category, null, ['usage_count' => 1, 'created_at' => now()->subDays(3)]);
    $high = resolvedResolutionFor($category, null, ['usage_count' => 10, 'created_at' => now()->subDays(5)]);
    $mid  = resolvedResolutionFor($category, null, ['usage_count' => 5, 'created_at' => now()->subDays(1)]);

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions->pluck('id')->all())->toBe([$high->id, $mid->id, $low->id]);
});

it('breaks usage_count ties by created_at DESC', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $user     = User::factory()->tech()->create();

    $older  = resolvedResolutionFor($category, null, ['usage_count' => 3, 'created_at' => now()->subDays(5)]);
    $newer  = resolvedResolutionFor($category, null, ['usage_count' => 3, 'created_at' => now()->subDays(1)]);

    $ids = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$newer->id, $older->id]);
});

// ── Custom field context ──────────────────────────────────────────────────────

it('loads custom field values from the source ticket', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $user     = User::factory()->tech()->create();

    $resolvedTicket = Ticket::factory()->resolved()->create(['category_id' => $category->id]);
    $resolution     = Resolution::factory()->create(['ticket_id' => $resolvedTicket->id]);
    $field          = CustomField::factory()->create(['name_en' => 'Asset Tag', 'name_ar' => 'رقم الأصل']);
    CustomFieldValue::factory()->create([
        'ticket_id'       => $resolvedTicket->id,
        'custom_field_id' => $field->id,
        'value'           => 'A-12345',
    ]);

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    $found = $suggestions->firstWhere('id', $resolution->id);
    expect($found->ticket->customFieldValues)->toHaveCount(1)
        ->and($found->ticket->customFieldValues->first()->value)->toBe('A-12345');
});

// ── Empty state ───────────────────────────────────────────────────────────────

it('returns empty collection when no matching resolutions exist', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    $ticket   = inProgressTicket($category);
    $user     = User::factory()->tech()->create();

    $suggestions = Livewire::actingAs($user)
        ->test(AutoSuggestPanel::class, ['ticket' => $ticket])
        ->instance()
        ->suggestions;

    expect($suggestions)->toBeEmpty();
});
