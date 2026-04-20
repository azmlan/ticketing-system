<?php

use Illuminate\Support\Arr;

function flatKeys(string $locale, string $file): array
{
    $path = resource_path("lang/{$locale}/{$file}.php");
    return array_keys(Arr::dot(require $path));
}

test('auth translation files have matching keys in ar and en', function () {
    expect(flatKeys('ar', 'auth'))->toBe(flatKeys('en', 'auth'));
});

test('profile translation files have matching keys in ar and en', function () {
    expect(flatKeys('ar', 'profile'))->toBe(flatKeys('en', 'profile'));
});

test('common translation files have matching keys in ar and en', function () {
    expect(flatKeys('ar', 'common'))->toBe(flatKeys('en', 'common'));
});

test('validation translation files have matching keys in ar and en', function () {
    expect(flatKeys('ar', 'validation'))->toBe(flatKeys('en', 'validation'));
});

test('auth and profile blade views use translation helpers', function () {
    $views = array_merge(
        glob(resource_path('views/livewire/auth/*.blade.php')) ?: [],
    );

    foreach ($views as $file) {
        $content = file_get_contents($file);
        expect($content)->toContain("__(");
    }
});
