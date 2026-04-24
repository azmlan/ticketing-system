<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Escalation\Livewire\ReviewConditionReport;
use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\ConditionReportAttachment;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeApprovalTicket(User $tech): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-' . str_pad((string) rand(1, 9999999), 7, '0', STR_PAD_LEFT),
        'subject'         => 'Escalation approval test',
        'description'     => '<p>Test ticket.</p>',
        'status'          => TicketStatus::AwaitingApproval,
        'category_id'     => $category->id,
        'group_id'        => $category->group_id,
        'requester_id'    => User::factory()->create()->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);
}

function makePendingReport(Ticket $ticket, User $tech): ConditionReport
{
    return ConditionReport::create([
        'ticket_id'          => $ticket->id,
        'report_type'        => 'Hardware failure',
        'location_id'        => null,
        'report_date'        => now()->toDateString(),
        'current_condition'  => '<p>Screen cracked.</p>',
        'condition_analysis' => '<p>Physical damage.</p>',
        'required_action'    => '<p>Replace screen.</p>',
        'tech_id'            => $tech->id,
        'status'             => 'pending',
    ]);
}

function grantEscalationApprove(User $user): void
{
    $permission = Permission::firstOrCreate(
        ['key' => 'escalation.approve'],
        ['name_ar' => 'اعتماد تقارير الحالة', 'name_en' => 'Approve Escalations', 'group_key' => 'escalation']
    );
    $user->permissions()->syncWithoutDetaching([$permission->id => [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]]);
}

function grantViewAll(User $user): void
{
    $permission = Permission::firstOrCreate(
        ['key' => 'ticket.view-all'],
        ['name_ar' => 'عرض جميع التذاكر', 'name_en' => 'View All Tickets', 'group_key' => 'ticket']
    );
    $user->permissions()->syncWithoutDetaching([$permission->id => [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]]);
}

// ─── Approve happy path ───────────────────────────────────────────────────────

it('approver approves condition report — status approved, ticket moves to action_required', function () {
    Event::fake([TicketStatusChanged::class]);

    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    $report = makePendingReport($ticket, $tech);

    Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->call('approve')
        ->assertHasNoErrors()
        ->assertRedirect();

    $report->refresh();
    expect($report->status)->toBe('approved')
        ->and($report->reviewed_by)->toBe($approver->id)
        ->and($report->reviewed_at)->not->toBeNull();

    $ticket->refresh();
    expect($ticket->status->value)->toBe('action_required');
});

it('approver approve fires TicketStatusChanged event', function () {
    Event::fake([TicketStatusChanged::class]);

    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->call('approve');

    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->fromStatus === 'awaiting_approval'
            && $event->toStatus === 'action_required';
    });
});

// ─── Reject happy path ────────────────────────────────────────────────────────

it('approver rejects with notes — status rejected, ticket returns to in_progress', function () {
    Event::fake([TicketStatusChanged::class]);

    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    $report = makePendingReport($ticket, $tech);

    Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', 'Insufficient analysis provided.')
        ->call('reject')
        ->assertHasNoErrors()
        ->assertRedirect();

    $report->refresh();
    expect($report->status)->toBe('rejected')
        ->and($report->reviewed_by)->toBe($approver->id)
        ->and($report->reviewed_at)->not->toBeNull()
        ->and($report->review_notes)->toBe('Insufficient analysis provided.');

    $ticket->refresh();
    expect($ticket->status->value)->toBe('in_progress');
});

it('approver reject fires TicketStatusChanged event', function () {
    Event::fake([TicketStatusChanged::class]);

    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', 'Need more details.')
        ->call('reject');

    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->fromStatus === 'awaiting_approval'
            && $event->toStatus === 'in_progress';
    });
});

// ─── Validation ───────────────────────────────────────────────────────────────

it('reject without review_notes returns validation error', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', '')
        ->call('reject')
        ->assertHasErrors(['reviewNotes' => 'required']);
});

it('reject with review_notes exceeding 1000 chars returns validation error', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', str_repeat('a', 1001))
        ->call('reject')
        ->assertHasErrors(['reviewNotes' => 'max']);
});

// ─── Authorization ────────────────────────────────────────────────────────────

it('user without escalation.approve gets 403 when mounting the component', function () {
    $tech = User::factory()->tech()->create();
    $user = User::factory()->create();

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    Livewire::actingAs($user)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->assertStatus(403);
});

it('user without escalation.approve gets 403 when calling approve directly', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $noPermUser = User::factory()->create();

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    // Mount as approver, then switch to user without permission
    $component = Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id]);

    Livewire::actingAs($noPermUser);

    $component
        ->call('approve')
        ->assertStatus(403);
});

it('submitting tech cannot approve their own condition report (403)', function () {
    $tech = User::factory()->tech()->create();
    grantEscalationApprove($tech);

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    Livewire::actingAs($tech)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->call('approve')
        ->assertStatus(403);
});

it('submitting tech cannot reject their own condition report (403)', function () {
    $tech = User::factory()->tech()->create();
    grantEscalationApprove($tech);

    $ticket = makeApprovalTicket($tech);
    makePendingReport($ticket, $tech);

    Livewire::actingAs($tech)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', 'Some notes.')
        ->call('reject')
        ->assertStatus(403);
});

it('ticket not in awaiting_approval rejects approve action (403)', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    // Ticket already in action_required (not awaiting_approval)
    $ticket = Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-' . str_pad((string) rand(1, 9999999), 7, '0', STR_PAD_LEFT),
        'subject'         => 'Already actioned',
        'description'     => '<p>Test.</p>',
        'status'          => TicketStatus::ActionRequired,
        'category_id'     => $category->id,
        'group_id'        => $category->group_id,
        'requester_id'    => User::factory()->create()->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);

    // Create a condition report manually to allow mounting
    $report = makePendingReport($ticket, $tech);

    // Mount succeeds (no ticket-status check in mount, only permission check)
    $component = Livewire::actingAs($approver)
        ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id]);

    // But approve should 403 because ticket is not awaiting_approval
    $component->call('approve')->assertStatus(403);
});

// ─── Attachment serve route ───────────────────────────────────────────────────

it('condition report attachment serve route returns 200 for the submitting tech', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeApprovalTicket($tech);
    $report = makePendingReport($ticket, $tech);

    Storage::disk('local')->put("condition-reports/{$report->id}/test-attach", 'fake-image-data');

    $attachment = ConditionReportAttachment::create([
        'condition_report_id' => $report->id,
        'original_name'       => 'evidence.jpg',
        'file_path'           => "condition-reports/{$report->id}/test-attach",
        'file_size'           => 16,
        'mime_type'           => 'image/jpeg',
    ]);

    $this->actingAs($tech)
        ->get(route('escalation.condition-report-attachments.show', [$report->id, $attachment->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('condition report attachment serve route returns 200 for a user with escalation.approve', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    $report = makePendingReport($ticket, $tech);

    Storage::disk('local')->put("condition-reports/{$report->id}/test-attach", 'fake-image-data');

    $attachment = ConditionReportAttachment::create([
        'condition_report_id' => $report->id,
        'original_name'       => 'evidence.jpg',
        'file_path'           => "condition-reports/{$report->id}/test-attach",
        'file_size'           => 16,
        'mime_type'           => 'image/jpeg',
    ]);

    $this->actingAs($approver)
        ->get(route('escalation.condition-report-attachments.show', [$report->id, $attachment->id]))
        ->assertOk();
});

it('condition report attachment serve route returns 403 for unauthorized user', function () {
    $tech      = User::factory()->tech()->create();
    $otherUser = User::factory()->create();

    $ticket = makeApprovalTicket($tech);
    $report = makePendingReport($ticket, $tech);

    Storage::disk('local')->put("condition-reports/{$report->id}/test-attach", 'fake-image-data');

    $attachment = ConditionReportAttachment::create([
        'condition_report_id' => $report->id,
        'original_name'       => 'evidence.jpg',
        'file_path'           => "condition-reports/{$report->id}/test-attach",
        'file_size'           => 16,
        'mime_type'           => 'image/jpeg',
    ]);

    $this->actingAs($otherUser)
        ->get(route('escalation.condition-report-attachments.show', [$report->id, $attachment->id]))
        ->assertStatus(403);
});

it('condition report attachment serve route returns 200 for user with ticket.view-all', function () {
    $tech    = User::factory()->tech()->create();
    $manager = User::factory()->create();
    grantViewAll($manager);

    $ticket = makeApprovalTicket($tech);
    $report = makePendingReport($ticket, $tech);

    Storage::disk('local')->put("condition-reports/{$report->id}/test-attach", 'fake-image-data');

    $attachment = ConditionReportAttachment::create([
        'condition_report_id' => $report->id,
        'original_name'       => 'evidence.jpg',
        'file_path'           => "condition-reports/{$report->id}/test-attach",
        'file_size'           => 16,
        'mime_type'           => 'image/jpeg',
    ]);

    $this->actingAs($manager)
        ->get(route('escalation.condition-report-attachments.show', [$report->id, $attachment->id]))
        ->assertOk();
});

// ─── DB transaction rollback ─────────────────────────────────────────────────

it('rolls back condition report status update if state machine throws on approve', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantEscalationApprove($approver);

    $ticket = makeApprovalTicket($tech);
    $report = makePendingReport($ticket, $tech);

    $this->mock(\App\Modules\Tickets\Services\TicketStateMachine::class)
        ->shouldReceive('transition')
        ->andThrow(new \App\Modules\Tickets\Exceptions\InvalidTicketTransitionException('awaiting_approval', 'action_required', 'forced'));

    try {
        Livewire::actingAs($approver)
            ->test(ReviewConditionReport::class, ['ticketId' => $ticket->id])
            ->call('approve');
    } catch (\Throwable) {
    }

    $report->refresh();
    expect($report->status)->toBe('pending');

    $ticket->refresh();
    expect($ticket->status->value)->toBe('awaiting_approval');
});
