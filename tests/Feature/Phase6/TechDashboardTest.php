<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketPriority;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\TechDashboard;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use Database\Factories\TicketSlaFactory;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeTechUser(): User
{
    return User::factory()->tech()->create();
}

function makeGroupWithTech(User $tech): Group
{
    $group = Group::factory()->create();
    DB::table('group_user')->insert([
        'group_id' => $group->id,
        'user_id'  => $tech->id,
    ]);
    return $group;
}

function unassignedTicketInGroup(Group $group, array $attrs = []): Ticket
{
    $category = Category::factory()->create(['group_id' => $group->id]);
    return Ticket::factory()->create(array_merge([
        'group_id'    => $group->id,
        'category_id' => $category->id,
        'assigned_to' => null,
        'status'      => TicketStatus::AwaitingAssignment,
    ], $attrs));
}

function assignedTicketForTech(User $tech, array $attrs = []): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);
    return Ticket::factory()->create(array_merge([
        'group_id'    => $group->id,
        'category_id' => $category->id,
        'assigned_to' => $tech->id,
        'status'      => TicketStatus::InProgress,
    ], $attrs));
}

function attachSla(Ticket $ticket, string $resolutionStatus, string $responseStatus = 'on_track'): void
{
    DB::table('ticket_sla')->insert([
        'id'                          => \Illuminate\Support\Str::ulid(),
        'ticket_id'                   => $ticket->id,
        'response_target_minutes'     => 60,
        'resolution_target_minutes'   => 480,
        'response_elapsed_minutes'    => 0,
        'resolution_elapsed_minutes'  => 0,
        'response_met_at'             => null,
        'response_status'             => $responseStatus,
        'resolution_status'           => $resolutionStatus,
        'last_clock_start'            => now(),
        'is_clock_running'            => true,
        'created_at'                  => now(),
        'updated_at'                  => now(),
    ]);
}

// ─── Access control ───────────────────────────────────────────────────────────

it('redirects unauthenticated users to login', function () {
    $this->get(route('tickets.dashboard.tech'))
        ->assertRedirect(route('login'));
});

it('renders the tech dashboard for a tech user', function () {
    $tech = makeTechUser();

    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->assertOk();
});

it('returns 403 for a non-tech user without ticket.view-assigned', function () {
    $employee = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($employee)
        ->test(TechDashboard::class)
        ->assertForbidden();
});

// ─── Queue: unassigned tickets in tech's groups ───────────────────────────────

it('queue shows only unassigned tickets from tech groups', function () {
    $tech  = makeTechUser();
    $group = makeGroupWithTech($tech);

    $inQueue = unassignedTicketInGroup($group, ['subject' => 'Unassigned in my group']);
    $notMine = unassignedTicketInGroup(Group::factory()->create(), ['subject' => 'Different group']);

    $queue = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('queueTickets');

    expect($queue->pluck('id'))->toContain($inQueue->id)
        ->and($queue->pluck('id'))->not->toContain($notMine->id);
});

it('queue excludes assigned tickets even if in tech group', function () {
    $tech  = makeTechUser();
    $group = makeGroupWithTech($tech);

    $assigned   = unassignedTicketInGroup($group, ['assigned_to' => $tech->id]);
    $unassigned = unassignedTicketInGroup($group);

    $queue = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('queueTickets');

    expect($queue->pluck('id'))->not->toContain($assigned->id)
        ->and($queue->pluck('id'))->toContain($unassigned->id);
});

it('queue is sorted by priority descending then by date', function () {
    $tech  = makeTechUser();
    $group = makeGroupWithTech($tech);

    $low      = unassignedTicketInGroup($group, ['priority' => TicketPriority::Low]);
    $critical = unassignedTicketInGroup($group, ['priority' => TicketPriority::Critical]);
    $high     = unassignedTicketInGroup($group, ['priority' => TicketPriority::High]);

    $queue = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('queueTickets');

    $priorities = $queue->pluck('priority')->map->value->toArray();
    expect($priorities[0])->toBe('critical')
        ->and($priorities[1])->toBe('high')
        ->and($priorities[2])->toBe('low');
});

// ─── My Tickets: SLA urgency sort ────────────────────────────────────────────

it('my tickets shows only tickets assigned to the tech', function () {
    $tech  = makeTechUser();
    $other = makeTechUser();

    $mine    = assignedTicketForTech($tech, ['subject' => 'My ticket']);
    $theirs  = assignedTicketForTech($other, ['subject' => 'Their ticket']);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($mine->id)
        ->and($myTickets->pluck('id'))->not->toContain($theirs->id);
});

it('my tickets sorted breached before warning before on_track', function () {
    $tech = makeTechUser();

    $onTrack = assignedTicketForTech($tech, ['subject' => 'On track']);
    $warning = assignedTicketForTech($tech, ['subject' => 'Warning']);
    $breached = assignedTicketForTech($tech, ['subject' => 'Breached']);

    attachSla($onTrack, 'on_track');
    attachSla($warning, 'warning');
    attachSla($breached, 'breached');

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('myTickets');

    $subjects = $myTickets->pluck('subject')->toArray();
    $breachedPos = array_search('Breached', $subjects);
    $warningPos  = array_search('Warning', $subjects);
    $onTrackPos  = array_search('On track', $subjects);

    expect($breachedPos)->toBeLessThan($warningPos)
        ->and($warningPos)->toBeLessThan($onTrackPos);
});

it('my tickets with no sla appear after sla-tracked tickets', function () {
    $tech = makeTechUser();

    $withSla    = assignedTicketForTech($tech, ['subject' => 'With SLA']);
    $withoutSla = assignedTicketForTech($tech, ['subject' => 'No SLA']);

    attachSla($withSla, 'on_track');
    // $withoutSla has no ticket_sla row

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('myTickets');

    $subjects = $myTickets->pluck('subject')->toArray();
    expect(array_search('With SLA', $subjects))->toBeLessThan(
        array_search('No SLA', $subjects)
    );
});

// ─── Pending Transfer Requests panel ─────────────────────────────────────────

it('transfer panel shows pending requests targeting this tech only', function () {
    $tech     = makeTechUser();
    $otherTech = makeTechUser();
    $sender   = makeTechUser();

    $myTicket    = assignedTicketForTech($sender);
    $otherTicket = assignedTicketForTech($sender);

    $myTransfer = TransferRequest::factory()->create([
        'ticket_id'    => $myTicket->id,
        'from_user_id' => $sender->id,
        'to_user_id'   => $tech->id,
        'status'       => 'pending',
    ]);
    $otherTransfer = TransferRequest::factory()->create([
        'ticket_id'    => $otherTicket->id,
        'from_user_id' => $sender->id,
        'to_user_id'   => $otherTech->id,
        'status'       => 'pending',
    ]);

    $transfers = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('pendingTransfers');

    expect($transfers->pluck('id'))->toContain($myTransfer->id)
        ->and($transfers->pluck('id'))->not->toContain($otherTransfer->id);
});

it('transfer panel excludes non-pending requests', function () {
    $tech   = makeTechUser();
    $sender = makeTechUser();

    $ticket   = assignedTicketForTech($sender);
    $accepted = TransferRequest::factory()->create([
        'ticket_id'    => $ticket->id,
        'from_user_id' => $sender->id,
        'to_user_id'   => $tech->id,
        'status'       => 'accepted',
    ]);

    $transfers = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('pendingTransfers');

    expect($transfers->pluck('id'))->not->toContain($accepted->id);
});

it('tech can accept a pending transfer request', function () {
    $tech   = makeTechUser();
    $sender = makeTechUser();
    $ticket = assignedTicketForTech($sender);

    $tr = TransferRequest::factory()->create([
        'ticket_id'    => $ticket->id,
        'from_user_id' => $sender->id,
        'to_user_id'   => $tech->id,
        'status'       => 'pending',
    ]);

    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->call('acceptTransfer', $tr->id);

    expect($tr->fresh()->status)->toBe('accepted');
});

it('tech can decline a pending transfer request', function () {
    $tech   = makeTechUser();
    $sender = makeTechUser();
    $ticket = assignedTicketForTech($sender);

    $tr = TransferRequest::factory()->create([
        'ticket_id'    => $ticket->id,
        'from_user_id' => $sender->id,
        'to_user_id'   => $tech->id,
        'status'       => 'pending',
    ]);

    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->call('declineTransfer', $tr->id);

    expect($tr->fresh()->status)->toBe('rejected');
});

// ─── Quick stats ──────────────────────────────────────────────────────────────

it('quick stats reflect correct open count', function () {
    $tech  = makeTechUser();
    $other = makeTechUser();

    assignedTicketForTech($tech, ['status' => TicketStatus::InProgress]);
    assignedTicketForTech($tech, ['status' => TicketStatus::InProgress]);
    assignedTicketForTech($tech, ['status' => TicketStatus::Resolved, 'resolved_at' => now()]);
    assignedTicketForTech($other, ['status' => TicketStatus::InProgress]);

    $stats = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('stats');

    expect($stats['open'])->toBe(2);
});

it('quick stats resolved this week counts only current week', function () {
    $tech = makeTechUser();

    assignedTicketForTech($tech, [
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now(),
    ]);
    assignedTicketForTech($tech, [
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now()->subWeeks(2),
    ]);

    $stats = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('stats');

    expect($stats['resolved_week'])->toBe(1);
});

it('quick stats resolved this month counts current month', function () {
    $tech = makeTechUser();

    assignedTicketForTech($tech, [
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now(),
    ]);
    assignedTicketForTech($tech, [
        'status'      => TicketStatus::Resolved,
        'resolved_at' => now()->subMonths(2),
    ]);

    $stats = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('stats');

    expect($stats['resolved_month'])->toBe(1);
});

it('quick stats sla compliance is 100 when all closed tickets are on_track', function () {
    $tech = makeTechUser();

    $t1 = assignedTicketForTech($tech, ['status' => TicketStatus::Resolved]);
    $t2 = assignedTicketForTech($tech, ['status' => TicketStatus::Closed]);
    attachSla($t1, 'on_track');
    attachSla($t2, 'on_track');

    $stats = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('stats');

    expect($stats['sla_compliance'])->toBe(100);
});

it('quick stats sla compliance is 0 when no closed tickets have sla data', function () {
    $tech = makeTechUser();

    assignedTicketForTech($tech, ['status' => TicketStatus::Resolved]);

    $stats = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('stats');

    expect($stats['sla_compliance'])->toBe(0);
});

it('quick stats sla compliance calculates partial rate correctly', function () {
    $tech = makeTechUser();

    $t1 = assignedTicketForTech($tech, ['status' => TicketStatus::Resolved]);
    $t2 = assignedTicketForTech($tech, ['status' => TicketStatus::Resolved]);
    attachSla($t1, 'on_track');
    attachSla($t2, 'breached');

    $stats = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->viewData('stats');

    expect($stats['sla_compliance'])->toBe(50);
});

// ─── AR locale ────────────────────────────────────────────────────────────────

it('renders without errors in AR locale', function () {
    $tech = makeTechUser();
    app()->setLocale('ar');

    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->assertOk()
        ->assertSee('لوحة تحكم الفني');
});
