<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\ManagerDashboard;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeManager(): User
{
    $user       = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $permission = Permission::firstOrCreate(
        ['key' => 'ticket.view-all'],
        ['name_ar' => 'عرض جميع التذاكر', 'name_en' => 'View All Tickets', 'group_key' => 'ticket']
    );
    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    return $user;
}

function managerTicket(array $attrs = []): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::factory()->create(array_merge([
        'group_id'    => $group->id,
        'category_id' => $category->id,
    ], $attrs));
}

function attachSlaForManager(Ticket $ticket, string $resolutionStatus, int $elapsedMinutes = 120): void
{
    DB::table('ticket_sla')->insert([
        'id'                         => \Illuminate\Support\Str::ulid(),
        'ticket_id'                  => $ticket->id,
        'response_target_minutes'    => 60,
        'resolution_target_minutes'  => 480,
        'response_elapsed_minutes'   => 0,
        'resolution_elapsed_minutes' => $elapsedMinutes,
        'response_met_at'            => null,
        'response_status'            => 'on_track',
        'resolution_status'          => $resolutionStatus,
        'last_clock_start'           => now(),
        'is_clock_running'           => true,
        'created_at'                 => now(),
        'updated_at'                 => now(),
    ]);
}

// ─── Access control ───────────────────────────────────────────────────────────

it('redirects unauthenticated users to login', function () {
    $this->get(route('tickets.dashboard.manager'))
        ->assertRedirect(route('login'));
});

it('returns 403 for employee without ticket.view-all', function () {
    $employee = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);

    Livewire::actingAs($employee)
        ->test(ManagerDashboard::class)
        ->assertForbidden();
});

it('returns 403 for tech without ticket.view-all', function () {
    $tech = User::factory()->tech()->create();

    Livewire::actingAs($tech)
        ->test(ManagerDashboard::class)
        ->assertForbidden();
});

it('renders for user with ticket.view-all', function () {
    $manager = makeManager();

    Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->assertOk();
});

it('renders for superuser', function () {
    $super = User::factory()->superUser()->create();

    Livewire::actingAs($super)
        ->test(ManagerDashboard::class)
        ->assertOk();
});

// ─── Summary stats ────────────────────────────────────────────────────────────

it('status counts match seeded db state', function () {
    $manager = makeManager();

    $requester = User::factory()->create();
    $this->actingAs($requester);

    managerTicket(['status' => TicketStatus::AwaitingAssignment, 'requester_id' => $requester->id]);
    managerTicket(['status' => TicketStatus::InProgress, 'requester_id' => $requester->id]);
    managerTicket(['status' => TicketStatus::Resolved, 'requester_id' => $requester->id]);
    managerTicket(['status' => TicketStatus::Resolved, 'requester_id' => $requester->id]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);

    $statusCounts = $component->viewData('statusCounts');

    expect($statusCounts['awaiting_assignment'])->toBe(1)
        ->and($statusCounts['in_progress'])->toBe(1)
        ->and($statusCounts['resolved'])->toBe(2);
});

it('created_week count reflects tickets made this week', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $this->actingAs($requester);

    managerTicket(['requester_id' => $requester->id, 'created_at' => now()]);
    managerTicket(['requester_id' => $requester->id, 'created_at' => now()->subDays(8)]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);

    expect($component->viewData('createdWeek'))->toBe(1);
});

// ─── SLA section ─────────────────────────────────────────────────────────────

it('breached tickets list shows only breached open tickets', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $this->actingAs($requester);

    $breached = managerTicket([
        'requester_id' => $requester->id,
        'status'       => TicketStatus::InProgress,
    ]);
    $onTrack = managerTicket([
        'requester_id' => $requester->id,
        'status'       => TicketStatus::InProgress,
    ]);
    $resolvedBreached = managerTicket([
        'requester_id' => $requester->id,
        'status'       => TicketStatus::Resolved,
    ]);

    attachSlaForManager($breached, 'breached', 300);
    attachSlaForManager($onTrack, 'on_track', 60);
    attachSlaForManager($resolvedBreached, 'breached', 200);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);
    $breachedList = $component->viewData('breachedTickets');

    expect($breachedList)->toHaveCount(1)
        ->and($breachedList->first()->id)->toBe($breached->id);
});

it('breached_count matches open breached tickets', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $this->actingAs($requester);

    $t1 = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::InProgress]);
    $t2 = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::OnHold]);

    attachSlaForManager($t1, 'breached');
    attachSlaForManager($t2, 'breached');

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);

    expect($component->viewData('breachedCount'))->toBe(2);
});

it('sla_compliance is computed correctly', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $this->actingAs($requester);

    $compliant1 = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::Resolved]);
    $compliant2 = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::Resolved]);
    $breached   = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::Closed]);

    attachSlaForManager($compliant1, 'on_track');
    attachSlaForManager($compliant2, 'on_track');
    attachSlaForManager($breached, 'breached');

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);

    expect($component->viewData('slaCompliance'))->toBe(67);
});

// ─── Escalation queue ─────────────────────────────────────────────────────────

it('escalation queue contains only awaiting_approval and awaiting_final_approval tickets', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $this->actingAs($requester);

    $approval      = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::AwaitingApproval]);
    $finalApproval = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::AwaitingFinalApproval]);
    $inProgress    = managerTicket(['requester_id' => $requester->id, 'status' => TicketStatus::InProgress]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);
    $queue     = $component->viewData('escalationQueue');

    $ids = collect($queue)->pluck('id');

    expect($ids)->toContain($approval->id)
        ->and($ids)->toContain($finalApproval->id)
        ->and($ids)->not->toContain($inProgress->id);
});

// ─── Unassigned count ─────────────────────────────────────────────────────────

it('unassigned count matches tickets with no assigned_to', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $tech      = User::factory()->tech()->create();
    $this->actingAs($requester);

    managerTicket(['requester_id' => $requester->id, 'assigned_to' => null, 'status' => TicketStatus::AwaitingAssignment]);
    managerTicket(['requester_id' => $requester->id, 'assigned_to' => null, 'status' => TicketStatus::AwaitingAssignment]);
    managerTicket(['requester_id' => $requester->id, 'assigned_to' => $tech->id, 'status' => TicketStatus::InProgress]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);

    expect($component->viewData('unassignedCount'))->toBe(2);
});

// ─── Team workload ────────────────────────────────────────────────────────────

it('team workload counts match assignments', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $tech1     = User::factory()->tech()->create();
    $tech2     = User::factory()->tech()->create();
    $this->actingAs($requester);

    managerTicket(['requester_id' => $requester->id, 'assigned_to' => $tech1->id, 'status' => TicketStatus::InProgress]);
    managerTicket(['requester_id' => $requester->id, 'assigned_to' => $tech1->id, 'status' => TicketStatus::InProgress]);
    managerTicket(['requester_id' => $requester->id, 'assigned_to' => $tech2->id, 'status' => TicketStatus::InProgress]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);
    $workload  = $component->viewData('teamWorkload');

    $byTech = collect($workload)->keyBy('id');

    expect($byTech[$tech1->id]->open_count)->toBe(2)
        ->and($byTech[$tech2->id]->open_count)->toBe(1);
});

it('team workload is sorted by open count descending', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $tech1     = User::factory()->tech()->create();
    $tech2     = User::factory()->tech()->create();
    $this->actingAs($requester);

    managerTicket(['requester_id' => $requester->id, 'assigned_to' => $tech1->id, 'status' => TicketStatus::InProgress]);
    managerTicket(['requester_id' => $requester->id, 'assigned_to' => $tech2->id, 'status' => TicketStatus::InProgress]);
    managerTicket(['requester_id' => $requester->id, 'assigned_to' => $tech2->id, 'status' => TicketStatus::InProgress]);

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);
    $workload  = $component->viewData('teamWorkload');

    expect($workload->first()->id)->toBe($tech2->id);
});

// ─── Recent activity ──────────────────────────────────────────────────────────

it('recent activity contains system-wide tickets capped at 20', function () {
    $manager   = makeManager();
    $requester = User::factory()->create();
    $this->actingAs($requester);

    for ($i = 0; $i < 25; $i++) {
        managerTicket(['requester_id' => $requester->id]);
    }

    $component = Livewire::actingAs($manager)->test(ManagerDashboard::class);

    expect($component->viewData('recentActivity'))->toHaveCount(20);
});

// ─── Localization ─────────────────────────────────────────────────────────────

it('renders in AR locale without errors', function () {
    $manager = makeManager();
    app()->setLocale('ar');

    Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->assertOk();
});
