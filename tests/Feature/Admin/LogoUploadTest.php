<?php

use App\Modules\Admin\Livewire\Settings\BrandingSettings;
use App\Modules\Admin\Models\AppSetting;
use App\Modules\Shared\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function logoItManager(): User
{
    return User::factory()->create(['is_super_user' => true]);
}

// ─── Valid image upload ───────────────────────────────────────────────────────

test('valid image is stored outside web root with ulid filename', function () {
    Storage::fake('local');

    $user = logoItManager();
    $file = UploadedFile::fake()->image('company-logo.jpg', 200, 200);

    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test Co')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->set('logo', $file)
        ->call('save')
        ->assertHasNoErrors();

    $path = AppSetting::get('logo_path');
    expect($path)->not->toBeNull();
    expect($path)->toStartWith('logos/');

    Storage::disk('local')->assertExists($path);

    // Filename must be a ULID (26 chars) — no extension
    $filename = basename($path);
    expect(strlen($filename))->toBe(26);
});

test('logo is stored as jpeg regardless of original format', function () {
    Storage::fake('local');

    $user = logoItManager();
    $file = UploadedFile::fake()->image('logo.png', 100, 100);

    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test Co')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->set('logo', $file)
        ->call('save')
        ->assertHasNoErrors();

    $path = AppSetting::get('logo_path');
    $content = Storage::disk('local')->get($path);

    // JPEG magic bytes: FF D8 FF
    expect(substr($content, 0, 2))->toBe("\xFF\xD8");
});

test('old logo is deleted when a new logo is uploaded', function () {
    Storage::fake('local');

    $user = logoItManager();

    // First upload
    $file1 = UploadedFile::fake()->image('logo1.jpg', 100, 100);
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test Co')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->set('logo', $file1)
        ->call('save');

    $oldPath = AppSetting::get('logo_path');
    Storage::disk('local')->assertExists($oldPath);

    // Second upload
    $file2 = UploadedFile::fake()->image('logo2.jpg', 100, 100);
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test Co')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->set('logo', $file2)
        ->call('save');

    Storage::disk('local')->assertMissing($oldPath);
});

// ─── Malicious file (wrong MIME) ──────────────────────────────────────────────

test('malicious file with image extension but non-image content is rejected', function () {
    Storage::fake('local');

    $user = logoItManager();

    // UploadedFile::fake()->create() produces a file with application/octet-stream content
    // (not image bytes), even though we label it image/jpeg — finfo magic-bytes check will reject it
    $file = UploadedFile::fake()->create('malware.jpg', 10, 'image/jpeg');

    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test Co')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->set('logo', $file)
        ->call('save')
        ->assertHasErrors(['logo']);

    expect(AppSetting::get('logo_path'))->toBeNull();
});

// ─── File too large (over 2 MB) ───────────────────────────────────────────────

test('file larger than 2 MB is rejected', function () {
    Storage::fake('local');

    $user = logoItManager();
    // 3000 KB > 2048 KB limit
    $file = UploadedFile::fake()->create('large-logo.jpg', 3000, 'image/jpeg');

    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test Co')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->set('logo', $file)
        ->call('save')
        ->assertHasErrors(['logo']);

    expect(AppSetting::get('logo_path'))->toBeNull();
});

// ─── Authorized logo route ────────────────────────────────────────────────────

test('authenticated user can access logo via authorized route', function () {
    Storage::fake('local');

    $user = logoItManager();

    // Store a fake JPEG
    Storage::disk('local')->put('logos/testlogofile', "\xFF\xD8\xFF\xE0testjpeg");
    AppSetting::set('logo_path', 'logos/testlogofile');

    $this->actingAs($user)
        ->get(route('admin.logo'))
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('unauthenticated guest gets 403 on logo route', function () {
    Storage::fake('local');
    Storage::disk('local')->put('logos/testlogofile', "\xFF\xD8\xFF\xE0testjpeg");
    AppSetting::set('logo_path', 'logos/testlogofile');

    $this->get(route('admin.logo'))
        ->assertStatus(403);
});

test('logo route returns 404 when no logo is set', function () {
    $user = logoItManager();

    $this->actingAs($user)
        ->get(route('admin.logo'))
        ->assertStatus(404);
});
