<?php

use App\Modules\Shared\Models\User;

test('security headers are present on authenticated web response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
});

test('security headers are present on guest web response', function () {
    $response = $this->get(route('login'));

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
});

test('HSTS header is absent outside production', function () {
    $response = $this->get(route('login'));

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
