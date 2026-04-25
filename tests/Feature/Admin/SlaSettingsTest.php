<?php

use App\Modules\Admin\Livewire\Sla\SlaSettingsIndex;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function slaAdminUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'system.manage-sla')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

function seedSlaPolicies(): void
{
    DB::table('sla_policies')->insert([
        ['id' => (string) Str::ulid(), 'priority' => 'low',      'response_target_minutes' => 480,  'resolution_target_minutes' => 2880, 'use_24x7' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => (string) Str::ulid(), 'priority' => 'medium',   'response_target_minutes' => 240,  'resolution_target_minutes' => 1440, 'use_24x7' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => (string) Str::ulid(), 'priority' => 'high',     'response_target_minutes' => 60,   'resolution_target_minutes' => 480,  'use_24x7' => false, 'created_at' => now(), 'updated_at' => now()],
        ['id' => (string) Str::ulid(), 'priority' => 'critical', 'response_target_minutes' => 15,   'resolution_target_minutes' => 120,  'use_24x7' => true,  'created_at' => now(), 'updated_at' => now()],
    ]);
}

beforeEach(fn () => $this->seed(PermissionSeeder::class));

// ─── Route access ─────────────────────────────────────────────────────────────

test('unauthenticated user is redirected from SLA settings', function () {
    $this->get(route('admin.sla.settings'))
        ->assertRedirect(route('login'));
});

test('user without system.manage-sla is forbidden', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.sla.settings'))
        ->assertForbidden();
});

test('user with system.manage-sla can access SLA settings page', function () {
    seedSlaPolicies();
    $user = slaAdminUser();
    $this->actingAs($user)
        ->get(route('admin.sla.settings'))
        ->assertOk();
});

test('super user can access SLA settings page', function () {
    seedSlaPolicies();
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.sla.settings'))
        ->assertOk();
});

// ─── Load targets ─────────────────────────────────────────────────────────────

test('SLA targets are loaded from sla_policies table on mount', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    $component = Livewire::actingAs($user)->test(SlaSettingsIndex::class);

    expect($component->get('targets.low.response_target_minutes'))->toBe(480);
    expect($component->get('targets.medium.response_target_minutes'))->toBe(240);
    expect($component->get('targets.high.response_target_minutes'))->toBe(60);
    expect($component->get('targets.critical.response_target_minutes'))->toBe(15);
    expect($component->get('targets.critical.use_24x7'))->toBeTrue();
    expect($component->get('targets.low.use_24x7'))->toBeFalse();
});

test('business hours are loaded from app_settings on mount', function () {
    seedSlaPolicies();
    DB::table('app_settings')->where('key', 'business_hours_start')->update(['value' => '09:00']);
    DB::table('app_settings')->where('key', 'sla_warning_threshold')->update(['value' => '80']);

    $user = slaAdminUser();
    $component = Livewire::actingAs($user)->test(SlaSettingsIndex::class);

    expect($component->get('businessHoursStart'))->toBe('09:00');
    expect($component->get('slaWarningThreshold'))->toBe(80);
});

// ─── Save SLA targets ─────────────────────────────────────────────────────────

test('authorised user can save updated SLA targets', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('targets.low.response_target_minutes', 600)
        ->set('targets.low.resolution_target_minutes', 3000)
        ->set('targets.low.use_24x7', false)
        ->call('saveTargets')
        ->assertHasNoErrors();

    $row = DB::table('sla_policies')->where('priority', 'low')->first();
    expect($row->response_target_minutes)->toBe(600);
    expect($row->resolution_target_minutes)->toBe(3000);
    expect((bool) $row->use_24x7)->toBeFalse();
});

test('can toggle use_24x7 on a priority', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('targets.critical.use_24x7', false)
        ->call('saveTargets')
        ->assertHasNoErrors();

    $row = DB::table('sla_policies')->where('priority', 'critical')->first();
    expect((bool) $row->use_24x7)->toBeFalse();
});

test('all four priorities are updated in one save', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('targets.low.response_target_minutes', 720)
        ->set('targets.medium.response_target_minutes', 360)
        ->set('targets.high.response_target_minutes', 90)
        ->set('targets.critical.response_target_minutes', 20)
        ->call('saveTargets')
        ->assertHasNoErrors();

    expect(DB::table('sla_policies')->where('priority', 'low')->value('response_target_minutes'))->toBe(720);
    expect(DB::table('sla_policies')->where('priority', 'medium')->value('response_target_minutes'))->toBe(360);
    expect(DB::table('sla_policies')->where('priority', 'high')->value('response_target_minutes'))->toBe(90);
    expect(DB::table('sla_policies')->where('priority', 'critical')->value('response_target_minutes'))->toBe(20);
});

// ─── Target validation ────────────────────────────────────────────────────────

test('validation fails when response_target_minutes is zero', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('targets.low.response_target_minutes', 0)
        ->call('saveTargets')
        ->assertHasErrors(['targets.low.response_target_minutes']);
});

test('validation fails when response_target_minutes is negative', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('targets.high.response_target_minutes', -5)
        ->call('saveTargets')
        ->assertHasErrors(['targets.high.response_target_minutes']);
});

test('validation fails when resolution_target_minutes is not an integer', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('targets.medium.resolution_target_minutes', 'abc')
        ->call('saveTargets')
        ->assertHasErrors(['targets.medium.resolution_target_minutes']);
});

// ─── Save business hours ──────────────────────────────────────────────────────

test('authorised user can save business hours configuration', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('businessHoursStart', '09:00')
        ->set('businessHoursEnd', '17:00')
        ->set('workingDays', ['mon', 'tue', 'wed', 'thu', 'fri'])
        ->set('slaWarningThreshold', 80)
        ->call('saveBusinessHours')
        ->assertHasNoErrors();

    expect(DB::table('app_settings')->where('key', 'business_hours_start')->value('value'))->toBe('09:00');
    expect(DB::table('app_settings')->where('key', 'business_hours_end')->value('value'))->toBe('17:00');

    $days = json_decode(DB::table('app_settings')->where('key', 'working_days')->value('value'), true);
    expect($days)->toBe(['mon', 'tue', 'wed', 'thu', 'fri']);

    expect(DB::table('app_settings')->where('key', 'sla_warning_threshold')->value('value'))->toBe('80');
});

// ─── Business hours validation ────────────────────────────────────────────────

test('validation fails for invalid start time format', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('businessHoursStart', '9:00')
        ->call('saveBusinessHours')
        ->assertHasErrors(['businessHoursStart']);
});

test('validation fails for invalid end time format', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('businessHoursEnd', '5pm')
        ->call('saveBusinessHours')
        ->assertHasErrors(['businessHoursEnd']);
});

test('validation fails when no working days are selected', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('workingDays', [])
        ->call('saveBusinessHours')
        ->assertHasErrors(['workingDays']);
});

test('validation fails when warning threshold is zero', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('slaWarningThreshold', 0)
        ->call('saveBusinessHours')
        ->assertHasErrors(['slaWarningThreshold']);
});

test('validation fails when warning threshold exceeds 99', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('slaWarningThreshold', 100)
        ->call('saveBusinessHours')
        ->assertHasErrors(['slaWarningThreshold']);
});

test('warning threshold of 99 is valid', function () {
    seedSlaPolicies();
    $user = slaAdminUser();

    Livewire::actingAs($user)
        ->test(SlaSettingsIndex::class)
        ->set('slaWarningThreshold', 99)
        ->call('saveBusinessHours')
        ->assertHasNoErrors();
});
