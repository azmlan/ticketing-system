<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Reporting\Reports\TicketVolumeReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketPriority;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a row per day that has tickets in the date range', function () {
    $category = Category::factory()->create();
    $group    = Group::factory()->create();
    $requester = User::factory()->create(['is_tech' => false]);

    Ticket::factory()->create([
        'requester_id' => $requester->id,
        'category_id'  => $category->id,
        'group_id'     => $group->id,
        'created_at'   => '2026-04-01 10:00:00',
    ]);
    Ticket::factory()->create([
        'requester_id' => $requester->id,
        'category_id'  => $category->id,
        'group_id'     => $group->id,
        'created_at'   => '2026-04-01 14:00:00',
    ]);
    Ticket::factory()->create([
        'requester_id' => $requester->id,
        'category_id'  => $category->id,
        'group_id'     => $group->id,
        'created_at'   => '2026-04-03 09:00:00',
    ]);

    $report = new TicketVolumeReport;
    $rows = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toHaveCount(2);
    expect($rows->firstWhere('period', '2026-04-01')['count'])->toBe(2);
    expect($rows->firstWhere('period', '2026-04-03')['count'])->toBe(1);
});

it('excludes tickets outside the date range', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $category  = Category::factory()->create();
    $group     = Group::factory()->create();

    Ticket::factory()->create([
        'requester_id' => $requester->id,
        'category_id'  => $category->id,
        'group_id'     => $group->id,
        'created_at'   => '2026-03-15 10:00:00',
    ]);

    $report = new TicketVolumeReport;
    $rows = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows)->toBeEmpty();
});

it('returns rows with period and count keys', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $category  = Category::factory()->create();
    $group     = Group::factory()->create();

    Ticket::factory()->create([
        'requester_id' => $requester->id,
        'category_id'  => $category->id,
        'group_id'     => $group->id,
        'created_at'   => '2026-04-10 10:00:00',
    ]);

    $report = new TicketVolumeReport;
    $rows = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30']);

    expect($rows->first())->toHaveKeys(['period', 'count']);
});

it('filters by category', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $catA = Category::factory()->create();
    $catB = Category::factory()->create();
    $group = Group::factory()->create();

    Ticket::factory()->create(['requester_id' => $requester->id, 'category_id' => $catA->id, 'group_id' => $group->id, 'created_at' => '2026-04-05']);
    Ticket::factory()->create(['requester_id' => $requester->id, 'category_id' => $catB->id, 'group_id' => $group->id, 'created_at' => '2026-04-05']);

    $report = new TicketVolumeReport;
    $rows = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30', 'category_id' => $catA->id]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['count'])->toBe(1);
});

it('filters by priority', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $category  = Category::factory()->create();
    $group     = Group::factory()->create();

    Ticket::factory()->create(['requester_id' => $requester->id, 'category_id' => $category->id, 'group_id' => $group->id, 'priority' => TicketPriority::High, 'created_at' => '2026-04-05']);
    Ticket::factory()->create(['requester_id' => $requester->id, 'category_id' => $category->id, 'group_id' => $group->id, 'priority' => TicketPriority::Low, 'created_at' => '2026-04-05']);

    $report = new TicketVolumeReport;
    $rows = $report->run(['date_from' => '2026-04-01', 'date_to' => '2026-04-30', 'priority' => 'high']);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['count'])->toBe(1);
});

it('returns empty collection when no filters produce results', function () {
    $report = new TicketVolumeReport;
    $rows = $report->run(['date_from' => '2020-01-01', 'date_to' => '2020-01-31']);

    expect($rows)->toBeEmpty();
});
