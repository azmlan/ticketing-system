<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Assignment\Services\TransferService;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeInProgressTicket(User $assignedTech): Ticket
{
    $group = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::factory()->inProgress()->create([
        'group_id' => $group->id,
        'category_id' => $category->id,
        'assigned_to' => $assignedTech->id,
    ]);
}

// ─── Request transfer ─────────────────────────────────────────────────────────

it('assigned tech can request a transfer to another tech', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);

    Livewire::actingAs($techA)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->set('transferToUserId', $techB->id)
        ->call('requestTransfer')
        ->assertHasNoErrors();

    $tr = TransferRequest::where('ticket_id', $ticket->id)->first();

    expect($tr)->not->toBeNull()
        ->and($tr->from_user_id)->toBe($techA->id)
        ->and($tr->to_user_id)->toBe($techB->id)
        ->and($tr->status)->toBe('pending');
});

it('second pending transfer request for same ticket is rejected', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $techC = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);

    // First request
    app(TransferService::class)->request($ticket, $techA, $techB);

    // Second request should fail
    expect(fn () => app(TransferService::class)->request($ticket, $techA, $techC))
        ->toThrow(RuntimeException::class);
});

// ─── Accept transfer ──────────────────────────────────────────────────────────

it('target tech can accept a transfer request', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    Livewire::actingAs($techB)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('acceptTransfer', $tr->id)
        ->assertHasNoErrors();

    $freshTicket = $ticket->fresh();
    $freshTr = $tr->fresh();

    expect($freshTicket->assigned_to)->toBe($techB->id)
        ->and($freshTr->status)->toBe('accepted')
        ->and($freshTr->responded_at)->not->toBeNull();
});

it('accept sets assigned_to to the target tech and does not change status', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    app(TransferService::class)->accept($tr, $techB);

    expect($ticket->fresh())
        ->assigned_to->toBe($techB->id)
        ->status->toBe(TicketStatus::InProgress);
});

it('non-target tech cannot accept a transfer request (AuthorizationException)', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $techC = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    expect(fn () => app(TransferService::class)->accept($tr, $techC))
        ->toThrow(AuthorizationException::class);
});

// ─── Reject transfer ──────────────────────────────────────────────────────────

it('target tech can reject a transfer request', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    Livewire::actingAs($techB)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('rejectTransfer', $tr->id)
        ->assertHasNoErrors();

    expect($tr->fresh()->status)->toBe('rejected');
});

it('reject does not change assigned_to', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    app(TransferService::class)->reject($tr, $techB);

    expect($ticket->fresh()->assigned_to)->toBe($techA->id);
});

// ─── Revoke transfer ──────────────────────────────────────────────────────────

it('requesting tech can revoke a pending transfer request', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    Livewire::actingAs($techA)
        ->test(ShowTicket::class, ['ticket' => $ticket])
        ->call('revokeTransfer', $tr->id)
        ->assertHasNoErrors();

    expect($tr->fresh()->status)->toBe('revoked');
});

it('revoke fails if transfer is not pending', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->accepted()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
    ]);

    expect(fn () => app(TransferService::class)->revoke($tr, $techA))
        ->toThrow(RuntimeException::class);
});

it('non-requesting tech cannot revoke a transfer request (AuthorizationException)', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    expect(fn () => app(TransferService::class)->revoke($tr, $techB))
        ->toThrow(AuthorizationException::class);
});

// ─── Transfer records never deleted ──────────────────────────────────────────

it('transfer records are never deleted after accept', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    app(TransferService::class)->accept($tr, $techB);

    expect(TransferRequest::find($tr->id))->not->toBeNull();
});

it('transfer records are never deleted after reject', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    app(TransferService::class)->reject($tr, $techB);

    expect(TransferRequest::find($tr->id))->not->toBeNull();
});

it('transfer records are never deleted after revoke', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);
    $tr = TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    app(TransferService::class)->revoke($tr, $techA);

    expect(TransferRequest::find($tr->id))->not->toBeNull();
});

// ─── One pending at a time (Livewire contention) ─────────────────────────────

it('requesting a transfer while one is pending throws via Livewire', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();
    $techC = User::factory()->tech()->create();
    $ticket = makeInProgressTicket($techA);

    // Establish a pending transfer
    TransferRequest::factory()->create([
        'ticket_id' => $ticket->id,
        'from_user_id' => $techA->id,
        'to_user_id' => $techB->id,
        'status' => 'pending',
    ]);

    // Attempting a second request should throw RuntimeException
    expect(fn () => app(TransferService::class)->request($ticket, $techA, $techC))
        ->toThrow(RuntimeException::class);
});
