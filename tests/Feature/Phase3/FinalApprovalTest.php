<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Escalation\Livewire\ReviewSignedMaintenanceRequest;
use App\Modules\Escalation\Livewire\UploadSignedMaintenanceRequest;
use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeActionRequiredSetup(): array
{
    $requester = User::factory()->create();
    $tech      = User::factory()->tech()->create();
    $group     = Group::factory()->create();
    $category  = Category::factory()->create(['group_id' => $group->id]);

    $ticket = Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-' . str_pad((string) rand(1, 9999999), 7, '0', STR_PAD_LEFT),
        'subject'         => 'Final approval test',
        'description'     => '<p>Test.</p>',
        'status'          => TicketStatus::ActionRequired,
        'category_id'     => $category->id,
        'group_id'        => $group->id,
        'requester_id'    => $requester->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);

    Storage::disk('local')->put("maintenance-requests/{$ticket->id}/generated.docx", 'docx-content');

    $mr = MaintenanceRequest::create([
        'ticket_id'           => $ticket->id,
        'generated_file_path' => "maintenance-requests/{$ticket->id}/generated.docx",
        'generated_locale'    => 'ar',
        'status'              => 'pending',
    ]);

    return [$ticket, $requester, $tech, $mr];
}

function makeAwaitingFinalSetup(): array
{
    $requester = User::factory()->create();
    $tech      = User::factory()->tech()->create();
    $group     = Group::factory()->create();
    $category  = Category::factory()->create(['group_id' => $group->id]);

    $ticket = Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-' . str_pad((string) rand(1, 9999999), 7, '0', STR_PAD_LEFT),
        'subject'         => 'Final approval test',
        'description'     => '<p>Test.</p>',
        'status'          => TicketStatus::AwaitingFinalApproval,
        'category_id'     => $category->id,
        'group_id'        => $group->id,
        'requester_id'    => $requester->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);

    Storage::disk('local')->put("maintenance-requests/{$ticket->id}/signed/doc", 'signed-content');

    $mr = MaintenanceRequest::create([
        'ticket_id'            => $ticket->id,
        'generated_file_path'  => "maintenance-requests/{$ticket->id}/generated.docx",
        'generated_locale'     => 'ar',
        'submitted_file_path'  => "maintenance-requests/{$ticket->id}/signed/doc",
        'submitted_at'         => now(),
        'status'               => 'submitted',
    ]);

    return [$ticket, $requester, $tech, $mr];
}

function grantFinalApprove(User $user): void
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

function makePdfFile(): \Illuminate\Http\Testing\File
{
    $resource = tmpfile();
    fwrite($resource, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    rewind($resource);
    return new \Illuminate\Http\Testing\File('signed.pdf', $resource);
}

function makeDocxFile(): \Illuminate\Http\Testing\File
{
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->addSection()->addText('Signed maintenance request');
    $tmpPath = tempnam(sys_get_temp_dir(), 'test_docx_') . '.docx';
    \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($tmpPath);

    $resource = tmpfile();
    fwrite($resource, file_get_contents($tmpPath));
    rewind($resource);
    @unlink($tmpPath);

    return new \Illuminate\Http\Testing\File('signed.docx', $resource);
}

// ─── Upload happy path ────────────────────────────────────────────────────────

it('requester uploads valid PDF — submitted_file_path set, submitted_at set, status=submitted, ticket=awaiting_final_approval', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket, $requester] = makeActionRequiredSetup();
    $file = makePdfFile();

    Livewire::actingAs($requester)
        ->test(UploadSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('signedFile', $file)
        ->call('upload')
        ->assertHasNoErrors()
        ->assertRedirect();

    $mr = MaintenanceRequest::where('ticket_id', $ticket->id)->first();
    expect($mr->submitted_file_path)->not->toBeNull()
        ->and($mr->submitted_at)->not->toBeNull()
        ->and($mr->status)->toBe('submitted');

    $ticket->refresh();
    expect($ticket->status->value)->toBe('awaiting_final_approval');
});

it('requester uploads valid DOCX — ticket transitions to awaiting_final_approval', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket, $requester] = makeActionRequiredSetup();
    $file = makeDocxFile();

    Livewire::actingAs($requester)
        ->test(UploadSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('signedFile', $file)
        ->call('upload')
        ->assertHasNoErrors()
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->status->value)->toBe('awaiting_final_approval');
});

it('upload fires TicketStatusChanged to awaiting_final_approval', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket, $requester] = makeActionRequiredSetup();
    $file = makePdfFile();

    Livewire::actingAs($requester)
        ->test(UploadSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('signedFile', $file)
        ->call('upload');

    Event::assertDispatched(TicketStatusChanged::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id
            && $event->toStatus === 'awaiting_final_approval';
    });
});

// ─── Upload authorization ─────────────────────────────────────────────────────

it('non-requester (tech) cannot upload signed document — 403', function () {
    [$ticket, , $tech] = makeActionRequiredSetup();
    $file = makePdfFile();

    Livewire::actingAs($tech)
        ->test(UploadSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('signedFile', $file)
        ->call('upload')
        ->assertStatus(403);
});

it('unrelated user cannot upload signed document — 403', function () {
    [$ticket] = makeActionRequiredSetup();
    $other = User::factory()->create();
    $file  = makePdfFile();

    Livewire::actingAs($other)
        ->test(UploadSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('signedFile', $file)
        ->call('upload')
        ->assertStatus(403);
});

// ─── Upload MIME validation ───────────────────────────────────────────────────

it('upload with image MIME type (not PDF/DOCX) is rejected with validation error', function () {
    [$ticket, $requester] = makeActionRequiredSetup();
    $file = UploadedFile::fake()->image('bad.jpg', 100, 100);

    Livewire::actingAs($requester)
        ->test(UploadSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('signedFile', $file)
        ->call('upload')
        ->assertHasErrors(['signedFile']);

    $mr = MaintenanceRequest::where('ticket_id', $ticket->id)->first();
    expect($mr->submitted_file_path)->toBeNull();
});

// ─── Approve ─────────────────────────────────────────────────────────────────

it('approver approves — status=approved, ticket=resolved, reviewed_by and reviewed_at set', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket, , , $mr] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->call('approve')
        ->assertHasNoErrors()
        ->assertRedirect();

    $mr->refresh();
    expect($mr->status)->toBe('approved')
        ->and($mr->reviewed_by)->toBe($approver->id)
        ->and($mr->reviewed_at)->not->toBeNull();

    $ticket->refresh();
    expect($ticket->status->value)->toBe('resolved');
});

it('approve fires TicketStatusChanged to resolved', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->call('approve');

    Event::assertDispatched(TicketStatusChanged::class, function ($e) use ($ticket) {
        return $e->ticket->id === $ticket->id && $e->toStatus === 'resolved';
    });
});

// ─── Reject (resubmit) ────────────────────────────────────────────────────────

it('reject resubmit without review_notes returns validation error', function () {
    [$ticket] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', '')
        ->call('rejectResubmit')
        ->assertHasErrors(['reviewNotes' => 'required']);
});

it('reject resubmit with notes — ticket loops to action_required, rejection_count incremented, no duplicate MR', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket, , , $mr] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', 'Signature is missing on page 2.')
        ->call('rejectResubmit')
        ->assertHasNoErrors()
        ->assertRedirect();

    $mr->refresh();
    expect($mr->status)->toBe('rejected')
        ->and($mr->rejection_count)->toBe(1)
        ->and($mr->review_notes)->toBe('Signature is missing on page 2.')
        ->and($mr->reviewed_by)->toBe($approver->id);

    $ticket->refresh();
    expect($ticket->status->value)->toBe('action_required');

    // No duplicate maintenance_requests record
    expect(MaintenanceRequest::where('ticket_id', $ticket->id)->count())->toBe(1);
});

it('reject resubmit fires TicketStatusChanged to action_required', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('showRejectForm', true)
        ->set('reviewNotes', 'Missing signature.')
        ->call('rejectResubmit');

    Event::assertDispatched(TicketStatusChanged::class, function ($e) use ($ticket) {
        return $e->ticket->id === $ticket->id && $e->toStatus === 'action_required';
    });
});

// ─── Reject permanently ───────────────────────────────────────────────────────

it('reject permanently without close_reason returns validation error', function () {
    [$ticket] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('showPermanentForm', true)
        ->set('closeReason', '')
        ->call('rejectPermanently')
        ->assertHasErrors(['closeReason' => 'required']);
});

it('reject permanently — ticket closed, MR status rejected, close_reason persisted', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket, , , $mr] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('showPermanentForm', true)
        ->set('closeReason', 'out_of_scope')
        ->call('rejectPermanently')
        ->assertHasNoErrors()
        ->assertRedirect();

    $mr->refresh();
    expect($mr->status)->toBe('rejected')
        ->and($mr->reviewed_by)->toBe($approver->id);

    $ticket->refresh();
    expect($ticket->status->value)->toBe('closed')
        ->and($ticket->close_reason)->toBe('out_of_scope');
});

it('reject permanently fires TicketStatusChanged to closed', function () {
    Event::fake([TicketStatusChanged::class]);

    [$ticket] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('showPermanentForm', true)
        ->set('closeReason', 'duplicate')
        ->call('rejectPermanently');

    Event::assertDispatched(TicketStatusChanged::class, function ($e) use ($ticket) {
        return $e->ticket->id === $ticket->id && $e->toStatus === 'closed';
    });
});

// ─── Authorization (review component) ────────────────────────────────────────

it('user without escalation.approve gets 403 when mounting review component', function () {
    [$ticket] = makeAwaitingFinalSetup();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->assertStatus(403);
});

it('user without escalation.approve gets 403 calling approve directly', function () {
    [$ticket] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    $noPerms = User::factory()->create();

    $component = Livewire::actingAs($approver)
        ->test(ReviewSignedMaintenanceRequest::class, ['ticketId' => $ticket->id]);

    Livewire::actingAs($noPerms);

    $component->call('approve')->assertStatus(403);
});

// ─── Signed document serve route ─────────────────────────────────────────────

it('requester can download their signed document', function () {
    [$ticket, $requester, , $mr] = makeAwaitingFinalSetup();

    $this->actingAs($requester)
        ->get(route('escalation.signed-document.show', $mr->id))
        ->assertOk();
});

it('assigned tech can download the signed document', function () {
    [$ticket, , $tech, $mr] = makeAwaitingFinalSetup();

    $this->actingAs($tech)
        ->get(route('escalation.signed-document.show', $mr->id))
        ->assertOk();
});

it('approver can download the signed document', function () {
    [$ticket, , , $mr] = makeAwaitingFinalSetup();
    $approver = User::factory()->create();
    grantFinalApprove($approver);

    $this->actingAs($approver)
        ->get(route('escalation.signed-document.show', $mr->id))
        ->assertOk();
});

it('unrelated user gets 403 on signed document serve route', function () {
    [$ticket, , , $mr] = makeAwaitingFinalSetup();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('escalation.signed-document.show', $mr->id))
        ->assertStatus(403);
});

it('unauthenticated user is redirected from signed document serve route', function () {
    [$ticket, , , $mr] = makeAwaitingFinalSetup();

    $this->get(route('escalation.signed-document.show', $mr->id))
        ->assertRedirect('/login');
});

// ─── Rate limit ───────────────────────────────────────────────────────────────

it('upload rate limit: 21st upload in an hour returns 429', function () {
    [$ticket, $requester] = makeActionRequiredSetup();
    $key = 'upload:' . $requester->id;

    for ($i = 0; $i < 20; $i++) {
        RateLimiter::hit($key, 3600);
    }

    $file = makePdfFile();

    Livewire::actingAs($requester)
        ->test(UploadSignedMaintenanceRequest::class, ['ticketId' => $ticket->id])
        ->set('signedFile', $file)
        ->call('upload')
        ->assertStatus(429);
});
