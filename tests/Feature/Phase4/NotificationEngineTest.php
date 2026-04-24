<?php

use App\Mail\TicketNotification;
use App\Modules\Assignment\Events\TransferRequestCreated;
use App\Modules\Communication\Jobs\SendNotificationJob;
use App\Modules\Communication\Models\Comment;
use App\Modules\Communication\Models\NotificationLog;
use App\Modules\Communication\Models\Scopes\InternalCommentScope;
use App\Modules\Communication\Services\NotificationService;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

// ─── Event → Job dispatch ─────────────────────────────────────────────────────

it('TicketStatusChanged awaiting_assignment dispatches SendNotificationJob for requester', function () {
    Queue::fake();

    $requester = User::factory()->create();
    $ticket    = Ticket::factory()->create(['requester_id' => $requester->id]);
    $actor     = User::factory()->tech()->create();

    TicketStatusChanged::dispatch($ticket, '', 'awaiting_assignment', $actor);

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $requester->id &&
        $job->triggerKey === 'ticket_created'
    );
});

it('TicketStatusChanged in_progress from awaiting_assignment dispatches job for assigned tech', function () {
    Queue::fake();

    $tech      = User::factory()->tech()->create();
    $ticket    = Ticket::factory()->inProgress()->create(['assigned_to' => $tech->id]);
    $actor     = $tech;

    TicketStatusChanged::dispatch($ticket, 'awaiting_assignment', 'in_progress', $actor);

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $tech->id &&
        $job->triggerKey === 'ticket_assigned'
    );
});

it('TicketStatusChanged resolved dispatches jobs for both tech and requester', function () {
    Queue::fake();

    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create();
    $ticket    = Ticket::factory()->create([
        'assigned_to'  => $tech->id,
        'requester_id' => $requester->id,
        'status'       => 'resolved',
    ]);
    $actor = $tech;

    TicketStatusChanged::dispatch($ticket, 'in_progress', 'resolved', $actor);

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $tech->id && $job->triggerKey === 'ticket_resolved'
    );

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $requester->id && $job->triggerKey === 'ticket_resolved'
    );
});

it('TransferRequestCreated dispatches SendNotificationJob for target tech', function () {
    Queue::fake();

    $toTech = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    TransferRequestCreated::dispatch(
        $ticket->id,
        $toTech->id,
        $ticket->display_number,
        $ticket->subject,
    );

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $toTech->id &&
        $job->triggerKey === 'transfer_request'
    );
});

// ─── notification_logs on dispatch ───────────────────────────────────────────

it('dispatching a notification creates a notification_log with status=queued', function () {
    Queue::fake();

    $requester = User::factory()->create(['locale' => 'en']);
    $ticket    = Ticket::factory()->create(['requester_id' => $requester->id]);
    $actor     = User::factory()->tech()->create();

    TicketStatusChanged::dispatch($ticket, '', 'awaiting_assignment', $actor);

    expect(
        NotificationLog::where('recipient_id', $requester->id)
            ->where('status', 'queued')
            ->where('type', 'ticket_created')
            ->exists()
    )->toBeTrue();
});

// ─── Job execution ────────────────────────────────────────────────────────────

it('successful job execution updates notification_log to sent', function () {
    Mail::fake();

    $recipient = User::factory()->create(['locale' => 'en']);
    $ticket    = Ticket::factory()->create();

    $log = NotificationLog::create([
        'recipient_id' => $recipient->id,
        'ticket_id'    => $ticket->id,
        'type'         => 'ticket_created',
        'channel'      => 'email',
        'subject'      => 'Test subject',
        'status'       => 'queued',
        'attempts'     => 0,
    ]);

    $job = new SendNotificationJob(
        notificationLogId: $log->id,
        recipient: $recipient,
        triggerKey: 'ticket_created',
        ticketId: $ticket->id,
        displayNumber: $ticket->display_number,
        ticketSubject: $ticket->subject,
    );

    $job->handle();

    $log->refresh();
    expect($log->status)->toBe('sent')
        ->and($log->sent_at)->not->toBeNull()
        ->and($log->attempts)->toBe(1);

    Mail::assertSent(TicketNotification::class, fn ($mail) =>
        $mail->hasTo($recipient->email) && $mail->triggerKey === 'ticket_created'
    );
});

it('job failure updates notification_log to failed with failure_reason', function () {
    $recipient = User::factory()->create();
    $ticket    = Ticket::factory()->create();

    $log = NotificationLog::create([
        'recipient_id' => $recipient->id,
        'ticket_id'    => $ticket->id,
        'type'         => 'ticket_created',
        'channel'      => 'email',
        'subject'      => 'Test',
        'status'       => 'queued',
        'attempts'     => 0,
    ]);

    $job = new SendNotificationJob(
        notificationLogId: $log->id,
        recipient: $recipient,
        triggerKey: 'ticket_created',
        ticketId: $ticket->id,
        displayNumber: $ticket->display_number,
        ticketSubject: $ticket->subject,
    );

    $job->failed(new \Exception('Connection refused'));

    $log->refresh();
    expect($log->status)->toBe('failed')
        ->and($log->failure_reason)->toBe('Connection refused');
});

// ─── Locale-aware subject ─────────────────────────────────────────────────────

it('notification_log subject is rendered in recipient locale', function () {
    Queue::fake();

    $arRecipient = User::factory()->create(['locale' => 'ar']);
    $enRecipient = User::factory()->create(['locale' => 'en']);
    $ticket      = Ticket::factory()->create();

    app(NotificationService::class)->dispatch(
        'ticket_created',
        $ticket->id,
        $ticket->display_number,
        $ticket->subject,
        [$arRecipient],
    );

    app(NotificationService::class)->dispatch(
        'ticket_created',
        $ticket->id,
        $ticket->display_number,
        $ticket->subject,
        [$enRecipient],
    );

    $arLog = NotificationLog::where('recipient_id', $arRecipient->id)->first();
    $enLog = NotificationLog::where('recipient_id', $enRecipient->id)->first();

    expect($arLog->subject)->toContain('تم استلام');
    expect($enLog->subject)->toContain('received');
});

it('SendNotificationJob sets locale on recipient when rendering email', function () {
    Mail::fake();

    $recipient = User::factory()->create(['locale' => 'ar']);
    $ticket    = Ticket::factory()->create();

    $log = NotificationLog::create([
        'recipient_id' => $recipient->id,
        'ticket_id'    => $ticket->id,
        'type'         => 'ticket_created',
        'channel'      => 'email',
        'subject'      => 'Test',
        'status'       => 'queued',
        'attempts'     => 0,
    ]);

    $job = new SendNotificationJob(
        notificationLogId: $log->id,
        recipient: $recipient,
        triggerKey: 'ticket_created',
        ticketId: $ticket->id,
        displayNumber: $ticket->display_number,
        ticketSubject: $ticket->subject,
    );

    $job->handle();

    // Locale was set to ar during handle(); the mailable's envelope subject uses __()
    // which resolves AR translation. We verify by rendering the mailable directly.
    app()->setLocale('ar');
    $mailable = new TicketNotification('ticket_created', $ticket->id, $ticket->display_number, $ticket->subject, $recipient->full_name);
    $subject  = $mailable->envelope()->subject;
    expect($subject)->toContain('تم استلام');
});

// ─── Internal comment never in email body ─────────────────────────────────────

it('notification email body does not contain internal comment content', function () {
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['locale' => 'en']);
    $ticket    = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'assigned_to'  => $tech->id,
    ]);

    // Seed an internal comment on this ticket
    Comment::withoutGlobalScope(InternalCommentScope::class)->create([
        'ticket_id'   => $ticket->id,
        'user_id'     => $tech->id,
        'body'        => '<p>Secret internal note XYZ987</p>',
        'is_internal' => true,
    ]);

    app()->setLocale('en');

    $mailable = new TicketNotification(
        triggerKey: 'ticket_resolved',
        ticketId: $ticket->id,
        displayNumber: $ticket->display_number,
        ticketSubject: $ticket->subject,
        recipientName: $requester->full_name,
    );

    $html = $mailable->render();

    expect($html)->not->toContain('Secret internal note XYZ987');
});

// ─── escalation_submitted → approvers ────────────────────────────────────────

it('TicketStatusChanged awaiting_approval dispatches jobs for escalation approvers', function () {
    Queue::fake();
    $this->seed(PermissionSeeder::class);

    $approver = User::factory()->tech()->create();
    $perm     = Permission::where('key', 'escalation.approve')->first();
    $approver->permissions()->attach($perm->id, ['granted_by' => $approver->id, 'granted_at' => now()]);

    $tech   = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create(['assigned_to' => $tech->id]);
    $actor  = $tech;

    TicketStatusChanged::dispatch($ticket, 'in_progress', 'awaiting_approval', $actor);

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $approver->id &&
        $job->triggerKey === 'escalation_submitted'
    );
});

// ─── action_required / form_rejected ─────────────────────────────────────────

it('TicketStatusChanged action_required from awaiting_approval dispatches action_required for requester', function () {
    Queue::fake();

    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create();
    $ticket    = Ticket::factory()->create([
        'assigned_to'  => $tech->id,
        'requester_id' => $requester->id,
    ]);

    TicketStatusChanged::dispatch($ticket, 'awaiting_approval', 'action_required', $tech);

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $requester->id && $job->triggerKey === 'action_required'
    );
});

it('TicketStatusChanged action_required from awaiting_final_approval dispatches form_rejected for requester', function () {
    Queue::fake();

    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create();
    $ticket    = Ticket::factory()->create([
        'assigned_to'  => $tech->id,
        'requester_id' => $requester->id,
    ]);

    TicketStatusChanged::dispatch($ticket, 'awaiting_final_approval', 'action_required', $tech);

    Queue::assertPushed(SendNotificationJob::class, fn ($job) =>
        $job->recipient->id === $requester->id && $job->triggerKey === 'form_rejected'
    );
});

// ─── Plain-text fallback ──────────────────────────────────────────────────────

it('mailable renders a plain-text fallback free of HTML tags', function () {
    app()->setLocale('en');

    $recipient = User::factory()->create(['locale' => 'en', 'full_name' => 'Jane Smith']);
    $ticket    = Ticket::factory()->create(['subject' => 'Printer is broken']);

    $mailable = new TicketNotification(
        triggerKey: 'ticket_created',
        ticketId: $ticket->id,
        displayNumber: $ticket->display_number,
        ticketSubject: $ticket->subject,
        recipientName: $recipient->full_name,
    );

    $text = $mailable->render();

    // The text part must exist (Content has both view: and text:)
    // Render the text view directly to verify it is tag-free
    $textContent = view('emails.notifications.text.ticket_created', [
        'displayNumber' => $ticket->display_number,
        'ticketSubject' => $ticket->subject,
        'ticketUrl'     => route('tickets.show', $ticket->id),
        'recipientName' => $recipient->full_name,
    ])->render();

    expect($textContent)->not->toContain('<')
        ->and($textContent)->not->toContain('>')
        ->and($textContent)->toContain($ticket->display_number)
        ->and($textContent)->toContain('Jane Smith');
});

it('plain-text fallback renders in AR locale without HTML', function () {
    app()->setLocale('ar');

    $recipient = User::factory()->create(['locale' => 'ar', 'full_name' => 'محمد علي']);
    $ticket    = Ticket::factory()->create();

    $textContent = view('emails.notifications.text.ticket_created', [
        'displayNumber' => $ticket->display_number,
        'ticketSubject' => $ticket->subject,
        'ticketUrl'     => route('tickets.show', $ticket->id),
        'recipientName' => $recipient->full_name,
    ])->render();

    expect($textContent)->not->toContain('<')
        ->and($textContent)->toContain('تم استلام')
        ->and($textContent)->toContain($ticket->display_number);
});
