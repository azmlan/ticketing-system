<?php

use Illuminate\Support\Arr;

function flatKeys(string $locale, string $file): array
{
    $path = resource_path("lang/{$locale}/{$file}.php");
    return array_keys(Arr::dot(require $path));
}

$translationFiles = ['auth', 'profile', 'common', 'validation', 'errors', 'layout', 'promote'];

foreach ($translationFiles as $file) {
    test("{$file} translation files have matching keys in ar and en", function () use ($file) {
        expect(flatKeys('ar', $file))->toBe(flatKeys('en', $file));
    });
}

test('auth and profile blade views use translation helpers', function () {
    $views = array_merge(
        glob(resource_path('views/livewire/auth/*.blade.php')) ?: [],
    );

    foreach ($views as $file) {
        $content = file_get_contents($file);
        expect($content)->toContain("__(");
    }
});
