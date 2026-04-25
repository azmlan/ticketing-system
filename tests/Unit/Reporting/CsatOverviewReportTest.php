<?php

use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Reporting\Reports\CsatOverviewReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCsatForDate(string $date, string $status = 'submitted', ?int $rating = 4): CsatRating
{
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);
    $ticket    = Ticket::factory()->create(['assigned_to' => $tech->id]);

    return CsatRating::factory()->create([
        'ticket_id'    => $ticket->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'status'       => $status,
        'rating'       => $status === 'submitted' ? $rating : null,
        'submitted_at' => $status === 'submitted' ? now() : null,
        'created_at'   => $date,
    ]);
}

it('returns one row per day with CSAT activity', function () {
    makeCsatForDate(now()->toDateTimeString());
    makeCsatForDate(now()->subDay()->toDateTimeString());

    $report = new CsatOverviewReport;
    $rows   = $report->run(['date_from' => now()->subDays(2)->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toHaveCount(2);
});

it('calculates avg_rating for submitted ratings only', function () {
    makeCsatForDate(now()->toDateTimeString(), 'submitted', 5);
    makeCsatForDate(now()->toDateTimeString(), 'submitted', 3);
    makeCsatForDate(now()->toDateTimeString(), 'pending', null);

    $report = new CsatOverviewReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['avg_rating'])->toBe(4.0);
    expect($rows->first()['submitted_count'])->toBe(2);
    expect($rows->first()['total_count'])->toBe(3);
});

it('calculates response_rate correctly', function () {
    makeCsatForDate(now()->toDateTimeString(), 'submitted', 4);
    makeCsatForDate(now()->toDateTimeString(), 'pending', null);
    makeCsatForDate(now()->toDateTimeString(), 'expired', null);
    makeCsatForDate(now()->toDateTimeString(), 'expired', null);

    $report = new CsatOverviewReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    // 1 submitted / 4 total = 25.0%
    expect($rows->first()['response_rate'])->toBe('25.0%');
});

it('counts rating distribution correctly', function () {
    makeCsatForDate(now()->toDateTimeString(), 'submitted', 1);
    makeCsatForDate(now()->toDateTimeString(), 'submitted', 5);
    makeCsatForDate(now()->toDateTimeString(), 'submitted', 5);

    $report = new CsatOverviewReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['rating_1'])->toBe(1);
    expect($rows->first()['rating_5'])->toBe(2);
    expect($rows->first()['rating_2'])->toBe(0);
});

it('shows none for avg_rating when no submitted ratings', function () {
    makeCsatForDate(now()->toDateTimeString(), 'pending', null);

    $report = new CsatOverviewReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['avg_rating'])->toBe(__('reports.labels.none'));
});

it('returns empty when no CSAT records in range', function () {
    makeCsatForDate(now()->toDateTimeString());

    $report = new CsatOverviewReport;
    $rows   = $report->run(['date_from' => '2000-01-01', 'date_to' => '2000-01-31']);

    expect($rows)->toBeEmpty();
});

it('filters by tech_id', function () {
    $techA     = User::factory()->tech()->create();
    $techB     = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);

    $ticketA = Ticket::factory()->create(['assigned_to' => $techA->id]);
    $ticketB = Ticket::factory()->create(['assigned_to' => $techB->id]);

    CsatRating::factory()->submitted()->create([
        'ticket_id'    => $ticketA->id,
        'tech_id'      => $techA->id,
        'requester_id' => $requester->id,
        'created_at'   => now(),
    ]);
    CsatRating::factory()->submitted()->create([
        'ticket_id'    => $ticketB->id,
        'tech_id'      => $techB->id,
        'requester_id' => $requester->id,
        'created_at'   => now(),
    ]);

    $report = new CsatOverviewReport;
    $rows   = $report->run([
        'date_from' => now()->subDay()->toDateString(),
        'date_to'   => now()->addDay()->toDateString(),
        'tech_id'   => $techA->id,
    ]);

    expect($rows->first()['submitted_count'])->toBe(1);
});
