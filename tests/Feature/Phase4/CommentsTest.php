<?php

use App\Modules\Communication\Events\CommentCreated;
use App\Modules\Communication\Livewire\AddComment;
use App\Modules\Communication\Models\Comment;
use App\Modules\Communication\Models\ResponseTemplate;
use App\Modules\Communication\Models\Scopes\InternalCommentScope;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Event::fake([CommentCreated::class]);
    RateLimiter::clear('comment.create:*');
});

// ─── Internal comment by tech ─────────────────────────────────────────────────

it('tech posts internal comment → is_internal=true record created and CommentCreated fired', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p>Internal note.</p>')
        ->set('isInternal', true)
        ->call('submit')
        ->assertHasNoErrors();

    $comment = Comment::withoutGlobalScope(InternalCommentScope::class)
        ->where('ticket_id', $ticket->id)
        ->first();

    expect($comment)->not->toBeNull()
        ->and($comment->is_internal)->toBeTrue()
        ->and($comment->user_id)->toBe($tech->id);

    Event::assertDispatched(CommentCreated::class, fn ($e) => $e->ticketId === $ticket->id && $e->isInternal === true);
});

// ─── Public comment by tech ───────────────────────────────────────────────────

it('tech posts public comment → is_internal=false record created', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p>Public reply.</p>')
        ->set('isInternal', false)
        ->call('submit')
        ->assertHasNoErrors();

    $comment = Comment::withoutGlobalScope(InternalCommentScope::class)
        ->where('ticket_id', $ticket->id)
        ->first();

    expect($comment)->not->toBeNull()
        ->and($comment->is_internal)->toBeFalse();

    Event::assertDispatched(CommentCreated::class, fn ($e) => $e->isInternal === false);
});

// ─── Employee posts comment → always stored as public ─────────────────────────

it('employee posts comment with is_internal=false → stored as is_internal=false', function () {
    $employee = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $ticket   = Ticket::factory()->create(['requester_id' => $employee->id]);

    Livewire::actingAs($employee)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p>My question.</p>')
        ->set('isInternal', false)
        ->call('submit')
        ->assertHasNoErrors();

    $comment = Comment::withoutGlobalScope(InternalCommentScope::class)
        ->where('ticket_id', $ticket->id)
        ->first();

    expect($comment)->not->toBeNull()
        ->and($comment->is_internal)->toBeFalse();
});

// ─── Employee cannot post internal comments ───────────────────────────────────

it('employee POSTing with is_internal=true receives 403', function () {
    $employee = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $ticket   = Ticket::factory()->create(['requester_id' => $employee->id]);

    Livewire::actingAs($employee)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p>Trying to post internal.</p>')
        ->set('isInternal', true)
        ->call('submit')
        ->assertStatus(403);
});

// ─── Employee comment list never sees internal comments ───────────────────────

it('employee fetching comment list never sees internal comments', function () {
    $employee = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $ticket   = Ticket::factory()->create(['requester_id' => $employee->id]);

    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->public()->create(['ticket_id' => $ticket->id]);

    $component = Livewire::actingAs($employee)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id]);

    $comments = $component->viewData('comments');

    expect($comments)->toHaveCount(1)
        ->and($comments->every(fn ($c) => ! $c->is_internal))->toBeTrue();
});

// ─── Tech sees all comments including internal ────────────────────────────────

it('tech fetching comment list sees both public and internal comments', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->public()->create(['ticket_id' => $ticket->id]);

    $component = Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id]);

    $comments = $component->viewData('comments');

    expect($comments)->toHaveCount(2);
});

// ─── Validation: body max length ─────────────────────────────────────────────

it('body exceeding 10,000 chars returns validation error', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', str_repeat('a', 10001))
        ->call('submit')
        ->assertHasErrors(['body']);
});

// ─── Rate limit ───────────────────────────────────────────────────────────────

it('31st comment within an hour returns 429', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();
    $key    = 'comment.create:' . $tech->id;

    for ($i = 0; $i < 30; $i++) {
        RateLimiter::hit($key, 3600);
    }

    Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p>One too many.</p>')
        ->call('submit')
        ->assertStatus(429);
});

// ─── HTML sanitization ────────────────────────────────────────────────────────

it('script tag in body is stripped before storage', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p>Hello</p><script>alert("xss")</script>')
        ->set('isInternal', false)
        ->call('submit')
        ->assertHasNoErrors();

    $comment = Comment::withoutGlobalScope(InternalCommentScope::class)
        ->where('ticket_id', $ticket->id)
        ->first();

    expect($comment->body)->not->toContain('<script>');
});

it('javascript URI in href is neutralised before storage', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p><a href="javascript:alert(1)">click</a></p>')
        ->set('isInternal', false)
        ->call('submit')
        ->assertHasNoErrors();

    $comment = Comment::withoutGlobalScope(InternalCommentScope::class)
        ->where('ticket_id', $ticket->id)
        ->first();

    expect($comment->body)->not->toContain('javascript:');
});

// ─── Response template pre-fill ──────────────────────────────────────────────

it('selecting a template pre-fills body and sets is_internal to template default', function () {
    $tech     = User::factory()->tech()->create();
    $ticket   = Ticket::factory()->create();
    $template = ResponseTemplate::factory()->create([
        'body_en'     => '<p>Standard reply text</p>',
        'body_ar'     => '<p>نص الرد القياسي</p>',
        'is_internal' => false,
        'is_active'   => true,
    ]);

    // Default locale is 'ar' — pre-fill uses body_ar
    $component = Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('selectedTemplate', $template->id);

    expect($component->get('isInternal'))->toBeFalse()
        ->and($component->get('body'))->toContain('نص الرد القياسي');
});

it('selecting an internal template pre-fills is_internal=true', function () {
    $tech     = User::factory()->tech()->create();
    $ticket   = Ticket::factory()->create();
    $template = ResponseTemplate::factory()->create([
        'body_en'     => '<p>Internal note template</p>',
        'is_internal' => true,
        'is_active'   => true,
    ]);

    $component = Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('selectedTemplate', $template->id);

    expect($component->get('isInternal'))->toBeTrue();
});

it('inactive template is not selectable via pre-fill', function () {
    $tech     = User::factory()->tech()->create();
    $ticket   = Ticket::factory()->create();
    $template = ResponseTemplate::factory()->inactive()->create([
        'body_en' => '<p>Inactive template</p>',
    ]);

    $component = Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('selectedTemplate', $template->id);

    // Body should NOT be pre-filled because the template is inactive
    expect($component->get('body'))->toBe('');
});

// ─── Unauthenticated access ───────────────────────────────────────────────────

it('unauthenticated user cannot mount the component', function () {
    $ticket = Ticket::factory()->create();

    Livewire::test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->assertStatus(403);
});

// ─── Form reset after submit ──────────────────────────────────────────────────

it('form fields are reset after successful submit', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    $component = Livewire::actingAs($tech)
        ->test(AddComment::class, ['ticketUlid' => $ticket->id])
        ->set('body', '<p>A comment.</p>')
        ->set('isInternal', false)
        ->call('submit')
        ->assertHasNoErrors();

    expect($component->get('body'))->toBe('')
        ->and($component->get('isInternal'))->toBeTrue()
        ->and($component->get('selectedTemplate'))->toBe('');
});
