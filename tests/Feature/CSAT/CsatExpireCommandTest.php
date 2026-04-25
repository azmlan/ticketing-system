<?php

use App\Modules\CSAT\Models\CsatRating;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks pending ratings as expired when expires_at has passed', function () {
    CsatRating::factory()->expiredSoon()->create();
    CsatRating::factory()->expiredSoon()->create();

    $this->artisan('csat:expire')->assertExitCode(0);

    expect(CsatRating::expired()->count())->toBe(2);
});

it('does not expire pending ratings that have not yet expired', function () {
    CsatRating::factory()->create(['expires_at' => now()->addDays(3)]);

    $this->artisan('csat:expire')->assertExitCode(0);

    expect(CsatRating::pending()->count())->toBe(1);
    expect(CsatRating::expired()->count())->toBe(0);
});

it('does not reprocess already-expired rows', function () {
    $rating = CsatRating::factory()->expired()->create();
    $original = $rating->updated_at;

    // Run twice
    $this->artisan('csat:expire');
    $this->artisan('csat:expire');

    expect(CsatRating::expired()->count())->toBe(1);
});

it('does not touch submitted ratings', function () {
    CsatRating::factory()->submitted()->create(['expires_at' => now()->subDay()]);

    $this->artisan('csat:expire')->assertExitCode(0);

    expect(CsatRating::submitted()->count())->toBe(1);
    expect(CsatRating::expired()->count())->toBe(0);
});

it('outputs the count of expired ratings', function () {
    CsatRating::factory()->expiredSoon()->create();
    CsatRating::factory()->expiredSoon()->create();

    $this->artisan('csat:expire')
        ->expectsOutputToContain('2')
        ->assertExitCode(0);
});
