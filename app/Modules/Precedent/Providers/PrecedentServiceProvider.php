<?php

namespace App\Modules\Precedent\Providers;

use App\Modules\Precedent\Livewire\ResolveModal;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PrecedentServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(base_path('resources/lang'), 'precedent');

        Livewire::component('precedent.resolve-modal', ResolveModal::class);
    }
}
