<?php

namespace App\Modules\Admin\Providers;

use App\Modules\Admin\Livewire\Categories\CategoryIndex;
use App\Modules\Admin\Livewire\Categories\SubcategoryIndex;
use App\Modules\Admin\Livewire\Departments\DepartmentIndex;
use App\Modules\Admin\Livewire\Groups\GroupIndex;
use App\Modules\Admin\Livewire\Groups\GroupMembersIndex;
use App\Modules\Admin\Livewire\Locations\LocationIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('admin.categories.category-index', CategoryIndex::class);
        Livewire::component('admin.categories.subcategory-index', SubcategoryIndex::class);
        Livewire::component('admin.groups.group-index', GroupIndex::class);
        Livewire::component('admin.groups.group-members-index', GroupMembersIndex::class);
        Livewire::component('admin.departments.department-index', DepartmentIndex::class);
        Livewire::component('admin.locations.location-index', LocationIndex::class);

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        $this->loadTranslationsFrom(__DIR__.'/../../../resources/lang', 'admin_lang');
    }
}
