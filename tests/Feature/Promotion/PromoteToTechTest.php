<?php

use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\TechProfile;
use App\Modules\Shared\Models\User;
use App\Modules\Auth\Livewire\PromoteToTech;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('user with user.promote can promote an employee to tech', function () {
    $promoter = User::factory()->create();
    $permission = Permission::where('key', 'user.promote')->firstOrFail();
    $promoter->permissions()->attach($permission->id, [
        'granted_by' => $promoter->id,
        'granted_at' => now(),
    ]);

    $employee = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($promoter)
        ->test(PromoteToTech::class)
        ->set('user_id', $employee->id)
        ->call('promote')
        ->assertHasNoErrors();

    $employee->refresh();
    expect($employee->is_tech)->toBeTrue();

    $profile = TechProfile::where('user_id', $employee->id)->firstOrFail();
    expect($profile->promoted_by)->toBe($promoter->id)
        ->and($profile->promoted_at)->not->toBeNull();
});

test('user without user.promote is denied with 403 on mount', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('promote'))->assertForbidden();
});

test('promoting an already-tech user fails with validation error', function () {
    $promoter = User::factory()->create();
    $permission = Permission::where('key', 'user.promote')->firstOrFail();
    $promoter->permissions()->attach($permission->id, [
        'granted_by' => $promoter->id,
        'granted_at' => now(),
    ]);

    $tech = User::factory()->tech()->create();

    Livewire::actingAs($promoter)
        ->test(PromoteToTech::class)
        ->set('user_id', $tech->id)
        ->call('promote')
        ->assertHasErrors(['user_id']);
});

test('promotion creates exactly one tech_profiles row', function () {
    $promoter = User::factory()->create();
    $permission = Permission::where('key', 'user.promote')->firstOrFail();
    $promoter->permissions()->attach($permission->id, [
        'granted_by' => $promoter->id,
        'granted_at' => now(),
    ]);

    $employee = User::factory()->create(['is_tech' => false]);

    Livewire::actingAs($promoter)
        ->test(PromoteToTech::class)
        ->set('user_id', $employee->id)
        ->call('promote');

    expect(TechProfile::where('user_id', $employee->id)->count())->toBe(1);
});
