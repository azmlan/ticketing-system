<?php

use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Reporting\Reports\CsatByTechReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeSubmittedRating(User $tech, int $rating): CsatRating
{
    $requester = User::factory()->create(['is_tech' => false]);
    $ticket    = Ticket::factory()->create(['assigned_to' => $tech->id]);

    return CsatRating::factory()->create([
        'ticket_id'    => $ticket->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'status'       => 'submitted',
        'rating'       => $rating,
        'submitted_at' => now(),
        'created_at'   => now(),
    ]);
}

it('returns one row per tech with submitted ratings', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    makeSubmittedRating($techA, 4);
    makeSubmittedRating($techB, 5);

    $report = new CsatByTechReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toHaveCount(2);
});

it('calculates avg_rating per tech', function () {
    $tech = User::factory()->tech()->create();

    makeSubmittedRating($tech, 5);
    makeSubmittedRating($tech, 3);

    $report = new CsatByTechReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['avg_rating'])->toBe(4.0);
    expect($rows->first()['rating_count'])->toBe(2);
});

it('returns the lowest_rating for a tech', function () {
    $tech = User::factory()->tech()->create();

    makeSubmittedRating($tech, 5);
    makeSubmittedRating($tech, 2);
    makeSubmittedRating($tech, 4);

    $report = new CsatByTechReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['lowest_rating'])->toBe(2);
});

it('excludes techs with only pending ratings', function () {
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);
    $ticket    = Ticket::factory()->create(['assigned_to' => $tech->id]);

    CsatRating::factory()->create([
        'ticket_id'    => $ticket->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'status'       => 'pending',
        'rating'       => null,
        'created_at'   => now(),
    ]);

    $report = new CsatByTechReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows)->toBeEmpty();
});

it('orders by avg_rating ascending (lowest-rated first)', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    makeSubmittedRating($techA, 5);
    makeSubmittedRating($techB, 2);

    $report = new CsatByTechReport;
    $rows   = $report->run(['date_from' => now()->subDay()->toDateString(), 'date_to' => now()->addDay()->toDateString()]);

    expect($rows->first()['tech_name'])->toBe($techB->full_name);
    expect($rows->last()['tech_name'])->toBe($techA->full_name);
});

it('filters by tech_id', function () {
    $techA = User::factory()->tech()->create();
    $techB = User::factory()->tech()->create();

    makeSubmittedRating($techA, 4);
    makeSubmittedRating($techB, 3);

    $report = new CsatByTechReport;
    $rows   = $report->run([
        'date_from' => now()->subDay()->toDateString(),
        'date_to'   => now()->addDay()->toDateString(),
        'tech_id'   => $techA->id,
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['tech_name'])->toBe($techA->full_name);
});

it('returns empty when no submitted ratings in range', function () {
    $tech = User::factory()->tech()->create();
    makeSubmittedRating($tech, 4);

    $report = new CsatByTechReport;
    $rows   = $report->run(['date_from' => '2000-01-01', 'date_to' => '2000-01-31']);

    expect($rows)->toBeEmpty();
});
