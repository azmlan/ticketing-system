<?php

use App\Modules\Shared\Models\TechProfile;
use App\Modules\Shared\Models\User;

test('create-superuser command creates user with correct flags and tech profile', function () {
    $this->artisan('app:create-superuser')
        ->expectsQuestion('Full name', 'System Admin')
        ->expectsQuestion('Email', 'admin@example.com')
        ->expectsQuestion('Password', 'SecurePass1!')
        ->expectsOutputToContain('SuperUser created')
        ->assertExitCode(0);

    $user = User::where('email', 'admin@example.com')->firstOrFail();
    expect($user->is_super_user)->toBeTrue()
        ->and($user->is_tech)->toBeTrue();

    expect(TechProfile::where('user_id', $user->id)->exists())->toBeTrue();
});

test('create-superuser command is idempotent: overwrite does not duplicate rows', function () {
    $this->artisan('app:create-superuser')
        ->expectsQuestion('Full name', 'System Admin')
        ->expectsQuestion('Email', 'admin@example.com')
        ->expectsQuestion('Password', 'SecurePass1!')
        ->expectsOutputToContain('SuperUser created')
        ->assertExitCode(0);

    $this->artisan('app:create-superuser')
        ->expectsQuestion('Full name', 'System Admin Updated')
        ->expectsQuestion('Email', 'admin@example.com')
        ->expectsQuestion('Password', 'SecurePass1!')
        ->expectsConfirmation('A user with email [admin@example.com] already exists. Overwrite?', 'yes')
        ->expectsOutputToContain('SuperUser updated')
        ->assertExitCode(0);

    expect(User::withTrashed()->where('email', 'admin@example.com')->count())->toBe(1);
    expect(TechProfile::count())->toBe(1);
});

test('create-superuser aborts when overwrite is declined', function () {
    $this->artisan('app:create-superuser')
        ->expectsQuestion('Full name', 'System Admin')
        ->expectsQuestion('Email', 'admin@example.com')
        ->expectsQuestion('Password', 'SecurePass1!')
        ->expectsOutputToContain('SuperUser created')
        ->assertExitCode(0);

    $this->artisan('app:create-superuser')
        ->expectsQuestion('Full name', 'New Name')
        ->expectsQuestion('Email', 'admin@example.com')
        ->expectsQuestion('Password', 'SecurePass1!')
        ->expectsConfirmation('A user with email [admin@example.com] already exists. Overwrite?', 'no')
        ->expectsOutputToContain('Aborted')
        ->assertExitCode(0);

    expect(User::where('email', 'admin@example.com')->value('full_name'))->toBe('System Admin');
});
