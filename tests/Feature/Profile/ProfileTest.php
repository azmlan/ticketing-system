<?php

use App\Modules\Auth\Livewire\Profile;
use App\Modules\Shared\Models\Department;
use App\Modules\Shared\Models\Location;
use App\Modules\Shared\Models\User;
use Livewire\Livewire;

test('authenticated user can update profile fields', function () {
    $dept = Department::factory()->create();
    $loc  = Location::factory()->create();
    $user = User::factory()->create(['full_name' => 'Original Name']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('full_name', 'Updated Name')
        ->set('phone', '+966500000000')
        ->set('department_id', $dept->id)
        ->set('location_id', $loc->id)
        ->call('saveProfile');

    $user->refresh();

    expect($user->full_name)->toBe('Updated Name')
        ->and($user->phone)->toBe('+966500000000')
        ->and($user->department_id)->toBe($dept->id)
        ->and($user->location_id)->toBe($loc->id);
});

test('toggling locale ar to en persists to users locale column', function () {
    $user = User::factory()->create(['locale' => 'ar']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('locale', 'en')
        ->call('saveProfile');

    expect($user->fresh()->locale)->toBe('en');
});

test('anonymous request to profile redirects to login', function () {
    $this->get(route('profile'))
        ->assertRedirect(route('login'));
});

test('email update without current password is rejected', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('email', 'new@example.com')
        ->call('saveProfile')
        ->assertHasErrors(['current_password']);
});

test('email update with correct current password succeeds', function () {
    $user = User::factory()->create([
        'email'    => 'old@example.com',
        'password' => bcrypt('SecurePass1!'),
    ]);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('email', 'new@example.com')
        ->set('current_password', 'SecurePass1!')
        ->call('saveProfile');

    expect($user->fresh()->email)->toBe('new@example.com');
});

test('profile component only modifies the authenticated user', function () {
    $user1 = User::factory()->create(['full_name' => 'User One']);
    $user2 = User::factory()->create(['full_name' => 'User Two']);

    Livewire::actingAs($user1)
        ->test(Profile::class)
        ->set('full_name', 'Updated Name')
        ->call('saveProfile');

    expect($user1->fresh()->full_name)->toBe('Updated Name')
        ->and($user2->fresh()->full_name)->toBe('User Two');
});

test('profile change password updates password hash', function () {
    $user = User::factory()->create(['password' => bcrypt('OldPass1!')]);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('current_password', 'OldPass1!')
        ->set('password', 'NewSecure1!')
        ->set('password_confirmation', 'NewSecure1!')
        ->call('changePassword');

    expect(\Illuminate\Support\Facades\Hash::check('NewSecure1!', $user->fresh()->password))->toBeTrue();
});

test('profile change password with wrong current password is rejected', function () {
    $user = User::factory()->create(['password' => bcrypt('CorrectPass1!')]);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('current_password', 'WrongPass1!')
        ->set('password', 'NewSecure1!')
        ->set('password_confirmation', 'NewSecure1!')
        ->call('changePassword')
        ->assertHasErrors(['current_password']);
});
