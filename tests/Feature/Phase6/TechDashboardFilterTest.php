<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketPriority;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Livewire\TechDashboard;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function filterTech(): User
{
    return User::factory()->tech()->create();
}

function filterGroup(User $tech): Group
{
    $group = Group::factory()->create();
    DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $tech->id]);
    return $group;
}

function filterQueueTicket(Group $group, array $attrs = []): Ticket
{
    $category = Category::factory()->create(['group_id' => $group->id]);
    return Ticket::factory()->create(array_merge([
        'group_id'    => $group->id,
        'category_id' => $category->id,
        'assigned_to' => null,
        'status'      => TicketStatus::AwaitingAssignment,
    ], $attrs));
}

function filterMyTicket(User $tech, array $attrs = []): Ticket
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

// ─── Schema ───────────────────────────────────────────────────────────────────

it('users table has preferences JSON column', function () {
    expect(Schema::hasColumn('users', 'preferences'))->toBeTrue();
});

// ─── Status filter ────────────────────────────────────────────────────────────

it('status filter narrows my-tickets to selected statuses', function () {
    $tech = filterTech();

    $inProgress = filterMyTicket($tech, ['status' => TicketStatus::InProgress]);
    $onHold     = filterMyTicket($tech, ['status' => TicketStatus::OnHold]);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterStatus', ['in_progress'])
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($inProgress->id)
        ->and($myTickets->pluck('id'))->not->toContain($onHold->id);
});

it('status filter narrows queue to selected statuses', function () {
    $tech  = filterTech();
    $group = filterGroup($tech);

    $awaiting = filterQueueTicket($group, ['status' => TicketStatus::AwaitingAssignment]);
    $onHold   = filterQueueTicket($group, ['status' => TicketStatus::OnHold]);

    $queue = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterStatus', ['awaiting_assignment'])
        ->viewData('queueTickets');

    expect($queue->pluck('id'))->toContain($awaiting->id)
        ->and($queue->pluck('id'))->not->toContain($onHold->id);
});

// ─── Priority filter ──────────────────────────────────────────────────────────

it('priority filter returns only matching priority in my-tickets', function () {
    $tech = filterTech();

    $critical = filterMyTicket($tech, ['priority' => TicketPriority::Critical]);
    $low      = filterMyTicket($tech, ['priority' => TicketPriority::Low]);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterPriority', ['critical'])
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($critical->id)
        ->and($myTickets->pluck('id'))->not->toContain($low->id);
});

it('priority filter returns only matching priority in queue', function () {
    $tech  = filterTech();
    $group = filterGroup($tech);

    $high   = filterQueueTicket($group, ['priority' => TicketPriority::High]);
    $medium = filterQueueTicket($group, ['priority' => TicketPriority::Medium]);

    $queue = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterPriority', ['high'])
        ->viewData('queueTickets');

    expect($queue->pluck('id'))->toContain($high->id)
        ->and($queue->pluck('id'))->not->toContain($medium->id);
});

// ─── Category filter ──────────────────────────────────────────────────────────

it('category filter returns only tickets in that category', function () {
    $tech     = filterTech();
    $group    = filterGroup($tech);
    $catA     = Category::factory()->create(['group_id' => $group->id]);
    $catB     = Category::factory()->create(['group_id' => $group->id]);

    $inCatA = filterMyTicket($tech, ['category_id' => $catA->id, 'group_id' => $group->id]);
    $inCatB = filterMyTicket($tech, ['category_id' => $catB->id, 'group_id' => $group->id]);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterCategory', $catA->id)
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($inCatA->id)
        ->and($myTickets->pluck('id'))->not->toContain($inCatB->id);
});

// ─── Subcategory resets when category changes ─────────────────────────────────

it('subcategory resets to empty when category changes', function () {
    $tech  = filterTech();
    $group = filterGroup($tech);
    $catA  = Category::factory()->create(['group_id' => $group->id]);
    $catB  = Category::factory()->create(['group_id' => $group->id]);
    $sub   = Subcategory::factory()->create(['category_id' => $catA->id]);

    $component = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterCategory', $catA->id)
        ->set('filterSubcategory', $sub->id);

    expect($component->get('filterSubcategory'))->toBe($sub->id);

    $component->set('filterCategory', $catB->id);

    expect($component->get('filterSubcategory'))->toBe('');
});

// ─── Date range filter ────────────────────────────────────────────────────────

it('date_from excludes tickets created before the date', function () {
    $tech = filterTech();

    $old   = filterMyTicket($tech);
    DB::table('tickets')->where('id', $old->id)->update(['created_at' => now()->subDays(10)]);
    $fresh = filterMyTicket($tech);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('dateFrom', now()->subDays(2)->format('Y-m-d'))
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($fresh->id)
        ->and($myTickets->pluck('id'))->not->toContain($old->id);
});

it('date_to excludes tickets created after the date', function () {
    $tech = filterTech();

    $fresh = filterMyTicket($tech);
    $old   = filterMyTicket($tech);
    DB::table('tickets')->where('id', $old->id)->update(['created_at' => now()->subDays(10)]);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('dateTo', now()->subDays(5)->format('Y-m-d'))
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($old->id)
        ->and($myTickets->pluck('id'))->not->toContain($fresh->id);
});

it('date range combined excludes tickets outside the window', function () {
    $tech = filterTech();

    $inside  = filterMyTicket($tech);
    DB::table('tickets')->where('id', $inside->id)->update(['created_at' => now()->subDays(5)]);
    $outside = filterMyTicket($tech);
    DB::table('tickets')->where('id', $outside->id)->update(['created_at' => now()->subDays(20)]);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($inside->id)
        ->and($myTickets->pluck('id'))->not->toContain($outside->id);
});

// ─── Combined filters ─────────────────────────────────────────────────────────

it('status + priority combined returns intersection not union', function () {
    $tech = filterTech();

    $match    = filterMyTicket($tech, [
        'status'   => TicketStatus::InProgress,
        'priority' => TicketPriority::Critical,
    ]);
    $wrongPri = filterMyTicket($tech, [
        'status'   => TicketStatus::InProgress,
        'priority' => TicketPriority::Low,
    ]);
    $wrongStat = filterMyTicket($tech, [
        'status'   => TicketStatus::OnHold,
        'priority' => TicketPriority::Critical,
    ]);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterStatus', ['in_progress'])
        ->set('filterPriority', ['critical'])
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))
        ->toContain($match->id)
        ->not->toContain($wrongPri->id)
        ->not->toContain($wrongStat->id);
});

// ─── Group filter + persistence ───────────────────────────────────────────────

it('group filter narrows queue to specific groups', function () {
    $tech   = filterTech();
    $groupA = filterGroup($tech);
    $groupB = filterGroup($tech);

    $inA = filterQueueTicket($groupA, ['subject' => 'Group A ticket']);
    $inB = filterQueueTicket($groupB, ['subject' => 'Group B ticket']);

    $queue = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterGroups', [$groupA->id])
        ->viewData('queueTickets');

    expect($queue->pluck('id'))->toContain($inA->id)
        ->and($queue->pluck('id'))->not->toContain($inB->id);
});

it('group preference is saved to users.preferences on change', function () {
    $tech  = filterTech();
    $group = filterGroup($tech);

    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterGroups', [$group->id]);

    $saved = data_get($tech->fresh()->preferences, 'tech_dashboard.groups');
    expect($saved)->toContain($group->id);
});

it('group preference is restored from DB on mount', function () {
    $tech  = filterTech();
    $group = filterGroup($tech);

    // Pre-save a preference
    $tech->update(['preferences' => ['tech_dashboard' => ['groups' => [$group->id]]]]);

    $component = Livewire::actingAs($tech)->test(TechDashboard::class);

    expect($component->get('filterGroups'))->toContain($group->id);
});

it('group preference persists across separate component mounts', function () {
    $tech  = filterTech();
    $group = filterGroup($tech);

    // First mount: set preference
    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('filterGroups', [$group->id]);

    // Second mount: verify restored
    $component = Livewire::actingAs($tech)->test(TechDashboard::class);
    expect($component->get('filterGroups'))->toContain($group->id);
});

// ─── Sort controls ────────────────────────────────────────────────────────────

it('sortBy created_at desc returns newest first in my-tickets', function () {
    $tech = filterTech();

    $older = filterMyTicket($tech);
    DB::table('tickets')->where('id', $older->id)->update(['created_at' => now()->subHours(2)]);
    $newer = filterMyTicket($tech);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('sortBy', 'created_at')
        ->set('sortDir', 'desc')
        ->viewData('myTickets');

    expect($myTickets->first()->id)->toBe($newer->id);
});

it('sortBy created_at asc returns oldest first in my-tickets', function () {
    $tech = filterTech();

    $older = filterMyTicket($tech);
    DB::table('tickets')->where('id', $older->id)->update(['created_at' => now()->subHours(2)]);
    $newer = filterMyTicket($tech);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('sortBy', 'created_at')
        ->set('sortDir', 'asc')
        ->viewData('myTickets');

    expect($myTickets->first()->id)->toBe($older->id);
});

// ─── Text search ──────────────────────────────────────────────────────────────

it('search filters my-tickets by subject match', function () {
    $tech = filterTech();

    $match   = filterMyTicket($tech, ['subject' => 'Keyboard not responding']);
    $nomatch = filterMyTicket($tech, ['subject' => 'Network issue']);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('search', 'Keyboard')
        ->viewData('myTickets');

    expect($myTickets->pluck('id'))->toContain($match->id)
        ->and($myTickets->pluck('id'))->not->toContain($nomatch->id);
});

it('search filters queue by subject match', function () {
    $tech  = filterTech();
    $group = filterGroup($tech);

    $match   = filterQueueTicket($group, ['subject' => 'Printer offline']);
    $nomatch = filterQueueTicket($group, ['subject' => 'Monitor issue']);

    $queue = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('search', 'Printer')
        ->viewData('queueTickets');

    expect($queue->pluck('id'))->toContain($match->id)
        ->and($queue->pluck('id'))->not->toContain($nomatch->id);
});

it('empty search returns all tickets without filtering', function () {
    $tech = filterTech();

    filterMyTicket($tech, ['subject' => 'Alpha']);
    filterMyTicket($tech, ['subject' => 'Beta']);

    $myTickets = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->set('search', '')
        ->viewData('myTickets');

    expect($myTickets->count())->toBe(2);
});
