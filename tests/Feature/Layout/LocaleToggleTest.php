<?php

use App\Modules\Shared\Models\User;

test('authenticated user can toggle locale to en', function () {
    $user = User::factory()->create(['locale' => 'ar']);

    $this->withSession(['_token' => 'x'])
        ->actingAs($user)
        ->post(route('locale.toggle', 'en'), ['_token' => 'x'])
        ->assertRedirect();

    expect($user->fresh()->locale)->toBe('en');
});

test('authenticated user can toggle locale to ar', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->withSession(['_token' => 'x'])
        ->actingAs($user)
        ->post(route('locale.toggle', 'ar'), ['_token' => 'x'])
        ->assertRedirect();

    expect($user->fresh()->locale)->toBe('ar');
});

test('unauthenticated locale toggle redirects to login', function () {
    $this->withSession(['_token' => 'x'])
        ->post(route('locale.toggle', 'en'), ['_token' => 'x'])
        ->assertRedirect(route('login'));
});

test('invalid locale returns 404', function () {
    $user = User::factory()->create();

    $this->withSession(['_token' => 'x'])
        ->actingAs($user)
        ->post('/locale/fr', ['_token' => 'x'])
        ->assertNotFound();
});
