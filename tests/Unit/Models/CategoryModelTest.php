<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;

// ─── localizedName() ─────────────────────────────────────────────────────────

it('localizedName returns name_ar when locale is ar', function () {
    app()->setLocale('ar');
    $category = Category::factory()->create(['name_ar' => 'أجهزة', 'name_en' => 'Hardware']);

    expect($category->localizedName())->toBe('أجهزة');
});

it('localizedName returns name_en when locale is en', function () {
    app()->setLocale('en');
    $category = Category::factory()->create(['name_ar' => 'أجهزة', 'name_en' => 'Hardware']);

    expect($category->localizedName())->toBe('Hardware');
});

// ─── Active scope ─────────────────────────────────────────────────────────────

it('active scope returns only active categories', function () {
    Category::factory()->create(['is_active' => true]);
    Category::factory()->inactive()->create();

    expect(Category::active()->count())->toBe(1);
});

it('active scope excludes soft-deleted categories', function () {
    $active  = Category::factory()->create(['is_active' => true]);
    $deleted = Category::factory()->create(['is_active' => true]);
    $deleted->delete();

    expect(Category::active()->count())->toBe(1)
        ->and(Category::active()->first()->id)->toBe($active->id);
});

// ─── Relationships ────────────────────────────────────────────────────────────

it('category belongs to a group', function () {
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    expect($category->group->id)->toBe($group->id);
});

it('category has many subcategories', function () {
    $category = Category::factory()->create();
    Subcategory::factory()->count(3)->create(['category_id' => $category->id]);

    expect($category->subcategories)->toHaveCount(3);
});

// ─── Factory ─────────────────────────────────────────────────────────────────

it('category factory produces a valid row', function () {
    $category = Category::factory()->create();

    expect($category->id)->toHaveLength(26)
        ->and($category->name_ar)->not->toBeEmpty()
        ->and($category->name_en)->not->toBeEmpty()
        ->and($category->is_active)->toBeTrue()
        ->and($category->version)->toBe(1);
});
