<?php

use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

test('@permission block renders when user has the permission', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    Auth::login($user);

    $rendered = Blade::render("@permission('ticket.view-all')\nSECRET\n@endpermission");

    expect($rendered)->toContain('SECRET');
});

test('@permission block is hidden when user lacks the permission', function () {
    $user = User::factory()->create();

    Auth::login($user);

    $rendered = Blade::render("@permission('ticket.view-all')\nSECRET\n@endpermission");

    expect($rendered)->not->toContain('SECRET');
});

test('@unlesspermission block renders when user lacks the permission', function () {
    $user = User::factory()->create();

    Auth::login($user);

    $rendered = Blade::render("@unlesspermission('ticket.view-all')\nVISIBLE\n@endunlesspermission");

    expect($rendered)->toContain('VISIBLE');
});

test('@unlesspermission block is hidden when user has the permission', function () {
    $user = User::factory()->create();
    $permission = Permission::where('key', 'ticket.view-all')->firstOrFail();
    $user->permissions()->attach($permission->id, [
        'granted_by' => $user->id,
        'granted_at' => now(),
    ]);

    Auth::login($user);

    $rendered = Blade::render("@unlesspermission('ticket.view-all')\nVISIBLE\n@endunlesspermission");

    expect($rendered)->not->toContain('VISIBLE');
});

test('@permission respects super user bypass', function () {
    $user = User::factory()->superUser()->create();

    Auth::login($user);

    $rendered = Blade::render("@permission('ticket.delete')\nADMIN_ONLY\n@endpermission");

    expect($rendered)->toContain('ADMIN_ONLY');
});
