<?php

use App\Modules\Communication\Models\Comment;
use App\Modules\Communication\Models\NotificationLog;
use App\Modules\Communication\Models\ResponseTemplate;
use App\Modules\Communication\Models\Scopes\InternalCommentScope;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;

// ─── Comment model & factory ──────────────────────────────────────────────────

it('Comment factory creates a record with is_internal=true by default', function () {
    $comment = Comment::factory()->create();

    expect($comment->is_internal)->toBeTrue();
    expect(Comment::withoutGlobalScope(InternalCommentScope::class)->find($comment->id))
        ->not->toBeNull();
});

it('Comment factory public() state sets is_internal=false', function () {
    $comment = Comment::factory()->public()->create();

    expect($comment->is_internal)->toBeFalse();
});

it('Comment factory internal() state sets is_internal=true', function () {
    $comment = Comment::factory()->internal()->create();

    expect($comment->is_internal)->toBeTrue();
});

it('Comment casts is_internal as boolean', function () {
    $comment = Comment::factory()->create(['is_internal' => 1]);

    expect($comment->is_internal)->toBeTrue()
        ->and($comment->is_internal)->toBeBool();
});

it('Comment author relationship returns the user', function () {
    $user    = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $user->id]);

    expect($comment->author->id)->toBe($user->id);
});

// ─── InternalCommentScope — employee leak tests ───────────────────────────────

it('employee cannot see internal comments via Comment::all()', function () {
    $employee = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $ticket   = Ticket::factory()->create();

    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->public()->create(['ticket_id' => $ticket->id]);

    $this->actingAs($employee);

    $visible = Comment::where('ticket_id', $ticket->id)->get();

    expect($visible)->toHaveCount(1);
    expect($visible->every(fn ($c) => ! $c->is_internal))->toBeTrue();
});

it('tech can see all comments including internal ones', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->public()->create(['ticket_id' => $ticket->id]);

    $this->actingAs($tech);

    $visible = Comment::where('ticket_id', $ticket->id)->get();

    expect($visible)->toHaveCount(2);
});

it('superuser can see all comments including internal ones', function () {
    $superUser = User::factory()->superUser()->create();
    $ticket    = Ticket::factory()->create();

    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->public()->create(['ticket_id' => $ticket->id]);

    $this->actingAs($superUser);

    $visible = Comment::where('ticket_id', $ticket->id)->get();

    expect($visible)->toHaveCount(2);
});

it('withoutGlobalScope bypasses InternalCommentScope', function () {
    $employee = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $ticket   = Ticket::factory()->create();

    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);

    $this->actingAs($employee);

    $all = Comment::withoutGlobalScope(InternalCommentScope::class)
        ->where('ticket_id', $ticket->id)
        ->get();

    expect($all)->toHaveCount(1);
});

it('unauthenticated context sees all comments (queue safety)', function () {
    $ticket = Ticket::factory()->create();

    Comment::factory()->internal()->create(['ticket_id' => $ticket->id]);
    Comment::factory()->public()->create(['ticket_id' => $ticket->id]);

    // No actingAs — simulates queue/CLI context
    $all = Comment::where('ticket_id', $ticket->id)->get();

    expect($all)->toHaveCount(2);
});

// ─── NotificationLog model & factory ─────────────────────────────────────────

it('NotificationLog factory creates a queued record', function () {
    $log = NotificationLog::factory()->create();

    expect($log->status)->toBe('queued');
    expect($log->attempts)->toBe(0);
    expect($log->sent_at)->toBeNull();
});

it('NotificationLog factory sent() state has correct fields', function () {
    $log = NotificationLog::factory()->sent()->create();

    expect($log->status)->toBe('sent');
    expect($log->sent_at)->not->toBeNull();
    expect($log->attempts)->toBe(1);
});

it('NotificationLog factory failed() state has correct fields', function () {
    $log = NotificationLog::factory()->failed()->create();

    expect($log->status)->toBe('failed');
    expect($log->failure_reason)->not->toBeNull();
    expect($log->attempts)->toBe(3);
});

it('NotificationLog recipient relationship returns the user', function () {
    $user = User::factory()->create();
    $log  = NotificationLog::factory()->create(['recipient_id' => $user->id]);

    expect($log->recipient->id)->toBe($user->id);
});

it('NotificationLog ticket_id can be null', function () {
    $log = NotificationLog::factory()->create(['ticket_id' => null]);

    expect($log->ticket_id)->toBeNull();
});

// ─── ResponseTemplate model & factory ────────────────────────────────────────

it('ResponseTemplate factory creates a record with all bilingual fields', function () {
    $template = ResponseTemplate::factory()->create();

    expect($template->title_ar)->not->toBeEmpty();
    expect($template->title_en)->not->toBeEmpty();
    expect($template->body_ar)->not->toBeEmpty();
    expect($template->body_en)->not->toBeEmpty();
});

it('ResponseTemplate factory creates is_internal=true and is_active=true by default', function () {
    $template = ResponseTemplate::factory()->create();

    expect($template->is_internal)->toBeTrue();
    expect($template->is_active)->toBeTrue();
});

it('ResponseTemplate factory public() state sets is_internal=false', function () {
    $template = ResponseTemplate::factory()->public()->create();

    expect($template->is_internal)->toBeFalse();
});

it('ResponseTemplate active() scope excludes inactive records', function () {
    ResponseTemplate::factory()->create(['is_active' => true]);
    ResponseTemplate::factory()->inactive()->create();

    expect(ResponseTemplate::active()->count())->toBe(1);
});

it('ResponseTemplate active() scope excludes soft-deleted records', function () {
    $live    = ResponseTemplate::factory()->create();
    $deleted = ResponseTemplate::factory()->create();
    $deleted->delete();

    $results = ResponseTemplate::active()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($live->id);
});

it('ResponseTemplate uses SoftDeletes', function () {
    $template = ResponseTemplate::factory()->create();
    $id       = $template->id;
    $template->delete();

    expect(ResponseTemplate::find($id))->toBeNull();
    expect(ResponseTemplate::withTrashed()->find($id))->not->toBeNull();
});

// ─── CommunicationServiceProvider ────────────────────────────────────────────

it('CommunicationServiceProvider boots without exception', function () {
    expect(app()->getProvider(\App\Modules\Communication\Providers\CommunicationServiceProvider::class))
        ->not->toBeNull();
});
