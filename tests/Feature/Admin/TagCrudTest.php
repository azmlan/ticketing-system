<?php

use App\Modules\Admin\Livewire\Tags\TagIndex;
use App\Modules\Admin\Models\Tag;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function tagManageUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'system.manage-tags')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

// ─── Route access ─────────────────────────────────────────────────────────────

test('unauthenticated user is redirected from admin tags', function () {
    $this->get(route('admin.tags.index'))
        ->assertRedirect(route('login'));
});

test('user without system.manage-tags cannot access admin tags', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.tags.index'))
        ->assertForbidden();
});

test('user with system.manage-tags can access admin tags', function () {
    $user = tagManageUser();
    $this->actingAs($user)
        ->get(route('admin.tags.index'))
        ->assertOk();
});

test('super user can access admin tags', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.tags.index'))
        ->assertOk();
});

// ─── Create ──────────────────────────────────────────────────────────────────

test('authorised user can create a tag', function () {
    $user = tagManageUser();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'عاجل')
        ->set('formNameEn', 'Urgent')
        ->set('formColor', '#EF4444')
        ->set('formIsActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $tag = Tag::first();
    expect($tag)->not->toBeNull();
    expect($tag->name_ar)->toBe('عاجل');
    expect($tag->name_en)->toBe('Urgent');
    expect($tag->color)->toBe('#EF4444');
    expect($tag->is_active)->toBeTrue();
});

test('creating a tag without name_ar fails validation', function () {
    $user = tagManageUser();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('openCreate')
        ->set('formNameAr', '')
        ->set('formNameEn', 'Urgent')
        ->set('formColor', '#EF4444')
        ->call('save')
        ->assertHasErrors(['formNameAr' => 'required']);
});

test('creating a tag without name_en fails validation', function () {
    $user = tagManageUser();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'عاجل')
        ->set('formNameEn', '')
        ->set('formColor', '#EF4444')
        ->call('save')
        ->assertHasErrors(['formNameEn' => 'required']);
});

test('invalid hex color is rejected', function () {
    $user = tagManageUser();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'عاجل')
        ->set('formNameEn', 'Urgent')
        ->set('formColor', 'red') // invalid
        ->call('save')
        ->assertHasErrors(['formColor']);
});

test('short hex color without hash is rejected', function () {
    $user = tagManageUser();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('openCreate')
        ->set('formNameAr', 'عاجل')
        ->set('formNameEn', 'Urgent')
        ->set('formColor', '#FFF') // 4-char not valid
        ->call('save')
        ->assertHasErrors(['formColor']);
});

// ─── Edit ────────────────────────────────────────────────────────────────────

test('authorised user can edit a tag', function () {
    $user = tagManageUser();
    $tag  = Tag::factory()->create(['name_en' => 'Old', 'color' => '#111111']);

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('openEdit', $tag->id)
        ->set('formNameEn', 'Updated')
        ->set('formColor', '#22C55E')
        ->call('save')
        ->assertHasNoErrors();

    expect($tag->fresh()->name_en)->toBe('Updated');
    expect($tag->fresh()->color)->toBe('#22C55E');
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('authorised user can deactivate a tag', function () {
    $user = tagManageUser();
    $tag  = Tag::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('toggleActive', $tag->id);

    expect($tag->fresh()->is_active)->toBeFalse();
});

test('authorised user can reactivate a tag', function () {
    $user = tagManageUser();
    $tag  = Tag::factory()->create(['is_active' => false]);

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('toggleActive', $tag->id);

    expect($tag->fresh()->is_active)->toBeTrue();
});

// ─── Soft-delete ──────────────────────────────────────────────────────────────

test('authorised user can soft-delete a tag', function () {
    $user = tagManageUser();
    $tag  = Tag::factory()->create();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->call('delete', $tag->id);

    expect(Tag::withTrashed()->find($tag->id)->deleted_at)->not->toBeNull();
    expect(Tag::find($tag->id))->toBeNull();
});

test('soft-deleted tag is still shown in list (withTrashed)', function () {
    $user = tagManageUser();
    $tag  = Tag::factory()->create(['name_en' => 'SoftDeleted']);
    $tag->delete();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->assertSee('SoftDeleted');
});

// ─── Permission gates ─────────────────────────────────────────────────────────

test('user without system.manage-tags cannot mount TagIndex', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TagIndex::class)
        ->assertForbidden();
});

// ─── Ticket::tags() relationship ──────────────────────────────────────────────

test('ticket tags relationship returns attached tags', function () {
    $user   = User::factory()->create();
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create(['requester_id' => $user->id]);
    $tag1   = Tag::factory()->create(['is_active' => true]);
    $tag2   = Tag::factory()->create(['is_active' => true]);

    $ticket->tags()->attach([$tag1->id, $tag2->id]);

    expect($ticket->tags)->toHaveCount(2);
    expect($ticket->tags->pluck('id'))->toContain($tag1->id);
    expect($ticket->tags->pluck('id'))->toContain($tag2->id);
});

test('ticket tags pivot has no duplicate entries', function () {
    $user   = User::factory()->create();
    $ticket = Ticket::factory()->create(['requester_id' => $user->id]);
    $tag    = Tag::factory()->create();

    $ticket->tags()->attach($tag->id);

    expect(fn () => $ticket->tags()->attach($tag->id))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
