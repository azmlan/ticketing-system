<?php

use App\Modules\Admin\Livewire\Categories\CategoryIndex;
use App\Modules\Admin\Livewire\Categories\SubcategoryIndex;
use App\Modules\Admin\Livewire\CustomFields\CustomFieldIndex;
use App\Modules\Admin\Livewire\Groups\GroupIndex;
use App\Modules\Admin\Livewire\Groups\GroupMembersIndex;
use App\Modules\Admin\Livewire\Sla\SlaSettingsIndex;
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

});
