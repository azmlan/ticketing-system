<?php

use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;

// ── Config completeness ──────────────────────────────────────────────────────

it('config/permissions.php contains all keys from SPEC §6.3', function () {
    $expected = [
        'ticket.assign', 'ticket.close', 'ticket.view-all',
        'ticket.manage-priority', 'ticket.delete',
        'escalation.approve',
        'group.manage', 'group.manage-manager', 'group.manage-members',
        'category.manage',
        'user.promote', 'user.manage-permissions', 'user.view-directory',
        'system.view-audit-log', 'system.manage-notifications',
        'system.manage-departments', 'system.manage-locations',
        'system.manage-tags', 'system.manage-response-templates',
        'system.manage-custom-fields', 'system.view-reports',
        'system.manage-sla',
    ];

    $actual = array_keys(config('permissions'));

    foreach ($expected as $key) {
        expect($actual)->toContain($key);
    }
    expect($actual)->toHaveCount(count($expected));
});

// ── Seeder ───────────────────────────────────────────────────────────────────

it('seeder inserts all permissions from config', function () {
    $this->seed(PermissionSeeder::class);

    $configCount = count(config('permissions'));
    expect(Permission::count())->toBe($configCount);
});

it('seeder is idempotent — running twice does not duplicate rows', function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(PermissionSeeder::class);

    expect(Permission::count())->toBe(count(config('permissions')));
});

it('every config key has a matching db row after seeding', function () {
    $this->seed(PermissionSeeder::class);

    foreach (array_keys(config('permissions')) as $key) {
        expect(Permission::where('key', $key)->exists())->toBeTrue("Missing permission: {$key}");
    }
});

// ── permission_user pivot ────────────────────────────────────────────────────

it('granting a permission creates a pivot row with granted_by and granted_at', function () {
    $this->seed(PermissionSeeder::class);

    $granter = User::factory()->create();
    $user    = User::factory()->create();
    $perm    = Permission::where('key', 'ticket.view-all')->first();

    $user->permissions()->attach($perm->id, [
        'granted_by' => $granter->id,
        'granted_at' => now(),
    ]);

    $pivot = $user->permissions()->first()->pivot;

    expect($pivot->granted_by)->toBe($granter->id)
        ->and($pivot->granted_at)->not->toBeNull();
});

it('granting the same permission twice fails unique constraint', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $perm = Permission::where('key', 'ticket.assign')->first();

    $user->permissions()->attach($perm->id, ['granted_at' => now()]);
    $user->permissions()->attach($perm->id, ['granted_at' => now()]);
})->throws(QueryException::class);

it('User::permissions() eager-loads attached permissions', function () {
    $this->seed(PermissionSeeder::class);

    $user  = User::factory()->create();
    $perms = Permission::whereIn('key', ['ticket.view-all', 'user.view-directory'])->get();

    foreach ($perms as $perm) {
        $user->permissions()->attach($perm->id, ['granted_at' => now()]);
    }

    $loaded = User::with('permissions')->find($user->id);

    expect($loaded->permissions)->toHaveCount(2);
});

it('deleting a user cascades permission_user rows', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $perm = Permission::where('key', 'ticket.assign')->first();
    $user->permissions()->attach($perm->id, ['granted_at' => now()]);

    $user->forceDelete();

    expect(\Illuminate\Support\Facades\DB::table('permission_user')
        ->where('user_id', $user->id)->count())->toBe(0);
});
