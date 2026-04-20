<?php

use App\Modules\Shared\Models\User;

test('middleware sets rtl direction and ar lang for arabic user', function () {
    $user = User::factory()->create(['locale' => 'ar']);

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('lang="ar"', false);
});

test('middleware sets ltr direction and en lang for english user', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertSee('lang="en"', false);
});

test('guest request uses default locale ar with rtl direction', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('lang="ar"', false);
});

test('toggling locale updates html dir on next request', function () {
    $user = User::factory()->create(['locale' => 'ar']);

    // Change locale to en
    $user->forceFill(['locale' => 'en'])->save();

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertSee('dir="ltr"', false)
        ->assertSee('lang="en"', false);
});
