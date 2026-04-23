<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;

it('localizedName returns name_ar when locale is ar', function () {
    app()->setLocale('ar');
    $group = Group::factory()->create(['name_ar' => 'دعم تقني', 'name_en' => 'Tech Support']);

    expect($group->localizedName())->toBe('دعم تقني');
});

it('localizedName returns name_en when locale is en', function () {
    app()->setLocale('en');
    $group = Group::factory()->create(['name_ar' => 'دعم تقني', 'name_en' => 'Tech Support']);

    expect($group->localizedName())->toBe('Tech Support');
});

it('active scope filters inactive groups', function () {
    Group::factory()->create(['is_active' => true]);
    Group::factory()->inactive()->create();

    expect(Group::active()->count())->toBe(1);
});

it('group belongs to many users via group_user pivot', function () {
    $group = Group::factory()->create();
    $users = User::factory()->count(2)->create();

    $group->users()->attach($users->pluck('id'));

    expect($group->users)->toHaveCount(2);
});

it('group has many categories', function () {
    $group = Group::factory()->create();
    Category::factory()->count(2)->create(['group_id' => $group->id]);

    expect($group->categories)->toHaveCount(2);
});

it('group manager is nullable', function () {
    $group = Group::factory()->create(['manager_id' => null]);

    expect($group->manager)->toBeNull();
});

it('group factory produces a valid row', function () {
    $group = Group::factory()->create();

    expect($group->id)->toHaveLength(26)
        ->and($group->name_ar)->not->toBeEmpty()
        ->and($group->name_en)->not->toBeEmpty()
        ->and($group->is_active)->toBeTrue();
});
