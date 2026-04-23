<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Escalation\Jobs\GenerateMaintenanceRequestDocxJob;
use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeMrTicket(string $status, ?User $requester = null, ?User $tech = null): Ticket
{
    $tech      ??= User::factory()->tech()->create();
    $requester ??= User::factory()->create();
    $group       = Group::factory()->create();
    $category    = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-' . str_pad((string) rand(1, 9999999), 7, '0', STR_PAD_LEFT),
        'subject'         => 'MR test ticket',
        'description'     => '<p>Test ticket description.</p>',
        'status'          => TicketStatus::from($status),
        'category_id'     => $category->id,
        'group_id'        => $category->group_id,
        'requester_id'    => $requester->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);
}

function makeApprovedCr(Ticket $ticket, User $tech, User $approver): ConditionReport
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
        'status'             => 'approved',
        'reviewed_by'        => $approver->id,
        'reviewed_at'        => now(),
    ]);
}

function grantMrApprove(User $user): void
{
    $perm = Permission::firstOrCreate(
        ['key' => 'escalation.approve'],
        ['name_ar' => 'اعتماد تقارير الحالة', 'name_en' => 'Approve Escalations', 'group_key' => 'escalation']
    );
    $user->permissions()->syncWithoutDetaching([$perm->id => [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]]);
}

// ─── Listener: job dispatch ───────────────────────────────────────────────────

it('TicketStatusChanged with action_required dispatches generation job', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('awaiting_approval', null, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    app(TicketStateMachine::class)->transition($ticket, 'action_required', $approver);

    Queue::assertPushed(GenerateMaintenanceRequestDocxJob::class, function ($job) use ($ticket) {
        return $job->ticketUlid === $ticket->id && $job->locale === 'ar';
    });
});

it('TicketStatusChanged with non-action_required does not dispatch generation job', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();

    $ticket = makeMrTicket('awaiting_approval', null, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    // Transition to in_progress (reject), not action_required
    app(TicketStateMachine::class)->transition($ticket, 'in_progress', $approver);

    Queue::assertNothingPushed();
});

// ─── Listener: synchronous record creation ────────────────────────────────────

it('MaintenanceRequest record is created synchronously with status=pending before job runs', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('awaiting_approval', null, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    app(TicketStateMachine::class)->transition($ticket, 'action_required', $approver);

    $record = MaintenanceRequest::where('ticket_id', $ticket->id)->first();
    expect($record)->not->toBeNull()
        ->and($record->status)->toBe('pending');

    // generated_file_path is null until the job runs (job is faked here)
    expect($record->generated_file_path)->toBeNull();
});

it('duplicate maintenance_request record is not created when listener fires twice', function () {
    $tech     = User::factory()->tech()->create();
    $approver = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('awaiting_approval', null, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    // First transition creates the record
    app(TicketStateMachine::class)->transition($ticket, 'action_required', $approver);

    // Directly fire event again to simulate reject-resubmit scenario
    TicketStatusChanged::dispatch($ticket, 'awaiting_final_approval', 'action_required', $approver);

    expect(MaintenanceRequest::where('ticket_id', $ticket->id)->count())->toBe(1);

    // But the job should have been dispatched twice
    Queue::assertPushed(GenerateMaintenanceRequestDocxJob::class, 2);
});

// ─── Download route: authorization ───────────────────────────────────────────

it('download route with locale=ar returns 200 with correct Content-Type for requester', function () {
    Queue::fake([]); // reset; download does not push jobs

    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create();
    $approver  = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('action_required', $requester, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    $this->actingAs($requester)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'ar']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
});

it('download route with locale=en returns 200', function () {
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create();
    $approver  = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('action_required', $requester, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    $this->actingAs($requester)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'en']))
        ->assertOk();
});

it('download route returns 200 for the assigned tech', function () {
    $tech      = User::factory()->tech()->create();
    $approver  = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('action_required', null, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    $this->actingAs($tech)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'ar']))
        ->assertOk();
});

it('download route returns 200 for user with escalation.approve', function () {
    $tech      = User::factory()->tech()->create();
    $approver  = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('action_required', null, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    $this->actingAs($approver)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'ar']))
        ->assertOk();
});

it('download route returns 403 for unrelated employee', function () {
    $tech    = User::factory()->tech()->create();
    $other   = User::factory()->create();
    $approver = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('action_required', null, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    $this->actingAs($other)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'ar']))
        ->assertForbidden();
});

it('download route returns 404 for invalid locale', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeMrTicket('action_required', null, $tech);

    $this->actingAs($tech)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'xx']))
        ->assertNotFound();
});

it('download route redirects unauthenticated users', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeMrTicket('action_required', null, $tech);

    $this->get(route('escalation.maintenance-request.download', [$ticket->id, 'ar']))
        ->assertRedirect();
});

// ─── Second generation keeps old file ────────────────────────────────────────

it('on second download old generated_file_path is replaced and old file remains on disk', function () {
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create();
    $approver  = User::factory()->create();
    grantMrApprove($approver);

    $ticket = makeMrTicket('action_required', $requester, $tech);
    makeApprovedCr($ticket, $tech, $approver);

    // First download
    $this->actingAs($requester)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'ar']))
        ->assertOk();

    $firstPath = MaintenanceRequest::where('ticket_id', $ticket->id)->value('generated_file_path');

    // Second download with different locale
    $this->actingAs($requester)
        ->get(route('escalation.maintenance-request.download', [$ticket->id, 'en']))
        ->assertOk();

    $secondPath = MaintenanceRequest::where('ticket_id', $ticket->id)->value('generated_file_path');

    expect($secondPath)->not->toBe($firstPath);

    // Both files remain (old file kept for audit)
    Storage::disk('local')->assertExists($firstPath);
    Storage::disk('local')->assertExists($secondPath);
});
