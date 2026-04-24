<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    // Ensure a fresh rate limiter state (array driver in tests)
    // Key is per-user, so new users are naturally isolated — this guard is for shared user tests.
    RateLimiter::clear('ticket.create:*');
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeCategory(): Category
{
    $group = Group::factory()->create();
    return Category::factory()->create(['group_id' => $group->id]);
}

// ─── Happy path ───────────────────────────────────────────────────────────────

it('authenticated employee can create a ticket', function () {
    $user     = User::factory()->create();
    $category = makeCategory();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Printer not working')
        ->set('description', '<p>The office printer is broken.</p>')
        ->set('category_id', $category->id)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect();

    $ticket = Ticket::withoutGlobalScopes()->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->status)->toBe(TicketStatus::AwaitingAssignment)
        ->and($ticket->requester_id)->toBe($user->id)
        ->and($ticket->group_id)->toBe($category->group_id)
        ->and($ticket->category_id)->toBe($category->id)
        ->and($ticket->incident_origin)->toBe('web');
});

it('display_number is formatted TKT-0000001 for the first ticket', function () {
    $user     = User::factory()->create();
    $category = makeCategory();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'First ticket')
        ->set('description', '<p>Test.</p>')
        ->set('category_id', $category->id)
        ->call('submit');

    $ticket = Ticket::withoutGlobalScopes()->first();
    expect($ticket->display_number)->toBe('TKT-0000001');
});

it('display_number increments to TKT-0000002 for the second ticket', function () {
    $user     = User::factory()->create();
    $category = makeCategory();

    foreach (['First ticket', 'Second ticket'] as $subject) {
        Livewire::actingAs($user)
            ->test(CreateTicket::class)
            ->set('subject', $subject)
            ->set('description', '<p>Test.</p>')
            ->set('category_id', $category->id)
            ->call('submit');
    }

    $tickets = Ticket::withoutGlobalScopes()->orderBy('created_at')->get();
    expect($tickets[0]->display_number)->toBe('TKT-0000001')
        ->and($tickets[1]->display_number)->toBe('TKT-0000002');
});

it('redirect after creation uses the ULID route, not display_number', function () {
    $user     = User::factory()->create();
    $category = makeCategory();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'ULID route test')
        ->set('description', '<p>Test.</p>')
        ->set('category_id', $category->id)
        ->call('submit')
        ->assertRedirectContains('/tickets/');

    $ticket = Ticket::withoutGlobalScopes()->first();
    // Redirect must use ULID (26 chars), never display_number
    expect($ticket->id)->toHaveLength(26);
});

// ─── HTML sanitization ────────────────────────────────────────────────────────

it('stores description with <script> tags stripped', function () {
    $user     = User::factory()->create();
    $category = makeCategory();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'XSS test')
        ->set('description', '<p>Hello</p><script>alert("xss")</script>')
        ->set('category_id', $category->id)
        ->call('submit');

    $ticket = Ticket::withoutGlobalScopes()->first();
    expect($ticket->description)->not->toContain('<script>');
});

it('stores description with event handlers stripped', function () {
    $user     = User::factory()->create();
    $category = makeCategory();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Event handler test')
        ->set('description', '<p onclick="alert(1)">Click me</p>')
        ->set('category_id', $category->id)
        ->call('submit');

    $ticket = Ticket::withoutGlobalScopes()->first();
    expect($ticket->description)->not->toContain('onclick');
});

// ─── Validation ───────────────────────────────────────────────────────────────

it('rejects missing subject', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', '')
        ->set('description', '<p>Test.</p>')
        ->set('category_id', makeCategory()->id)
        ->call('submit')
        ->assertHasErrors(['subject']);
});

it('rejects missing description', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', '')
        ->set('category_id', makeCategory()->id)
        ->call('submit')
        ->assertHasErrors(['description']);
});

it('rejects missing category', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Test')
        ->set('description', '<p>Test.</p>')
        ->set('category_id', '')
        ->call('submit')
        ->assertHasErrors(['category_id']);
});

it('rejects subject exceeding 255 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', str_repeat('a', 256))
        ->set('description', '<p>Test.</p>')
        ->set('category_id', makeCategory()->id)
        ->call('submit')
        ->assertHasErrors(['subject']);
});

// ─── Authentication ───────────────────────────────────────────────────────────

it('unauthenticated GET /tickets/create redirects to login', function () {
    $this->get('/tickets/create')->assertRedirect('/login');
});

// ─── Rate limiting ────────────────────────────────────────────────────────────

it('returns 429 on the 11th ticket creation within an hour', function () {
    $user     = User::factory()->create();
    $category = makeCategory();
    $key      = 'ticket.create:' . $user->id;

    // Pre-fill 10 hits to simulate prior successful creations
    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($key, 3600);
    }

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Over limit')
        ->set('description', '<p>Test.</p>')
        ->set('category_id', $category->id)
        ->call('submit')
        ->assertStatus(429);
});

// ─── Subcategory loading ──────────────────────────────────────────────────────

it('subcategory list updates when category changes', function () {
    $user     = User::factory()->create();
    $category = makeCategory();

    Subcategory::factory()->count(2)->create(['category_id' => $category->id, 'is_active' => true]);
    Subcategory::factory()->inactive()->create(['category_id' => $category->id]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('category_id', $category->id)
        ->assertSet('subcategory_id', '')
        ->assertCount('subcategories', 2);
});

it('subcategory_id resets when category changes', function () {
    $user = User::factory()->create();
    $cat1 = makeCategory();
    $cat2 = makeCategory();
    $sub  = Subcategory::factory()->create(['category_id' => $cat1->id]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('category_id', $cat1->id)
        ->set('subcategory_id', $sub->id)
        ->set('category_id', $cat2->id)
        ->assertSet('subcategory_id', '');
});
