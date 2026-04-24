<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketPriority;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use App\Modules\Tickets\Models\TransferRequest;

// ─── Factory & casts ──────────────────────────────────────────────────────────

it('factory produces a ticket with expected defaults', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->id)->toHaveLength(26)
        ->and($ticket->status)->toBe(TicketStatus::AwaitingAssignment)
        ->and($ticket->priority)->toBeNull()
        ->and($ticket->incident_origin)->toBe('web');
});

it('status is cast to TicketStatus enum', function () {
    $ticket = Ticket::factory()->create(['status' => 'in_progress']);

    expect($ticket->status)->toBe(TicketStatus::InProgress);
});

it('priority is cast to TicketPriority enum when set', function () {
    $ticket = Ticket::factory()->create(['priority' => 'high']);

    expect($ticket->priority)->toBe(TicketPriority::High);
});

it('priority is null when not set', function () {
    $ticket = Ticket::factory()->create(['priority' => null]);

    expect($ticket->priority)->toBeNull();
});

// ─── Relationships ────────────────────────────────────────────────────────────

it('resolves requester relationship', function () {
    $user   = User::factory()->create();
    $ticket = Ticket::factory()->create(['requester_id' => $user->id]);

    // Use withoutGlobalScope to bypass EmployeeTicketScope in this assertion
    $ticket = Ticket::withoutGlobalScopes()->find($ticket->id);

    expect($ticket->requester->id)->toBe($user->id);
});

it('resolves category relationship', function () {
    $category = Category::factory()->create();
    $ticket   = Ticket::factory()->create(['category_id' => $category->id]);

    $ticket = Ticket::withoutGlobalScopes()->find($ticket->id);

    expect($ticket->category->id)->toBe($category->id);
});

it('resolves group relationship', function () {
    $group  = Group::factory()->create();
    $ticket = Ticket::factory()->create(['group_id' => $group->id]);

    $ticket = Ticket::withoutGlobalScopes()->find($ticket->id);

    expect($ticket->group->id)->toBe($group->id);
});

it('resolves attachments relationship', function () {
    $ticket = Ticket::factory()->create();
    $uploader = User::factory()->create();

    TicketAttachment::create([
        'ticket_id'     => $ticket->id,
        'original_name' => 'doc.pdf',
        'file_path'     => '/storage/x.pdf',
        'file_size'     => 1024,
        'mime_type'     => 'application/pdf',
        'uploaded_by'   => $uploader->id,
    ]);

    $ticket = Ticket::withoutGlobalScopes()->find($ticket->id);

    expect($ticket->attachments)->toHaveCount(1);
});

it('resolves transferRequests relationship', function () {
    $from   = User::factory()->create();
    $to     = User::factory()->create();
    $ticket = Ticket::factory()->create();

    TransferRequest::create([
        'ticket_id'    => $ticket->id,
        'from_user_id' => $from->id,
        'to_user_id'   => $to->id,
        'status'       => 'pending',
    ]);

    $ticket = Ticket::withoutGlobalScopes()->find($ticket->id);

    expect($ticket->transferRequests)->toHaveCount(1);
});
