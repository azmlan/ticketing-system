<?php

use App\Modules\Auth\Livewire\Register;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('register:ip:127.0.0.1');
});

test('valid registration creates user with correct defaults', function () {
    Livewire::test(Register::class)
        ->set('full_name', 'أحمد العلي')
        ->set('email', 'ahmed@example.com')
        ->set('password', 'SecurePass1!')
        ->set('password_confirmation', 'SecurePass1!')
        ->set('locale', 'ar')
        ->call('register');

    $user = User::where('email', 'ahmed@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->is_tech)->toBeFalse()
        ->and($user->is_super_user)->toBeFalse()
        ->and($user->locale)->toBe('ar');
});

test('registration persists locale en', function () {
    Livewire::test(Register::class)
        ->set('full_name', 'John Smith')
        ->set('email', 'john@example.com')
        ->set('password', 'SecurePass1!')
        ->set('password_confirmation', 'SecurePass1!')
        ->set('locale', 'en')
        ->call('register');

    expect(User::where('email', 'john@example.com')->value('locale'))->toBe('en');
});

test('registration with 9-char password is rejected', function () {
    Livewire::test(Register::class)
        ->set('full_name', 'Test User')
        ->set('email', 'user@example.com')
        ->set('password', 'Short1!A')
        ->set('password_confirmation', 'Short1!A')
        ->call('register')
        ->assertHasErrors(['password']);

    expect(User::where('email', 'user@example.com')->exists())->toBeFalse();
});

test('registration with no symbol is rejected', function () {
    Livewire::test(Register::class)
        ->set('full_name', 'Test User')
        ->set('email', 'user@example.com')
        ->set('password', 'SecurePass12345')
        ->set('password_confirmation', 'SecurePass12345')
        ->call('register')
        ->assertHasErrors(['password']);
});

test('registration with no uppercase is rejected', function () {
    Livewire::test(Register::class)
        ->set('full_name', 'Test User')
        ->set('email', 'user@example.com')
        ->set('password', 'securepass1!')
        ->set('password_confirmation', 'securepass1!')
        ->call('register')
        ->assertHasErrors(['password']);
});

test('registration with no number is rejected', function () {
    Livewire::test(Register::class)
        ->set('full_name', 'Test User')
        ->set('email', 'user@example.com')
        ->set('password', 'SecurePass!!')
        ->set('password_confirmation', 'SecurePass!!')
        ->call('register')
        ->assertHasErrors(['password']);
});

test('registration with duplicate email is rejected', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(Register::class)
        ->set('full_name', 'Another User')
        ->set('email', 'taken@example.com')
        ->set('password', 'SecurePass1!')
        ->set('password_confirmation', 'SecurePass1!')
        ->call('register')
        ->assertHasErrors(['email']);
});

test('4th registration from same IP within 1 hour returns 429', function () {
    foreach (range(1, 3) as $i) {
        Livewire::test(Register::class)
            ->set('full_name', 'Test User')
            ->set('email', "user{$i}@example.com")
            ->set('password', 'SecurePass1!')
            ->set('password_confirmation', 'SecurePass1!')
            ->set('locale', 'ar')
            ->call('register');
    }

    Livewire::test(Register::class)
        ->set('full_name', 'Test User')
        ->set('email', 'user4@example.com')
        ->set('password', 'SecurePass1!')
        ->set('password_confirmation', 'SecurePass1!')
        ->call('register')
        ->assertStatus(429);
});
