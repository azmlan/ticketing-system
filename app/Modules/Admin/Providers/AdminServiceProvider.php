<?php

namespace App\Modules\Admin\Providers;

use App\Modules\Admin\Livewire\Categories\CategoryIndex;
use App\Modules\Admin\Livewire\Categories\SubcategoryIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('admin.categories.category-index', CategoryIndex::class);
        Livewire::component('admin.categories.subcategory-index', SubcategoryIndex::class);

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        $this->loadTranslationsFrom(__DIR__.'/../../../resources/lang', 'admin_lang');
    }
}
