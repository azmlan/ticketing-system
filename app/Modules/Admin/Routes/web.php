<?php

use App\Modules\Admin\Controllers\LogoController;
use App\Modules\Admin\Livewire\Categories\CategoryIndex;
use App\Modules\Admin\Livewire\Categories\SubcategoryIndex;
use App\Modules\Admin\Livewire\CustomFields\CustomFieldIndex;
use App\Modules\Admin\Livewire\Departments\DepartmentIndex;
use App\Modules\Admin\Livewire\Groups\GroupIndex;
use App\Modules\Admin\Livewire\Groups\GroupMembersIndex;
use App\Modules\Admin\Livewire\Locations\LocationIndex;
use App\Modules\Admin\Livewire\ResponseTemplates\ResponseTemplateIndex;
use App\Modules\Admin\Livewire\Settings\BrandingSettings;
use App\Modules\Admin\Livewire\Sla\SlaSettingsIndex;
use App\Modules\Admin\Livewire\Tags\TagIndex;
use App\Modules\Admin\Livewire\Users\UserDetail;
use App\Modules\Admin\Livewire\Users\UserList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // ── Categories ────────────────────────────────────────────────────────────
    Route::middleware('can:category.manage')->group(function () {
        Route::get('/categories', CategoryIndex::class)->name('categories.index');
        Route::get('/categories/{category}/subcategories', SubcategoryIndex::class)
            ->name('categories.subcategories');
    });

    // ── Groups ────────────────────────────────────────────────────────────────
    Route::middleware('can:group.manage')->group(function () {
        Route::get('/groups', GroupIndex::class)->name('groups.index');
    });

    // members page: OR logic handled in component mount()
    Route::get('/groups/{group}/members', GroupMembersIndex::class)
        ->middleware('auth')
        ->name('groups.members');

    // ── Custom Fields ─────────────────────────────────────────────────────────
    Route::middleware('can:system.manage-custom-fields')->group(function () {
        Route::get('/custom-fields', CustomFieldIndex::class)->name('custom-fields.index');
    });

    // ── SLA Settings ──────────────────────────────────────────────────────────
    Route::middleware('can:system.manage-sla')->group(function () {
        Route::get('/sla-settings', SlaSettingsIndex::class)->name('sla.settings');
    });

    // ── Tags ──────────────────────────────────────────────────────────────────
    Route::middleware('can:system.manage-tags')->group(function () {
        Route::get('/tags', TagIndex::class)->name('tags.index');
    });

    // ── Response Templates ────────────────────────────────────────────────────
    Route::middleware('can:system.manage-response-templates')->group(function () {
        Route::get('/response-templates', ResponseTemplateIndex::class)->name('response-templates.index');
    });

    // ── Departments ───────────────────────────────────────────────────────────
    Route::middleware('can:system.manage-departments')->group(function () {
        Route::get('/departments', DepartmentIndex::class)->name('departments.index');
    });

    // ── Locations ─────────────────────────────────────────────────────────────
    Route::middleware('can:system.manage-locations')->group(function () {
        Route::get('/locations', LocationIndex::class)->name('locations.index');
    });

    // ── Users ─────────────────────────────────────────────────────────────────
    // OR logic (user.promote OR user.manage-permissions) handled in component mount()
    Route::get('/users', UserList::class)->name('users.index');
    Route::get('/users/{user}', UserDetail::class)->name('users.show');

    // ── Branding ──────────────────────────────────────────────────────────────
    // IT Manager only (is_super_user) — checked in component mount()
    Route::get('/branding', BrandingSettings::class)->name('branding.index');

});

// ── Logo serve (no auth middleware — checked in controller) ───────────────────
Route::get('/admin/logo', [LogoController::class, 'show'])->name('admin.logo');
