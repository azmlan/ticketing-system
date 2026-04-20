<?php

use App\Modules\Shared\Models\TechProfile;
use App\Modules\Shared\Models\User;
use Illuminate\Database\QueryException;

it('creates a tech profile for a user', function () {
    $promoter = User::factory()->create();
    $tech     = User::factory()->tech()->create();

    $profile = TechProfile::create([
        'user_id'        => $tech->id,
        'specialization' => 'Network',
        'job_title_ar'   => 'مهندس شبكات',
        'job_title_en'   => 'Network Engineer',
        'promoted_at'    => now(),
        'promoted_by'    => $promoter->id,
    ]);

    expect($profile->fresh())
        ->user_id->toBe($tech->id)
        ->specialization->toBe('Network')
        ->promoted_by->toBe($promoter->id);
});

it('second tech profile for same user fails unique constraint', function () {
    $promoter = User::factory()->create();
    $tech     = User::factory()->tech()->create();

    TechProfile::create([
        'user_id'     => $tech->id,
        'promoted_at' => now(),
        'promoted_by' => $promoter->id,
    ]);

    TechProfile::create([
        'user_id'     => $tech->id,
        'promoted_at' => now(),
        'promoted_by' => $promoter->id,
    ]);
})->throws(QueryException::class);

it('User::techProfile() relationship resolves', function () {
    $promoter = User::factory()->create();
    $tech     = User::factory()->tech()->create();

    TechProfile::create([
        'user_id'     => $tech->id,
        'promoted_at' => now(),
        'promoted_by' => $promoter->id,
    ]);

    expect($tech->techProfile)->not->toBeNull()
        ->and($tech->techProfile->user_id)->toBe($tech->id);
});

it('TechProfile::promoter() resolves to the promoting user', function () {
    $promoter = User::factory()->create();
    $tech     = User::factory()->tech()->create();

    $profile = TechProfile::create([
        'user_id'     => $tech->id,
        'promoted_at' => now(),
        'promoted_by' => $promoter->id,
    ]);

    expect($profile->promoter->id)->toBe($promoter->id);
});

it('deleting a user cascades tech_profiles row', function () {
    $promoter = User::factory()->create();
    $tech     = User::factory()->tech()->create();

    TechProfile::create([
        'user_id'     => $tech->id,
        'promoted_at' => now(),
        'promoted_by' => $promoter->id,
    ]);

    $techId = $tech->id;
    $tech->forceDelete();

    expect(TechProfile::where('user_id', $techId)->exists())->toBeFalse();
});
