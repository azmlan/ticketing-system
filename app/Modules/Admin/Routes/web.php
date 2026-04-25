<?php

use App\Modules\Admin\Livewire\Categories\CategoryIndex;
use App\Modules\Admin\Livewire\Categories\SubcategoryIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // ── Categories ────────────────────────────────────────────────────────────
    Route::middleware('can:category.manage')->group(function () {
        Route::get('/categories', CategoryIndex::class)->name('categories.index');
        Route::get('/categories/{category}/subcategories', SubcategoryIndex::class)
            ->name('categories.subcategories');
    });

});
