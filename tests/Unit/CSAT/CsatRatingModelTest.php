<?php

use App\Modules\CSAT\Models\CsatRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('pending scope returns only pending rows', function () {
    CsatRating::factory()->create(['status' => 'pending']);
    CsatRating::factory()->create(['status' => 'submitted', 'rating' => 4, 'submitted_at' => now()]);
    CsatRating::factory()->create(['status' => 'expired', 'expires_at' => now()->subDay()]);

    expect(CsatRating::pending()->count())->toBe(1);
});

it('submitted scope returns only submitted rows', function () {
    CsatRating::factory()->create(['status' => 'pending']);
    CsatRating::factory()->create(['status' => 'submitted', 'rating' => 3, 'submitted_at' => now()]);
    CsatRating::factory()->create(['status' => 'submitted', 'rating' => 5, 'submitted_at' => now()]);

    expect(CsatRating::submitted()->count())->toBe(2);
});

it('expired scope returns only expired rows', function () {
    CsatRating::factory()->create(['status' => 'pending']);
    CsatRating::factory()->expired()->create();
    CsatRating::factory()->expired()->create();

    expect(CsatRating::expired()->count())->toBe(2);
});

it('casts rating as integer', function () {
    $rating = CsatRating::factory()->submitted()->create(['rating' => 4]);

    expect($rating->rating)->toBe(4)
        ->toBeInt();
});

it('casts expires_at as datetime', function () {
    $rating = CsatRating::factory()->create();

    expect($rating->expires_at)->toBeInstanceOf(Carbon::class);
});

it('casts submitted_at as datetime when set', function () {
    $rating = CsatRating::factory()->submitted()->create();

    expect($rating->submitted_at)->toBeInstanceOf(Carbon::class);
});

it('ticket relationship resolves correctly', function () {
    $rating = CsatRating::factory()->create();

    expect($rating->ticket)->not->toBeNull()
        ->and($rating->ticket->id)->toBe($rating->ticket_id);
});

it('requester relationship resolves correctly', function () {
    $rating = CsatRating::factory()->create();

    expect($rating->requester)->not->toBeNull()
        ->and($rating->requester->id)->toBe($rating->requester_id);
});

it('tech relationship resolves correctly', function () {
    $rating = CsatRating::factory()->create();

    expect($rating->tech)->not->toBeNull()
        ->and($rating->tech->id)->toBe($rating->tech_id);
});

it('dismissed_count defaults to zero', function () {
    $rating = CsatRating::factory()->create();

    expect($rating->dismissed_count)->toBe(0);
});
