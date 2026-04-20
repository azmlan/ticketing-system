<?php

use App\Modules\Auth\Livewire\PasswordReset;
use App\Modules\Auth\Livewire\PasswordResetRequest;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('reset:email:user@example.com');
    RateLimiter::clear('reset:email:reset@example.com');
    RateLimiter::clear('reset:email:throttle@example.com');
});

test('password reset notification is dispatched to registered email', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.com']);

    Livewire::test(PasswordResetRequest::class)
        ->set('email', 'reset@example.com')
        ->call('sendResetLink');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset with valid token updates password hash', function () {
    $user = User::factory()->create([
        'email'    => 'reset@example.com',
        'password' => bcrypt('OldPass1!'),
    ]);

    $token = Password::createToken($user);

    Livewire::test(PasswordReset::class, ['token' => $token])
        ->set('email', 'reset@example.com')
        ->set('password', 'NewSecure1!')
        ->set('password_confirmation', 'NewSecure1!')
        ->call('resetPassword');

    $user->refresh();

    expect(\Illuminate\Support\Facades\Hash::check('NewSecure1!', $user->password))->toBeTrue();
});

test('password reset with invalid token fails', function () {
    User::factory()->create(['email' => 'reset@example.com']);

    Livewire::test(PasswordReset::class, ['token' => 'invalid-token'])
        ->set('email', 'reset@example.com')
        ->set('password', 'NewSecure1!')
        ->set('password_confirmation', 'NewSecure1!')
        ->call('resetPassword')
        ->assertHasErrors(['email']);
});

test('password reset with weak password is rejected', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $token = Password::createToken($user);

    Livewire::test(PasswordReset::class, ['token' => $token])
        ->set('email', 'reset@example.com')
        ->set('password', 'weakpassword')
        ->set('password_confirmation', 'weakpassword')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

test('4th password reset request from same email within 1 hour returns 429', function () {
    User::factory()->create(['email' => 'throttle@example.com']);

    foreach (range(1, 3) as $_) {
        Livewire::test(PasswordResetRequest::class)
            ->set('email', 'throttle@example.com')
            ->call('sendResetLink');
    }

    Livewire::test(PasswordResetRequest::class)
        ->set('email', 'throttle@example.com')
        ->call('sendResetLink')
        ->assertStatus(429);
});
