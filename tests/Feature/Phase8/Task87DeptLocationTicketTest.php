<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\Department;
use App\Modules\Shared\Models\Location;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Models\Ticket;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function dept87Category(): Category
{
    $group = Group::factory()->create();
    return Category::factory()->create(['group_id' => $group->id]);
}

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

// ─── Department/Location shown on create form ─────────────────────────────────

test('active departments appear in ticket creation form', function () {
    app()->setLocale('en');
    $user = User::factory()->create();
    $dept = Department::factory()->create(['name_en' => 'Engineering']);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertSee('Engineering');
});

test('inactive departments are hidden from ticket creation form', function () {
    $user = User::factory()->create();
    $dept = Department::factory()->inactive()->create(['name_en' => 'Hidden Dept']);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertDontSee('Hidden Dept');
});

test('soft-deleted departments are hidden from ticket creation form', function () {
    $user = User::factory()->create();
    $dept = Department::factory()->create(['name_en' => 'Deleted Dept']);
    $dept->delete();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertDontSee('Deleted Dept');
});

test('active locations appear in ticket creation form', function () {
    app()->setLocale('en');
    $user = User::factory()->create();
    Location::factory()->create(['name_en' => 'Main Campus']);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertSee('Main Campus');
});

test('inactive locations are hidden from ticket creation form', function () {
    $user = User::factory()->create();
    Location::factory()->inactive()->create(['name_en' => 'Hidden Site']);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->assertDontSee('Hidden Site');
});

// ─── Ticket creation saves department and location ────────────────────────────

test('ticket creation saves department_id when provided', function () {
    $user     = User::factory()->create();
    $category = dept87Category();
    $dept     = Department::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test ticket')
        ->set('description', 'Test description')
        ->set('category_id', $category->id)
        ->set('department_id', $dept->id)
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::first();
    expect($ticket->department_id)->toBe($dept->id);
});

test('ticket creation saves location_id when provided', function () {
    $user     = User::factory()->create();
    $category = dept87Category();
    $loc      = Location::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test ticket')
        ->set('description', 'Test description')
        ->set('category_id', $category->id)
        ->set('location_id', $loc->id)
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::first();
    expect($ticket->location_id)->toBe($loc->id);
});

test('ticket creation succeeds without department or location', function () {
    $user     = User::factory()->create();
    $category = dept87Category();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Ticket without dept/loc')
        ->set('description', 'No department or location provided')
        ->set('category_id', $category->id)
        ->call('submit')
        ->assertHasNoErrors();

    $ticket = Ticket::first();
    expect($ticket->department_id)->toBeNull();
    expect($ticket->location_id)->toBeNull();
});

test('ticket creation rejects invalid department_id', function () {
    $user     = User::factory()->create();
    $category = dept87Category();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test ticket')
        ->set('description', 'Test description')
        ->set('category_id', $category->id)
        ->set('department_id', '01INVALIDULID0000000000000')
        ->call('submit')
        ->assertHasErrors(['department_id']);
});

test('ticket creation rejects invalid location_id', function () {
    $user     = User::factory()->create();
    $category = dept87Category();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test ticket')
        ->set('description', 'Test description')
        ->set('category_id', $category->id)
        ->set('location_id', '01INVALIDULID0000000000000')
        ->call('submit')
        ->assertHasErrors(['location_id']);
});
