<?php

use App\Modules\CSAT\Mail\TicketResolvedMail;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues a TicketResolvedMail when ticket transitions to resolved', function () {
    Queue::fake();

    $requester = User::factory()->create(['locale' => 'en', 'is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::InProgress,
        'assigned_to' => $tech->id,
        'requester_id' => $requester->id,
    ]);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $tech));

    Queue::assertPushed(SendQueuedMailable::class, function ($job) {
        return $job->mailable instanceof TicketResolvedMail;
    });
});

it('does not queue mail when resolution event fires a second time', function () {
    Queue::fake();

    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
        'assigned_to' => $tech->id,
        'requester_id' => $requester->id,
    ]);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $tech));
    Queue::assertPushed(SendQueuedMailable::class, 1);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $tech));
    Queue::assertPushed(SendQueuedMailable::class, 1);
});

it('does not queue mail when ticket has no assigned tech', function () {
    Queue::fake();

    $requester = User::factory()->create(['is_tech' => false]);
    $ticket = Ticket::factory()->create([
        'assigned_to' => null,
        'requester_id' => $requester->id,
    ]);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $requester));

    Queue::assertNotPushed(SendQueuedMailable::class, function ($job) {
        return $job->mailable instanceof TicketResolvedMail;
    });
});

it('mailable contains display_number and tech name', function () {
    $requester = User::factory()->create(['locale' => 'en', 'is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true, 'full_name' => 'Khalid Al-Harbi']);
    $ticket = Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'requester_id' => $requester->id,
    ]);

    $mailable = new TicketResolvedMail($ticket, $requester, $tech);

    $mailable->assertSeeInHtml($ticket->display_number);
    $mailable->assertSeeInHtml('Khalid Al-Harbi');
    $mailable->assertSeeInText($ticket->display_number);
    $mailable->assertSeeInText('Khalid Al-Harbi');
});

it('mailable does not contain a survey or rating URL', function () {
    $requester = User::factory()->create(['locale' => 'en', 'is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'requester_id' => $requester->id,
    ]);

    $mailable = new TicketResolvedMail($ticket, $requester, $tech);

    $mailable->assertDontSeeInHtml('csat/rate');
    $mailable->assertDontSeeInHtml('survey');
    $mailable->assertDontSeeInText('survey');
});

it('mailable uses the requester locale', function () {
    $requesterAr = User::factory()->create(['locale' => 'ar', 'is_tech' => false]);
    $requesterEn = User::factory()->create(['locale' => 'en', 'is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);

    $ticketAr = Ticket::factory()->create(['assigned_to' => $tech->id, 'requester_id' => $requesterAr->id]);
    $ticketEn = Ticket::factory()->create(['assigned_to' => $tech->id, 'requester_id' => $requesterEn->id]);

    $mailAr = new TicketResolvedMail($ticketAr, $requesterAr, $tech);
    $mailEn = new TicketResolvedMail($ticketEn, $requesterEn, $tech);

    expect($mailAr->locale)->toBe('ar');
    expect($mailEn->locale)->toBe('en');
});

it('mailable queued on the notifications queue', function () {
    Queue::fake();

    $requester = User::factory()->create(['locale' => 'en', 'is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create([
        'assigned_to' => $tech->id,
        'requester_id' => $requester->id,
    ]);

    event(new TicketStatusChanged($ticket, 'in_progress', 'resolved', $tech));

    Queue::assertPushedOn('notifications', SendQueuedMailable::class);
});
