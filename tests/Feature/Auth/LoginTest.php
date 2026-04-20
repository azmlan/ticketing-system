<?php

use App\Modules\Auth\Livewire\Login;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('login:ip:127.0.0.1');
});

test('login with correct credentials authenticates the user', function () {
    $user = User::factory()->create([
        'email'    => 'auth@example.com',
        'password' => bcrypt('SecurePass1!'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'auth@example.com')
        ->set('password', 'SecurePass1!')
        ->call('login');

    $this->assertAuthenticatedAs($user);
});

test('successful login sets authenticated user', function () {
    $user = User::factory()->create([
        'email'    => 'session@example.com',
        'password' => bcrypt('SecurePass1!'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'session@example.com')
        ->set('password', 'SecurePass1!')
        ->call('login');

    $this->assertAuthenticatedAs($user);
});

test('login with wrong password fails and adds error', function () {
    User::factory()->create([
        'email'    => 'fail@example.com',
        'password' => bcrypt('CorrectPass1!'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'fail@example.com')
        ->set('password', 'WrongPass1!')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('6th login attempt within 1 minute is blocked with 429', function () {
    User::factory()->create([
        'email'    => 'block@example.com',
        'password' => bcrypt('CorrectPass1!'),
    ]);

    foreach (range(1, 5) as $_) {
        Livewire::test(Login::class)
            ->set('email', 'block@example.com')
            ->set('password', 'WrongPass1!')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', 'block@example.com')
        ->set('password', 'WrongPass1!')
        ->call('login')
        ->assertStatus(429);
});

test('logout redirects to login', function () {
    $user = User::factory()->create();

    $this->withSession(['_token' => 'csrf-test'])
        ->actingAs($user)
        ->post(route('logout'), ['_token' => 'csrf-test'])
        ->assertRedirect(route('login'));
});

test('logout clears auth guard', function () {
    $user = User::factory()->create();

    $provider = app(\App\Modules\Auth\Contracts\AuthProviderInterface::class);

    \Illuminate\Support\Facades\Auth::login($user);
    expect(\Illuminate\Support\Facades\Auth::check())->toBeTrue();

    $provider->logout();

    expect(\Illuminate\Support\Facades\Auth::check())->toBeFalse();
});
