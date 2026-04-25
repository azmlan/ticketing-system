<?php

use App\Modules\Admin\Models\AppSetting;

// ─── get() ────────────────────────────────────────────────────────────────────

it('get returns null when key is missing and no default given', function () {
    expect(AppSetting::get('nonexistent_key'))->toBeNull();
});

it('get returns default when key is missing', function () {
    expect(AppSetting::get('nonexistent_key', 'fallback'))->toBe('fallback');
});

it('get returns stored value when key exists', function () {
    AppSetting::updateOrCreate(['key' => 'company_name'], ['value' => 'Acme Corp']);

    expect(AppSetting::get('company_name'))->toBe('Acme Corp');
});

it('get returns null value when key exists but value is null', function () {
    AppSetting::updateOrCreate(['key' => 'logo_path'], ['value' => null]);

    expect(AppSetting::get('logo_path', 'default'))->toBeNull();
});

// ─── set() ────────────────────────────────────────────────────────────────────

it('set inserts a new row when key does not exist', function () {
    AppSetting::set('new_key', 'new_value');

    expect(AppSetting::where('key', 'new_key')->count())->toBe(1)
        ->and(AppSetting::get('new_key'))->toBe('new_value');
});

it('set updates existing row when key already exists', function () {
    AppSetting::set('company_name', 'Old Name');
    AppSetting::set('company_name', 'New Name');

    expect(AppSetting::where('key', 'company_name')->count())->toBe(1)
        ->and(AppSetting::get('company_name'))->toBe('New Name');
});

it('set can store null value', function () {
    AppSetting::set('logo_path', null);

    expect(AppSetting::get('logo_path', 'default'))->toBeNull();
});

it('set can store json string', function () {
    $days = json_encode(['sun', 'mon', 'tue']);
    AppSetting::set('working_days', $days);

    expect(AppSetting::get('working_days'))->toBe($days);
});

// ─── ULID ─────────────────────────────────────────────────────────────────────

it('app setting has a valid ulid primary key', function () {
    AppSetting::set('test_key', 'test_value');
    $setting = AppSetting::where('key', 'test_key')->first();

    expect($setting->id)->toHaveLength(26);
});

// ─── Migration seeds ──────────────────────────────────────────────────────────

it('migration seeds 9 default app setting keys', function () {
    $expectedKeys = [
        'company_name',
        'logo_path',
        'primary_color',
        'secondary_color',
        'business_hours_start',
        'business_hours_end',
        'working_days',
        'sla_warning_threshold',
        'session_timeout_hours',
    ];

    foreach ($expectedKeys as $key) {
        expect(AppSetting::where('key', $key)->exists())->toBeTrue("Key '{$key}' is missing");
    }
});

it('default primary_color is #2563EB', function () {
    expect(AppSetting::get('primary_color'))->toBe('#2563EB');
});

it('default sla_warning_threshold is 75', function () {
    expect(AppSetting::get('sla_warning_threshold'))->toBe('75');
});

it('default working_days contains expected days', function () {
    $days = json_decode(AppSetting::get('working_days'), true);

    expect($days)->toBe(['sun', 'mon', 'tue', 'wed', 'thu']);
});
