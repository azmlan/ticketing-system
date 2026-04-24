<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Escalation\Livewire\SubmitConditionReport;
use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\ConditionReportAttachment;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    RateLimiter::clear('upload:*');
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeConditionReportTicket(User $tech): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-0000001',
        'subject'         => 'Escalation test',
        'description'     => '<p>Some device broke.</p>',
        'status'          => TicketStatus::InProgress,
        'category_id'     => $category->id,
        'group_id'        => $category->group_id,
        'requester_id'    => User::factory()->create()->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);
}

function validReportData(): array
{
    return [
        'reportType'        => 'Hardware failure',
        'locationId'        => '',
        'currentCondition'  => '<p>Screen is cracked and power button unresponsive.</p>',
        'conditionAnalysis' => '<p>Physical damage from drop impact.</p>',
        'requiredAction'    => '<p>Replace screen and inspect internal components.</p>',
    ];
}

// ─── Happy path ───────────────────────────────────────────────────────────────

it('tech submits valid condition report — record created and ticket moves to awaiting_approval', function () {
    Event::fake([TicketStatusChanged::class]);

    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);
    $data   = validReportData();

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', $data['reportType'])
        ->set('locationId', $data['locationId'])
        ->set('currentCondition', $data['currentCondition'])
        ->set('conditionAnalysis', $data['conditionAnalysis'])
        ->set('requiredAction', $data['requiredAction'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect();

    // ConditionReport created with correct tech_id and status
    $report = ConditionReport::first();
    expect($report)->not->toBeNull()
        ->and($report->tech_id)->toBe($tech->id)
        ->and($report->status)->toBe('pending')
        ->and($report->ticket_id)->toBe($ticket->id);

    // Ticket status transitioned
    $ticket->refresh();
    expect($ticket->status->value)->toBe('awaiting_approval');

    // TicketStatusChanged event was fired
    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->toStatus === 'awaiting_approval';
    });
});

// ─── Validation errors ────────────────────────────────────────────────────────

it('returns validation error when current_condition is empty', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', 'Hardware failure')
        ->set('currentCondition', '')
        ->set('conditionAnalysis', '<p>Analysis.</p>')
        ->set('requiredAction', '<p>Action.</p>')
        ->call('submit')
        ->assertHasErrors(['currentCondition' => 'required']);
});

it('returns validation error when report_type is empty', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', '')
        ->set('currentCondition', '<p>Condition.</p>')
        ->set('conditionAnalysis', '<p>Analysis.</p>')
        ->set('requiredAction', '<p>Action.</p>')
        ->call('submit')
        ->assertHasErrors(['reportType' => 'required']);
});

// ─── Authorization ────────────────────────────────────────────────────────────

it('non-tech user gets 403 when mounting the component', function () {
    $user   = User::factory()->create(['is_tech' => false]);
    $ticket = makeConditionReportTicket(User::factory()->tech()->create());

    Livewire::actingAs($user)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->assertStatus(403);
});

it('non-tech user gets 403 when calling submit directly', function () {
    // Mount as tech to bypass mount() check, then change auth
    $tech    = User::factory()->tech()->create();
    $nonTech = User::factory()->create(['is_tech' => false]);
    $ticket  = makeConditionReportTicket($tech);

    // Mount as tech
    $component = Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id]);

    // Switch auth to non-tech and call submit
    Livewire::actingAs($nonTech);

    $component
        ->set('reportType', 'Hardware failure')
        ->set('currentCondition', '<p>Condition.</p>')
        ->set('conditionAnalysis', '<p>Analysis.</p>')
        ->set('requiredAction', '<p>Action.</p>')
        ->call('submit')
        ->assertStatus(403);
});

it('submission on a ticket not in_progress receives 403', function () {
    $tech   = User::factory()->tech()->create();
    $group  = Group::factory()->create();
    $cat    = Category::factory()->create(['group_id' => $group->id]);

    $ticket = Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-0000002',
        'subject'         => 'Already approved',
        'description'     => '<p>Test.</p>',
        'status'          => TicketStatus::AwaitingApproval,
        'category_id'     => $cat->id,
        'group_id'        => $cat->group_id,
        'requester_id'    => User::factory()->create()->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', 'Hardware failure')
        ->set('currentCondition', '<p>Condition.</p>')
        ->set('conditionAnalysis', '<p>Analysis.</p>')
        ->set('requiredAction', '<p>Action.</p>')
        ->call('submit')
        ->assertStatus(403);
});

// ─── File attachment validation ───────────────────────────────────────────────

it('attachment with wrong MIME type is rejected with validation error', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);

    // fake()->create() produces random bytes; finfo detects application/octet-stream → not allowed
    $badFile = UploadedFile::fake()->create('photo.jpg', 1, 'image/jpeg');

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', 'Hardware failure')
        ->set('currentCondition', '<p>Condition.</p>')
        ->set('conditionAnalysis', '<p>Analysis.</p>')
        ->set('requiredAction', '<p>Action.</p>')
        ->set('attachments', [$badFile])
        ->call('submit')
        ->assertHasErrors(['attachments']);
});

it('6th attachment in one submit is rejected', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);

    $files = array_map(fn ($i) => UploadedFile::fake()->image("photo_{$i}.jpg", 50, 50), range(1, 6));

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', 'Hardware failure')
        ->set('currentCondition', '<p>Condition.</p>')
        ->set('conditionAnalysis', '<p>Analysis.</p>')
        ->set('requiredAction', '<p>Action.</p>')
        ->set('attachments', $files)
        ->call('submit')
        ->assertHasErrors(['attachments']);
});

// ─── Attachment happy path ────────────────────────────────────────────────────

it('valid image attachments are stored and ConditionReportAttachment records created', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);
    $image  = UploadedFile::fake()->image('evidence.jpg', 300, 200);

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', 'Hardware failure')
        ->set('currentCondition', '<p>Condition.</p>')
        ->set('conditionAnalysis', '<p>Analysis.</p>')
        ->set('requiredAction', '<p>Action.</p>')
        ->set('attachments', [$image])
        ->call('submit')
        ->assertHasNoErrors();

    $report = ConditionReport::first();
    expect($report->attachments)->toHaveCount(1);

    $attachment = $report->attachments->first();
    expect($attachment)->toBeInstanceOf(ConditionReportAttachment::class)
        ->and($attachment->mime_type)->toBe('image/jpeg')
        ->and(Storage::disk('local')->exists($attachment->file_path))->toBeTrue();
});

// ─── Event ────────────────────────────────────────────────────────────────────

it('TicketStatusChanged event is fired on successful submission', function () {
    Event::fake([TicketStatusChanged::class]);

    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);

    Livewire::actingAs($tech)
        ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
        ->set('reportType', 'Network issue')
        ->set('currentCondition', '<p>No connectivity.</p>')
        ->set('conditionAnalysis', '<p>NIC failed.</p>')
        ->set('requiredAction', '<p>Replace NIC.</p>')
        ->call('submit')
        ->assertHasNoErrors();

    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->fromStatus === 'in_progress'
            && $event->toStatus === 'awaiting_approval';
    });
});

// ─── DB transaction rollback ─────────────────────────────────────────────────

it('rolls back ConditionReport if state machine throws', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeConditionReportTicket($tech);

    // Force the state machine to throw after ConditionReport would be created
    $this->mock(TicketStateMachine::class)
        ->shouldReceive('transition')
        ->andThrow(new \App\Modules\Tickets\Exceptions\InvalidTicketTransitionException('in_progress', 'awaiting_approval', 'forced test failure'));

    expect(ConditionReport::count())->toBe(0);

    try {
        Livewire::actingAs($tech)
            ->test(SubmitConditionReport::class, ['ticketId' => $ticket->id])
            ->set('reportType', 'Hardware failure')
            ->set('currentCondition', '<p>Condition.</p>')
            ->set('conditionAnalysis', '<p>Analysis.</p>')
            ->set('requiredAction', '<p>Action.</p>')
            ->call('submit');
    } catch (\Throwable) {
        // Exception may bubble; we only care that no partial record remains
    }

    // Transaction rolled back — no ConditionReport persisted
    expect(ConditionReport::count())->toBe(0);

    // Ticket status unchanged
    $ticket->refresh();
    expect($ticket->status->value)->toBe('in_progress');
});
